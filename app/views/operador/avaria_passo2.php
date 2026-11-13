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

<div style="text-align: center; margin-bottom: 22px;">
    <h1 class="headline-lg" style="margin: 0 0 16px 0; font-size: 54px; font-weight: 900; color: #0F172A;">
        TIPO DE AVARIA
    </h1>
    <div style="background: #FFFFFF; border: 7px solid #0F172A; border-radius: 32px; padding: 18px 30px; margin: 0 auto; display: flex; align-items: center; justify-content: center; gap: 20px; max-width: 100%; box-shadow: 0 10px 26px rgba(0,0,0,0.14); flex-wrap: wrap;">
        <?php if (!empty($produto['foto_url'])): ?>
            <img src="<?= APP_URL . sanitize_html($produto['foto_url']) ?>" alt="Foto" style="width: 160px; height: 160px; object-fit: contain; border-radius: 22px; border: 5px solid #10B981; background: #FFFFFF; box-shadow: 0 8px 20px rgba(0,0,0,0.14); padding: 6px;">
        <?php endif; ?>
        <div style="text-align: left;">
            <div style="font-size: 34px; font-weight: 900; color: #0F172A; line-height: 1.15;"><?= sanitize_html($produtoNome) ?></div>
            <div style="font-size: 32px; font-weight: 900; color: #2563EB; margin-top: 4px;">➔ <?= $quantidade ?> un.</div>
        </div>
    </div>
</div>

<!-- 5 Grandes Botões Verticais de Avaria (Urna Eletrônica - Alta Acessibilidade Massiva) -->
<form action="<?= APP_URL ?>/operador/avaria/resumo" method="POST" id="formAvariaPasso2" style="width: 100%;">
    <input type="hidden" name="tipo_avaria" id="inputTipoAvaria" value="">

    <div style="display: flex; flex-direction: column; gap: 22px; width: 100%;">
        
        <!-- Destinados à Lojinha Interna (Amarelo / Laranja - Prazo e Avarias Leves) -->
        <button type="button" class="btn-urna btn-urna-warning btn-avaria-opcao" onclick="submeterTipo('amassada')">
            <span style="font-size: 58px;">🥫</span> <span>AMASSADA</span>
        </button>

        <button type="button" class="btn-urna btn-urna-warning btn-avaria-opcao" onclick="submeterTipo('def_rotulo')">
            <span style="font-size: 58px;">🏷️</span> <span>DEFEITO NO RÓTULO</span>
        </button>

        <button type="button" class="btn-urna btn-urna-warning btn-avaria-opcao" onclick="submeterTipo('emba_danificada')">
            <span style="font-size: 58px;">📦</span> <span>EMBALAGEM DANIFICADA</span>
        </button>

        <button type="button" class="btn-urna btn-urna-warning btn-avaria-opcao" onclick="submeterTipo('validade')">
            <span style="font-size: 58px;">⏳</span> <span>VALIDADE (30 DIAS)</span>
        </button>

        <!-- Destinados ao Descarte Crítico (Vermelho Perigo - Não Vão para a Lojinha) -->
        <button type="button" class="btn-urna btn-urna-danger btn-avaria-opcao" onclick="submeterTipo('furo')">
            <span style="font-size: 58px;">🚫</span> <span>FURO</span>
        </button>

        <button type="button" class="btn-urna btn-urna-danger btn-avaria-opcao" onclick="submeterTipo('micro_furo')">
            <span style="font-size: 58px;">⚠️</span> <span>MICRO FURO</span>
        </button>

        <button type="button" class="btn-urna btn-urna-danger btn-avaria-opcao" onclick="submeterTipo('quebrada')">
            <span style="display: inline-flex; align-items: center; justify-content: center; width: 58px; height: 58px; flex-shrink: 0;">
                <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <!-- Gargalo quebrado da garrafa -->
                    <path d="M10 2h4v2.5l-1 1.5-3-1z" fill="currentColor" fill-opacity="0.3"/>
                    <!-- Corpo da garrafa com quebra irregular e pontiaguda -->
                    <path d="M6 12.5l3-2.5 2 2 2.5-3.5 2 2 2.5-2v8.5a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2v-6.5z" fill="currentColor" fill-opacity="0.25"/>
                    <!-- Trincas no vidro -->
                    <path d="M9 15l2.5-2 1 2 2.5-1.5"/>
                    <!-- Cacos de vidro projetados -->
                    <path d="M17 4l2-1.5 1 2.5z" fill="currentColor"/>
                </svg>
            </span>
            <span>QUEBRADA</span>
        </button>
    </div>

    <!-- Botão Voltar (Fundo Azul de Alto Destaque com Seta Vetorial Espessa) -->
    <div style="margin-top: 26px; width: 100%;">
        <a href="<?= APP_URL ?>/operador/avaria/passo1?origem=voltar" class="btn-urna btn-urna-secondary" style="min-height: 135px; font-size: 40px; font-weight: 900; border-radius: 36px; background: linear-gradient(145deg, #2563EB 0%, #1D4ED8 100%); color: #FFFFFF; border: 6px solid #1E40AF; box-shadow: 0 10px 26px rgba(37, 99, 235, 0.35); display: flex; align-items: center; justify-content: center; gap: 18px; text-decoration: none;">
            <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink: 0;">
                <line x1="21" y1="12" x2="3" y2="12"></line>
                <polyline points="11 4 3 12 11 20"></polyline>
            </svg>
            <span>VOLTAR / CORRIGIR</span>
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
