<?php
declare(strict_types=1);

/**
 * Projeto Repack - Urna Eletrônica (Registro de Produção por Lote / Turno Consolidado)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md
 */

require_once __DIR__ . '/../../models/Produto.php';

$produtos = Produto::listarAtivos();

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

$pageTitle = "Registrar Produção por Lote";
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="text-align: center; margin-bottom: 20px;">
    <h1 class="headline-lg" style="margin: 0; font-size: 46px; font-weight: 900; color: #0F172A; letter-spacing: 0.5px;">
        REGISTRAR PRODUÇÃO
    </h1>
    <span style="display: inline-block; font-size: 20px; font-weight: 800; color: #059669; background: #ECFDF5; border: 2px solid #10B981; padding: 4px 18px; border-radius: 9999px; margin-top: 6px;">
        📦 LANÇAMENTO POR LOTE / TURNO
    </span>
</div>

<!-- ============================================================= -->
<!-- BLOCO 1: PERÍODO DO LOTE / TURNO TRABALHADO                   -->
<!-- ============================================================= -->
<div class="urna-card" style="width: 100%; padding: 24px; border-radius: 28px; border: 5px solid #0F172A; background: #FFFFFF; margin-bottom: 24px;">
    <div style="font-size: 24px; font-weight: 900; color: #0F172A; text-transform: uppercase; margin-bottom: 14px; text-align: center;">
        1. SELECIONE O TURNO OU HORÁRIO DO LOTE:
    </div>

    <!-- Botões Rápidos de Turno -->
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 18px;">
        <button type="button" id="btnTurnoManha" class="btn-urna" onclick="selecionarTurno('manha')" style="min-height: 80px; font-size: 24px; font-weight: 900; border-radius: 20px; background: #FEF3C7; color: #92400E; border: 4px solid #F59E0B;">
            ☀️ MANHÃ<br><span style="font-size: 17px; font-weight: 800;">08:00 - 12:00</span>
        </button>
        <button type="button" id="btnTurnoTarde" class="btn-urna" onclick="selecionarTurno('tarde')" style="min-height: 80px; font-size: 24px; font-weight: 900; border-radius: 20px; background: #E0E7FF; color: #3730A3; border: 4px solid #6366F1;">
            ⛅ TARDE<br><span style="font-size: 17px; font-weight: 800;">13:00 - 17:00</span>
        </button>
        <button type="button" id="btnTurnoOutro" class="btn-urna" onclick="selecionarTurno('outro')" style="min-height: 80px; font-size: 22px; font-weight: 900; border-radius: 20px; background: #F1F5F9; color: #334155; border: 4px solid #94A3B8;">
            ⚙️ OUTRO<br><span style="font-size: 17px; font-weight: 800;">HORÁRIO</span>
        </button>
    </div>

    <!-- Campos de Ajuste Fino de Horário -->
    <div id="wrapperHorariosPersonalizados" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 12px; background: #F8FAFC; padding: 16px; border-radius: 20px; border: 2px dashed #CBD5E1;">
        <div>
            <label for="inputHoraInicio" style="display: block; font-size: 19px; font-weight: 900; color: #475569; margin-bottom: 6px;">
                HORA INÍCIO:
            </label>
            <input type="time" id="inputHoraInicio" class="input-sku-gigante" style="min-height: 65px; font-size: 32px; font-weight: 900; text-align: center; border-radius: 16px;" value="08:00" onchange="atualizarDuracaoLote()">
        </div>
        <div>
            <label for="inputHoraFim" style="display: block; font-size: 19px; font-weight: 900; color: #475569; margin-bottom: 6px;">
                HORA TÉRMINO:
            </label>
            <input type="time" id="inputHoraFim" class="input-sku-gigante" style="min-height: 65px; font-size: 32px; font-weight: 900; text-align: center; border-radius: 16px;" value="12:00" onchange="atualizarDuracaoLote()">
        </div>
    </div>

    <!-- Badge Informativo de Duração Total Calculada -->
    <div style="text-align: center; margin-top: 14px;">
        <span style="font-size: 22px; font-weight: 900; color: #1E293B;">
            TEMPO TOTAL DO BLOCO: 
            <span id="displayDuracaoTotal" style="color: #2563EB; font-size: 26px; font-weight: 900;">4h 00min</span>
        </span>
    </div>
</div>

