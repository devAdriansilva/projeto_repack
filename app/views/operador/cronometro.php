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

<div style="text-align: center; margin-bottom: 16px;">
    <h1 style="margin: 0; font-size: 28px; line-height: 1.25; font-weight: 900; color: #0F172A; letter-spacing: 0.5px;">
        REGISTRAR PRODUÇÃO
    </h1>
    <span style="display: inline-block; font-size: 15px; font-weight: 800; color: #059669; background: #ECFDF5; border: 2px solid #10B981; padding: 4px 14px; border-radius: 9999px; margin-top: 6px;">
        📦 LANÇAMENTO POR LOTE / TURNO
    </span>
</div>

<!-- ============================================================= -->
<!-- BLOCO 1: PERÍODO DO LOTE / TURNO TRABALHADO                   -->
<!-- ============================================================= -->
<div class="urna-card" style="width: 100%; padding: 18px 14px; border-radius: 20px; border: 3.5px solid #0F172A; background: #FFFFFF; margin-bottom: 18px; box-shadow: 0 4px 14px rgba(0,0,0,0.06);">
    
    <!-- Seção 1A: DATA DA PRODUÇÃO (Hoje, Ontem ou Outra) -->
    <div style="font-size: 20px; font-weight: 900; color: #0F172A; text-transform: uppercase; margin-bottom: 10px; text-align: center; letter-spacing: 0.5px;">
        1. DATA DA REEMBALAGEM:
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 14px;">
        <button type="button" id="btnDataHoje" class="btn-urna" onclick="selecionarDataProducao('hoje')" style="min-height: 70px; padding: 6px 4px; border-radius: 14px; background: #ECFDF5; color: #065F46; border: 4px solid #10B981; font-size: 19px; font-weight: 900; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; box-shadow: 0 4px 10px rgba(16,185,129,0.2);">
            <span>📅 HOJE</span>
            <span id="labelDataHoje" style="font-size: 14px; font-weight: 800; color: #047857;">--/--</span>
        </button>

        <button type="button" id="btnDataOntem" class="btn-urna" onclick="selecionarDataProducao('ontem')" style="min-height: 70px; padding: 6px 4px; border-radius: 14px; background: #F1F5F9; color: #334155; border: 2.5px solid #94A3B8; font-size: 19px; font-weight: 900; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; opacity: 0.65;">
            <span>📅 ONTEM</span>
            <span id="labelDataOntem" style="font-size: 14px; font-weight: 800; color: #64748B;">--/--</span>
        </button>

        <button type="button" id="btnDataOutra" class="btn-urna" onclick="selecionarDataProducao('outra')" style="min-height: 70px; padding: 6px 4px; border-radius: 14px; background: #F1F5F9; color: #334155; border: 2.5px solid #94A3B8; font-size: 18px; font-weight: 900; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; opacity: 0.65;">
            <span>🗓️ OUTRA</span>
            <span style="font-size: 13px; font-weight: 800; color: #64748B;">DATA</span>
        </button>
    </div>

    <!-- Campo Seletor de Data Customizada (Escondido por padrão) -->
    <div id="wrapperDataPersonalizada" style="display: none; margin-bottom: 14px; background: #F8FAFC; padding: 12px; border-radius: 16px; border: 2.5px dashed #94A3B8; text-align: center;">
        <label for="inputDataProducao" style="font-size: 18px; font-weight: 900; color: #1E293B; display: block; margin-bottom: 6px;">
            SELECIONE A DATA ESPECÍFICA:
        </label>
        <input type="date" id="inputDataProducao" style="min-height: 52px; font-size: 22px; font-weight: 900; text-align: center; border-radius: 14px; border: 3px solid #0F172A; width: 100%; max-width: 280px; margin: 0 auto; display: block;" onchange="aplicarDataPersonalizada(this.value)">
    </div>

    <!-- Seção 1B: TURNO DO LOTE -->
    <div style="font-size: 20px; font-weight: 900; color: #0F172A; text-transform: uppercase; margin: 14px 0 10px 0; text-align: center; letter-spacing: 0.5px; border-top: 2px solid #E2E8F0; padding-top: 14px;">
        2. TURNO DO LOTE:
    </div>

    <!-- 2 Botões Grandes e Proporcionais de Turno (Manhã e Tarde) -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
        <button type="button" id="btnTurnoManha" class="btn-urna" onclick="selecionarTurno('manha')" style="min-height: 80px; padding: 10px 8px; border-radius: 16px; background: #FEF3C7; color: #92400E; border: 4px solid #F59E0B; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; box-shadow: 0 4px 12px rgba(245,158,11,0.2);">
            <span style="font-size: 24px; font-weight: 900; line-height: 1;">☀️ MANHÃ</span>
            <span style="font-size: 17px; font-weight: 800; color: #B45309;">07:00 às 12:00</span>
        </button>

        <button type="button" id="btnTurnoTarde" class="btn-urna" onclick="selecionarTurno('tarde')" style="min-height: 80px; padding: 10px 8px; border-radius: 16px; background: #E0E7FF; color: #3730A3; border: 4px solid #6366F1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; opacity: 0.6;">
            <span style="font-size: 24px; font-weight: 900; line-height: 1;">⛅ TARDE</span>
            <span style="font-size: 17px; font-weight: 800; color: #4338CA;">14:00 às 17:00</span>
        </button>
    </div>

    <!-- Botão de Turno Personalizado / Outro Horário -->
    <div style="text-align: center; margin-bottom: 12px;">
        <button type="button" id="btnTurnoOutro" onclick="alternarHorarioPersonalizado()" style="background: #F1F5F9; border: 2.5px solid #94A3B8; color: #1E293B; font-size: 16px; font-weight: 800; border-radius: 14px; padding: 8px 18px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
            <span>⚙️ OUTRO HORÁRIO / PERSONALIZAR</span>
        </button>
    </div>

    <!-- Campos de Ajuste Fino de Horário (Escondido por padrão, abre ao personalizar) -->
    <div id="wrapperHorariosPersonalizados" style="display: none; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px; background: #F8FAFC; padding: 14px; border-radius: 16px; border: 2px dashed #94A3B8;">
        <div>
            <label for="inputHoraInicio" style="display: block; font-size: 18px; font-weight: 900; color: #1E293B; margin-bottom: 6px; text-align: center;">
                HORA INÍCIO:
            </label>
            <input type="time" id="inputHoraInicio" style="min-height: 52px; font-size: 24px; font-weight: 900; text-align: center; border-radius: 14px; border: 3px solid #0F172A; width: 100%;" value="07:00" onchange="atualizarDuracaoLote()">
        </div>
        <div>
            <label for="inputHoraFim" style="display: block; font-size: 18px; font-weight: 900; color: #1E293B; margin-bottom: 6px; text-align: center;">
                HORA TÉRMINO:
            </label>
            <input type="time" id="inputHoraFim" style="min-height: 52px; font-size: 24px; font-weight: 900; text-align: center; border-radius: 14px; border: 3px solid #0F172A; width: 100%;" value="12:00" onchange="atualizarDuracaoLote()">
        </div>
    </div>

    <!-- Faixa Destaque Consolidada com Data e Duração -->
    <div style="text-align: center; margin-top: 12px; background: #F0FDF4; border: 2.5px solid #86EFAC; border-radius: 14px; padding: 10px 12px;">
        <div style="font-size: 16px; font-weight: 800; color: #166534; margin-bottom: 2px;">
            DATA DA PRODUÇÃO: <strong id="displayDataProducaoResumo" style="color: #0F172A; font-size: 19px;">--/--/----</strong>
        </div>
        <span style="font-size: 17px; font-weight: 900; color: #14532D;">
            DURAÇÃO DO BLOCO: 
            <span id="displayDuracaoTotal" style="color: #059669; font-size: 24px; font-weight: 900;">5h 00min</span>
        </span>
    </div>
