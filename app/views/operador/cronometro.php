<?php
declare(strict_types=1);

/**
 * Projeto Repack - Urna Eletrônica (Registro de Produção - Dois Momentos: Iniciar / Pausar / Finalizar)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md (Seção 12.1 e 13.2)
 */

require_once __DIR__ . '/../../models/Produto.php';

$produtos = Produto::listarAtivos();

// Verifica se há produção em andamento na sessão ou se está retornando da conferência
$isOrigemVoltar = (isset($_GET['origem']) && $_GET['origem'] === 'voltar');
$isNovo = (isset($_GET['novo']) && $_GET['novo'] === '1');

if ($isNovo) {
    unset($_SESSION['producao_ativa'], $_SESSION['cronometro_temp']);
    $producaoAtiva = null;
} else {
    $producaoAtiva = $_SESSION['producao_ativa'] ?? null;
}

if ($isOrigemVoltar && isset($_SESSION['cronometro_temp'])) {
    $producaoAtiva = [
        'produto_id' => (int)$_SESSION['cronometro_temp']['produto_id'],
        'produto_nome' => (string)$_SESSION['cronometro_temp']['produto_nome'],
        'produto_codigo' => (string)($_SESSION['cronometro_temp']['produto_codigo'] ?? ''),
        'produto_embalagem' => (string)($_SESSION['cronometro_temp']['produto_embalagem'] ?? ''),
        'data_hora_inicio' => (string)$_SESSION['cronometro_temp']['data_hora_inicio'],
        'pausado' => (bool)($_SESSION['cronometro_temp']['pausado'] ?? false),
        'segundos_pausa' => (int)($_SESSION['cronometro_temp']['segundos_pausa'] ?? 0)
    ];
    $pacotesIniciais = (int)($_SESSION['cronometro_temp']['pacotes_refeitos'] ?? 1);
} else {
    $pacotesIniciais = 1;
}

// Se tiver produto ativo, preenche código e embalagem se necessário
if ($producaoAtiva && empty($producaoAtiva['produto_codigo'])) {
    foreach ($produtos as $p) {
        if ((int)$p['id'] === (int)$producaoAtiva['produto_id']) {
            $producaoAtiva['produto_codigo'] = (string)$p['codigo'];
            $producaoAtiva['produto_embalagem'] = (string)($p['pacote_padrao'] ?? '');
            break;
        }
    }
}

// Mapeia array seguro para JS em memória
$produtosJson = array_map(function($p) {
    return [
        'id' => (int)$p['id'],
        'codigo' => (string)$p['codigo'],
        'nome' => (string)$p['nome'],
        'embalagem' => (string)($p['pacote_padrao'] ?? '')
    ];
}, $produtos);

$pageTitle = "Registrar Produção";
require_once __DIR__ . '/../layouts/header.php';
?>