<!-- ============================================================= -->
<!-- BLOCO 2: INCLUSÃO DE PRODUTO NO LOTE (SKU + QUANTIDADE)       -->
<!-- ============================================================= -->
<div class="urna-card" style="width: 100%; padding: 24px; border-radius: 28px; border: 5px solid #0F172A; background: #FFFFFF; margin-bottom: 24px;">
    <div style="font-size: 24px; font-weight: 900; color: #0F172A; text-transform: uppercase; margin-bottom: 14px; text-align: center;">
        2. INCLUIR ITEM REEMBALADO:
    </div>

    <div class="sku-entry-container" style="margin-bottom: 0;">
        <label for="inputCodigoSku" style="display: block; font-size: 34px; font-weight: 900; color: #0F172A; text-transform: uppercase; margin-bottom: 12px; text-align: center; letter-spacing: 0.5px;">
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
                   oninput="processarEntradaSku(this.value)">
            <button type="button" 
                    id="btnLimparSku" 
                    class="btn-limpar-sku" 
                    style="display: none;" 
                    onclick="limparSku()" 
                    aria-label="Limpar código">✕</button>
        </div>

        <!-- Card de Confirmação Imediata do SKU -->
        <div class="card-confirmacao-sku" id="cardConfirmacaoSku" style="display: none; margin-top: 14px;">
            <div class="badge-status-ok">
                <svg width="28" height="28" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
                <span>PRODUTO IDENTIFICADO</span>
            </div>
            <div class="nome-produto-destaque" id="displayNomeProduto" style="font-size: 30px;">
                Nome do Produto
            </div>
            <div class="detalhes-produto">
                <span class="badge-sku" id="displaySkuBadge">SKU 0000</span>
                <span class="badge-embalagem" id="displayEmbalagem">Padrão</span>
            </div>
        </div>

        <!-- Card de Erro: SKU Não Encontrado -->
        <div id="cardNaoEncontrado" style="display: none; background: #FEF2F2; border: 4px solid #EF4444; border-radius: 20px; padding: 16px; margin-top: 14px; text-align: center;">
            <div style="font-size: 22px; font-weight: 900; color: #991B1B;">
                ⚠️ Código <span id="codigoNaoEncontrado"></span> não cadastrado!
            </div>
        </div>

        <!-- Sugestões de Busca Rápida -->
        <div id="wrapperSugestoes" style="display: none; margin-top: 12px; width: 100%;">
            <div style="font-size: 17px; font-weight: 900; color: #475569; margin-bottom: 8px;">TOQUE PARA SELECIONAR:</div>
            <div id="listaSugestoes" class="lista-sugestoes-sku"></div>
        </div>

        <!-- Seletor de Quantidade de Pacotes com Teclado e Botões Grandes -->
        <div style="margin-top: 20px;">
            <label for="displayQtd" style="display: block; font-size: 30px; font-weight: 900; color: #0F172A; text-transform: uppercase; margin-bottom: 10px; text-align: center;">
                QUANTIDADE DE PACOTES:
            </label>
            <div class="counter-container">
                <button type="button" class="btn-counter" onclick="alterarQuantidade(-1)" aria-label="Diminuir">-</button>
                <input type="text" 
                       id="displayQtd" 
                       class="counter-display" 
                       inputmode="numeric" 
                       pattern="[0-9]*" 
                       min="1" 
                       autocomplete="off" 
                       value="1" 
                       onfocus="this.select()" 
                       oninput="atualizarQuantidadeDigitada(this.value)" 
                       onblur="validarQuantidadeBlur()" 
                       aria-label="Quantidade de pacotes">
                <button type="button" class="btn-counter" onclick="alterarQuantidade(1)" aria-label="Aumentar">+</button>
            </div>
        </div>

        <!-- Botão Monumental de Adicionar Item ao Lote -->
        <div style="margin-top: 22px;">
            <button type="button" id="btnAdicionarAoLote" class="btn-urna btn-urna-primary" onclick="adicionarItemAoLote()" style="min-height: 90px; font-size: 32px; font-weight: 900; border-radius: 26px; width: 100%; display: flex; align-items: center; justify-content: center; gap: 14px;">
                <span>➕</span> <span>ADICIONAR AO LOTE</span>
            </button>
        </div>
    </div>
</div>