</div>

<!-- ============================================================= -->
<!-- BLOCO 2: INCLUSÃO DE PRODUTO NO LOTE (SKU + QUANTIDADE)       -->
<!-- ============================================================= -->
<div class="urna-card" style="width: 100%; padding: 18px 14px; border-radius: 20px; border: 3.5px solid #0F172A; background: #FFFFFF; margin-bottom: 18px; box-shadow: 0 4px 14px rgba(0,0,0,0.06);">
    <div style="font-size: 20px; font-weight: 900; color: #0F172A; text-transform: uppercase; margin-bottom: 12px; text-align: center; letter-spacing: 0.5px;">
        3. INCLUIR ITEM REEMBALADO:
    </div>

    <div class="sku-entry-container" style="margin-bottom: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
            <label for="inputCodigoSku" id="labelModoBusca" style="font-size: 20px; font-weight: 900; color: #0F172A; text-transform: uppercase; margin: 0; letter-spacing: 0.5px;">
                CÓDIGO (SKU):
            </label>
            <!-- Seletor Rápido de Teclado (Código 123 vs Nome ABC) -->
            <div style="display: inline-flex; background: #F1F5F9; border: 2px solid #CBD5E1; border-radius: 14px; padding: 3px; gap: 3px;">
                <button type="button" id="btnModoCodigo" onclick="alternarModoBusca('codigo')" style="min-height: 38px; padding: 4px 12px; border-radius: 10px; font-size: 16px; font-weight: 900; border: none; cursor: pointer; background: #0F172A; color: #FFFFFF; transition: all 0.15s ease; touch-action: manipulation;" title="Teclado Numérico (Código SKU)">
                    🔢 123
                </button>
                <button type="button" id="btnModoNome" onclick="alternarModoBusca('nome')" style="min-height: 38px; padding: 4px 12px; border-radius: 10px; font-size: 16px; font-weight: 900; border: none; cursor: pointer; background: transparent; color: #64748B; transition: all 0.15s ease; touch-action: manipulation;" title="Teclado Alfanumérico (Busca por Nome)">
                    🔤 ABC
                </button>
            </div>
        </div>
        
        <div class="sku-input-wrapper">
            <input type="text" 
                   id="inputCodigoSku" 
                   class="input-sku-gigante" 
                   inputmode="numeric" 
                   pattern="[0-9]*" 
                   placeholder="9068" 
                   value=""
                   autocomplete="off"
                   style="height: 72px; min-height: 72px; font-size: 38px; font-weight: 900; border-radius: 16px; border: 3.5px solid #0F172A; padding: 6px 58px 6px 14px; letter-spacing: 3px;"
                   oninput="processarEntradaSku(this.value)">
            <button type="button" 
                    id="btnLimparSku" 
                    class="btn-limpar-sku" 
                    style="display: none; width: 42px; height: 42px; font-size: 20px; right: 10px;" 
                    onclick="limparSku()" 
                    aria-label="Limpar código">✕</button>
        </div>
        <div style="font-size: 13px; font-weight: 700; color: #64748B; text-align: center; margin-top: 6px;">
            💡 Dica: Toque em <strong>🔤 ABC</strong> ou <strong>segure o campo</strong> para buscar pelo nome.
        </div>

        <!-- Card de Confirmação Imediata do SKU (Com Foto Monumental e Alto Contraste) -->
        <div class="card-confirmacao-sku" id="cardConfirmacaoSku" style="display: none; margin-top: 14px; border: 3px solid #10B981; border-radius: 16px; padding: 12px; text-align: center; background: #F0FDF4;">
            <div class="badge-status-ok" style="font-size: 18px; font-weight: 900; color: #065F46; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
                <span>PRODUTO IDENTIFICADO</span>
            </div>

            <!-- Foto Destaque do Produto (Harmonizada) -->
            <div id="wrapperFotoProduto" style="text-align: center; margin: 8px 0 10px 0;">
                <img id="imgFotoProduto" 
                     src="" 
                     alt="Foto do Produto" 
                     style="width: 100%; max-width: 220px; height: 160px; object-fit: contain; border-radius: 14px; background: #FFFFFF; border: 2.5px solid #059669; box-shadow: 0 4px 12px rgba(5,150,105,0.18); padding: 4px; display: none;">
            </div>

            <div class="nome-produto-destaque" id="displayNomeProduto" style="font-size: 20px; font-weight: 900; color: #0F172A; line-height: 1.25; word-break: break-word;">
                Nome do Produto
            </div>
            <div class="detalhes-produto" style="margin-top: 8px; display: flex; justify-content: center; gap: 8px; flex-wrap: wrap;">
                <span class="badge-sku" id="displaySkuBadge" style="font-size: 14px; font-weight: 900; background: #0F172A; color: #FFFFFF; padding: 3px 10px; border-radius: 8px;">SKU 0000</span>
                <span class="badge-embalagem" id="displayEmbalagem" style="font-size: 14px; font-weight: 800; background: #E2E8F0; color: #334155; padding: 3px 10px; border-radius: 8px;">Padrão</span>
            </div>
        </div>

        <!-- Card de Erro: SKU Não Encontrado -->
        <div id="cardNaoEncontrado" style="display: none; background: #FEF2F2; border: 3px solid #EF4444; border-radius: 16px; padding: 14px; margin-top: 12px; text-align: center;">
            <div style="font-size: 18px; font-weight: 900; color: #991B1B;">
                ⚠️ Código <span id="codigoNaoEncontrado"></span> não cadastrado!
            </div>
        </div>

        <!-- Sugestões de Busca Rápida -->
        <div id="wrapperSugestoes" style="display: none; margin-top: 12px; width: 100%;">
            <div style="font-size: 16px; font-weight: 900; color: #475569; margin-bottom: 6px;">TOQUE PARA SELECIONAR:</div>
            <div id="listaSugestoes" class="lista-sugestoes-sku"></div>
        </div>

        <!-- Seletor Horizontal de Quantidade (Rigorosamente Lado a Lado [-] [ 1 ] [+]) -->
        <div style="margin-top: 20px; text-align: center;">
            <label for="displayQtd" style="display: block; font-size: 20px; font-weight: 900; color: #0F172A; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 0.5px;">
                QUANTIDADE DE PACOTES:
            </label>

            <!-- Container Horizontal Flexível Travado em Linha Única sem Quebra -->
            <div style="display: flex; flex-direction: row !important; flex-wrap: nowrap !important; align-items: center; justify-content: center; gap: 10px; width: 100%; margin: 0 auto; max-width: 420px; box-sizing: border-box;">
                <!-- Botão Menos -->
                <button type="button" 
                        onclick="alterarQuantidade(-1)" 
                        style="flex: 0 0 auto; width: 70px; height: 70px; border-radius: 16px; font-size: 38px; font-weight: 900; display: flex; align-items: center; justify-content: center; border: 3.5px solid #0F172A; background-color: #FFFFFF; color: #0F172A; cursor: pointer; touch-action: manipulation; box-shadow: 0 4px 10px rgba(0,0,0,0.1); padding: 0;" 
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
                       style="flex: 1 1 auto; min-width: 75px; max-width: 130px; height: 70px; background-color: #FFFFFF; border: 3.5px solid #0F172A; border-radius: 16px; font-size: 38px; font-weight: 900; color: #0F172A; text-align: center; box-sizing: border-box; padding: 0 8px; outline: none; touch-action: manipulation; box-shadow: 0 4px 10px rgba(0,0,0,0.1);" 
                       aria-label="Quantidade de pacotes">

                <!-- Botão Mais -->
                <button type="button" 
                        onclick="alterarQuantidade(1)" 
                        style="flex: 0 0 auto; width: 70px; height: 70px; border-radius: 16px; font-size: 38px; font-weight: 900; display: flex; align-items: center; justify-content: center; border: 3.5px solid #0F172A; background-color: #FFFFFF; color: #0F172A; cursor: pointer; touch-action: manipulation; box-shadow: 0 4px 10px rgba(0,0,0,0.1); padding: 0;" 
                        aria-label="Aumentar">
                    +
                </button>
            </div>
        </div>

        <!-- Botão Adicionar Item ao Lote (Harmonizado) -->
        <div style="margin-top: 20px;">
            <button type="button" id="btnAdicionarAoLote" class="btn-urna btn-urna-primary" onclick="adicionarItemAoLote()" style="min-height: 65px; font-size: 20px; font-weight: 900; border-radius: 16px; width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px; background: linear-gradient(145deg, #059669 0%, #047857 100%); border: 3.5px solid #065F46; box-shadow: 0 6px 16px rgba(5,150,105,0.25);">
                <span style="font-size: 24px;">➕</span> <span>ADICIONAR AO LOTE</span>
            </button>
        </div>
    </div>
