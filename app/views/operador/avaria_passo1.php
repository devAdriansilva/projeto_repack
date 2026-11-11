<?php
declare(strict_types=1);

/**
 * Projeto Repack - Urna Eletrônica (Avaria - Passo 1: Produto por Código SKU e Quantidade)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md (Seção 12.1)
 */

require_once __DIR__ . '/../../models/Produto.php';

$produtos = Produto::listarAtivos();

// Produto previamente selecionado (caso esteja voltando do Passo 2)
$produtoPreSelecionadoId = (int)($_SESSION['avaria_temp']['produto_id'] ?? 0);
$quantidadeInicial = (int)($_SESSION['avaria_temp']['quantidade'] ?? 1);

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

$pageTitle = "Registrar B.O. - Digitar Código";
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="text-align: center; margin-bottom: 18px;">
    <span class="badge" style="background-color: var(--secondary); color: #FFF; font-size: 15px; font-weight: 800; padding: 6px 18px; border-radius: 9999px;">
        PASSO 1 DE 3
    </span>
    <h1 class="headline-lg" style="margin-top: 10px; margin-bottom: 4px; font-size: 24px;">REGISTRAR B.O.</h1>
    <p class="body-md" style="color: var(--on-surface-muted); margin-bottom: 0;">Digite o código do produto avariado e ajuste as unidades</p>
</div>

<form action="<?= APP_URL ?>/operador/avaria/passo2" method="POST" id="formAvariaPasso1" onsubmit="return validarEnvio(event)">
    <input type="hidden" name="produto_id" id="inputProdutoId" value="<?= $produtoPreSelecionado['id'] ?? '' ?>">
    <input type="hidden" name="quantidade" id="inputQuantidade" value="<?= $quantidadeInicial ?>">

    <div class="sku-entry-container">
        
        <!-- Campo de Código Gigante com Teclado Numérico Nativo -->
        <label for="inputCodigoSku" style="display: block; font-size: 16px; font-weight: 800; color: var(--on-surface); text-transform: uppercase; margin-bottom: 8px; text-align: center;">
            Código do Produto (SKU):
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
        <div style="font-size: 13px; font-weight: 600; color: var(--on-surface-muted); text-align: center; margin-top: 6px;">
            Digite o código impresso na caixa/lata ou pesquise pelo nome
        </div>

        <!-- Card de Confirmação Imediata (Verde) -->
        <div class="card-confirmacao-sku" id="cardConfirmacaoSku" style="<?= $produtoPreSelecionado ? 'display: block;' : 'display: none;' ?>">
            <div class="badge-status-ok">
                <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
                <span>Produto Localizado</span>
            </div>
            <div class="nome-produto-destaque" id="displayNomeProduto">
                <?= sanitize_html($produtoPreSelecionado['nome'] ?? '') ?>
            </div>
            <div class="detalhes-produto">
                <span class="sku-badge" id="displaySkuBadge">SKU <?= sanitize_html($produtoPreSelecionado['codigo'] ?? '') ?></span>
                <span class="badge-embalagem" id="displayEmbalagem">
                    <?= sanitize_html($produtoPreSelecionado['pacote_padrao'] ?? '') ?>
                </span>
            </div>
        </div>

        <!-- Alerta de Código Não Encontrado -->
        <div class="card-nao-encontrado" id="cardNaoEncontrado" style="display: none;">
            ⚠️ Nenhum produto cadastrado com o código <strong id="codigoNaoEncontrado"></strong>.<br>
            <span style="font-size: 14px; font-weight: 500;">Confira os dígitos na etiqueta da embalagem.</span>
        </div>

        <!-- Lista Rápida de Sugestões (Quando o operador digita texto como "Skol" ou "Brahma") -->
        <div id="wrapperSugestoes" style="display: none;">
            <div style="font-size: 14px; font-weight: 800; color: var(--on-surface-muted); text-transform: uppercase; margin: 12px 0 6px 0;">
                Selecione o produto correspondente:
            </div>
            <div class="lista-sugestoes-sku" id="listaSugestoes"></div>
        </div>

    </div>

    <!-- Seletor Numérico Gigante -->
    <div class="urna-card" style="text-align: center; max-width: 580px; margin: 0 auto 20px auto; padding: 18px;">
        <label class="body-lg" style="font-weight: 800; display: block; margin-bottom: 12px; font-size: 18px;">
            QUANTIDADE AVARIADA:
        </label>
        <div class="seletor-numerico-wrapper" style="justify-content: center;">
            <button type="button" class="btn-counter" onclick="alterarQuantidade(-1)" aria-label="Diminuir quantidade">−</button>
            <div class="counter-display" id="displayQtd"><?= $quantidadeInicial ?></div>
            <button type="button" class="btn-counter" onclick="alterarQuantidade(1)" aria-label="Aumentar quantidade">+</button>
        </div>
    </div>

    <!-- Alerta Flutuante se tentar avançar sem produto -->
    <div id="alertaSemProduto" style="display: none; max-width: 580px; margin: 0 auto 14px auto; background: #FEF2F2; color: #991B1B; border: 2px solid #EF4444; border-radius: 12px; padding: 12px; font-weight: 800; text-align: center; font-size: 15px;">
        ⚠️ Digite um código válido antes de avançar!
    </div>

    <!-- Botões de Ação do Rodapé (Urna Eletrônica 80px) -->
    <div class="dupla-confirmacao-rodape" style="max-width: 580px; margin: 0 auto;">
        <a href="<?= APP_URL ?>/operador" class="btn-urna btn-urna-warning" style="min-height: 72px;">
            CANCELAR
        </a>
        <button type="submit" class="btn-urna btn-urna-primary" id="btnAvancar" style="min-height: 72px;">
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