<!-- ============================================================= -->
<!-- MOMENTO 1: SELEÇÃO DE PRODUTO E INÍCIO (Antes de Iniciar)       -->
<!-- ============================================================= -->
<div id="secaoMomento1" style="<?= $producaoAtiva ? 'display: none;' : 'display: block;' ?>">
    <div style="text-align: center; margin-bottom: 20px;">
        <h1 class="headline-lg" style="margin: 0; font-size: 48px; font-weight: 900; color: #0F172A; letter-spacing: 0.5px;">
            REGISTRAR PRODUÇÃO
        </h1>
    </div>

    <!-- Entrada por Código SKU (Idêntica ao Registro de B.O.) -->
    <div class="sku-entry-container">
        <label for="inputCodigoSku" style="display: block; font-size: 36px; font-weight: 900; color: #0F172A; text-transform: uppercase; margin-bottom: 16px; text-align: center; letter-spacing: 0.5px;">
            CÓDIGO DO PRODUTO:
        </label>
        
        <div class="sku-input-wrapper">
            <input type="text" 
                   id="inputCodigoSku" 
                   class="input-sku-gigante" 
                   inputmode="numeric" 
                   pattern="[0-9]*"
                   placeholder="9068" 
                   value=""
                   autocomplete="off"
                   autofocus
                   oninput="processarEntradaSku(this.value)">
            <button type="button" 
                    id="btnLimparSku" 
                    class="btn-limpar-sku" 
                    style="display: none;" 
                    onclick="limparSku()" 
                    aria-label="Limpar código">✕</button>
        </div>

        <!-- Card de Confirmação Imediata (Verde) -->
        <div class="card-confirmacao-sku" id="cardConfirmacaoSku" style="display: none;">
            <div class="badge-status-ok">
                <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
                <span>PRODUTO IDENTIFICADO</span>
            </div>
            <div class="nome-produto-destaque" id="displayNomeProduto"></div>
            <div class="detalhes-produto">
                <span class="badge-sku" id="displaySkuBadge"></span>
            </div>
        </div>

        <!-- Alerta de Código Não Encontrado (Sem legendas ou textos explicativos) -->
        <div class="card-nao-encontrado" id="cardNaoEncontrado" style="display: none;">
            ⚠️ PRODUTO NÃO ENCONTRADO (<strong id="codigoNaoEncontrado"></strong>)
        </div>

        <!-- Menu Flutuante de Sugestões por Nome -->
        <div id="wrapperSugestoes" class="wrapper-sugestoes-busca" style="display: none;">
            <div id="listaSugestoes" style="max-height: 280px; overflow-y: auto;"></div>
        </div>
    </div>

    <!-- Alerta Flutuante se tentar iniciar sem produto -->
    <div id="alertaSemProduto" style="display: none; width: 100%; margin: 24px 0 0 0; background: #FEF2F2; color: #991B1B; border: 4.5px solid #EF4444; border-radius: 22px; padding: 22px; font-weight: 900; text-align: center; font-size: 24px;">
        ⚠️ DIGITE O CÓDIGO DO PRODUTO!
    </div>

    <!-- Rodapé Momento 1: Ações Empilhadas Verticalmente (Escala Monumental) -->
    <div style="width: 100%; margin-top: 32px; display: flex; flex-direction: column; align-items: center; gap: 20px;">
        <button type="button" 
                class="btn-urna btn-urna-primary" 
                id="btnIniciarProducao" 
                onclick="iniciarProducao()" 
                style="width: 100%; max-width: 650px; min-height: 140px; font-size: 46px; font-weight: 900; border-radius: 36px; display: flex; align-items: center; justify-content: center; gap: 18px; margin: 0 auto; box-shadow: 0 14px 32px rgba(16, 185, 129, 0.4);">
            <span>INICIAR</span>
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink: 0;">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <polyline points="13 4 21 12 13 20"></polyline>
            </svg>
        </button>

        <a href="<?= APP_URL ?>/operador" 
           class="btn-urna btn-urna-warning" 
           style="width: 100%; max-width: 650px; min-height: 140px; font-size: 46px; font-weight: 900; border-radius: 36px; display: flex; align-items: center; justify-content: center; gap: 18px; margin: 0 auto; text-decoration: none;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink: 0;">
                <line x1="21" y1="12" x2="3" y2="12"></line>
                <polyline points="11 4 3 12 11 20"></polyline>
            </svg>
            <span style="font-size: 38px; font-weight: 900; line-height: 1;">✕</span>
            <span>CANCELAR</span>
        </a>
    </div>
</div>