<!-- ============================================================= -->
<!-- BLOCO 3: LISTA DOS ITENS DO LOTE (COMANDA ATUAL)              -->
<!-- ============================================================= -->
<div class="urna-card" style="width: 100%; padding: 24px; border-radius: 28px; border: 5px solid #0F172A; background: #FFFFFF; margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 3px solid #E2E8F0; padding-bottom: 10px;">
        <div style="font-size: 24px; font-weight: 900; color: #0F172A; text-transform: uppercase;">
            3. ITENS NO LOTE (<span id="contadorItensLote">0</span>)
        </div>
        <button type="button" onclick="limparTodoLote()" style="background: none; border: none; color: #DC2626; font-size: 18px; font-weight: 800; cursor: pointer; text-decoration: underline;">
            Limpar Todos
        </button>
    </div>

    <!-- Container Dinâmico da Lista -->
    <div id="listaItensLote" style="display: flex; flex-direction: column; gap: 12px; max-height: 480px; overflow-y: auto; padding-right: 4px;">
        <!-- Itens inseridos via JavaScript -->
    </div>

    <!-- Mensagem Vazia Inicial -->
    <div id="msgLoteVazio" style="text-align: center; padding: 34px 16px; background: #F8FAFC; border: 3px dashed #CBD5E1; border-radius: 20px;">
        <span style="font-size: 42px;">📋</span>
        <div style="font-size: 22px; font-weight: 900; color: #64748B; margin-top: 10px;">
            Nenhum produto adicionado ao lote ainda.
        </div>
        <div style="font-size: 18px; font-weight: 700; color: #94A3B8; margin-top: 4px;">
            Digite o código do produto acima e clique em "Adicionar ao Lote".
        </div>
    </div>

    <!-- Rodapé Consolidado do Lote -->
    <div id="cardConsolidadoLote" style="display: none; margin-top: 18px; padding-top: 18px; border-top: 4px solid #CBD5E1; background: #F8FAFC; border-radius: 20px; padding: 18px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; text-align: center;">
            <div style="border-right: 3px solid #E2E8F0; padding-right: 10px;">
                <div style="font-size: 18px; font-weight: 900; color: #64748B; text-transform: uppercase;">TOTAL DO LOTE:</div>
                <div style="font-size: 48px; font-weight: 900; color: #0F172A; line-height: 1.1;">
                    <span id="displayTotalPacotesLote">0</span> <span style="font-size: 22px; color: #64748B;">pcts</span>
                </div>
            </div>
            <div>
                <div style="font-size: 18px; font-weight: 900; color: #64748B; text-transform: uppercase;">TEMPO MÉDIO:</div>
                <div style="font-size: 38px; font-weight: 900; color: #059669; line-height: 1.1; margin-top: 4px;">
                    <span id="displayTempoMedioPorPacote">--</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================= -->
<!-- BLOCO 4: DUPLA CONFIRMAÇÃO DA URNA (RODAPÉ MONUMENTAL)        -->
<!-- ============================================================= -->
<form id="formAvancarLote" action="<?= APP_URL ?>/operador/cronometro/resumo" method="POST" onsubmit="return validarAvancoLote(event)">
    <input type="hidden" name="payload_lote" id="inputPayloadLote" value="">

    <div class="dupla-confirmacao-rodape" style="width: 100%; margin: 26px 0 0 0; gap: 20px;">
        <a href="<?= APP_URL ?>/operador?limpar_cronometro=1" onclick="cancelarRascunhoLote()" class="btn-urna btn-urna-warning" style="min-height: 140px; font-size: 46px; font-weight: 900; border-radius: 36px; text-decoration: none; display: flex; align-items: center; justify-content: center;">
            CANCELAR
        </a>
        <button type="submit" class="btn-urna btn-urna-primary" id="btnAvancarLote" style="min-height: 140px; font-size: 44px; font-weight: 900; border-radius: 36px;">
            CONFERIR ➔
        </button>
    </div>
</form>

<script>
const PRODUTOS = <?= json_encode($produtosJson, JSON_UNESCAPED_UNICODE) ?>;

// Estado reativo do lote
let produtoAtivo = null;
let quantidadeAtual = 1;
let turnoAtual = 'manha'; // 'manha', 'tarde', 'outro'
let itensLote = [];

// 1. Inicialização e Restauração de Rascunho
window.addEventListener('DOMContentLoaded', () => {
    restaurarRascunhoLote();
    atualizarDuracaoLote();
});