</div>

<!-- ============================================================= -->
<!-- BLOCO 3: LISTA DOS ITENS DO LOTE (COMANDA ATUAL)              -->
<!-- ============================================================= -->
<div class="urna-card" style="width: 100%; padding: 18px 14px; border-radius: 20px; border: 3.5px solid #0F172A; background: #FFFFFF; margin-bottom: 18px; box-shadow: 0 4px 14px rgba(0,0,0,0.06);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 2px solid #E2E8F0; padding-bottom: 10px;">
        <div style="font-size: 20px; font-weight: 900; color: #0F172A; text-transform: uppercase;">
            3. ITENS NO LOTE (<span id="contadorItensLote">0</span>)
        </div>
        <button type="button" onclick="limparTodoLote()" style="background: none; border: none; color: #DC2626; font-size: 16px; font-weight: 900; cursor: pointer; text-decoration: underline;">
            Limpar Todos
        </button>
    </div>

    <!-- Container Dinâmico da Lista de Produtos -->
    <div id="listaItensLote" style="display: flex; flex-direction: column; gap: 10px; max-height: 480px; overflow-y: auto; padding-right: 2px;">
        <!-- Inserido dinamicamente via JS -->
    </div>

    <!-- Mensagem Vazia Inicial -->
    <div id="msgLoteVazio" style="text-align: center; padding: 26px 14px; background: #F8FAFC; border: 2px dashed #CBD5E1; border-radius: 16px;">
        <span style="font-size: 38px;">📋</span>
        <div style="font-size: 19px; font-weight: 900; color: #475569; margin-top: 8px;">
            Nenhum produto adicionado ao lote ainda.
        </div>
        <div style="font-size: 15px; font-weight: 700; color: #94A3B8; margin-top: 4px;">
            Digite o código do produto acima e clique em "Adicionar ao Lote".
        </div>
    </div>

    <!-- Rodapé Consolidado do Lote -->
    <div id="cardConsolidadoLote" style="display: none; margin-top: 16px; padding: 14px; border-top: 2.5px solid #CBD5E1; background: #F8FAFC; border-radius: 16px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; text-align: center;">
            <div style="border-right: 2px solid #E2E8F0; padding-right: 6px;">
                <div style="font-size: 16px; font-weight: 900; color: #64748B; text-transform: uppercase;">TOTAL DO LOTE:</div>
                <div style="font-size: 36px; font-weight: 900; color: #0F172A; line-height: 1.1; margin-top: 2px;">
                    <span id="displayTotalPacotesLote">0</span> <span style="font-size: 18px; color: #64748B; font-weight: 800;">pcts</span>
                </div>
            </div>
            <div>
                <div style="font-size: 16px; font-weight: 900; color: #64748B; text-transform: uppercase;">TEMPO MÉDIO:</div>
                <div style="font-size: 24px; font-weight: 900; color: #059669; line-height: 1.15; margin-top: 6px;">
                    <span id="displayTempoMedioPorPacote">--</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================= -->
