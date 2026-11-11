<?php
declare(strict_types=1);

/**
 * Projeto Repack - Urna Eletrônica (Registro de Produção por Marcadores de Horário)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md (Seção 12.1 e 13.2)
 */

require_once __DIR__ . '/../../models/Produto.php';

$produtos = Produto::listarAtivos();

// Produto e dados previamente selecionados (caso esteja voltando explicitamente da conferência)
$isOrigemVoltar = (isset($_GET['origem']) && $_GET['origem'] === 'voltar');
if (!$isOrigemVoltar) {
    unset($_SESSION['cronometro_temp']);
}

$produtoPreSelecionadoId = $isOrigemVoltar ? (int)($_SESSION['cronometro_temp']['produto_id'] ?? 0) : 0;
$pacotesIniciais = $isOrigemVoltar ? (int)($_SESSION['cronometro_temp']['pacotes_refeitos'] ?? 1) : 1;
if ($pacotesIniciais < 1) {
    $pacotesIniciais = 1;
}

$horaInicioPrevia = '';
if ($isOrigemVoltar && !empty($_SESSION['cronometro_temp']['data_hora_inicio'])) {
    $horaInicioPrevia = date('H:i', strtotime($_SESSION['cronometro_temp']['data_hora_inicio']));
}

$horaFimPrevia = '';
if ($isOrigemVoltar && !empty($_SESSION['cronometro_temp']['data_hora_fim'])) {
    $horaFimPrevia = date('H:i', strtotime($_SESSION['cronometro_temp']['data_hora_fim']));
}

