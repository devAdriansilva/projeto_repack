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

<div style="text-align: center; margin-bottom: 18px;">
    <h1 class="headline-lg" style="margin: 0; font-size: 46px; font-weight: 900; color: #0F172A; letter-spacing: 0.5px;">
        REGISTRAR PRODUÇÃO
    </h1>
    <span style="display: inline-block; font-size: 20px; font-weight: 800; color: #059669; background: #ECFDF5; border: 2.5px solid #10B981; padding: 6px 20px; border-radius: 9999px; margin-top: 8px;">
        📦 LANÇAMENTO POR LOTE / TURNO
    </span>
</div>

<!-- ============================================================= -->
<!-- BLOCO 1: PERÍODO DO LOTE / TURNO TRABALHADO                   -->
<!-- ============================================================= -->
<div class="urna-card" style="width: 100%; padding: 26px 20px; border-radius: 30px; border: 6px solid #0F172A; background: #FFFFFF; margin-bottom: 24px; box-shadow: 0 8px 24px rgba(0,0,0,0.08);">
    <div style="font-size: 26px; font-weight: 900; color: #0F172A; text-transform: uppercase; margin-bottom: 18px; text-align: center; letter-spacing: 0.5px;">
        1. SELECIONE O TURNO DO LOTE:
    </div>

    <!-- 2 Botões Grandes e Proporcionais de Turno (Manhã e Tarde) -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
        <button type="button" id="btnTurnoManha" class="btn-urna" onclick="selecionarTurno('manha')" style="min-height: 95px; padding: 12px 8px; border-radius: 22px; background: #FEF3C7; color: #92400E; border: 5px solid #F59E0B; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; box-shadow: 0 4px 12px rgba(245,158,11,0.25);">
            <span style="font-size: 30px; font-weight: 900; line-height: 1;">☀️ MANHÃ</span>
            <span style="font-size: 22px; font-weight: 800; color: #B45309;">08:00 às 12:00</span>
        </button>

        <button type="button" id="btnTurnoTarde" class="btn-urna" onclick="selecionarTurno('tarde')" style="min-height: 95px; padding: 12px 8px; border-radius: 22px; background: #E0E7FF; color: #3730A3; border: 5px solid #6366F1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; opacity: 0.6;">
            <span style="font-size: 30px; font-weight: 900; line-height: 1;">⛅ TARDE</span>
            <span style="font-size: 22px; font-weight: 800; color: #4338CA;">13:00 às 17:00</span>
        </button>
    </div>

    <!-- Botão de Turno Personalizado / Outro Horário -->
    <div style="text-align: center; margin-bottom: 14px;">
        <button type="button" id="btnTurnoOutro" onclick="alternarHorarioPersonalizado()" style="background: #F1F5F9; border: 3px solid #94A3B8; color: #334155; font-size: 20px; font-weight: 900; border-radius: 16px; padding: 10px 24px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
            <span>⚙️ OUTRO HORÁRIO / PERSONALIZAR</span>
        </button>
    </div>

    <!-- Campos de Ajuste Fino de Horário (Escondido por padrão, abre ao personalizar) -->
    <div id="wrapperHorariosPersonalizados" style="display: none; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 14px; background: #F8FAFC; padding: 18px; border-radius: 20px; border: 3px dashed #94A3B8;">
        <div>
            <label for="inputHoraInicio" style="display: block; font-size: 22px; font-weight: 900; color: #1E293B; margin-bottom: 8px; text-align: center;">
                HORA INÍCIO:
            </label>
            <input type="time" id="inputHoraInicio" class="input-sku-gigante" style="min-height: 70px; font-size: 34px; font-weight: 900; text-align: center; border-radius: 18px; border: 4px solid #0F172A; width: 100%;" value="08:00" onchange="atualizarDuracaoLote()">
        </div>
        <div>
            <label for="inputHoraFim" style="display: block; font-size: 22px; font-weight: 900; color: #1E293B; margin-bottom: 8px; text-align: center;">
                HORA TÉRMINO:
            </label>
            <input type="time" id="inputHoraFim" class="input-sku-gigante" style="min-height: 70px; font-size: 34px; font-weight: 900; text-align: center; border-radius: 18px; border: 4px solid #0F172A; width: 100%;" value="12:00" onchange="atualizarDuracaoLote()">
        </div>
    </div>

    <!-- Faixa Destaque de Duração Total Calculada -->
    <div style="text-align: center; margin-top: 14px; background: #F0FDF4; border: 2.5px solid #86EFAC; border-radius: 16px; padding: 10px;">
        <span style="font-size: 24px; font-weight: 900; color: #14532D;">
            DURAÇÃO DO BLOCO: 
            <span id="displayDuracaoTotal" style="color: #059669; font-size: 30px; font-weight: 900;">4h 00min</span>
        </span>
    </div>