<!-- BLOCO 4: DUPLA CONFIRMAÇÃO DA URNA (RODAPÉ HARMONIZADO 80PX)   -->
<!-- ============================================================= -->
<form id="formAvancarLote" action="<?= APP_URL ?>/operador/cronometro/resumo" method="POST" onsubmit="return validarAvancoLote(event)">
    <input type="hidden" name="payload_lote" id="inputPayloadLote" value="">

    <div class="dupla-confirmacao-rodape" style="width: 100%; margin: 18px 0 0 0; gap: 14px;">
        <a href="<?= APP_URL ?>/operador?limpar_cronometro=1" onclick="cancelarRascunhoLote()" class="btn-urna btn-urna-warning" style="min-height: 80px; font-size: 24px; font-weight: 900; border-radius: 20px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 10px;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink: 0;">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            <span>CANCELAR</span>
        </a>
        <button type="submit" class="btn-urna btn-urna-primary" id="btnAvancarLote" style="min-height: 80px; font-size: 24px; font-weight: 900; border-radius: 20px; display: flex; align-items: center; justify-content: center; gap: 10px;">
            <span>CONFERIR</span>
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink: 0;">
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
let dataProducaoAtual = ''; // YYYY-MM-DD
let tipoDataAtual = 'hoje'; // 'hoje', 'ontem', 'outra'
let itensLote = [];