<!-- ============================================================= -->
<!-- MOMENTO 2: PRODUÇÃO EM ANDAMENTO & FINALIZAÇÃO                -->
<!-- ============================================================= -->
<div id="secaoMomento2" style="<?= $producaoAtiva ? 'display: block;' : 'display: none;' ?>">
    <!-- Cartão Central com Produto, Início e Status de Pausa (Sem legenda redundante) -->
    <div class="urna-card" style="width: 100%; text-align: center; padding: 38px 28px; border-radius: 36px; border: 8px solid #0F172A; box-shadow: 0 14px 34px rgba(0, 0, 0, 0.18); background: #FFFFFF; margin-top: 10px;">
        
        <!-- Badge de Pausa (quando pausado) -->
        <div id="badgeStatusPausa" style="<?= (!empty($producaoAtiva['pausado'])) ? 'display: inline-flex;' : 'display: none;' ?> align-items: center; justify-content: center; gap: 10px; background: #FEF3C7; color: #92400E; border: 4px solid #F59E0B; padding: 10px 24px; border-radius: 9999px; font-size: 26px; font-weight: 900; margin-bottom: 16px;">
            ⏸ EM PAUSA
        </div>

        <div class="headline-lg" id="ativoNomeProduto" style="color: #0F172A; font-size: 52px; font-weight: 900; line-height: 1.2; word-break: break-word;">
            <?= sanitize_html($producaoAtiva['produto_nome'] ?? '') ?>
        </div>
        <div style="margin-top: 14px; display: flex; justify-content: center; gap: 14px; flex-wrap: wrap;">
            <span class="badge-sku" id="ativoSkuBadge" style="font-size: 24px; padding: 8px 22px;">
                SKU <?= sanitize_html($producaoAtiva['produto_codigo'] ?? '') ?>
            </span>
        </div>

        <div style="border-top: 5px solid #CBD5E1; margin-top: 26px; padding-top: 22px;">
            <div style="font-size: 28px; font-weight: 900; color: #059669; text-transform: uppercase;">
                🟢 INÍCIO: <span id="ativoHoraInicioDisplay" style="font-size: 64px; font-weight: 900; margin-left: 8px;"><?= !empty($producaoAtiva['data_hora_inicio']) ? date('H:i', strtotime($producaoAtiva['data_hora_inicio'])) : '--:--' ?></span>
            </div>
        </div>
    </div>

    <!-- Seletor Monumental de Pacotes Refeitos (com digitação direta e botões -/+) -->
    <div class="sku-entry-container" style="margin-top: 26px;">
        <label for="displayPacotes" style="display: block; font-size: 36px; font-weight: 900; color: #0F172A; text-transform: uppercase; margin-bottom: 16px; text-align: center; letter-spacing: 0.5px;">
            PACOTES REFEITOS:
        </label>
        <div class="seletor-numerico-wrapper" style="justify-content: center; gap: 26px; margin: 0;">
            <button type="button" class="btn-counter" onclick="alterarPacotes(-1)" aria-label="Diminuir pacotes">−</button>
            <input type="number" 
                   id="displayPacotes" 
                   class="counter-display" 
                   inputmode="numeric" 
                   pattern="[0-9]*" 
                   min="1" 
                   autocomplete="off" 
                   value="<?= $pacotesIniciais ?>" 
                   onfocus="this.select()" 
                   oninput="atualizarPacotesDigitados(this.value)" 
                   onblur="validarPacotesBlur()" 
                   aria-label="Pacotes refeitos">
            <button type="button" class="btn-counter" onclick="alterarPacotes(1)" aria-label="Aumentar pacotes">+</button>
        </div>
    </div>

    <!-- Botão de Pausar / Retomar Produção -->
    <div style="margin-top: 26px; width: 100%;">
        <button type="button" 
                class="btn-urna" 
                id="btnPausarRetomar" 
                onclick="togglePausaProducao()" 
                style="min-height: 125px; font-size: 38px; font-weight: 900; border-radius: 32px; <?= (!empty($producaoAtiva['pausado'])) ? 'background: linear-gradient(145deg, #10B981 0%, #059669 100%); border: 6px solid #047857;' : 'background: linear-gradient(145deg, #F59E0B 0%, #D97706 100%); border: 6px solid #B45309;' ?> color: #FFFFFF; box-shadow: 0 10px 24px rgba(0, 0, 0, 0.2);">
            <?= (!empty($producaoAtiva['pausado'])) ? '▶ RETOMAR PRODUÇÃO' : '⏸ PAUSAR PRODUÇÃO' ?>
        </button>
    </div>

    <!-- Rodapé Momento 2: CANCELAR ou FINALIZAR PRODUÇÃO -->
    <div class="dupla-confirmacao-rodape" style="width: 100%; margin: 26px 0 0 0; gap: 20px;">
        <button type="button" class="btn-urna btn-urna-warning" onclick="descartarProducaoAtiva()" style="min-height: 135px; font-size: 38px; font-weight: 900; border-radius: 36px;">
            CANCELAR
        </button>
        <button type="button" class="btn-urna btn-urna-primary" id="btnFinalizarProducao" onclick="finalizarProducao()" style="min-height: 135px; font-size: 40px; font-weight: 900; border-radius: 36px;">
            FINALIZAR ➔
        </button>
    </div>