</div>

<!-- ============================================================= -->
<!-- BLOCO 2: INCLUSÃO DE PRODUTO NO LOTE (SKU + QUANTIDADE)       -->
<!-- ============================================================= -->
<div class="urna-card" style="width: 100%; padding: 26px 20px; border-radius: 30px; border: 6px solid #0F172A; background: #FFFFFF; margin-bottom: 24px; box-shadow: 0 8px 24px rgba(0,0,0,0.08);">
    <div style="font-size: 26px; font-weight: 900; color: #0F172A; text-transform: uppercase; margin-bottom: 16px; text-align: center; letter-spacing: 0.5px;">
        2. INCLUIR ITEM REEMBALADO:
    </div>

    <div class="sku-entry-container" style="margin-bottom: 0;">
        <label for="inputCodigoSku" style="display: block; font-size: 38px; font-weight: 900; color: #0F172A; text-transform: uppercase; margin-bottom: 14px; text-align: center; letter-spacing: 0.5px;">
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

        <!-- Card de Confirmação Imediata do SKU (Com Foto Monumental e Alto Contraste) -->
        <div class="card-confirmacao-sku" id="cardConfirmacaoSku" style="display: none; margin-top: 18px; border: 5px solid #10B981; border-radius: 24px; padding: 20px; text-align: center; background: #F0FDF4;">
            <div class="badge-status-ok" style="font-size: 22px; font-weight: 900; color: #065F46; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                <svg width="30" height="30" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
                <span>PRODUTO IDENTIFICADO</span>
            </div>

            <!-- Foto Destaque do Produto (Monumental para Acessibilidade) -->
            <div id="wrapperFotoProduto" style="text-align: center; margin: 12px 0 14px 0;">
                <img id="imgFotoProduto" 
                     src="" 
                     alt="Foto do Produto" 
                     style="width: 100%; max-width: 380px; height: 320px; object-fit: contain; border-radius: 24px; background: #FFFFFF; border: 5px solid #059669; box-shadow: 0 10px 28px rgba(5,150,105,0.22); padding: 8px; display: none;">
            </div>

            <div class="nome-produto-destaque" id="displayNomeProduto" style="font-size: 32px; font-weight: 900; color: #0F172A; line-height: 1.2; word-break: break-word;">
                Nome do Produto
            </div>
            <div class="detalhes-produto" style="margin-top: 10px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                <span class="badge-sku" id="displaySkuBadge" style="font-size: 20px; font-weight: 900; background: #0F172A; color: #FFFFFF; padding: 4px 14px; border-radius: 10px;">SKU 0000</span>
                <span class="badge-embalagem" id="displayEmbalagem" style="font-size: 20px; font-weight: 800; background: #E2E8F0; color: #334155; padding: 4px 14px; border-radius: 10px;">Padrão</span>
            </div>
        </div>

        <!-- Card de Erro: SKU Não Encontrado -->
        <div id="cardNaoEncontrado" style="display: none; background: #FEF2F2; border: 4px solid #EF4444; border-radius: 20px; padding: 18px; margin-top: 16px; text-align: center;">
            <div style="font-size: 24px; font-weight: 900; color: #991B1B;">
                ⚠️ Código <span id="codigoNaoEncontrado"></span> não cadastrado!
            </div>
        </div>

        <!-- Sugestões de Busca Rápida -->
        <div id="wrapperSugestoes" style="display: none; margin-top: 14px; width: 100%;">
            <div style="font-size: 19px; font-weight: 900; color: #475569; margin-bottom: 8px;">TOQUE PARA SELECIONAR:</div>
            <div id="listaSugestoes" class="lista-sugestoes-sku"></div>
        </div>

        <!-- Seletor Horizontal de Quantidade (Rigorosamente Lado a Lado [-] [ 1 ] [+]) -->
        <div style="margin-top: 26px; text-align: center;">
            <label for="displayQtd" style="display: block; font-size: 34px; font-weight: 900; color: #0F172A; text-transform: uppercase; margin-bottom: 14px; letter-spacing: 0.5px;">
                QUANTIDADE DE PACOTES:
            </label>

            <!-- Container Horizontal Flexível Travado em Linha Única sem Quebra -->
            <div style="display: flex; flex-direction: row !important; flex-wrap: nowrap !important; align-items: center; justify-content: center; gap: 14px; width: 100%; margin: 0 auto; max-width: 520px;">
                <!-- Botão Menos -->
                <button type="button" 
                        onclick="alterarQuantidade(-1)" 
                        style="flex: 0 0 auto; width: clamp(85px, 24vw, 115px); height: clamp(85px, 24vw, 115px); border-radius: 28px; font-size: clamp(52px, 14vw, 76px); font-weight: 900; display: flex; align-items: center; justify-content: center; border: 6px solid #0F172A; background-color: #FFFFFF; color: #0F172A; cursor: pointer; touch-action: manipulation; box-shadow: 0 6px 16px rgba(0,0,0,0.12);" 
                        aria-label="Diminuir">
                    −
                </button>

                <!-- Input Numérico Centralizado -->
                <input type="text" 
                       id="displayQtd" 
                       inputmode="numeric" 
                       pattern="[0-9]*" 
                       min="1" 
                       autocomplete="off" 
                       value="1" 
                       onfocus="this.select()" 
                       oninput="atualizarQuantidadeDigitada(this.value)" 
                       onblur="validarQuantidadeBlur()" 
                       style="flex: 1 1 auto; min-width: 100px; max-width: 220px; height: clamp(85px, 24vw, 115px); background-color: #FFFFFF; border: 6px solid #0F172A; border-radius: 28px; font-size: clamp(52px, 14vw, 76px); font-weight: 900; color: #0F172A; text-align: center; box-sizing: border-box; padding: 0 8px; outline: none; touch-action: manipulation; box-shadow: 0 6px 16px rgba(0,0,0,0.12);" 
                       aria-label="Quantidade de pacotes">

                <!-- Botão Mais -->
                <button type="button" 
                        onclick="alterarQuantidade(1)" 
                        style="flex: 0 0 auto; width: clamp(85px, 24vw, 115px); height: clamp(85px, 24vw, 115px); border-radius: 28px; font-size: clamp(52px, 14vw, 76px); font-weight: 900; display: flex; align-items: center; justify-content: center; border: 6px solid #0F172A; background-color: #FFFFFF; color: #0F172A; cursor: pointer; touch-action: manipulation; box-shadow: 0 6px 16px rgba(0,0,0,0.12);" 
                        aria-label="Aumentar">
                    +
                </button>
            </div>
        </div>

        <!-- Botão Monumental de Adicionar Item ao Lote -->
        <div style="margin-top: 26px;">
            <button type="button" id="btnAdicionarAoLote" class="btn-urna btn-urna-primary" onclick="adicionarItemAoLote()" style="min-height: 95px; font-size: 34px; font-weight: 900; border-radius: 28px; width: 100%; display: flex; align-items: center; justify-content: center; gap: 14px; background: linear-gradient(145deg, #059669 0%, #047857 100%); border: 5px solid #065F46; box-shadow: 0 8px 22px rgba(5,150,105,0.35);">
                <span style="font-size: 38px;">➕</span> <span>ADICIONAR AO LOTE</span>
            </button>
        </div>
    </div>
