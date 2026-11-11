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

<div style="text-align: center; margin-bottom: 12px;">
    <span class="badge" style="background-color: var(--secondary); color: #FFF; font-size: 15px; font-weight: 800; padding: 6px 18px; border-radius: 9999px;">
        PASSO 2 DE 3
    </span>
    <h1 class="headline-lg" style="margin-top: 8px; margin-bottom: 4px; font-size: 26px;">QUAL É O TIPO DA AVARIA?</h1>
    <div style="background: #F8FAFC; border: 2px solid #E2E8F0; border-radius: 14px; padding: 10px 16px; margin: 8px auto 0 auto; display: inline-block; max-width: 100%;">
        <span style="font-size: 16px; font-weight: 800; color: #0F172A;"><?= sanitize_html($produtoNome) ?></span>
        <span style="font-size: 16px; font-weight: 900; color: #2563EB; margin-left: 8px;">(<?= $quantidade ?> un.)</span>
    </div>
</div>

<!-- 5 Grandes Botões Verticais de Avaria (Urna Eletrônica) -->
<form action="<?= APP_URL ?>/operador/avaria/resumo" method="POST" id="formAvariaPasso2" style="width: 100%;">
    <input type="hidden" name="tipo_avaria" id="inputTipoAvaria" value="">

    <div style="display: flex; flex-direction: column; gap: 14px; width: 100%;">
        
        <!-- Destinados à Lojinha Interna (Amarelo / Laranja) -->
        <button type="button" class="btn-urna btn-urna-warning btn-avaria-opcao" onclick="submeterTipo('amassada')">
            AMASSADA
        </button>

        <button type="button" class="btn-urna btn-urna-warning btn-avaria-opcao" onclick="submeterTipo('def_rotulo')">
            DEFEITO NO RÓTULO
        </button>

        <button type="button" class="btn-urna btn-urna-warning btn-avaria-opcao" onclick="submeterTipo('emba_danificada')">
            EMBALAGEM DANIFICADA
        </button>

        <!-- Destinados ao Descarte Crítico (Vermelho Perigo) -->
        <button type="button" class="btn-urna btn-urna-danger btn-avaria-opcao" onclick="submeterTipo('furo')">
            FURO (DESCARTE)
        </button>

        <button type="button" class="btn-urna btn-urna-danger btn-avaria-opcao" onclick="submeterTipo('micro_furo')">
            MICRO FURO (DESCARTE)
        </button>
    </div>

    <!-- Botão Voltar -->
    <div style="margin-top: 18px; width: 100%;">
        <a href="<?= APP_URL ?>/operador/avaria/passo1" class="btn-urna btn-urna-outline" style="min-height: 70px; font-size: 19px; font-weight: 800; border-radius: 18px;">
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