let quantidadeAtual = <?= $quantidadeInicial ?>;

function processarEntradaSku(valor) {
    const termo = valor.trim().toLowerCase();
    const btnLimpar = document.getElementById('btnLimparSku');
    const input = document.getElementById('inputCodigoSku');
    const cardOk = document.getElementById('cardConfirmacaoSku');
    const cardErro = document.getElementById('cardNaoEncontrado');
    const wrapperSugestoes = document.getElementById('wrapperSugestoes');
    const listaSugestoes = document.getElementById('listaSugestoes');
    const alerta = document.getElementById('alertaSemProduto');

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

    // Se é puramente numérico e tem 3+ dígitos, tenta prefixo de SKU
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
            // Digitou 4+ números e não bateu com nenhum SKU
            desmarcarProduto();
            cardOk.style.display = 'none';
            wrapperSugestoes.style.display = 'none';
            document.getElementById('codigoNaoEncontrado').innerText = valor;
            cardErro.style.display = 'block';
            input.classList.remove('sucesso-encontrado');
            return;
        }
    }

    // 2. Busca por texto (Nome do produto)
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

    // Estado neutro digitando
    desmarcarProduto();
    cardOk.style.display = 'none';
    cardErro.style.display = 'none';
    wrapperSugestoes.style.display = 'none';
    input.classList.remove('sucesso-encontrado');
}

function selecionarProdutoConfirmado(p) {
    produtoAtivo = p;
    document.getElementById('inputProdutoId').value = p.id;
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
    document.getElementById('inputProdutoId').value = '';
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
            <div style="color: #2563EB; font-weight: 900; font-size: 20px;">➔</div>
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
    quantidadeAtual = Math.max(1, quantidadeAtual + delta);
    document.getElementById('displayQtd').innerText = quantidadeAtual;
    document.getElementById('inputQuantidade').value = quantidadeAtual;
}

function validarEnvio(e) {
    const prodId = document.getElementById('inputProdutoId').value;
    if (!prodId || !produtoAtivo) {
        e.preventDefault();
        const alerta = document.getElementById('alertaSemProduto');
        if (alerta) alerta.style.display = 'block';
        const input = document.getElementById('inputCodigoSku');
        input.focus();
        if (navigator.vibrate) navigator.vibrate(150);
        return false;
    }
    return true;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