</div>

<!-- ============================================================= -->
<!-- BLOCO 3: LISTA DOS ITENS DO LOTE (COMANDA ATUAL)              -->
<!-- ============================================================= -->
<div class="urna-card" style="width: 100%; padding: 26px 20px; border-radius: 30px; border: 6px solid #0F172A; background: #FFFFFF; margin-bottom: 24px; box-shadow: 0 8px 24px rgba(0,0,0,0.08);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 3.5px solid #E2E8F0; padding-bottom: 12px;">
        <div style="font-size: 26px; font-weight: 900; color: #0F172A; text-transform: uppercase;">
            3. ITENS NO LOTE (<span id="contadorItensLote">0</span>)
        </div>
        <button type="button" onclick="limparTodoLote()" style="background: none; border: none; color: #DC2626; font-size: 19px; font-weight: 900; cursor: pointer; text-decoration: underline;">
            Limpar Todos
        </button>
    </div>

    <!-- Container Dinâmico da Lista de Produtos -->
    <div id="listaItensLote" style="display: flex; flex-direction: column; gap: 14px; max-height: 520px; overflow-y: auto; padding-right: 2px;">
        <!-- Inserido dinamicamente via JS -->
    </div>

    <!-- Mensagem Vazia Inicial -->
    <div id="msgLoteVazio" style="text-align: center; padding: 36px 16px; background: #F8FAFC; border: 3px dashed #CBD5E1; border-radius: 22px;">
        <span style="font-size: 46px;">📋</span>
        <div style="font-size: 24px; font-weight: 900; color: #475569; margin-top: 10px;">
            Nenhum produto adicionado ao lote ainda.
        </div>
        <div style="font-size: 19px; font-weight: 700; color: #94A3B8; margin-top: 6px;">
            Digite o código do produto acima e clique em "Adicionar ao Lote".
        </div>
    </div>

    <!-- Rodapé Consolidado do Lote -->
    <div id="cardConsolidadoLote" style="display: none; margin-top: 20px; padding: 20px; border-top: 4px solid #CBD5E1; background: #F8FAFC; border-radius: 22px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; text-align: center;">
            <div style="border-right: 3px solid #E2E8F0; padding-right: 8px;">
                <div style="font-size: 20px; font-weight: 900; color: #64748B; text-transform: uppercase;">TOTAL DO LOTE:</div>
                <div style="font-size: 54px; font-weight: 900; color: #0F172A; line-height: 1.1; margin-top: 4px;">
                    <span id="displayTotalPacotesLote">0</span> <span style="font-size: 24px; color: #64748B; font-weight: 800;">pcts</span>
                </div>
            </div>
            <div>
                <div style="font-size: 20px; font-weight: 900; color: #64748B; text-transform: uppercase;">TEMPO MÉDIO:</div>
                <div style="font-size: 38px; font-weight: 900; color: #059669; line-height: 1.15; margin-top: 8px;">
                    <span id="displayTempoMedioPorPacote">--</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================= -->