</div>

<script>
const PRODUTOS = <?= json_encode($produtosJson, JSON_UNESCAPED_UNICODE) ?>;
let produtoSelecionadoMomento1 = null;

// Objeto de produção ativa em memória (recuperado de PHP ou localStorage)
let producaoAtiva = <?= $producaoAtiva ? json_encode($producaoAtiva, JSON_UNESCAPED_UNICODE) : 'null' ?>;
let pacotesContador = <?= $pacotesIniciais ?>;

// Checagem no cliente ao carregar a página
window.addEventListener('DOMContentLoaded', () => {
    // Se a URL contiver novo=1, limpa qualquer produção residual do localStorage
    if (window.location.search.includes('novo=1')) {
        localStorage.removeItem('repack_producao_ativa');
        producaoAtiva = null;
        const m1 = document.getElementById('secaoMomento1');
        const m2 = document.getElementById('secaoMomento2');
        if (m1) m1.style.display = 'block';
        if (m2) m2.style.display = 'none';
        return;
    }

    if (!producaoAtiva) {
        const local = localStorage.getItem('repack_producao_ativa');
        if (local) {
            try {
                producaoAtiva = JSON.parse(local);
                if (producaoAtiva && producaoAtiva.produto_id && producaoAtiva.data_hora_inicio) {
                    restaurarMomento2(producaoAtiva);
                    // Re-sincroniza silenciosamente com o backend caso a sessão PHP tenha expirado
                    fetch('<?= APP_URL ?>/api/produtividade/iniciar', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(producaoAtiva)
                    }).catch(() => {});
                }
            } catch (e) {
                console.error('Falha ao restaurar producao do localStorage', e);
            }
        }
    } else {
        atualizarVisualPausa();
    }

    // Heartbeat automático a cada 2 minutos para manter a sessão PHP viva em turnos longos
    setInterval(() => {
        if (producaoAtiva && producaoAtiva.produto_id) {
            fetch('<?= APP_URL ?>/api/produtividade/heartbeat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ping: Date.now() })
            }).catch(() => {});
        }
    }, 120000);
});

/* ==========================================================================
   MOMENTO 1: ENTRADA DE SKU E INÍCIO
   ========================================================================== */