// 1. Inicialização e Restauração de Rascunho
window.addEventListener('DOMContentLoaded', () => {
    inicializarDatas();
    const rascunhoAtivo = restaurarRascunhoLote();
    if (!rascunhoAtivo) {
        detectarTurnoAutomatico();
    }
    atualizarDuracaoLote();
    inicializarLongPressBusca();
});

// Controle de Modo de Teclado e Busca (Código 123 vs Nome ABC)
let modoBuscaAtual = 'codigo'; // 'codigo' ou 'nome'
let timerLongPress = null;

function alternarModoBusca(novoModo, viaLongPress = false) {
    modoBuscaAtual = novoModo;
    const input = document.getElementById('inputCodigoSku');
    const label = document.getElementById('labelModoBusca');
    const btnCod = document.getElementById('btnModoCodigo');
    const btnNom = document.getElementById('btnModoNome');

    if (novoModo === 'nome') {
        input.setAttribute('inputmode', 'text');
        input.removeAttribute('pattern');
        input.placeholder = 'Ex: Skol, Guaraná...';
        label.innerText = 'BUSCA POR NOME:';
        
        btnNom.style.background = '#0F172A';
        btnNom.style.color = '#FFFFFF';
        btnCod.style.background = 'transparent';
        btnCod.style.color = '#64748B';
    } else {
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('pattern', '[0-9]*');
        input.placeholder = '9068';
        label.innerText = 'CÓDIGO (SKU):';
        
        btnCod.style.background = '#0F172A';
        btnCod.style.color = '#FFFFFF';
        btnNom.style.background = 'transparent';
        btnNom.style.color = '#64748B';
    }

    if (navigator.vibrate) {
        navigator.vibrate(60);
    }

    input.focus();
}