function selecionarTurno(tipo) {
    turnoAtual = tipo;
    const btnM = document.getElementById('btnTurnoManha');
    const btnT = document.getElementById('btnTurnoTarde');
    const btnO = document.getElementById('btnTurnoOutro');
    const wrapper = document.getElementById('wrapperHorariosPersonalizados');

    btnM.style.opacity = (tipo === 'manha') ? '1' : '0.55';
    btnT.style.opacity = (tipo === 'tarde') ? '1' : '0.55';
    btnO.style.opacity = (tipo === 'outro') ? '1' : '0.55';

    if (tipo === 'manha') {
        document.getElementById('inputHoraInicio').value = '08:00';
        document.getElementById('inputHoraFim').value = '12:00';
    } else if (tipo === 'tarde') {
        document.getElementById('inputHoraInicio').value = '13:00';
        document.getElementById('inputHoraFim').value = '17:00';
    }

    atualizarDuracaoLote();
    salvarRascunhoLote();
}

function calcularSegundosEntreHorarios(hInicio, hFim) {
    const hoje = new Date().toISOString().split('T')[0];
    const dataIni = new Date(`${hoje}T${hInicio}:00`);
    let dataFim = new Date(`${hoje}T${hFim}:00`);

    if (dataFim <= dataIni) {
        // Se término for menor ou igual ao início, assume que virou a noite ou adiciona 1 dia
        dataFim = new Date(dataFim.getTime() + 24 * 3600 * 1000);
    }

    const segundos = Math.max(60, Math.floor((dataFim - dataIni) / 1000));
    return segundos;
}

function atualizarDuracaoLote() {
    const hInicio = document.getElementById('inputHoraInicio').value || '08:00';
    const hFim = document.getElementById('inputHoraFim').value || '12:00';
    const seg = calcularSegundosEntreHorarios(hInicio, hFim);

    const horas = Math.floor(seg / 3600);
    const minutos = Math.floor((seg % 3600) / 60);

    const texto = `${horas}h ${String(minutos).padStart(2, '0')}min`;
    document.getElementById('displayDuracaoTotal').innerText = texto;

    recalcularTotaisLote();
    salvarRascunhoLote();
}

