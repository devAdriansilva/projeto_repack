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

<div style="text-align: center; margin-bottom: 20px;">
    <h1 class="headline-lg" style="margin: 0 0 14px 0; font-size: 48px; font-weight: 900; color: #0F172A;">
        TIPO DE AVARIA
    </h1>
    <div style="background: #FFFFFF; border: 6px solid #0F172A; border-radius: 28px; padding: 20px 36px; margin: 0 auto; display: inline-block; max-width: 100%; box-shadow: 0 8px 24px rgba(0,0,0,0.12);">
        <span style="font-size: 34px; font-weight: 900; color: #0F172A;"><?= sanitize_html($produtoNome) ?></span>
        <span style="font-size: 34px; font-weight: 900; color: #2563EB; margin-left: 18px;">➔ <?= $quantidade ?> un.</span>
    </div>
</div>

<!-- 5 Grandes Botões Verticais de Avaria (Urna Eletrônica - Alta Acessibilidade Massiva) -->
<form action="<?= APP_URL ?>/operador/avaria/resumo" method="POST" id="formAvariaPasso2" style="width: 100%;">
    <input type="hidden" name="tipo_avaria" id="inputTipoAvaria" value="">

    <div style="display: flex; flex-direction: column; gap: 20px; width: 100%;">
        
        <!-- Destinados à Lojinha Interna (Amarelo / Laranja) -->
        <button type="button" class="btn-urna btn-urna-warning btn-avaria-opcao" onclick="submeterTipo('amassada')">
            <span style="font-size: 52px;">🥫</span> <span>AMASSADA</span>
        </button>

        <button type="button" class="btn-urna btn-urna-warning btn-avaria-opcao" onclick="submeterTipo('def_rotulo')">
            <span style="font-size: 52px;">🏷️</span> <span>DEFEITO NO RÓTULO</span>
        </button>

        <button type="button" class="btn-urna btn-urna-warning btn-avaria-opcao" onclick="submeterTipo('emba_danificada')">
            <span style="font-size: 52px;">📦</span> <span>EMBALAGEM DANIFICADA</span>
        </button>

        <!-- Destinados ao Descarte Crítico (Vermelho Perigo) -->
        <button type="button" class="btn-urna btn-urna-danger btn-avaria-opcao" onclick="submeterTipo('furo')">
            <span style="font-size: 52px;">🚫</span> <span>FURO (DESCARTE)</span>
        </button>

        <button type="button" class="btn-urna btn-urna-danger btn-avaria-opcao" onclick="submeterTipo('micro_furo')">
            <span style="font-size: 52px;">⚠️</span> <span>MICRO FURO (DESCARTE)</span>
        </button>
    </div>

    <!-- Botão Voltar -->
    <div style="margin-top: 24px; width: 100%;">
        <a href="<?= APP_URL ?>/operador/avaria/passo1" class="btn-urna btn-urna-outline" style="min-height: 115px; font-size: 32px; font-weight: 900; border-radius: 28px; border: 6px solid #0F172A;">
            ⬅ VOLTAR / CORRIGIR
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
