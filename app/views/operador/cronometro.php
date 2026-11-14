<?php
declare(strict_types=1);

/**
 * Projeto Repack - Urna Eletrônica (Registro de Produção - Três Etapas Lineares: Produto -> Quantidade -> Início/Execução)
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

// Se tiver produto ativo, preenche código, embalagem e foto se necessário
if ($producaoAtiva && empty($producaoAtiva['produto_codigo'])) {
    foreach ($produtos as $p) {
        if ((int)$p['id'] === (int)$producaoAtiva['produto_id']) {
            $producaoAtiva['produto_codigo'] = (string)$p['codigo'];
            $producaoAtiva['produto_embalagem'] = (string)($p['pacote_padrao'] ?? '');
            $producaoAtiva['produto_foto_url'] = !empty($p['foto_url']) ? (APP_URL . $p['foto_url']) : null;
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
        'embalagem' => (string)($p['pacote_padrao'] ?? ''),
        'foto_url' => !empty($p['foto_url']) ? (APP_URL . $p['foto_url']) : null
    ];
}, $produtos);

$pageTitle = "Registrar Produção";
require_once __DIR__ . '/../layouts/header.php';
?>

<!-- ============================================================= -->
<!-- ETAPA 1: DIGITAÇÃO DO ITEM / CÓDIGO DO PRODUTO                -->
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

            <!-- Foto Destaque (Heroica para Acessibilidade) -->
            <div id="wrapperFotoMomento1" style="text-align: center; margin: 18px 0 16px 0;">
                <img id="imgFotoMomento1" src="" alt="Foto" style="width: 100%; max-width: 400px; height: 380px; object-fit: contain; border-radius: 28px; background: #FFFFFF; border: 5px solid #059669; box-shadow: 0 10px 28px rgba(5,150,105,0.25); display: none; padding: 8px;">
            </div>

            <div class="nome-produto-destaque" id="displayNomeProduto"></div>
            <div class="detalhes-produto">
                <span class="badge-sku" id="displaySkuBadge"></span>
            </div>
        </div>

        <!-- Alerta de Código Não Encontrado -->
        <div class="card-nao-encontrado" id="cardNaoEncontrado" style="display: none;">
            ⚠️ PRODUTO NÃO ENCONTRADO (<strong id="codigoNaoEncontrado"></strong>)
        </div>

        <!-- Menu Flutuante de Sugestões por Nome -->
        <div id="wrapperSugestoes" class="wrapper-sugestoes-busca" style="display: none;">
            <div id="listaSugestoes" style="max-height: 280px; overflow-y: auto;"></div>
        </div>
    </div>

    <!-- Alerta Flutuante se tentar avançar sem produto -->
    <div id="alertaSemProduto" style="display: none; width: 100%; margin: 24px 0 0 0; background: #FEF2F2; color: #991B1B; border: 4.5px solid #EF4444; border-radius: 22px; padding: 22px; font-weight: 900; text-align: center; font-size: 24px;">
        ⚠️ DIGITE O CÓDIGO DO PRODUTO!
    </div>

    <!-- Rodapé Etapa 1: INICIAR PRODUÇÃO ou CANCELAR -->
    <div style="width: 100%; margin-top: 32px; display: flex; flex-direction: column; align-items: center; gap: 20px;">
        <button type="button" 
                class="btn-urna btn-urna-primary" 
                id="btnIniciarProducaoDireto" 
                onclick="iniciarProducaoDireto()" 
                style="width: 100%; max-width: 650px; min-height: 140px; font-size: 44px; font-weight: 900; border-radius: 36px; display: flex; align-items: center; justify-content: center; gap: 18px; margin: 0 auto; box-shadow: 0 14px 32px rgba(16, 185, 129, 0.4);">
            <span>INICIAR PRODUÇÃO ➔</span>
        </button>

        <a href="<?= APP_URL ?>/operador" 
           class="btn-urna btn-urna-warning" 
           style="width: 100%; max-width: 650px; min-height: 130px; font-size: 42px; font-weight: 900; border-radius: 36px; display: flex; align-items: center; justify-content: center; gap: 18px; margin: 0 auto; text-decoration: none;">
            <span style="font-size: 38px; font-weight: 900; line-height: 1;">✕</span>
            <span>CANCELAR</span>
        </a>
    </div>
</div>

<!-- ============================================================= -->
<!-- ETAPA 2: PRODUÇÃO EM ANDAMENTO (CRONÔMETRO ATIVO)             -->
<!-- ============================================================= -->
<div id="secaoMomento2" style="<?= $producaoAtiva ? 'display: block;' : 'display: none;' ?>">
    <!-- Cartão Central com Produto, Início e Status de Pausa -->
    <div class="urna-card" style="width: 100%; text-align: center; padding: 38px 28px; border-radius: 36px; border: 8px solid #0F172A; box-shadow: 0 14px 34px rgba(0, 0, 0, 0.18); background: #FFFFFF; margin-top: 10px;">
        
        <!-- Badge de Pausa (quando pausado) -->
        <div id="badgeStatusPausa" style="<?= (!empty($producaoAtiva['pausado'])) ? 'display: inline-flex;' : 'display: none;' ?> align-items: center; justify-content: center; gap: 10px; background: #FEF3C7; color: #92400E; border: 4px solid #F59E0B; padding: 10px 24px; border-radius: 9999px; font-size: 26px; font-weight: 900; margin-bottom: 16px;">
            ⏸ EM PAUSA
        </div>

        <div class="headline-lg" id="ativoNomeProduto" style="color: #0F172A; font-size: 52px; font-weight: 900; line-height: 1.2; word-break: break-word;">
            <?= sanitize_html($producaoAtiva['produto_nome'] ?? '') ?>
        </div>

        <!-- Foto Destaque Momento 2 (Heroica para Acessibilidade) -->
        <div id="wrapperFotoMomento2" style="text-align: center; margin: 18px 0 16px 0;">
            <img id="ativoFotoProduto" 
                 src="<?= !empty($producaoAtiva['produto_foto_url']) ? sanitize_html($producaoAtiva['produto_foto_url']) : '' ?>" 
                 alt="Foto" 
                 style="width: 100%; max-width: 400px; height: 380px; object-fit: contain; border-radius: 28px; border: 5px solid #059669; background: #FFFFFF; box-shadow: 0 10px 28px rgba(5,150,105,0.25); <?= empty($producaoAtiva['produto_foto_url']) ? 'display: none;' : 'display: inline-block;' ?>; padding: 8px;">
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

    <!-- Rodapé: CANCELAR CICLO ou FINALIZAR PRODUÇÃO (Avança para Quantidade) -->
    <div class="dupla-confirmacao-rodape" style="width: 100%; margin: 26px 0 0 0; gap: 20px;">
        <button type="button" class="btn-urna btn-urna-warning" onclick="descartarProducaoAtiva()" style="min-height: 135px; font-size: 36px; font-weight: 900; border-radius: 36px;">
            ✕ DESCARTAR
        </button>
        <button type="button" class="btn-urna btn-urna-primary" id="btnFinalizarProducao" onclick="avancarParaDefinirQuantidade()" style="min-height: 135px; font-size: 40px; font-weight: 900; border-radius: 36px; box-shadow: 0 12px 28px rgba(16, 185, 129, 0.4);">
            FINALIZAR ➔
        </button>
    </div>
</div>

<!-- ============================================================= -->
<!-- ETAPA 3: DEFINIÇÃO DE QUANTIDADE (TELA DEDICADA E ISOLADA)    -->
<!-- ============================================================= -->
<div id="secaoMomentoQuantidade" style="display: none;">
    <div style="text-align: center; margin-bottom: 20px;">
        <h1 class="headline-lg" style="margin: 0; font-size: 46px; font-weight: 900; color: #0F172A; letter-spacing: 0.5px;">
            PACOTES REFEITOS
        </h1>
    </div>

    <!-- Card com Resumo do Produto e Horários Marcados -->
    <div class="urna-card" style="width: 100%; text-align: center; padding: 26px 20px; border-radius: 32px; border: 6px solid #059669; background: #FFFFFF; margin-bottom: 24px; box-shadow: 0 10px 28px rgba(0,0,0,0.08);">
        <div id="qtdWrapperFoto" style="text-align: center; margin-bottom: 12px;">
            <img id="qtdFotoProduto" src="" alt="Foto" style="width: 100%; max-width: 260px; height: 220px; object-fit: contain; border-radius: 20px; background: #FFFFFF; display: none; padding: 6px;">
        </div>
        <div class="headline-lg" id="qtdNomeProduto" style="color: #0F172A; font-size: 42px; font-weight: 900; line-height: 1.2;"></div>
        <div style="margin-top: 8px;">
            <span class="badge-sku" id="qtdSkuBadge" style="font-size: 22px; padding: 6px 18px;"></span>
        </div>

        <div style="border-top: 4px solid #E2E8F0; margin-top: 18px; padding-top: 14px; display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; font-size: 22px; font-weight: 800; color: #334155;">
            <div>🟢 INÍCIO: <span id="qtdHoraInicio" style="color: #059669; font-weight: 900;">--:--</span></div>
            <div>🏁 FIM: <span id="qtdHoraFim" style="color: #DC2626; font-weight: 900;">--:--</span></div>
        </div>
    </div>

    <!-- Seletor Monumental de Quantidade (80px e Botões -/+) -->
    <div class="sku-entry-container" style="margin-top: 10px;">
        <label for="displayPacotesEtapa2" style="display: block; font-size: 38px; font-weight: 900; color: #0F172A; text-transform: uppercase; margin-bottom: 16px; text-align: center; letter-spacing: 0.5px;">
            DIGITE A QUANTIDADE FEITA:
        </label>
        <div class="seletor-numerico-wrapper" style="justify-content: center; gap: 26px; margin: 0;">
            <button type="button" class="btn-counter" onclick="alterarPacotesEtapa2(-1)" aria-label="Diminuir pacotes">−</button>
            <input type="number" 
                   id="displayPacotesEtapa2" 
                   class="counter-display" 
                   inputmode="numeric" 
                   pattern="[0-9]*" 
                   min="1" 
                   autocomplete="off" 
                   value="<?= $pacotesIniciais ?>" 
                   onfocus="this.select()" 
                   oninput="atualizarPacotesEtapa2Digitados(this.value)" 
                   onblur="validarPacotesEtapa2Blur()" 
                   aria-label="Pacotes a refazer"
                   style="font-size: 84px; font-weight: 900; width: 200px; text-align: center; height: 130px; border-radius: 26px; border: 5px solid #CBD5E1;">
            <button type="button" class="btn-counter" onclick="alterarPacotesEtapa2(1)" aria-label="Aumentar pacotes">+</button>
        </div>
    </div>

    <!-- Rodapé Etapa 3: CONFIRMAR QUANTIDADE e AVANÇAR PARA CONFERÊNCIA -->
    <div style="width: 100%; margin-top: 32px; display: flex; flex-direction: column; align-items: center; gap: 20px;">
        <button type="button" 
                class="btn-urna btn-urna-primary" 
                id="btnAvancarParaConferencia" 
                onclick="submeterParaConferencia()" 
                style="width: 100%; max-width: 650px; min-height: 140px; font-size: 42px; font-weight: 900; border-radius: 36px; display: flex; align-items: center; justify-content: center; gap: 18px; margin: 0 auto; box-shadow: 0 14px 32px rgba(16, 185, 129, 0.4);">
            <span>CONFIRMAR ➔</span>
        </button>

        <button type="button" 
                class="btn-urna btn-urna-secondary" 
                onclick="voltarParaTrabalhoEmAndamento()" 
                style="width: 100%; max-width: 650px; min-height: 125px; font-size: 38px; font-weight: 900; border-radius: 36px; display: flex; align-items: center; justify-content: center; gap: 18px; margin: 0 auto;">
            <span>⬅ VOLTAR AO TRABALHO</span>
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
        document.getElementById('secaoMomento1').style.display = 'block';
        document.getElementById('secaoMomentoQuantidade').style.display = 'none';
        document.getElementById('secaoMomento2').style.display = 'none';
        return;
    }

    if (!producaoAtiva) {
        const local = localStorage.getItem('repack_producao_ativa');
        if (local) {
            try {
                producaoAtiva = JSON.parse(local);
                if (producaoAtiva && producaoAtiva.produto_id && producaoAtiva.data_hora_inicio) {
                    restaurarMomento2(producaoAtiva);
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

    // Se estiver retornando da tela de conferência (?origem=voltar), abre direto a etapa de quantidade
    if (window.location.search.includes('origem=voltar') && producaoAtiva) {
        avancarParaDefinirQuantidade();
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
   ETAPA 1: ENTRADA DE SKU E IDENTIFICAÇÃO DO PRODUTO
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

    const imgMomento1 = document.getElementById('imgFotoMomento1');
    if (imgMomento1) {
        if (p.foto_url) {
            imgMomento1.src = p.foto_url;
            imgMomento1.style.display = 'inline-block';
        } else {
            imgMomento1.src = '';
            imgMomento1.style.display = 'none';
        }
    }

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
    const imgMomento1 = document.getElementById('imgFotoMomento1');
    if (imgMomento1) {
        imgMomento1.src = '';
        imgMomento1.style.display = 'none';
    }
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

function formatarDataHoraLocal(d = new Date()) {
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

/* ==========================================================================
   ETAPA 1 -> ETAPA 2: INICIAR PRODUÇÃO DIRETO APÓS DIGITAR O CÓDIGO
   ========================================================================== */