function processarEntradaSku(valor) {
    const termo = valor.trim().toLowerCase();
    const btnLimpar = document.getElementById('btnLimparSku');
    const input = document.getElementById('inputCodigoSku');
    const cardOk = document.getElementById('cardConfirmacaoSku');
    const cardErro = document.getElementById('cardNaoEncontrado');
    const wrapperSugestoes = document.getElementById('wrapperSugestoes');
    const alerta = document.getElementById('alertaSemProduto');

    if (alerta) alerta.style.display = 'none';
    btnLimpar.style.display = termo ? 'flex' : 'none';

    if (!termo) {
        produtoSelecionadoMomento1 = null;
        cardOk.style.display = 'none';
        cardErro.style.display = 'none';
        wrapperSugestoes.style.display = 'none';
        input.classList.remove('sucesso-encontrado');
        return;
    }

    // 1. Busca exata por código SKU
    const matchExato = PRODUTOS.find(p => p.codigo.toLowerCase() === termo);
    if (matchExato) {
        selecionarProdutoConfirmado(matchExato);
        cardErro.style.display = 'none';
        wrapperSugestoes.style.display = 'none';
        return;
    }

    // 2. Busca numérica parcial
    const apenasNumeros = /^\d+$/.test(termo);
    if (apenasNumeros) {
        const matchesSku = PRODUTOS.filter(p => p.codigo.startsWith(termo));
        if (matchesSku.length === 1) {
            selecionarProdutoConfirmado(matchesSku[0]);
            cardErro.style.display = 'none';
            wrapperSugestoes.style.display = 'none';
            return;
        } else if (matchesSku.length > 1 && matchesSku.length <= 5) {
            produtoSelecionadoMomento1 = null;
            cardOk.style.display = 'none';
            cardErro.style.display = 'none';
            exibirSugestoes(matchesSku);
            return;
        } else if (termo.length >= 4) {
            produtoSelecionadoMomento1 = null;
            cardOk.style.display = 'none';
            wrapperSugestoes.style.display = 'none';
            document.getElementById('codigoNaoEncontrado').innerText = valor;
            cardErro.style.display = 'block';
            input.classList.remove('sucesso-encontrado');
            return;
        }
    }

    // 3. Busca por texto
    if (!apenasNumeros && termo.length >= 2) {
        const matchesNome = PRODUTOS.filter(p => p.nome.toLowerCase().includes(termo)).slice(0, 6);
        if (matchesNome.length > 0) {
            produtoSelecionadoMomento1 = null;
            cardOk.style.display = 'none';
            cardErro.style.display = 'none';
            exibirSugestoes(matchesNome);
            return;
        } else {
            produtoSelecionadoMomento1 = null;
            cardOk.style.display = 'none';
            wrapperSugestoes.style.display = 'none';
            document.getElementById('codigoNaoEncontrado').innerText = valor;
            cardErro.style.display = 'block';
            input.classList.remove('sucesso-encontrado');
            return;
        }
    }

    produtoSelecionadoMomento1 = null;
    cardOk.style.display = 'none';
    cardErro.style.display = 'none';
    wrapperSugestoes.style.display = 'none';
    input.classList.remove('sucesso-encontrado');
}

function selecionarProdutoConfirmado(p) {
    produtoSelecionadoMomento1 = p;
    document.getElementById('displayNomeProduto').innerText = p.nome;
    document.getElementById('displaySkuBadge').innerText = 'SKU ' + p.codigo;
    const embDisplay = document.getElementById('displayEmbalagem');
    if (embDisplay) embDisplay.innerText = p.embalagem || 'Unidade';

    const input = document.getElementById('inputCodigoSku');
    input.value = p.codigo;
    input.classList.add('sucesso-encontrado');

    document.getElementById('btnLimparSku').style.display = 'flex';
    document.getElementById('cardConfirmacaoSku').style.display = 'block';
    document.getElementById('cardNaoEncontrado').style.display = 'none';
    document.getElementById('wrapperSugestoes').style.display = 'none';

    if (navigator.vibrate) navigator.vibrate(50);
}

function limparSku() {
    const input = document.getElementById('inputCodigoSku');
    input.value = '';
    input.classList.remove('sucesso-encontrado');
    produtoSelecionadoMomento1 = null;
    document.getElementById('cardConfirmacaoSku').style.display = 'none';
    document.getElementById('cardNaoEncontrado').style.display = 'none';
    document.getElementById('wrapperSugestoes').style.display = 'none';
    document.getElementById('btnLimparSku').style.display = 'none';
    input.focus();
}

function exibirSugestoes(itens) {
    const wrapper = document.getElementById('wrapperSugestoes');
    const lista = document.getElementById('listaSugestoes');
    lista.innerHTML = '';

    itens.forEach(item => {
        const div = document.createElement('div');
        div.className = 'item-sugestao-sku';
        div.style.padding = '14px 18px';
        div.style.borderBottom = '1px solid #E2E8F0';
        div.style.cursor = 'pointer';
        div.style.fontSize = '20px';
        div.style.fontWeight = '700';
        div.innerHTML = `<span style="background: #2563EB; color: #FFF; padding: 2px 8px; border-radius: 6px; font-size: 14px; margin-right: 8px;">SKU ${escapeHtml(item.codigo)}</span> ${escapeHtml(item.nome)}`;
        div.onclick = () => selecionarProdutoConfirmado(item);
        lista.appendChild(div);
    });

    wrapper.style.display = 'block';
}