$produtoPreSelecionado = null;
if ($produtoPreSelecionadoId > 0) {
    foreach ($produtos as $p) {
        if ((int)$p['id'] === $produtoPreSelecionadoId) {
            $produtoPreSelecionado = $p;
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

<div style="text-align: center; margin-bottom: 20px;">
    <h1 class="headline-lg" style="margin: 0; font-size: 48px; font-weight: 900; color: #0F172A; letter-spacing: 0.5px;">
        REGISTRAR PRODUÇÃO
    </h1>
</div>

<form action="<?= APP_URL ?>/operador/cronometro/resumo" method="POST" id="formProducao" autocomplete="off" onsubmit="return validarEnvioProducao(event)">
    <input type="hidden" name="produto_id" id="inputProdutoId" value="<?= $produtoPreSelecionado['id'] ?? '' ?>">
    <input type="hidden" name="produto_nome" id="inputProdutoNome" value="<?= sanitize_html($produtoPreSelecionado['nome'] ?? '') ?>">
    <input type="hidden" name="pacotes_refeitos" id="inputPacotesRefeitos" value="<?= $pacotesIniciais ?>">
    <input type="hidden" name="data_hora_inicio" id="inputDataHoraInicio" value="">
    <input type="hidden" name="data_hora_fim" id="inputDataHoraFim" value="">
    <input type="hidden" name="duracao_segundos" id="inputDuracaoSegundos" value="0">

    <!-- SEÇÃO 1: CÓDIGO (SKU) DO PRODUTO (Idêntico ao B.O.) -->
    <div class="sku-entry-container">
        <label for="inputCodigoSku" style="display: block; font-size: 36px; font-weight: 900; color: #0F172A; text-transform: uppercase; margin-bottom: 16px; text-align: center; letter-spacing: 0.5px;">
            CÓDIGO (SKU):
        </label>
        
        <div class="sku-input-wrapper">
            <input type="text" 
                   id="inputCodigoSku" 
                   class="input-sku-gigante <?= $produtoPreSelecionado ? 'sucesso-encontrado' : '' ?>" 
                   inputmode="numeric" 
                   pattern="[0-9]*"
                   placeholder="Ex: 25174" 
                   value="<?= sanitize_html($produtoPreSelecionado['codigo'] ?? '') ?>"
                   autocomplete="off"
                   autofocus
                   oninput="processarEntradaSku(this.value)">
            <button type="button" 
                    id="btnLimparSku" 
                    class="btn-limpar-sku" 
                    style="<?= $produtoPreSelecionado ? 'display: flex;' : 'display: none;' ?>" 
                    onclick="limparSku()" 
                    aria-label="Limpar código">✕</button>
        </div>

        <!-- Card de Confirmação Imediata (Verde de Alto Contraste) -->
        <div class="card-confirmacao-sku" id="cardConfirmacaoSku" style="<?= $produtoPreSelecionado ? 'display: block;' : 'display: none;' ?>">
            <div class="badge-status-ok">
                <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
                <span>PRODUTO IDENTIFICADO</span>
            </div>
            <div class="nome-produto-destaque" id="displayNomeProduto">
                <?= sanitize_html($produtoPreSelecionado['nome'] ?? '') ?>
            </div>
            <div class="detalhes-produto">
                <span class="badge-sku" id="displaySkuBadge">SKU <?= sanitize_html($produtoPreSelecionado['codigo'] ?? '') ?></span>
                <span class="badge-embalagem" id="displayEmbalagem">
                    <?= sanitize_html($produtoPreSelecionado['pacote_padrao'] ?? '') ?>
                </span>
            </div>
        </div>

        <!-- Alerta de Código Não Encontrado -->
        <div class="card-nao-encontrado" id="cardNaoEncontrado" style="display: none;">
            ⚠️ Nenhum produto cadastrado com o código <strong id="codigoNaoEncontrado"></strong>.<br>
            <span style="font-size: 18px; font-weight: 600;">Confira os dígitos na etiqueta da embalagem.</span>
        </div>

        <!-- Menu Flutuante de Busca por Texto -->
        <div id="wrapperSugestoes" class="wrapper-sugestoes-busca" style="display: none;">
            <div style="padding: 12px 18px; font-size: 18px; font-weight: 800; background: #F8FAFC; color: #475569; border-bottom: 2px solid #E2E8F0;">
                Toque no produto desejado:
            </div>
            <div id="listaSugestoes" style="max-height: 280px; overflow-y: auto;"></div>
        </div>
    </div>

    <!-- SEÇÃO 2: QUANTIDADE DE PACOTES REFEITOS (Escala Monumental com Digitação Direta) -->
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

    <!-- SEÇÃO 3: MARCADORES DE HORÁRIO DE INÍCIO E TÉRMINO (Sem Cronômetro) -->
    <div class="marcador-horarios-card" style="margin-top: 26px;">
        <div style="font-size: 34px; font-weight: 900; color: #0F172A; text-align: center; margin-bottom: 22px; text-transform: uppercase; letter-spacing: 0.5px;">
            MARCADORES DE HORÁRIO:
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
            <!-- Bloco Horário de Início -->
            <div style="background: #F8FAFC; border: 4px solid #CBD5E1; border-radius: 28px; padding: 22px; text-align: center;">
                <label for="inputHoraInicio" style="display: block; font-size: 28px; font-weight: 900; color: #059669; margin-bottom: 12px; text-transform: uppercase;">
                    🟢 HORÁRIO DE INÍCIO:
                </label>
                <input type="time" 
                       id="inputHoraInicio" 
                       class="input-hora-gigante" 
                       value="<?= $horaInicioPrevia ?>" 
                       autocomplete="off"
                       oninput="calcularDuracao()" 
                       onchange="calcularDuracao()">
                <button type="button" class="btn-marcar-agora" onclick="marcarHoraAgora('inicio')">
                    <span style="font-size: 30px;">⏰</span> <span>MARCAR AGORA</span>
                </button>
            </div>

            <!-- Bloco Horário de Término -->
            <div style="background: #F8FAFC; border: 4px solid #CBD5E1; border-radius: 28px; padding: 22px; text-align: center;">
                <label for="inputHoraFim" style="display: block; font-size: 28px; font-weight: 900; color: #DC2626; margin-bottom: 12px; text-transform: uppercase;">
                    🔴 HORÁRIO DE TÉRMINO:
                </label>
                <input type="time" 
                       id="inputHoraFim" 
                       class="input-hora-gigante" 
                       value="<?= $horaFimPrevia ?>" 
                       autocomplete="off"
                       oninput="calcularDuracao()" 
                       onchange="calcularDuracao()">
                <button type="button" class="btn-marcar-agora" onclick="marcarHoraAgora('fim')">
                    <span style="font-size: 30px;">⏰</span> <span>MARCAR AGORA</span>
                </button>
            </div>
        </div>

        <!-- Visor de Duração Total Calculada -->
        <div id="cardDuracao" class="visor-duracao-total" style="margin-top: 24px;">
            ⏱ DURAÇÃO CALCULADA: <strong id="textoDuracao">Preencha início e término</strong>
        </div>
    </div>

    <!-- Alerta Flutuante de Validação -->
    <div id="alertaValidacao" style="display: none; width: 100%; margin: 24px 0 0 0; background: #FEF2F2; color: #991B1B; border: 4.5px solid #EF4444; border-radius: 22px; padding: 22px; font-weight: 900; text-align: center; font-size: 24px;">
    </div>

    <!-- Botões de Ação do Rodapé (Grandes Alvos 135px de Alto Contraste) -->
    <div class="dupla-confirmacao-rodape" style="width: 100%; margin: 26px 0 0 0; gap: 20px;">
        <a href="<?= APP_URL ?>/operador" class="btn-urna btn-urna-warning" style="min-height: 135px; font-size: 42px; font-weight: 900; border-radius: 36px;">
            CANCELAR
        </a>
        <button type="submit" class="btn-urna btn-urna-primary" id="btnAvancar" style="min-height: 135px; font-size: 42px; font-weight: 900; border-radius: 36px;">
            PRÓXIMO ➔
        </button>
    </div>
</form>

<script>
const PRODUTOS = <?= json_encode($produtosJson, JSON_UNESCAPED_UNICODE) ?>;
let produtoAtivo = <?= $produtoPreSelecionado ? json_encode([
    'id' => (int)$produtoPreSelecionado['id'],
    'codigo' => (string)$produtoPreSelecionado['codigo'],
    'nome' => (string)$produtoPreSelecionado['nome'],
    'embalagem' => (string)($produtoPreSelecionado['pacote_padrao'] ?? '')
], JSON_UNESCAPED_UNICODE) : 'null' ?>;

let pacotesAtuais = <?= $pacotesIniciais ?>;

function processarEntradaSku(valor) {
    const termo = valor.trim().toLowerCase();
    const btnLimpar = document.getElementById('btnLimparSku');
    const input = document.getElementById('inputCodigoSku');
    const cardOk = document.getElementById('cardConfirmacaoSku');
    const cardErro = document.getElementById('cardNaoEncontrado');
    const wrapperSugestoes = document.getElementById('wrapperSugestoes');
    const alerta = document.getElementById('alertaValidacao');

    if (alerta) alerta.style.display = 'none';
    btnLimpar.style.display = termo ? 'flex' : 'none';

    if (!termo) {
        desmarcarProduto();
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

    // Se é numérico e tem 3+ dígitos, tenta prefixo
    const apenasNumeros = /^\d+$/.test(termo);
    if (apenasNumeros) {
        const matchesSku = PRODUTOS.filter(p => p.codigo.startsWith(termo));
        if (matchesSku.length === 1) {
            selecionarProdutoConfirmado(matchesSku[0]);
            cardErro.style.display = 'none';
            wrapperSugestoes.style.display = 'none';
            return;
        } else if (matchesSku.length > 1 && matchesSku.length <= 5) {
            desmarcarProduto();
            cardOk.style.display = 'none';
            cardErro.style.display = 'none';
            exibirSugestoes(matchesSku);
            return;
        } else if (termo.length >= 4) {
            desmarcarProduto();
            cardOk.style.display = 'none';
            wrapperSugestoes.style.display = 'none';
            document.getElementById('codigoNaoEncontrado').innerText = valor;
            cardErro.style.display = 'block';
            input.classList.remove('sucesso-encontrado');
            return;
        }
    }

    // 2. Busca por texto
    if (!apenasNumeros && termo.length >= 2) {
        const matchesNome = PRODUTOS.filter(p => p.nome.toLowerCase().includes(termo)).slice(0, 6);
        if (matchesNome.length > 0) {
            desmarcarProduto();
            cardOk.style.display = 'none';
            cardErro.style.display = 'none';
            exibirSugestoes(matchesNome);
            return;
        } else {
            desmarcarProduto();
            cardOk.style.display = 'none';
            wrapperSugestoes.style.display = 'none';
            document.getElementById('codigoNaoEncontrado').innerText = valor;
            cardErro.style.display = 'block';
            input.classList.remove('sucesso-encontrado');
            return;
        }
    }

    desmarcarProduto();
    cardOk.style.display = 'none';
    cardErro.style.display = 'none';
    wrapperSugestoes.style.display = 'none';
    input.classList.remove('sucesso-encontrado');
}

function selecionarProdutoConfirmado(p) {
    produtoAtivo = p;
    document.getElementById('inputProdutoId').value = p.id;
    document.getElementById('inputProdutoNome').value = p.nome;
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

function desmarcarProduto() {
    produtoAtivo = null;
    document.getElementById('inputProdutoId').value = '';
    document.getElementById('inputProdutoNome').value = '';
}

function limparSku() {
    const input = document.getElementById('inputCodigoSku');
    input.value = '';
    input.classList.remove('sucesso-encontrado');
    desmarcarProduto();
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

function alterarPacotes(delta) {
    pacotesAtuais = Math.max(1, pacotesAtuais + delta);
    document.getElementById('displayPacotes').value = pacotesAtuais;
    document.getElementById('inputPacotesRefeitos').value = pacotesAtuais;
    if (navigator.vibrate) navigator.vibrate(25);
}

function atualizarPacotesDigitados(val) {
    const parsed = parseInt(val, 10);
    if (!isNaN(parsed) && parsed >= 1) {
        pacotesAtuais = parsed;
        document.getElementById('inputPacotesRefeitos').value = pacotesAtuais;
    }
}

function validarPacotesBlur() {
    const display = document.getElementById('displayPacotes');
    const parsed = parseInt(display.value, 10);
    if (isNaN(parsed) || parsed < 1) {
        pacotesAtuais = 1;
        display.value = 1;
        document.getElementById('inputPacotesRefeitos').value = 1;
    } else {
        pacotesAtuais = parsed;
        display.value = parsed;
        document.getElementById('inputPacotesRefeitos').value = parsed;
    }
}

function marcarHoraAgora(campo) {
    const agora = new Date();
    const horas = String(agora.getHours()).padStart(2, '0');
    const mins = String(agora.getMinutes()).padStart(2, '0');
    const valor = `${horas}:${mins}`;

    if (campo === 'inicio') {
        document.getElementById('inputHoraInicio').value = valor;
    } else {
        document.getElementById('inputHoraFim').value = valor;
    }

    if (navigator.vibrate) navigator.vibrate(40);
    calcularDuracao();
}

function calcularDuracao() {
    const horaInicio = document.getElementById('inputHoraInicio').value;
    const horaFim = document.getElementById('inputHoraFim').value;
    const textoDuracao = document.getElementById('textoDuracao');
    const hiddenDuracao = document.getElementById('inputDuracaoSegundos');

    if (!horaInicio || !horaFim) {
        textoDuracao.innerText = 'Preencha início e término';
        hiddenDuracao.value = '0';
        return;
    }

    const [h1, m1] = horaInicio.split(':').map(Number);
    const [h2, m2] = horaFim.split(':').map(Number);

    let minutosInicio = h1 * 60 + m1;
    let minutosFim = h2 * 60 + m2;

    if (minutosFim < minutosInicio) {
        minutosFim += 24 * 60; // Suporte a virada de meia-noite
    }

    const diffMinutos = minutosFim - minutosInicio;
    const segundos = diffMinutos * 60;
    hiddenDuracao.value = String(segundos);

    const horasTotais = Math.floor(diffMinutos / 60);
    const minRestantes = diffMinutos % 60;

    let rotulo = '';
    if (horasTotais > 0 && minRestantes > 0) {
        rotulo = `${horasTotais}h ${minRestantes}min (${diffMinutos} min)`;
    } else if (horasTotais > 0) {
        rotulo = `${horasTotais} hora${horasTotais > 1 ? 's' : ''}`;
    } else {
        rotulo = `${minRestantes} minuto${minRestantes !== 1 ? 's' : ''}`;
    }

    textoDuracao.innerText = rotulo;
}

function validarEnvioProducao(e) {
    const alerta = document.getElementById('alertaValidacao');
    alerta.style.display = 'none';

    const prodId = document.getElementById('inputProdutoId').value;
    if (!prodId || !produtoAtivo) {
        e.preventDefault();
        alerta.innerText = '⚠️ DIGITE O CÓDIGO (SKU) DO PRODUTO!';
        alerta.style.display = 'block';
        document.getElementById('inputCodigoSku').focus();
        if (navigator.vibrate) navigator.vibrate(150);
        return false;
    }

    const horaInicio = document.getElementById('inputHoraInicio').value;
    const horaFim = document.getElementById('inputHoraFim').value;

    if (!horaInicio || !horaFim) {
        e.preventDefault();
        alerta.innerText = '⚠️ DEFINA O HORÁRIO DE INÍCIO E O DE TÉRMINO!';
        alerta.style.display = 'block';
        if (!horaInicio) document.getElementById('inputHoraInicio').focus();
        else document.getElementById('inputHoraFim').focus();
        if (navigator.vibrate) navigator.vibrate(150);
        return false;
    }

    // Monta timestamps ISO compatíveis com banco
    const hoje = new Date().toISOString().split('T')[0];
    document.getElementById('inputDataHoraInicio').value = `${hoje} ${horaInicio}:00`;

    const [h1, m1] = horaInicio.split(':').map(Number);
    const [h2, m2] = horaFim.split(':').map(Number);

    if ((h2 * 60 + m2) < (h1 * 60 + m1)) {
        const amanha = new Date();
        amanha.setDate(amanha.getDate() + 1);
        const dataAmanha = amanha.toISOString().split('T')[0];
        document.getElementById('inputDataHoraFim').value = `${dataAmanha} ${horaFim}:00`;
    } else {
        document.getElementById('inputDataHoraFim').value = `${hoje} ${horaFim}:00`;
    }

    calcularDuracao();
    return true;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
}

// Inicializa cálculo se já vier com horários prévios
window.addEventListener('DOMContentLoaded', () => {
    calcularDuracao();
});

// Protege contra restauração indevida de histórico do navegador
window.addEventListener('pageshow', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('origem') !== 'voltar') {
        const prodId = document.getElementById('inputProdutoId');
        if (!prodId || !prodId.value) {
            const inputSku = document.getElementById('inputCodigoSku');
            if (inputSku) inputSku.value = '';
            const cardOk = document.getElementById('cardConfirmacaoSku');
            if (cardOk) cardOk.style.display = 'none';
            const btnLimpar = document.getElementById('btnLimparSku');
            if (btnLimpar) btnLimpar.style.display = 'none';
        }
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
