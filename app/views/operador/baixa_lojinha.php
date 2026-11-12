<?php
declare(strict_types=1);

/**
 * Projeto Repack - Urna Eletrônica (Baixa de Itens da Lojinha)
 * Permite ao operador dar baixa imediata de perdas, quebras ou produtos vencidos na lojinha.
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md
 */

require_once __DIR__ . '/../../models/EstoqueLojinha.php';

// Obtém itens que atualmente possuem saldo físico em estoque na lojinha
$itensEstoque = EstoqueLojinha::listarVisaoGeral();

// Mapeia array seguro para JS em memória
$produtosJson = array_map(function($p) {
    return [
        'id' => (int)$p['produto_id'],
        'codigo' => (string)$p['codigo'],
        'nome' => (string)$p['nome'],
        'embalagem' => (string)($p['pacote_padrao'] ?? ''),
        'saldo_total' => (int)($p['quantidade_total'] ?? 0),
        'saldo_disponivel' => (int)($p['saldo_disponivel'] ?? 0)
    ];
}, $itensEstoque);

$pageTitle = "Baixa na Lojinha";
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="text-align: center; margin-bottom: 20px;">
    <h1 class="headline-lg" style="margin: 0; font-size: 46px; font-weight: 900; color: #DC2626; letter-spacing: 0.5px;">
        BAIXA NA LOJINHA
    </h1>
</div>

<!-- ============================================================= -->
<!-- ETAPA 1: SELEÇÃO DO PRODUTO, QUANTIDADE E MOTIVO               -->
<!-- ============================================================= -->
<div id="etapaSelecaoBaixa" style="display: block;">

    <!-- Entrada de Código SKU / Busca de Produto -->
    <div class="sku-entry-container">
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

        <!-- Card de Produto Selecionado -->
        <div class="card-confirmacao-sku" id="cardConfirmacaoSku" style="display: none;">
            <div class="badge-status-ok" style="background: #FEF2F2; color: #DC2626; border-color: #EF4444;">
                <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
                <span>PRODUTO IDENTIFICADO NO ESTOQUE</span>
            </div>
            <div class="nome-produto-destaque" id="displayNomeProduto"></div>
            <div class="detalhes-produto">
                <span class="badge-sku" id="displaySkuBadge"></span>
                <span class="badge-embalagem" id="displaySaldoBadge" style="background: #FEE2E2; color: #991B1B;"></span>
            </div>
        </div>

        <!-- Alerta de Produto Não Encontrado ou Sem Saldo -->
        <div class="card-nao-encontrado" id="cardNaoEncontrado" style="display: none;">
            ⚠️ Nenhum item com saldo na lojinha encontrado para "<strong id="codigoNaoEncontrado"></strong>".
        </div>

        <!-- Lista de Sugestões / Itens em Estoque -->
        <div id="wrapperSugestoes" class="wrapper-sugestoes-busca" style="display: none; margin-top: 10px;">
            <div id="listaSugestoes" style="max-height: 240px; overflow-y: auto;"></div>
        </div>
    </div>

    <!-- Seletor Numérico Gigante de Quantidade -->
    <div class="urna-card" style="text-align: center; width: 100%; margin: 20px 0; padding: 26px 20px; border-radius: 32px; border: 7px solid #0F172A; background: #FFFFFF;">
        <label style="font-weight: 900; display: block; margin-bottom: 18px; font-size: 32px; color: #0F172A; text-transform: uppercase;">
            QUANTIDADE PARA BAIXA:
        </label>
        <div class="seletor-numerico-wrapper" style="justify-content: center; gap: 24px; margin: 0;">
            <button type="button" class="btn-counter" onclick="alterarQuantidade(-1)" aria-label="Diminuir quantidade">−</button>
            <input type="number" 
                   id="displayQtd" 
                   class="counter-display" 
                   inputmode="numeric" 
                   pattern="[0-9]*" 
                   min="1" 
                   autocomplete="off" 
                   value="1" 
                   onfocus="this.select()" 
                   oninput="atualizarQuantidadeDigitada(this.value)" 
                   aria-label="Quantidade para baixa">
            <button type="button" class="btn-counter" onclick="alterarQuantidade(1)" aria-label="Aumentar quantidade">+</button>
        </div>
    </div>

    <!-- Seleção Rápida de Motivo da Baixa -->
    <div class="urna-card" style="width: 100%; margin: 0 0 24px 0; padding: 26px 20px; border-radius: 32px; border: 7px solid #0F172A; background: #FFFFFF;">
        <label style="font-weight: 900; display: block; margin-bottom: 20px; font-size: 32px; color: #0F172A; text-transform: uppercase; text-align: center; letter-spacing: 0.5px;">
            MOTIVO DO DESCARTE:
        </label>
        <div style="display: flex; flex-direction: column; gap: 18px;">
            <button type="button" 
                    class="btn-urna btn-motivo-baixa ativo" 
                    id="btnMotivoVencido" 
                    onclick="selecionarMotivo('Produto Vencido / Passou da Validade', this)">
                <span class="motivo-emoji">⏳</span>
                <span>Passou da Validade</span>
            </button>
            <button type="button" 
                    class="btn-urna btn-motivo-baixa" 
                    id="btnMotivoQuebra" 
                    onclick="selecionarMotivo('Avaria / Quebra na Lojinha', this)">
                <span class="motivo-emoji">💥</span>
                <span>Quebra / Vazamento</span>
            </button>
        </div>
    </div>

    <!-- Botões de Ação do Rodapé -->
    <div class="dupla-confirmacao-rodape" style="width: 100%; margin: 24px 0 0 0; gap: 20px;">
        <a href="<?= APP_URL ?>/operador" class="btn-urna btn-urna-warning" style="min-height: 135px; font-size: 42px; font-weight: 900; border-radius: 36px;">
            CANCELAR
        </a>
        <button type="button" class="btn-urna btn-urna-primary" id="btnAvancar" onclick="avancarConferencia()" style="min-height: 135px; font-size: 42px; font-weight: 900; border-radius: 36px;">
            AVANÇAR ➔
        </button>
    </div>