function iniciarProducao() {
    const alerta = document.getElementById('alertaSemProduto');
    if (!produtoSelecionadoMomento1) {
        alerta.style.display = 'block';
        document.getElementById('inputCodigoSku').focus();
        if (navigator.vibrate) navigator.vibrate(150);
        return;
    }

    alerta.style.display = 'none';
    const agora = new Date();
    const timestampInicio = agora.toISOString();

    producaoAtiva = {
        produto_id: produtoSelecionadoMomento1.id,
        produto_nome: produtoSelecionadoMomento1.nome,
        produto_codigo: produtoSelecionadoMomento1.codigo,
        produto_embalagem: produtoSelecionadoMomento1.embalagem,
        data_hora_inicio: timestampInicio,
        pausado: false,
        segundos_pausa: 0
    };

    localStorage.setItem('repack_producao_ativa', JSON.stringify(producaoAtiva));

    fetch('<?= APP_URL ?>/api/produtividade/iniciar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(producaoAtiva)
    }).catch(e => console.warn('Aviso: sessão offline temporária', e));

    if (navigator.vibrate) navigator.vibrate([80, 50, 80]);
    restaurarMomento2(producaoAtiva);
}

function restaurarMomento2(prod) {
    document.getElementById('secaoMomento1').style.display = 'none';
    document.getElementById('secaoMomento2').style.display = 'block';

    document.getElementById('ativoNomeProduto').innerText = prod.produto_nome;
    document.getElementById('ativoSkuBadge').innerText = 'SKU ' + (prod.produto_codigo || '');
    const embBadge = document.getElementById('ativoEmbalagemBadge');
    if (embBadge) embBadge.innerText = prod.produto_embalagem || 'Unidade';

    const dataObj = new Date(prod.data_hora_inicio);
    const horas = String(dataObj.getHours()).padStart(2, '0');
    const mins = String(dataObj.getMinutes()).padStart(2, '0');
    document.getElementById('ativoHoraInicioDisplay').innerText = `${horas}:${mins}`;

    // Restaura a contagem de pacotes refeitos se já estava em andamento
    if (prod.pacotes_refeitos) {
        pacotesContador = Math.max(1, parseInt(prod.pacotes_refeitos, 10));
        const disp = document.getElementById('displayPacotes');
        if (disp) disp.value = pacotesContador;
    }

    atualizarVisualPausa();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ==========================================================================
   MOMENTO 2: PAUSA, RETOMADA E FINALIZAÇÃO
   ========================================================================== */
function alterarPacotes(delta) {
    pacotesContador = Math.max(1, pacotesContador + delta);
    document.getElementById('displayPacotes').value = pacotesContador;
    if (navigator.vibrate) navigator.vibrate(25);
    persistirPacotesLocalStorage();
}

function atualizarPacotesDigitados(val) {
    const parsed = parseInt(val, 10);
    if (!isNaN(parsed) && parsed >= 1) {
        pacotesContador = parsed;
        persistirPacotesLocalStorage();
    }
}

function validarPacotesBlur() {
    const display = document.getElementById('displayPacotes');
    const parsed = parseInt(display.value, 10);
    if (isNaN(parsed) || parsed < 1) {
        pacotesContador = 1;
        display.value = 1;
    } else {
        pacotesContador = parsed;
        display.value = parsed;
    }
    persistirPacotesLocalStorage();
}

function persistirPacotesLocalStorage() {
    if (producaoAtiva) {
        producaoAtiva.pacotes_refeitos = pacotesContador;
        localStorage.setItem('repack_producao_ativa', JSON.stringify(producaoAtiva));
    }
}

function togglePausaProducao() {
    if (!producaoAtiva) return;

    const agora = new Date();

    if (!producaoAtiva.pausado) {
        // Pausar
        producaoAtiva.pausado = true;
        producaoAtiva.pausa_inicio = agora.toISOString();

        localStorage.setItem('repack_producao_ativa', JSON.stringify(producaoAtiva));

        fetch('<?= APP_URL ?>/api/produtividade/pausar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pausa_inicio: producaoAtiva.pausa_inicio })
        }).catch(() => {});

        if (navigator.vibrate) navigator.vibrate(60);
    } else {
        // Retomar
        const inicioPausaMs = new Date(producaoAtiva.pausa_inicio || agora.toISOString()).getTime();
        const diffPausaSeg = Math.max(0, Math.round((agora.getTime() - inicioPausaMs) / 1000));
        producaoAtiva.segundos_pausa = (producaoAtiva.segundos_pausa || 0) + diffPausaSeg;
        producaoAtiva.pausado = false;
        delete producaoAtiva.pausa_inicio;

        localStorage.setItem('repack_producao_ativa', JSON.stringify(producaoAtiva));

        fetch('<?= APP_URL ?>/api/produtividade/retomar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ segundos_pausa: producaoAtiva.segundos_pausa })
        }).catch(() => {});

        if (navigator.vibrate) navigator.vibrate([60, 40, 60]);
    }

    atualizarVisualPausa();
}