function iniciarProducaoDireto() {
    const alerta = document.getElementById('alertaSemProduto');
    if (!produtoSelecionadoMomento1) {
        if (alerta) alerta.style.display = 'block';
        document.getElementById('inputCodigoSku').focus();
        if (navigator.vibrate) navigator.vibrate(150);
        return;
    }

    if (alerta) alerta.style.display = 'none';

    const agora = new Date();
    const timestampInicio = formatarDataHoraLocal(agora);

    producaoAtiva = {
        produto_id: produtoSelecionadoMomento1.id,
        produto_nome: produtoSelecionadoMomento1.nome,
        produto_codigo: produtoSelecionadoMomento1.codigo,
        produto_embalagem: produtoSelecionadoMomento1.embalagem,
        produto_foto_url: produtoSelecionadoMomento1.foto_url,
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
    document.getElementById('secaoMomentoQuantidade').style.display = 'none';
    document.getElementById('secaoMomento2').style.display = 'block';

    document.getElementById('ativoNomeProduto').innerText = prod.produto_nome;
    document.getElementById('ativoSkuBadge').innerText = 'SKU ' + (prod.produto_codigo || '');

    const imgAtivo = document.getElementById('ativoFotoProduto');
    if (imgAtivo) {
        if (prod.produto_foto_url) {
            imgAtivo.src = prod.produto_foto_url;
            imgAtivo.style.display = 'inline-block';
        } else {
            imgAtivo.src = '';
            imgAtivo.style.display = 'none';
        }
    }

    const dataObj = new Date(prod.data_hora_inicio.replace(' ', 'T'));
    const horas = String(dataObj.getHours()).padStart(2, '0');
    const mins = String(dataObj.getMinutes()).padStart(2, '0');
    document.getElementById('ativoHoraInicioDisplay').innerText = `${horas}:${mins}`;

    atualizarVisualPausa();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ==========================================================================
   ETAPA 2: PRODUÇÃO EM ANDAMENTO (PAUSA / RETOMADA / DESCARTAR)
   ========================================================================== */
function togglePausaProducao() {
    if (!producaoAtiva) return;

    const agora = new Date();

    if (!producaoAtiva.pausado) {
        // Pausar
        producaoAtiva.pausado = true;
        producaoAtiva.pausa_inicio = formatarDataHoraLocal(agora);

        localStorage.setItem('repack_producao_ativa', JSON.stringify(producaoAtiva));

        fetch('<?= APP_URL ?>/api/produtividade/pausar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pausa_inicio: producaoAtiva.pausa_inicio })
        }).catch(() => {});

        if (navigator.vibrate) navigator.vibrate(60);
    } else {
        // Retomar
        const inicioPausaMs = new Date((producaoAtiva.pausa_inicio || formatarDataHoraLocal(agora)).replace(' ', 'T')).getTime();
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

    if (!btn || !producaoAtiva) return;

    if (producaoAtiva.pausado) {
        btn.innerText = '▶ RETOMAR PRODUÇÃO';
        btn.style.background = 'linear-gradient(145deg, #10B981 0%, #059669 100%)';
        btn.style.border = '6px solid #047857';
        if (badge) badge.style.display = 'inline-flex';
    } else {
        btn.innerText = '⏸ PAUSAR PRODUÇÃO';
        btn.style.background = 'linear-gradient(145deg, #F59E0B 0%, #D97706 100%)';
        btn.style.border = '6px solid #B45309';
        if (badge) badge.style.display = 'none';
    }
}

function descartarProducaoAtiva() {
    if (confirm('Deseja descartar este ciclo de produção?')) {
        localStorage.removeItem('repack_producao_ativa');
        fetch('<?= APP_URL ?>/api/produtividade/descartar', { method: 'POST' }).catch(() => {});
        window.location.href = '<?= APP_URL ?>/operador';
    }
}

/* ==========================================================================
   ETAPA 2 -> ETAPA 3: FINALIZAR PRODUÇÃO FÍSICA E ABRIR TELA DE QUANTIDADE
   ========================================================================== */
function avancarParaDefinirQuantidade() {
    if (!producaoAtiva) return;

    const agora = new Date();
    const timestampFim = formatarDataHoraLocal(agora);

    if (producaoAtiva.pausado && producaoAtiva.pausa_inicio) {
        const inicioPausaMs = new Date(producaoAtiva.pausa_inicio.replace(' ', 'T')).getTime();
        const diffPausaSeg = Math.max(0, Math.round((agora.getTime() - inicioPausaMs) / 1000));
        producaoAtiva.segundos_pausa = (producaoAtiva.segundos_pausa || 0) + diffPausaSeg;
        producaoAtiva.pausado = false;
        delete producaoAtiva.pausa_inicio;
    }

    producaoAtiva.data_hora_fim = timestampFim;
    localStorage.setItem('repack_producao_ativa', JSON.stringify(producaoAtiva));

    if (navigator.vibrate) navigator.vibrate(60);

    // Oculta Momento 2 e abre a tela dedicada para Digitar Quantidade
    document.getElementById('secaoMomento1').style.display = 'none';
    document.getElementById('secaoMomento2').style.display = 'none';
    document.getElementById('secaoMomentoQuantidade').style.display = 'block';

    // Preenche dados do produto na Etapa 3
    document.getElementById('qtdNomeProduto').innerText = producaoAtiva.produto_nome;
    document.getElementById('qtdSkuBadge').innerText = 'SKU ' + (producaoAtiva.produto_codigo || '');

    const imgQtd = document.getElementById('qtdFotoProduto');
    if (imgQtd) {
        if (producaoAtiva.produto_foto_url) {
            imgQtd.src = producaoAtiva.produto_foto_url;
            imgQtd.style.display = 'inline-block';
        } else {
            imgQtd.src = '';
            imgQtd.style.display = 'none';
        }
    }

    // Horários de Início e Término
    const dataObjInicio = new Date(producaoAtiva.data_hora_inicio.replace(' ', 'T'));
    const horasIni = String(dataObjInicio.getHours()).padStart(2, '0');
    const minsIni = String(dataObjInicio.getMinutes()).padStart(2, '0');
    document.getElementById('qtdHoraInicio').innerText = `${horasIni}:${minsIni}`;

    const dataObjFim = new Date(timestampFim.replace(' ', 'T'));
    const horasFim = String(dataObjFim.getHours()).padStart(2, '0');
    const minsFim = String(dataObjFim.getMinutes()).padStart(2, '0');
    document.getElementById('qtdHoraFim').innerText = `${horasFim}:${minsFim}`;

    const inputQtd = document.getElementById('displayPacotesEtapa2');
    inputQtd.value = pacotesContador || 1;
    inputQtd.focus();
    inputQtd.select();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function voltarParaTrabalhoEmAndamento() {
    document.getElementById('secaoMomentoQuantidade').style.display = 'none';
    document.getElementById('secaoMomento2').style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ==========================================================================
   ETAPA 3: CONTROLE NUMÉRICO DA QUANTIDADE
   ========================================================================== */
function alterarPacotesEtapa2(delta) {
    const input = document.getElementById('displayPacotesEtapa2');
    let atual = parseInt(input.value, 10) || 1;
    let nova = Math.max(1, atual + delta);
    input.value = nova;
    pacotesContador = nova;
    if (navigator.vibrate) navigator.vibrate(25);
}

function atualizarPacotesEtapa2Digitados(val) {
    const parsed = parseInt(val, 10);
    if (!isNaN(parsed) && parsed >= 1) {
        pacotesContador = parsed;
    }
}

function validarPacotesEtapa2Blur() {
    const input = document.getElementById('displayPacotesEtapa2');
    const parsed = parseInt(input.value, 10);
    if (isNaN(parsed) || parsed < 1) {
        pacotesContador = 1;
        input.value = 1;
    } else {
        pacotesContador = parsed;
        input.value = parsed;
    }
}

/* ==========================================================================
   ETAPA 3 -> ETAPA 4: AVANÇAR PARA CONFERÊNCIA E CONFIRMAÇÃO
   ========================================================================== */
function submeterParaConferencia() {
    if (!producaoAtiva) return;

    validarPacotesEtapa2Blur();

    const timestampFim = producaoAtiva.data_hora_fim || formatarDataHoraLocal(new Date());

    const inicioMs = new Date(producaoAtiva.data_hora_inicio.replace(' ', 'T')).getTime();
    const fimMs = new Date(timestampFim.replace(' ', 'T')).getTime();
    const duracaoBruta = Math.max(0, Math.round((fimMs - inicioMs) / 1000));
    const duracaoLiquida = Math.max(0, duracaoBruta - (producaoAtiva.segundos_pausa || 0));

    if (navigator.vibrate) navigator.vibrate(100);

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