</div>

<!-- ============================================================= -->
<!-- ETAPA 2: DUPLA CONFERÊNCIA E CONFIRMAÇÃO DA BAIXA              -->
<!-- ============================================================= -->
<div id="etapaConferenciaBaixa" style="display: none;">
    <div class="urna-card" style="width: 100%; text-align: center; padding: 36px 24px; border-radius: 36px; border: 8px solid #DC2626; box-shadow: 0 14px 34px rgba(220, 38, 38, 0.25); background: #FFFFFF;">
        <div style="font-size: 24px; font-weight: 900; color: #DC2626; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">
            CONFIRMAR BAIXA DEFINITIVA
        </div>
        <div class="headline-lg" id="resumoNomeProduto" style="color: #0F172A; font-size: 46px; font-weight: 900; line-height: 1.2;"></div>
        <div style="font-size: 26px; font-weight: 800; color: #64748B; margin-top: 8px;" id="resumoSku"></div>

        <div style="border-top: 4px dashed #CBD5E1; margin: 24px 0; padding-top: 20px;">
            <div style="font-size: 28px; font-weight: 900; color: #0F172A;">
                QUANTIDADE A DESCARTAR:
            </div>
            <div id="resumoQuantidade" style="font-size: 72px; font-weight: 900; color: #DC2626; margin: 4px 0;"></div>
            
            <div style="font-size: 24px; font-weight: 800; color: #475569; background: #F1F5F9; border-radius: 16px; padding: 12px; margin-top: 12px;">
                Motivo: <strong id="resumoMotivo" style="color: #0F172A;"></strong>
            </div>
        </div>
    </div>

    <!-- Dupla Confirmação: Corrigir / Confirmar Baixa -->
    <div class="dupla-confirmacao-rodape" style="width: 100%; margin: 24px 0 0 0; gap: 18px;">
        <button type="button" class="btn-urna btn-urna-warning" onclick="voltarParaSelecao()" style="min-height: 130px; font-size: 40px; font-weight: 900; border-radius: 34px;">
            CORRIGIR
        </button>
        <button type="button" class="btn-urna btn-urna-primary" id="btnConfirmarBaixa" onclick="confirmarBaixaDefinitiva()" style="min-height: 130px; font-size: 40px; font-weight: 900; border-radius: 34px; background: #DC2626; border-color: #991B1B;">
            CONFIRMAR BAIXA
        </button>
    </div>
</div>

