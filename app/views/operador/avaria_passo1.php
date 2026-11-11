<?php
declare(strict_types=1);

/**
 * Projeto Repack - Urna Eletrônica (Avaria - Passo 1: Produto e Quantidade)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md (Seção 12.1)
 */

require_once __DIR__ . '/../../models/Produto.php';

$produtos = Produto::listarAtivos();
$produtoSelecionadoId = (int)($_SESSION['avaria_temp']['produto_id'] ?? ($produtos[0]['id'] ?? 1));
$quantidadeInicial = (int)($_SESSION['avaria_temp']['quantidade'] ?? 1);

$pageTitle = "Avaria - Selecionar Produto";
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="text-align: center; margin-bottom: 24px;">
    <span class="badge" style="background-color: var(--secondary); color: #FFF; font-size: 16px; padding: 6px 16px; border-radius: 9999px;">
        PASSO 1 DE 3
    </span>
    <h1 class="headline-lg" style="margin-top: 10px; margin-bottom: 4px;">SELECIONE O PRODUTO E A QUANTIDADE</h1>
    <p class="body-md" style="color: var(--on-surface-muted);">Toque no produto avariado e ajuste as unidades</p>
</div>

<form action="<?= APP_URL ?>/operador/avaria/passo2" method="POST" id="formAvariaPasso1">
    <input type="hidden" name="produto_id" id="inputProdutoId" value="<?= $produtoSelecionadoId ?>">
    <input type="hidden" name="quantidade" id="inputQuantidade" value="<?= $quantidadeInicial ?>">

    <!-- Grade de Produtos Selecionáveis -->
    <div class="produto-grid">
        <?php foreach ($produtos as $p): ?>
            <button type="button" 
                    class="produto-card-btn <?= ((int)$p['id'] === $produtoSelecionadoId) ? 'selecionado' : '' ?>" 
                    id="prodCard_<?= (int)$p['id'] ?>"
                    onclick="selecionarProduto(<?= (int)$p['id'] ?>, '<?= sanitize_html($p['nome']) ?>')">
                <div class="produto-foto-thumb">
                    <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/>
                    </svg>
                </div>
                <div>
                    <div class="body-lg" style="font-weight: 700;"><?= sanitize_html($p['nome']) ?></div>
                    <div class="body-md" style="color: var(--on-surface-muted);"><?= sanitize_html($p['pacote_padrao']) ?></div>
                </div>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Seletor Numérico Gigante -->
    <div class="urna-card" style="text-align: center;">
        <label class="body-lg" style="font-weight: 700; display: block; margin-bottom: 12px;">QUANTIDADE AVARIADA:</label>
        <div class="seletor-numerico-wrapper">
            <button type="button" class="btn-counter" onclick="alterarQuantidade(-1)" aria-label="Diminuir quantidade">−</button>
            <div class="counter-display" id="displayQtd"><?= $quantidadeInicial ?></div>
            <button type="button" class="btn-counter" onclick="alterarQuantidade(1)" aria-label="Aumentar quantidade">+</button>
        </div>
    </div>

    <!-- Botões de Ação do Rodapé -->
    <div class="dupla-confirmacao-rodape">
        <a href="<?= APP_URL ?>/operador" class="btn-urna btn-urna-warning">
            CANCELAR
        </a>
        <button type="submit" class="btn-urna btn-urna-primary">
            PRÓXIMO ➔
        </button>
    </div>
</form>

<script>
let quantidadeAtual = <?= $quantidadeInicial ?>;

function selecionarProduto(id, nome) {
    document.querySelectorAll('.produto-card-btn').forEach(btn => btn.classList.remove('selecionado'));
    const selecionado = document.getElementById('prodCard_' + id);
    if (selecionado) selecionado.classList.add('selecionado');
    document.getElementById('inputProdutoId').value = id;
}

function alterarQuantidade(delta) {
    quantidadeAtual = Math.max(1, quantidadeAtual + delta);
    document.getElementById('displayQtd').innerText = quantidadeAtual;
    document.getElementById('inputQuantidade').value = quantidadeAtual;
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