function atualizarVisualPausa() {
    const btn = document.getElementById('btnPausarRetomar');
    const badge = document.getElementById('badgeStatusPausa');
    const titulo = document.getElementById('tituloMomento2');

    if (!btn || !producaoAtiva) return;

    if (producaoAtiva.pausado) {
        btn.innerText = '▶ RETOMAR PRODUÇÃO';
        btn.style.background = 'linear-gradient(145deg, #10B981 0%, #059669 100%)';
        btn.style.border = '6px solid #047857';
        if (badge) badge.style.display = 'inline-flex';
        if (titulo) titulo.innerText = 'PRODUÇÃO EM PAUSA';
    } else {
        btn.innerText = '⏸ PAUSAR PRODUÇÃO';
        btn.style.background = 'linear-gradient(145deg, #F59E0B 0%, #D97706 100%)';
        btn.style.border = '6px solid #B45309';
        if (badge) badge.style.display = 'none';
        if (titulo) titulo.innerText = 'PRODUÇÃO EM ANDAMENTO';
    }
}

function descartarProducaoAtiva() {
    if (confirm('Deseja descartar este ciclo de produção?')) {
        localStorage.removeItem('repack_producao_ativa');
        fetch('<?= APP_URL ?>/api/produtividade/descartar', { method: 'POST' }).catch(() => {});
        window.location.href = '<?= APP_URL ?>/operador';
    }
}

function finalizarProducao() {
    if (!producaoAtiva) return;

    const agora = new Date();
    const timestampFim = agora.toISOString();

    // Se estava pausado ao finalizar, computa última fração de pausa
    if (producaoAtiva.pausado && producaoAtiva.pausa_inicio) {
        const inicioPausaMs = new Date(producaoAtiva.pausa_inicio).getTime();
        const diffPausaSeg = Math.max(0, Math.round((agora.getTime() - inicioPausaMs) / 1000));
        producaoAtiva.segundos_pausa = (producaoAtiva.segundos_pausa || 0) + diffPausaSeg;
    }

    const inicioMs = new Date(producaoAtiva.data_hora_inicio).getTime();
    const fimMs = agora.getTime();
    const duracaoBruta = Math.max(0, Math.round((fimMs - inicioMs) / 1000));
    const duracaoLiquida = Math.max(0, duracaoBruta - (producaoAtiva.segundos_pausa || 0));

    if (navigator.vibrate) navigator.vibrate(100);

    // Submete formulário dinâmico para a conferência dupla
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= APP_URL ?>/operador/cronometro/resumo';

    const campos = {
        produto_id: producaoAtiva.produto_id,
        produto_nome: producaoAtiva.produto_nome,
        produto_codigo: producaoAtiva.produto_codigo || '',
        produto_embalagem: producaoAtiva.produto_embalagem || '',
        data_hora_inicio: producaoAtiva.data_hora_inicio,
        data_hora_fim: timestampFim,
        pacotes_refeitos: pacotesContador,
        duracao_segundos: duracaoLiquida,
        pausado: producaoAtiva.pausado ? 1 : 0,
        segundos_pausa: producaoAtiva.segundos_pausa || 0
    };

    for (const [chave, valor] of Object.entries(campos)) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = chave;
        input.value = valor;
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