<!-- BLOCO 4: DUPLA CONFIRMAÇÃO DA URNA (RODAPÉ MONUMENTAL 140PX)  -->
<!-- ============================================================= -->
<form id="formAvancarLote" action="<?= APP_URL ?>/operador/cronometro/resumo" method="POST" onsubmit="return validarAvancoLote(event)">
    <input type="hidden" name="payload_lote" id="inputPayloadLote" value="">

    <div class="dupla-confirmacao-rodape" style="width: 100%; margin: 26px 0 0 0; gap: 20px;">
        <a href="<?= APP_URL ?>/operador?limpar_cronometro=1" onclick="cancelarRascunhoLote()" class="btn-urna btn-urna-warning" style="min-height: 140px; font-size: 46px; font-weight: 900; border-radius: 36px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 14px;">
            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink: 0;">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            <span>CANCELAR</span>
        </a>
        <button type="submit" class="btn-urna btn-urna-primary" id="btnAvancarLote" style="min-height: 140px; font-size: 44px; font-weight: 900; border-radius: 36px; display: flex; align-items: center; justify-content: center; gap: 14px;">
            <span>CONFERIR</span>
            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink: 0;">
                <line x1="5" y1="12" x2="19" y2="12"></line>
                <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
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
    const wrapper = document.getElementById('wrapperHorariosPersonalizados');

    if (tipo === 'manha') {
        btnM.style.opacity = '1';
        btnM.style.borderWidth = '5px';
        btnT.style.opacity = '0.55';
        btnT.style.borderWidth = '3px';
        wrapper.style.display = 'none';
        document.getElementById('inputHoraInicio').value = '08:00';
        document.getElementById('inputHoraFim').value = '12:00';
    } else if (tipo === 'tarde') {
        btnM.style.opacity = '0.55';
        btnM.style.borderWidth = '3px';
        btnT.style.opacity = '1';
        btnT.style.borderWidth = '5px';
        wrapper.style.display = 'none';
        document.getElementById('inputHoraInicio').value = '13:00';
        document.getElementById('inputHoraFim').value = '17:00';
    } else {
        btnM.style.opacity = '0.55';
        btnT.style.opacity = '0.55';
        wrapper.style.display = 'grid';
    }

    atualizarDuracaoLote();
    salvarRascunhoLote();
}