function inicializarLongPressBusca() {
    const input = document.getElementById('inputCodigoSku');
    if (!input) return;

    const iniciarTimer = () => {
        timerLongPress = setTimeout(() => {
            const proximoModo = (modoBuscaAtual === 'codigo') ? 'nome' : 'codigo';
            alternarModoBusca(proximoModo, true);
        }, 500);
    };

    const cancelarTimer = () => {
        if (timerLongPress) {
            clearTimeout(timerLongPress);
            timerLongPress = null;
        }
    };

    input.addEventListener('touchstart', iniciarTimer, { passive: true });
    input.addEventListener('touchend', cancelarTimer, { passive: true });
    input.addEventListener('touchcancel', cancelarTimer, { passive: true });
    input.addEventListener('touchmove', cancelarTimer, { passive: true });

    // Suporte também para mouse no computador
    input.addEventListener('mousedown', iniciarTimer);
    input.addEventListener('mouseup', cancelarTimer);
    input.addEventListener('mouseleave', cancelarTimer);
}

function normalizarTexto(txt) {
    return (txt || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
}

function formatarDataIso(d) {
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

function formatarDataBr(d) {
    const pad = (n) => String(n).padStart(2, '0');
    return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()}`;
}

function inicializarDatas() {
    const agora = new Date();
    const ontem = new Date(agora.getTime() - 24 * 3600 * 1000);

    const pad = (n) => String(n).padStart(2, '0');
    const labelHoje = `${pad(agora.getDate())}/${pad(agora.getMonth() + 1)}`;
    const labelOntem = `${pad(ontem.getDate())}/${pad(ontem.getMonth() + 1)}`;

    document.getElementById('labelDataHoje').innerText = labelHoje;
    document.getElementById('labelDataOntem').innerText = labelOntem;
    document.getElementById('inputDataProducao').value = formatarDataIso(agora);
    document.getElementById('inputDataProducao').max = formatarDataIso(agora);

    if (!dataProducaoAtual) {
        selecionarDataProducao('hoje');
    }
}

function selecionarDataProducao(tipo) {
    tipoDataAtual = tipo;
    const agora = new Date();
    const btnHoje = document.getElementById('btnDataHoje');
    const btnOntem = document.getElementById('btnDataOntem');
    const btnOutra = document.getElementById('btnDataOutra');
    const wrapper = document.getElementById('wrapperDataPersonalizada');
    const displayDataTexto = document.getElementById('displayDataProducaoResumo');

    btnHoje.style.borderWidth = '3px';
    btnHoje.style.opacity = '0.65';
    btnHoje.style.background = '#F1F5F9';
    btnHoje.style.color = '#334155';

    btnOntem.style.borderWidth = '3px';
    btnOntem.style.opacity = '0.65';
    btnOntem.style.background = '#F1F5F9';
    btnOntem.style.color = '#334155';

    btnOutra.style.borderWidth = '3px';
    btnOutra.style.opacity = '0.65';
    btnOutra.style.background = '#F1F5F9';
    btnOutra.style.color = '#334155';

    if (tipo === 'hoje') {
        dataProducaoAtual = formatarDataIso(agora);
        btnHoje.style.borderWidth = '5px';
        btnHoje.style.opacity = '1';
        btnHoje.style.background = '#ECFDF5';
        btnHoje.style.color = '#065F46';
        wrapper.style.display = 'none';
        displayDataTexto.innerText = `${formatarDataBr(agora)} (Hoje)`;
    } else if (tipo === 'ontem') {
        const dOntem = new Date(agora.getTime() - 24 * 3600 * 1000);
        dataProducaoAtual = formatarDataIso(dOntem);
        btnOntem.style.borderWidth = '5px';
        btnOntem.style.opacity = '1';
        btnOntem.style.background = '#ECFDF5';
        btnOntem.style.color = '#065F46';
        wrapper.style.display = 'none';
        displayDataTexto.innerText = `${formatarDataBr(dOntem)} (Ontem)`;
    } else {
        btnOutra.style.borderWidth = '5px';
        btnOutra.style.opacity = '1';
        btnOutra.style.background = '#ECFDF5';
        btnOutra.style.color = '#065F46';
        wrapper.style.display = 'block';
        const dVal = document.getElementById('inputDataProducao').value || formatarDataIso(agora);
        dataProducaoAtual = dVal;
        const partes = dVal.split('-');
        displayDataTexto.innerText = `${partes[2]}/${partes[1]}/${partes[0]}`;
    }

    salvarRascunhoLote();
}

function aplicarDataPersonalizada(val) {
    if (!val) return;
    dataProducaoAtual = val;
    const partes = val.split('-');
    const displayDataTexto = document.getElementById('displayDataProducaoResumo');
    displayDataTexto.innerText = `${partes[2]}/${partes[1]}/${partes[0]}`;
    salvarRascunhoLote();
}

function selecionarTurno(tipo) {
    turnoAtual = tipo;
    const btnM = document.getElementById('btnTurnoManha');
    const btnT = document.getElementById('btnTurnoTarde');
    const wrapper = document.getElementById('wrapperHorariosPersonalizados');

    if (tipo === 'manha') {
        btnM.style.opacity = '1';
        btnM.style.borderWidth = '6px';
        btnM.style.boxShadow = '0 4px 14px rgba(245,158,11,0.25)';
        btnT.style.opacity = '0.55';
        btnT.style.borderWidth = '3px';
        btnT.style.boxShadow = 'none';
        wrapper.style.display = 'none';
        document.getElementById('inputHoraInicio').value = '07:00';
        document.getElementById('inputHoraFim').value = '12:00';
    } else if (tipo === 'tarde') {
        btnM.style.opacity = '0.55';
        btnM.style.borderWidth = '3px';
        btnM.style.boxShadow = 'none';
        btnT.style.opacity = '1';
        btnT.style.borderWidth = '6px';
        btnT.style.boxShadow = '0 4px 14px rgba(99,102,241,0.25)';
        wrapper.style.display = 'none';
        document.getElementById('inputHoraInicio').value = '14:00';
        document.getElementById('inputHoraFim').value = '17:00';
    } else {
        btnM.style.opacity = '0.55';
        btnM.style.borderWidth = '3px';
        btnM.style.boxShadow = 'none';
        btnT.style.opacity = '0.55';
        btnT.style.borderWidth = '3px';
        btnT.style.boxShadow = 'none';
        wrapper.style.display = 'grid';
    }

    atualizarDuracaoLote();
    salvarRascunhoLote();
}

function detectarTurnoAutomatico() {
    const horaAtual = new Date().getHours();
    // A partir das 13:00 seleciona automaticamente o turno da TARDE (14:00 às 17:00).
    // Antes das 13:00 seleciona automaticamente o turno da MANHÃ (07:00 às 12:00).
    if (horaAtual >= 13) {
        selecionarTurno('tarde');
    } else {
        selecionarTurno('manha');
    }
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

    // 1. Busca exata por código SKU (só auto-seleciona se o código digitado for 100% idêntico)
    const matchExato = PRODUTOS.find(p => p.codigo.toLowerCase() === termo);
    if (matchExato) {
        selecionarProdutoConfirmado(matchExato, false);
        cardErro.style.display = 'none';
        wrapperSugestoes.style.display = 'none';
        return;
    }

    // 2. Busca parcial por prefixo numérico de SKU (JAMAIS auto-completa o input para não sequestrar a digitação)
    const apenasNumeros = /^\d+$/.test(termo);
    if (apenasNumeros) {
        desmarcarProduto();
        cardOk.style.display = 'none';
        input.classList.remove('sucesso-encontrado');

        const matchesSku = PRODUTOS.filter(p => p.codigo.startsWith(termo));
        if (matchesSku.length > 0 && matchesSku.length <= 6) {
            cardErro.style.display = 'none';
            exibirSugestoes(matchesSku);
            return;
        } else if (matchesSku.length === 0 && termo.length >= 4) {
            // Digitou 4+ números e não bate com nenhum SKU nem prefixo
            wrapperSugestoes.style.display = 'none';
            document.getElementById('codigoNaoEncontrado').innerText = valor;
            cardErro.style.display = 'block';
            return;
        } else {
            wrapperSugestoes.style.display = 'none';
            cardErro.style.display = 'none';
            return;
        }
    }

    // 3. Busca por texto (Nome do produto)
    if (!apenasNumeros && termo.length >= 2) {
        desmarcarProduto();
        cardOk.style.display = 'none';
        input.classList.remove('sucesso-encontrado');

        const termoNorm = normalizarTexto(termo);
        const matchesNome = PRODUTOS.filter(p => normalizarTexto(p.nome).includes(termoNorm)).slice(0, 8);
        if (matchesNome.length > 0) {
            cardErro.style.display = 'none';
            exibirSugestoes(matchesNome);
            return;
        } else {
            wrapperSugestoes.style.display = 'none';
            document.getElementById('codigoNaoEncontrado').innerText = valor;
            cardErro.style.display = 'block';
            return;
        }
    }

    desmarcarProduto();
    cardOk.style.display = 'none';
    cardErro.style.display = 'none';
    wrapperSugestoes.style.display = 'none';
    input.classList.remove('sucesso-encontrado');
}

function selecionarProdutoConfirmado(p, atualizarInput = true) {
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
    if (atualizarInput) {
        input.value = p.codigo;
    }
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
            selecionarProdutoConfirmado(p, true);
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

    const hInicio = document.getElementById('inputHoraInicio').value || '07:00';
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
    const hInicio = document.getElementById('inputHoraInicio').value || '07:00';
    const hFim = document.getElementById('inputHoraFim').value || '12:00';

    const rascunho = {
        data_producao: dataProducaoAtual,
        tipo_data: tipoDataAtual,
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
    if (!salvoStr) return false;

    try {
        const salvo = JSON.parse(salvoStr);
        if (salvo && Array.isArray(salvo.itens)) {
            itensLote = salvo.itens;
            if (salvo.data_producao) {
                dataProducaoAtual = salvo.data_producao;
            }
            if (salvo.tipo_data) {
                selecionarDataProducao(salvo.tipo_data);
            }
            if (salvo.hora_inicio) document.getElementById('inputHoraInicio').value = salvo.hora_inicio;
            if (salvo.hora_fim) document.getElementById('inputHoraFim').value = salvo.hora_fim;
            if (salvo.turno) selecionarTurno(salvo.turno);
            renderizarListaLote();
            return true;
        }
    } catch(e) {}
    return false;
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

    const hInicio = document.getElementById('inputHoraInicio').value || '07:00';
    const hFim = document.getElementById('inputHoraFim').value || '12:00';
    const duracaoSeg = calcularSegundosEntreHorarios(hInicio, hFim);

    const payload = {
        data_producao: dataProducaoAtual || formatarDataIso(new Date()),
        tipo_data: tipoDataAtual,
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
