<?php
declare(strict_types=1);

/**
 * Projeto Repack - Urna Eletrônica (Avaria - Passo 2: Tipo de Avaria)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md (Seção 12.1)
 */

require_once __DIR__ . '/../../models/Produto.php';

// Captura produto e quantidade informados no passo 1
$produtoId = (int)($_POST['produto_id'] ?? ($_SESSION['avaria_temp']['produto_id'] ?? 1));
$quantidade = (int)($_POST['quantidade'] ?? ($_SESSION['avaria_temp']['quantidade'] ?? 1));

$produto = Produto::buscarPorId($produtoId);
$produtoNome = $produto['nome'] ?? 'Produto Selecionado';

// Salva temporariamente na sessão
$_SESSION['avaria_temp'] = [
    'produto_id' => $produtoId,
    'produto_nome' => $produtoNome,
    'quantidade' => $quantidade
];

$pageTitle = "Avaria - Tipo de Avaria";
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="text-align: center; margin-bottom: 24px;">
    <span class="badge" style="background-color: var(--secondary); color: #FFF; font-size: 16px; padding: 6px 16px; border-radius: 9999px;">
        PASSO 2 DE 3
    </span>
    <h1 class="headline-lg" style="margin-top: 10px; margin-bottom: 4px;">QUAL É O TIPO DA AVARIA?</h1>
    <p class="body-md" style="color: var(--on-surface-muted);">
        Item: <strong><?= sanitize_html($produtoNome) ?></strong> | Quantidade: <strong><?= $quantidade ?> un.</strong>
    </p>
</div>

<!-- 5 Grandes Botões Verticais de Avaria (80px cada) -->
<form action="<?= APP_URL ?>/operador/avaria/resumo" method="POST" id="formAvariaPasso2">
    <input type="hidden" name="tipo_avaria" id="inputTipoAvaria" value="">

    <div style="display: flex; flex-direction: column; gap: 16px; max-width: 600px; margin: 0 auto;">
        
        <!-- Destinados à Lojinha Interna (Amarelo / Laranja) -->
        <button type="button" class="btn-urna btn-urna-warning" onclick="submeterTipo('amassada')">
            AMASSADA
        </button>

        <button type="button" class="btn-urna btn-urna-warning" onclick="submeterTipo('def_rotulo')">
            DEFEITO NO RÓTULO
        </button>

        <button type="button" class="btn-urna btn-urna-warning" onclick="submeterTipo('emba_danificada')">
            EMBALAGEM DANIFICADA
        </button>

        <!-- Destinados ao Descarte Crítico (Vermelho Perigo) -->
        <button type="button" class="btn-urna btn-urna-danger" onclick="submeterTipo('furo')">
            FURO (DESCARTE)
        </button>

        <button type="button" class="btn-urna btn-urna-danger" onclick="submeterTipo('micro_furo')">
            MICRO FURO (DESCARTE)
        </button>
    </div>

    <!-- Botão Voltar -->
    <div style="max-width: 600px; margin: 24px auto 0 auto;">
        <a href="<?= APP_URL ?>/operador/avaria/passo1" class="btn-urna btn-urna-outline">
            ⬅ CORRIGIR PRODUTO / QUANTIDADE
        </a>
    </div>
</form>

<script>
function submeterTipo(tipo) {
    document.getElementById('inputTipoAvaria').value = tipo;
    document.getElementById('formAvariaPasso2').submit();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