function alternarHorarioPersonalizado() {
    const wrapper = document.getElementById('wrapperHorariosPersonalizados');
    if (wrapper.style.display === 'none' || wrapper.style.display === '') {
        selecionarTurno('outro');
    } else {
        selecionarTurno('manha');
    }
}

function calcularSegundosEntreHorarios(hInicio, hFim) {
    const hoje = new Date().toISOString().split('T')[0];
    const dataIni = new Date(`${hoje}T${hInicio}:00`);
    let dataFim = new Date(`${hoje}T${hFim}:00`);

    if (dataFim <= dataIni) {
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

    // Exibe a Foto Monumental se disponível
    const imgFoto = document.getElementById('imgFotoProduto');
    if (imgFoto) {
        if (p.foto_url) {
            imgFoto.src = p.foto_url;
            imgFoto.style.display = 'inline-block';
        } else {
            imgFoto.src = '';
            imgFoto.style.display = 'none';
        }
    }

    const cardOk = document.getElementById('cardConfirmacaoSku');
    cardOk.style.display = 'block';

    const input = document.getElementById('inputCodigoSku');
    input.classList.add('sucesso-encontrado');
    input.value = p.codigo;
    document.getElementById('btnLimparSku').style.display = 'flex';
}

function desmarcarProduto() {
    produtoAtivo = null;
    const imgFoto = document.getElementById('imgFotoProduto');
    if (imgFoto) {
        imgFoto.src = '';
        imgFoto.style.display = 'none';
    }
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

        const fotoThumb = p.foto_url 
            ? `<img src="${escapeHtml(p.foto_url)}" style="width: 50px; height: 50px; object-fit: contain; border-radius: 8px; border: 1px solid #CBD5E1; margin-right: 10px; background: #FFF;">`
            : '';

        btn.innerHTML = `
            <div style="display: flex; align-items: center;">
                ${fotoThumb}
                <div>
                    <span class="sku-badge">SKU ${escapeHtml(p.codigo)}</span>
                    <div style="font-weight: 800; font-size: 17px; color: var(--on-surface); line-height: 1.2;">
                        ${escapeHtml(p.nome)}
                    </div>
                    <div style="font-size: 14px; color: var(--on-surface-muted);">
                        ${escapeHtml(p.embalagem || '')}
                    </div>
                </div>
            </div>
            <div style="color: #059669; font-weight: 900; font-size: 24px;">➔</div>
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
        const fotoThumb = item.foto_url 
            ? `<img src="${escapeHtml(item.foto_url)}" style="width: 58px; height: 58px; object-fit: contain; border-radius: 12px; border: 2px solid #CBD5E1; background: #FFF; padding: 2px; flex-shrink: 0;">`
            : `<div style="width: 58px; height: 58px; border-radius: 12px; background: #E2E8F0; display: flex; align-items: center; justify-content: center; font-size: 26px; flex-shrink: 0;">📦</div>`;

        html += `
            <div style="display: flex; align-items: center; justify-content: space-between; background: #F8FAFC; border: 3px solid #CBD5E1; border-radius: 20px; padding: 12px 16px; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 12px; flex: 1; min-width: 0;">
                    ${fotoThumb}
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                            <span style="background: #0F172A; color: #FFFFFF; font-size: 15px; font-weight: 900; padding: 2px 8px; border-radius: 6px;">SKU ${escapeHtml(item.codigo)}</span>
                            <span style="font-size: 13px; color: #64748B; font-weight: 700;">${escapeHtml(item.embalagem)}</span>
                        </div>
                        <div style="font-size: 20px; font-weight: 900; color: #0F172A; line-height: 1.2; word-break: break-word;">
                            ${escapeHtml(item.nome)}
                        </div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; flex-shrink: 0;">
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
