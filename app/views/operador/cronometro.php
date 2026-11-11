<?php
declare(strict_types=1);

/**
 * Projeto Repack - Urna Eletrônica (Registro de Produção - Dois Momentos: Iniciar / Pausar / Finalizar)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md (Seção 12.1 e 13.2)
 */

require_once __DIR__ . '/../../models/Produto.php';

$produtos = Produto::listarAtivos();

// Verifica se há produção em andamento na sessão ou se está retornando da conferência
$producaoAtiva = $_SESSION['producao_ativa'] ?? null;
$isOrigemVoltar = (isset($_GET['origem']) && $_GET['origem'] === 'voltar');

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
    $pacotesIniciais = (int)($_SESSION['cronometro_temp']['pacotes_refeitos'] ?? 0);
} else {
    $pacotesIniciais = 0;
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
<div id="secaoMomento1" class="flow-container" style="<?= $producaoAtiva ? 'display: none;' : 'display: block;' ?>">
    <!-- Botão Voltar no Canto Superior Esquerdo -->
    <div>
        <a href="<?= APP_URL ?>/operador" class="btn-flow-voltar">
            <span>←</span> <span>Voltar</span>
        </a>
    </div>

    <!-- Título Central -->
    <h1 class="flow-title">Registrar Produção</h1>

    <!-- Campo Código do Produto -->
    <label for="inputCodigoSku" class="flow-label">Código do Produto</label>
    
    <div class="flow-input-container">
        <input type="text" 
               id="inputCodigoSku" 
               class="flow-input-field" 
               inputmode="numeric" 
               pattern="[0-9]*"
               placeholder="Ex: 7891011" 
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
    <div class="card-confirmacao-sku" id="cardConfirmacaoSku" style="display: none; margin-bottom: 20px;">
        <div class="badge-status-ok">
            <svg width="28" height="28" fill="currentColor" viewBox="0 0 24 24">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
            </svg>
            <span>PRODUTO IDENTIFICADO</span>
        </div>
        <div class="nome-produto-destaque" id="displayNomeProduto"></div>
        <div class="detalhes-produto">
            <span class="badge-sku" id="displaySkuBadge"></span>
            <span class="badge-embalagem" id="displayEmbalagem"></span>
        </div>
    </div>

    <!-- Alerta de Código Não Encontrado -->
    <div class="card-nao-encontrado" id="cardNaoEncontrado" style="display: none; margin-bottom: 20px;">
        ⚠️ PRODUTO NÃO ENCONTRADO (<strong id="codigoNaoEncontrado"></strong>)
    </div>

    <!-- Menu Flutuante de Sugestões por Nome -->
    <div id="wrapperSugestoes" class="wrapper-sugestoes-busca" style="display: none;">
        <div id="listaSugestoes" style="max-height: 280px; overflow-y: auto;"></div>
    </div>

    <!-- Alerta Flutuante se tentar iniciar sem produto -->
    <div id="alertaSemProduto" style="display: none; width: 100%; margin: 16px 0; background: #FEF2F2; color: #991B1B; border: 3px solid #EF4444; border-radius: 18px; padding: 18px; font-weight: 800; text-align: center; font-size: 22px;">
        ⚠️ DIGITE O CÓDIGO DO PRODUTO!
    </div>

    <!-- Botão de Ação: INICIAR -->
    <button type="button" class="btn-flow-iniciar" id="btnIniciarProducao" onclick="iniciarProducao()">
        <span>▶</span> <span>INICIAR</span>
    </button>
</div>

<!-- ============================================================= -->
<!-- MOMENTO 2: PRODUÇÃO EM ANDAMENTO & FINALIZAÇÃO                -->
<!-- ============================================================= -->
<div id="secaoMomento2" class="flow-container" style="<?= $producaoAtiva ? 'display: block;' : 'display: none;' ?>">
    
    <!-- Título Central -->
    <h1 class="flow-title" id="tituloMomento2">
        <?= (!empty($producaoAtiva['pausado'])) ? 'Trabalho em Pausa' : 'Trabalho em Andamento' ?>
    </h1>

    <!-- 1. Produto Selecionado -->
    <div class="flow-label">Produto selecionado:</div>
    <div class="flow-display-box" id="boxProdutoSelecionado">
        <div id="ativoSkuDisplay" style="font-size: clamp(38px, 8.5vw, 54px); font-weight: 900; color: #0F172A;">
            <?= sanitize_html($producaoAtiva['produto_codigo'] ?? '') ?>
        </div>
        <div id="ativoNomeProduto" style="font-size: clamp(22px, 4.8vw, 30px); font-weight: 800; color: #475569; margin-top: 8px; line-height: 1.2;">
            <?= sanitize_html($producaoAtiva['produto_nome'] ?? '') ?>
        </div>
    </div>

    <!-- 2. Card de Início -->
    <div class="flow-time-card <?= (!empty($producaoAtiva['pausado'])) ? 'pausado' : '' ?>" id="cardHoraInicio">
        <span>Início: <strong id="ativoHoraInicioDisplay"><?= !empty($producaoAtiva['data_hora_inicio']) ? date('H:i', strtotime($producaoAtiva['data_hora_inicio'])) : '--:--' ?></strong></span>
    </div>

    <!-- 3. Quantidade Feita -->
    <div class="flow-label">Quantidade Feita</div>
    <div class="flow-counter-row">
        <button type="button" class="btn-flow-counter" onclick="alterarPacotes(-1)" aria-label="Diminuir">−</button>
        <input type="number" 
               id="displayPacotes" 
               class="flow-input-quantidade" 
               inputmode="numeric" 
               pattern="[0-9]*" 
               min="0" 
               autocomplete="off" 
               value="<?= $pacotesIniciais ?>" 
               onfocus="this.select()" 
               oninput="atualizarPacotesDigitados(this.value)" 
               onblur="validarPacotesBlur()" 
               aria-label="Quantidade Feita">
        <button type="button" class="btn-flow-counter" onclick="alterarPacotes(1)" aria-label="Aumentar">+</button>
    </div>

    <!-- 4. Botão de Pausar / Retomar -->
    <button type="button" 
            class="btn-flow-pausa <?= (!empty($producaoAtiva['pausado'])) ? 'retomar' : '' ?>" 
            id="btnPausarRetomar" 
            onclick="togglePausaProducao()">
        <?= (!empty($producaoAtiva['pausado'])) ? '▶ RETOMAR' : '⏸ PAUSAR' ?>
    </button>

    <!-- 5. Botão Principal: FINALIZAR -->
    <button type="button" class="btn-flow-finalizar" id="btnFinalizarProducao" onclick="finalizarProducao()">
        <span style="font-size: 26px;">■</span> <span>FINALIZAR</span>
    </button>

    <!-- 6. Opção Discreta de Cancelamento -->
    <div style="text-align: center; margin-top: 20px;">
        <button type="button" onclick="descartarProducaoAtiva()" style="background: none; border: none; color: #94A3B8; font-size: 22px; font-weight: 800; text-decoration: underline; cursor: pointer; padding: 12px;">
            Descartar / Cancelar este ciclo
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
    if (!producaoAtiva) {
        const local = localStorage.getItem('repack_producao_ativa');
        if (local) {
            try {
                producaoAtiva = JSON.parse(local);
                if (producaoAtiva && producaoAtiva.produto_id && producaoAtiva.data_hora_inicio) {
                    restaurarMomento2(producaoAtiva);
                }
            } catch (e) {
                console.error('Falha ao restaurar producao do localStorage', e);
            }
        }
    } else {
        atualizarVisualPausa();
    }
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
    document.getElementById('displayEmbalagem').innerText = p.embalagem || 'Unidade';

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

    const skuDisplay = document.getElementById('ativoSkuDisplay');
    const nomeDisplay = document.getElementById('ativoNomeProduto');
    if (skuDisplay) skuDisplay.innerText = prod.produto_codigo || '';
    if (nomeDisplay) nomeDisplay.innerText = prod.produto_nome || '';

    const dataObj = new Date(prod.data_hora_inicio);
    const horas = String(dataObj.getHours()).padStart(2, '0');
    const mins = String(dataObj.getMinutes()).padStart(2, '0');
    const horaDisplay = document.getElementById('ativoHoraInicioDisplay');
    if (horaDisplay) horaDisplay.innerText = `${horas}:${mins}`;

    atualizarVisualPausa();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ==========================================================================
   MOMENTO 2: PAUSA, RETOMADA E FINALIZAÇÃO
   ========================================================================== */
function alterarPacotes(delta) {
    pacotesContador = Math.max(0, pacotesContador + delta);
    document.getElementById('displayPacotes').value = pacotesContador;
    if (navigator.vibrate) navigator.vibrate(25);
}

function atualizarPacotesDigitados(val) {
    const parsed = parseInt(val, 10);
    if (!isNaN(parsed) && parsed >= 0) {
        pacotesContador = parsed;
    }
}

function validarPacotesBlur() {
    const display = document.getElementById('displayPacotes');
    const parsed = parseInt(display.value, 10);
    if (isNaN(parsed) || parsed < 0) {
        pacotesContador = 0;
        display.value = 0;
    } else {
        pacotesContador = parsed;
        display.value = parsed;
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
    const cardHora = document.getElementById('cardHoraInicio');
    const titulo = document.getElementById('tituloMomento2');

    if (!btn || !producaoAtiva) return;

    if (producaoAtiva.pausado) {
        btn.innerHTML = '▶ RETOMAR';
        btn.classList.add('retomar');
        if (cardHora) cardHora.classList.add('pausado');
        if (titulo) titulo.innerText = 'Trabalho em Pausa';
    } else {
        btn.innerHTML = '⏸ PAUSAR';
        btn.classList.remove('retomar');
        if (cardHora) cardHora.classList.remove('pausado');
        if (titulo) titulo.innerText = 'Trabalho em Andamento';
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