// 2. Busca e Reconhecimento de Código SKU
function processarEntradaSku(valor) {
    const termo = valor.trim().toLowerCase();
    const btnLimpar = document.getElementById('btnLimparSku');
    const input = document.getElementById('inputCodigoSku');
    const cardOk = document.getElementById('cardConfirmacaoSku');
    const cardErro = document.getElementById('cardNaoEncontrado');
    const wrapperSugestoes = document.getElementById('wrapperSugestoes');

    btnLimpar.style.display = termo ? 'flex' : 'none';

    if (!termo) {
        desmarcarProduto();
        cardOk.style.display = 'none';
        cardErro.style.display = 'none';
        wrapperSugestoes.style.display = 'none';
        input.classList.remove('sucesso-encontrado');
        return;
    }

    const matchExato = PRODUTOS.find(p => p.codigo.toLowerCase() === termo);
    if (matchExato) {
        selecionarProdutoConfirmado(matchExato);
        cardErro.style.display = 'none';
        wrapperSugestoes.style.display = 'none';
        return;
    }

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

    if (!apenasNumeros && termo.length >= 2) {
        const matchesNome = PRODUTOS.filter(p => p.nome.toLowerCase().includes(termo)).slice(0, 6);
        if (matchesNome.length > 0) {
            desmarcarProduto();
            cardOk.style.display = 'none';
            cardErro.style.display = 'none';
            exibirSugestoes(matchesNome);
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
    document.getElementById('displayNomeProduto').innerText = p.nome;
    document.getElementById('displaySkuBadge').innerText = 'SKU ' + p.codigo;
    document.getElementById('displayEmbalagem').innerText = p.embalagem || 'Padrão';

    const cardOk = document.getElementById('cardConfirmacaoSku');
    cardOk.style.display = 'block';

    const input = document.getElementById('inputCodigoSku');
    input.classList.add('sucesso-encontrado');
    input.value = p.codigo;
    document.getElementById('btnLimparSku').style.display = 'flex';
}

function desmarcarProduto() {
    produtoAtivo = null;
}

function exibirSugestoes(lista) {
    const wrapper = document.getElementById('wrapperSugestoes');
    const container = document.getElementById('listaSugestoes');
    container.innerHTML = '';

    lista.forEach(p => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'item-sugestao-sku';
        btn.onclick = () => {
            selecionarProdutoConfirmado(p);
            wrapper.style.display = 'none';
        };

        btn.innerHTML = `
            <div>
                <span class="sku-badge">SKU ${escapeHtml(p.codigo)}</span>
                <div style="font-weight: 800; font-size: 16px; color: var(--on-surface); line-height: 1.2;">
                    ${escapeHtml(p.nome)}
                </div>
                <div style="font-size: 13px; color: var(--on-surface-muted);">
                    ${escapeHtml(p.embalagem || '')}
                </div>
            </div>
            <div style="color: #059669; font-weight: 900; font-size: 20px;">➔</div>
        `;
        container.appendChild(btn);
    });

    wrapper.style.display = 'block';
}

function limparSku() {
    const input = document.getElementById('inputCodigoSku');
    input.value = '';
    processarEntradaSku('');
    input.focus();
}

function alterarQuantidade(delta) {
    let atual = parseInt(document.getElementById('displayQtd').value, 10) || 1;
    quantidadeAtual = Math.max(1, atual + delta);
    document.getElementById('displayQtd').value = quantidadeAtual;
}

function atualizarQuantidadeDigitada(valor) {
    let num = parseInt(valor, 10);
    if (!isNaN(num) && num > 0) {
        quantidadeAtual = num;
    }
}

function validarQuantidadeBlur() {
    const input = document.getElementById('displayQtd');
    let num = parseInt(input.value, 10);
    if (isNaN(num) || num < 1) {
        num = 1;
    }
    quantidadeAtual = num;
    input.value = quantidadeAtual;
}

// 3. Adicionar Produto à Lista do Lote
function adicionarItemAoLote() {
    if (!produtoAtivo) {
        alert('Por favor, digite o código de um produto válido antes de adicionar.');
        document.getElementById('inputCodigoSku').focus();
        return;
    }

    const qtd = quantidadeAtual;
    if (qtd <= 0) {
        alert('Informe uma quantidade válida de pacotes.');
        return;
    }

    // Se já estiver na lista, soma a quantidade
    const indexExistente = itensLote.findIndex(item => item.produto_id === produtoAtivo.id);
    if (indexExistente >= 0) {
        itensLote[indexExistente].pacotes += qtd;
    } else {
        itensLote.push({
            produto_id: produtoAtivo.id,
            codigo: produtoAtivo.codigo,
            nome: produtoAtivo.nome,
            embalagem: produtoAtivo.embalagem || '',
            foto_url: produtoAtivo.foto_url || null,
            pacotes: qtd
        });
    }

    // Limpa campo para o próximo item
    limparSku();
    quantidadeAtual = 1;
    document.getElementById('displayQtd').value = 1;

    renderizarListaLote();
    salvarRascunhoLote();

    if (navigator.vibrate) navigator.vibrate(80);
}

function removerItemLote(index) {
    itensLote.splice(index, 1);
    renderizarListaLote();
    salvarRascunhoLote();
}

function limparTodoLote() {
    if (itensLote.length === 0) return;
    if (confirm('Deseja realmente limpar todos os itens adicionados ao lote?')) {
        itensLote = [];
        renderizarListaLote();
        salvarRascunhoLote();
    }
}

function renderizarListaLote() {
    const container = document.getElementById('listaItensLote');
    const msgVazio = document.getElementById('msgLoteVazio');
    const cardConsolidado = document.getElementById('cardConsolidadoLote');
    const contador = document.getElementById('contadorItensLote');

    contador.innerText = itensLote.length;

    if (itensLote.length === 0) {
        container.innerHTML = '';
        msgVazio.style.display = 'block';
        cardConsolidado.style.display = 'none';
        return;
    }

    msgVazio.style.display = 'none';
    cardConsolidado.style.display = 'block';

    let html = '';
    itensLote.forEach((item, index) => {
        html += `
            <div style="display: flex; align-items: center; justify-content: space-between; background: #F8FAFC; border: 3px solid #E2E8F0; border-radius: 18px; padding: 14px 18px; gap: 12px;">
                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <span style="background: #0F172A; color: #FFFFFF; font-size: 15px; font-weight: 900; padding: 2px 8px; border-radius: 6px;">SKU ${escapeHtml(item.codigo)}</span>
                        <span style="font-size: 13px; color: #64748B; font-weight: 700;">${escapeHtml(item.embalagem)}</span>
                    </div>
                    <div style="font-size: 20px; font-weight: 900; color: #0F172A; line-height: 1.2; word-break: break-word;">
                        ${escapeHtml(item.nome)}
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="background: #ECFDF5; border: 2.5px solid #10B981; border-radius: 14px; padding: 6px 14px; text-align: center;">
                        <span style="font-size: 28px; font-weight: 900; color: #065F46;">${item.pacotes}</span>
                        <span style="font-size: 14px; font-weight: 800; color: #059669; display: block; margin-top: -4px;">pcts</span>
                    </div>
                    <button type="button" onclick="removerItemLote(${index})" style="width: 48px; height: 48px; border-radius: 14px; background: #FEF2F2; border: 2.5px solid #EF4444; color: #DC2626; font-size: 22px; font-weight: 900; cursor: pointer; display: flex; align-items: center; justify-content: center;" aria-label="Remover">
                        ✕
                    </button>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    recalcularTotaisLote();
}

function recalcularTotaisLote() {
    const totalPacotes = itensLote.reduce((acc, item) => acc + item.pacotes, 0);
    document.getElementById('displayTotalPacotesLote').innerText = totalPacotes;

    const hInicio = document.getElementById('inputHoraInicio').value || '08:00';
    const hFim = document.getElementById('inputHoraFim').value || '12:00';
    const duracaoSeg = calcularSegundosEntreHorarios(hInicio, hFim);

    if (totalPacotes > 0 && duracaoSeg > 0) {
        const segPorPacote = Math.round(duracaoSeg / totalPacotes);
        const min = Math.floor(segPorPacote / 60);
        const s = segPorPacote % 60;
        let texto = '';
        if (min > 0) {
            texto += `${min} min `;
        }
        texto += `${s} s / pct`;
        document.getElementById('displayTempoMedioPorPacote').innerText = texto;
    } else {
        document.getElementById('displayTempoMedioPorPacote').innerText = '--';
    }
}

// 4. Persistência de Rascunho do Lote
function salvarRascunhoLote() {
    const hInicio = document.getElementById('inputHoraInicio').value || '08:00';
    const hFim = document.getElementById('inputHoraFim').value || '12:00';

    const rascunho = {
        turno: turnoAtual,
        hora_inicio: hInicio,
        hora_fim: hFim,
        itens: itensLote
    };

    try {
        localStorage.setItem('repack_lote_ativo', JSON.stringify(rascunho));
    } catch(e) {}
}

function restaurarRascunhoLote() {
    const salvoStr = localStorage.getItem('repack_lote_ativo');
    if (!salvoStr) return;

    try {
        const salvo = JSON.parse(salvoStr);
        if (salvo && Array.isArray(salvo.itens)) {
            itensLote = salvo.itens;
            if (salvo.hora_inicio) document.getElementById('inputHoraInicio').value = salvo.hora_inicio;
            if (salvo.hora_fim) document.getElementById('inputHoraFim').value = salvo.hora_fim;
            if (salvo.turno) selecionarTurno(salvo.turno);
            renderizarListaLote();
        }
    } catch(e) {}
}

function cancelarRascunhoLote() {
    try {
        localStorage.removeItem('repack_lote_ativo');
    } catch(e) {}
}

// 5. Validação de Avanço para a Conferência
function validarAvancoLote(e) {
    if (itensLote.length === 0) {
        e.preventDefault();
        alert('⚠️ Adicione pelo menos um produto reembalado ao lote antes de conferir.');
        document.getElementById('inputCodigoSku').focus();
        return false;
    }

    const hInicio = document.getElementById('inputHoraInicio').value || '08:00';
    const hFim = document.getElementById('inputHoraFim').value || '12:00';
    const duracaoSeg = calcularSegundosEntreHorarios(hInicio, hFim);

    const payload = {
        turno: turnoAtual,
        hora_inicio: hInicio,
        hora_fim: hFim,
        duracao_total_segundos: duracaoSeg,
        itens: itensLote
    };

    document.getElementById('inputPayloadLote').value = JSON.stringify(payload);
    return true;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text || '';
    return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
