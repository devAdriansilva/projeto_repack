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
    <h1 style="margin: 0 0 8px 0; font-size: 22px; line-height: 1.2; font-weight: 900; color: #0F172A;">
        TIPO DE AVARIA
    </h1>
    <div style="background: #FFFFFF; border: 2.5px solid #0F172A; border-radius: 14px; padding: 8px 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center; gap: 12px; max-width: 100%; box-shadow: 0 3px 10px rgba(0,0,0,0.06); flex-wrap: nowrap;">
        <?php if (!empty($produto['foto_url'])): ?>
            <img src="<?= APP_URL . sanitize_html($produto['foto_url']) ?>" alt="Foto" style="width: 50px; height: 50px; object-fit: contain; border-radius: 10px; border: 2px solid #10B981; background: #FFFFFF; box-shadow: 0 2px 6px rgba(0,0,0,0.1); padding: 2px; flex-shrink: 0;">
        <?php endif; ?>
        <div style="text-align: left; flex: 1; min-width: 0;">
            <div style="font-size: 16px; font-weight: 900; color: #0F172A; line-height: 1.2; word-break: break-word;"><?= sanitize_html($produtoNome) ?></div>
            <div style="font-size: 15px; font-weight: 900; color: #2563EB; margin-top: 2px;">➔ <?= $quantidade ?> un.</div>
        </div>
    </div>
</div>

<!-- 7 Botões Verticais de Avaria (Urna Eletrônica - Escala Mobile Harmonizada) -->
<form action="<?= APP_URL ?>/operador/avaria/resumo" method="POST" id="formAvariaPasso2" style="width: 100%;">
    <input type="hidden" name="tipo_avaria" id="inputTipoAvaria" value="">

    <div style="display: flex; flex-direction: column; gap: 8px; width: 100%;">
        
        <!-- Destinados à Lojinha Interna (Amarelo / Laranja - Prazo e Avarias Leves) -->
        <button type="button" class="btn-urna btn-urna-warning btn-avaria-opcao" onclick="submeterTipo('amassada')" style="min-height: 58px; height: auto; font-size: 17px; font-weight: 900; border-radius: 14px; border: 2.5px solid rgba(0,0,0,0.25); display: flex; align-items: center; justify-content: center; gap: 10px; padding: 8px 12px; text-transform: uppercase;">
            <span style="font-size: 26px;">🥫</span> <span>AMASSADA</span>
        </button>

        <button type="button" class="btn-urna btn-urna-warning btn-avaria-opcao" onclick="submeterTipo('def_rotulo')" style="min-height: 58px; height: auto; font-size: 17px; font-weight: 900; border-radius: 14px; border: 2.5px solid rgba(0,0,0,0.25); display: flex; align-items: center; justify-content: center; gap: 10px; padding: 8px 12px; text-transform: uppercase;">
            <span style="font-size: 26px;">🏷️</span> <span>DEFEITO NO RÓTULO</span>
        </button>

        <button type="button" class="btn-urna btn-urna-warning btn-avaria-opcao" onclick="submeterTipo('emba_danificada')" style="min-height: 58px; height: auto; font-size: 17px; font-weight: 900; border-radius: 14px; border: 2.5px solid rgba(0,0,0,0.25); display: flex; align-items: center; justify-content: center; gap: 10px; padding: 8px 12px; text-transform: uppercase;">
            <span style="font-size: 26px;">📦</span> <span>EMBALAGEM DANIFICADA</span>
        </button>

        <button type="button" class="btn-urna btn-urna-warning btn-avaria-opcao" onclick="submeterTipo('validade')" style="min-height: 58px; height: auto; font-size: 17px; font-weight: 900; border-radius: 14px; border: 2.5px solid rgba(0,0,0,0.25); display: flex; align-items: center; justify-content: center; gap: 10px; padding: 8px 12px; text-transform: uppercase;">
            <span style="font-size: 26px;">⏳</span> <span>VALIDADE (30 DIAS)</span>
        </button>

        <!-- Destinados ao Descarte Crítico (Vermelho Perigo - Não Vão para a Lojinha) -->
        <button type="button" class="btn-urna btn-urna-danger btn-avaria-opcao" onclick="submeterTipo('furo')" style="min-height: 58px; height: auto; font-size: 17px; font-weight: 900; border-radius: 14px; border: 2.5px solid rgba(0,0,0,0.25); display: flex; align-items: center; justify-content: center; gap: 10px; padding: 8px 12px; text-transform: uppercase;">
            <span style="font-size: 26px;">🚫</span> <span>FURO</span>
        </button>

        <button type="button" class="btn-urna btn-urna-danger btn-avaria-opcao" onclick="submeterTipo('micro_furo')" style="min-height: 58px; height: auto; font-size: 17px; font-weight: 900; border-radius: 14px; border: 2.5px solid rgba(0,0,0,0.25); display: flex; align-items: center; justify-content: center; gap: 10px; padding: 8px 12px; text-transform: uppercase;">
            <span style="font-size: 26px;">⚠️</span> <span>MICRO FURO</span>
        </button>

        <button type="button" class="btn-urna btn-urna-danger btn-avaria-opcao" onclick="submeterTipo('quebrada')" style="min-height: 58px; height: auto; font-size: 17px; font-weight: 900; border-radius: 14px; border: 2.5px solid rgba(0,0,0,0.25); display: flex; align-items: center; justify-content: center; gap: 10px; padding: 8px 12px; text-transform: uppercase;">
            <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; flex-shrink: 0;">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
    <div style="margin-top: 14px; width: 100%;">
        <a href="<?= APP_URL ?>/operador/avaria/passo1?origem=voltar" class="btn-urna btn-urna-secondary" style="min-height: 66px; font-size: 20px; font-weight: 900; border-radius: 16px; background: linear-gradient(145deg, #2563EB 0%, #1D4ED8 100%); color: #FFFFFF; border: 2.5px solid #1E40AF; box-shadow: 0 3px 10px rgba(37, 99, 235, 0.25); display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink: 0;">
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