<style>
.btn-motivo-baixa {
    min-height: 125px;
    font-size: 38px;
    font-weight: 900;
    border-radius: 28px;
    background: #F1F5F9;
    color: #0F172A;
    border: 5px solid #94A3B8;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 18px;
    padding: 18px 24px;
    transition: all 0.15s ease;
    touch-action: manipulation;
}
.btn-motivo-baixa .motivo-emoji {
    font-size: 50px;
    line-height: 1;
    flex-shrink: 0;
}
.btn-motivo-baixa:hover {
    background: #E2E8F0;
    border-color: #64748B;
}
.btn-motivo-baixa.ativo {
    background: #DC2626 !important;
    color: #FFFFFF !important;
    border: 6px solid #991B1B !important;
    box-shadow: 0 12px 30px rgba(220, 38, 38, 0.45) !important;
    transform: scale(1.01);
}
@media (max-width: 480px) {
    .btn-motivo-baixa {
        min-height: 118px;
        font-size: 34px;
        padding: 16px 18px;
        gap: 14px;
    }
    .btn-motivo-baixa .motivo-emoji {
        font-size: 44px;
    }
}
</style>

<script>
const PRODUTOS_ESTOQUE = <?= json_encode($produtosJson, JSON_UNESCAPED_UNICODE) ?>;
let produtoSelecionado = null;
let quantidadeBaixa = 1;
let motivoBaixa = 'Produto Vencido / Passou da Validade';

function processarEntradaSku(termo) {
    termo = termo.trim().toLowerCase();
    const btnLimpar = document.getElementById('btnLimparSku');
    const cardConf = document.getElementById('cardConfirmacaoSku');
    const cardNao = document.getElementById('cardNaoEncontrado');
    const wrapperSugestoes = document.getElementById('wrapperSugestoes');
    const listaSugestoes = document.getElementById('listaSugestoes');

    btnLimpar.style.display = termo !== '' ? 'flex' : 'none';

    if (termo === '') {
        produtoSelecionado = null;
        cardConf.style.display = 'none';
        cardNao.style.display = 'none';
        wrapperSugestoes.style.display = 'none';
        return;
    }

    // Busca exata por código SKU
    const matchExato = PRODUTOS_ESTOQUE.find(p => p.codigo.toLowerCase() === termo);
    if (matchExato) {
        selecionarProduto(matchExato);
        cardNao.style.display = 'none';
        wrapperSugestoes.style.display = 'none';
        return;
    }

    // Busca parcial por nome ou código
    const filtrados = PRODUTOS_ESTOQUE.filter(p => 
        p.codigo.toLowerCase().includes(termo) || p.nome.toLowerCase().includes(termo)
    );

    if (filtrados.length === 1 && filtrados[0].codigo.toLowerCase() === termo) {
        selecionarProduto(filtrados[0]);
        cardNao.style.display = 'none';
        wrapperSugestoes.style.display = 'none';
    } else if (filtrados.length > 0) {
        produtoSelecionado = null;
        cardConf.style.display = 'none';
        cardNao.style.display = 'none';
        wrapperSugestoes.style.display = 'block';

        listaSugestoes.innerHTML = '';
        filtrados.slice(0, 10).forEach(p => {
            const item = document.createElement('div');
            item.className = 'item-sugestao-sku';
            item.innerHTML = `
                <div>
                    <strong style="font-size: 20px; color: #0F172A; display: block;">${p.nome}</strong>
                    <span style="font-size: 15px; color: #64748B;">SKU: <strong>${p.codigo}</strong> | Saldo: <strong style="color: #059669;">${p.saldo_total} un.</strong></span>
                </div>
                <span style="font-size: 24px;">➔</span>
            `;
            item.onclick = () => {
                selecionarProduto(p);
                wrapperSugestoes.style.display = 'none';
            };
            listaSugestoes.appendChild(item);
        });
    } else {
        produtoSelecionado = null;
        cardConf.style.display = 'none';
        document.getElementById('codigoNaoEncontrado').textContent = termo;
        cardNao.style.display = 'block';
        wrapperSugestoes.style.display = 'none';
    }
}

function selecionarProduto(prod) {
    produtoSelecionado = prod;
    document.getElementById('inputCodigoSku').value = prod.codigo;
    document.getElementById('displayNomeProduto').textContent = prod.nome;
    document.getElementById('displaySkuBadge').textContent = 'SKU ' + prod.codigo;
    document.getElementById('displaySaldoBadge').textContent = 'Saldo Total: ' + prod.saldo_total + ' un.';
    document.getElementById('cardConfirmacaoSku').style.display = 'block';
    document.getElementById('cardNaoEncontrado').style.display = 'none';
    document.getElementById('wrapperSugestoes').style.display = 'none';
    document.getElementById('btnLimparSku').style.display = 'flex';
}

function limparSku() {
    produtoSelecionado = null;
    document.getElementById('inputCodigoSku').value = '';
    document.getElementById('cardConfirmacaoSku').style.display = 'none';
    document.getElementById('cardNaoEncontrado').style.display = 'none';
    document.getElementById('wrapperSugestoes').style.display = 'none';
    document.getElementById('btnLimparSku').style.display = 'none';
    document.getElementById('inputCodigoSku').focus();
}

function alterarQuantidade(delta) {
    const input = document.getElementById('displayQtd');
    let val = parseInt(input.value || '1', 10) + delta;
    if (val < 1) val = 1;
    if (produtoSelecionado && val > produtoSelecionado.saldo_total) {
        val = produtoSelecionado.saldo_total;
        alert('A quantidade não pode exceder o saldo total em estoque (' + produtoSelecionado.saldo_total + ' un.)');
    }
    input.value = val;
    quantidadeBaixa = val;
}

function atualizarQuantidadeDigitada(val) {
    let num = parseInt(val || '1', 10);
    if (num < 1) num = 1;
    if (produtoSelecionado && num > produtoSelecionado.saldo_total) {
        num = produtoSelecionado.saldo_total;
        alert('Quantidade limitada ao saldo total em estoque (' + produtoSelecionado.saldo_total + ' un.)');
    }
    quantidadeBaixa = num;
}

function selecionarMotivo(motivo, btn) {
    motivoBaixa = motivo;
    document.querySelectorAll('.btn-motivo-baixa').forEach(b => b.classList.remove('ativo'));
    btn.classList.add('ativo');
}

function avancarConferencia() {
    if (!produtoSelecionado) {
        alert('Por favor, selecione um produto com saldo na lojinha antes de avançar.');
        document.getElementById('inputCodigoSku').focus();
        return;
    }

    const inputQtd = document.getElementById('displayQtd');
    quantidadeBaixa = Math.max(1, parseInt(inputQtd.value || '1', 10));

    if (quantidadeBaixa > produtoSelecionado.saldo_total) {
        alert('A quantidade (' + quantidadeBaixa + ') excede o saldo em estoque (' + produtoSelecionado.saldo_total + ').');
        return;
    }

    document.getElementById('resumoNomeProduto').textContent = produtoSelecionado.nome;
    document.getElementById('resumoSku').textContent = 'SKU ' + produtoSelecionado.codigo;
    document.getElementById('resumoQuantidade').textContent = quantidadeBaixa + ' un.';
    document.getElementById('resumoMotivo').textContent = motivoBaixa;

    document.getElementById('etapaSelecaoBaixa').style.display = 'none';
    document.getElementById('etapaConferenciaBaixa').style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function voltarParaSelecao() {
    document.getElementById('etapaConferenciaBaixa').style.display = 'none';
    document.getElementById('etapaSelecaoBaixa').style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function confirmarBaixaDefinitiva() {
    const btn = document.getElementById('btnConfirmarBaixa');
    btn.disabled = true;
    btn.innerText = 'GRAVANDO BAIXA...';

    const operadorId = <?= (int)($_SESSION['operador_id'] ?? 1) ?>;

    const payload = {
        produto_id: produtoSelecionado.id,
        quantidade_baixa: quantidadeBaixa,
        motivo: motivoBaixa,
        usuario_id: operadorId
    };

    try {
        const response = await fetch('<?= APP_URL ?>/api/estoque/baixa-operador', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (response.ok && data.status === 'success') {
            exibirSucessoFeedback('BAIXA CONCLUÍDA!', 'Estoque da lojinha abatido.', () => {
                window.location.href = '<?= APP_URL ?>/operador';
            });
        } else {
            throw new Error(data.message || 'Falha ao registrar baixa.');
        }
    } catch (err) {
        alert('Erro ao registrar baixa: ' + err.message);
        btn.disabled = false;
        btn.innerText = 'CONFIRMAR BAIXA';
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
