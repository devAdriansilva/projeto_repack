<?php
declare(strict_types=1);

/**
 * Projeto Repack - Urna Eletrônica (Avaria - Passo 3: Conferência Dupla)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md (Seção 12.1 e 13.1)
 */

require_once __DIR__ . '/../../models/ApontamentoBO.php';
require_once __DIR__ . '/../../models/Produto.php';

$tipoAvaria = (string)($_POST['tipo_avaria'] ?? ($_SESSION['avaria_temp']['tipo_avaria'] ?? 'amassada'));
$produtoId = (int)($_SESSION['avaria_temp']['produto_id'] ?? 1);
$quantidade = (int)($_SESSION['avaria_temp']['quantidade'] ?? 1);

$produto = Produto::buscarPorId($produtoId);
$produtoNome = $produto['nome'] ?? 'Produto Desconhecido';

// Determina rótulo amigável e destino esperado
$mapaTipos = [
    'amassada' => 'Amassada',
    'def_rotulo' => 'Defeito no Rótulo',
    'emba_danificada' => 'Embalagem Danificada',
    'furo' => 'Furo',
    'micro_furo' => 'Micro Furo'
];
$tipoRotulo = $mapaTipos[$tipoAvaria] ?? $tipoAvaria;
$destino = ApontamentoBO::determinarDestino($tipoAvaria);

$_SESSION['avaria_temp']['tipo_avaria'] = $tipoAvaria;

$pageTitle = "Avaria - Conferência";
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="text-align: center; margin-bottom: 14px;">
    <span class="badge" style="background-color: var(--primary); color: #FFF; font-size: 15px; font-weight: 800; padding: 6px 18px; border-radius: 9999px;">
        PASSO 3 DE 3 - CONFERÊNCIA
    </span>
    <h1 class="headline-xl" style="margin-top: 8px; margin-bottom: 4px; font-size: 26px;">CONFIRA OS DADOS DO B.O.</h1>
    <p class="body-lg" style="color: var(--on-surface-muted); margin-bottom: 0;">Verifique com atenção antes de confirmar a gravação</p>
</div>

<!-- Cartão Central de Resumo -->
<div class="urna-card" style="max-width: 600px; margin: 0 auto; text-align: center; padding: 18px;">
    <div style="margin-bottom: 16px;">
        <span class="body-md" style="color: var(--on-surface-muted); font-weight: 700; text-transform: uppercase;">PRODUTO:</span>
        <div class="headline-lg" style="color: var(--on-surface); margin-top: 4px; font-size: 20px; line-height: 1.25;"><?= sanitize_html($produtoNome) ?></div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); padding: 14px 0;">
        <div>
            <span class="body-md" style="color: var(--on-surface-muted); font-weight: 700;">QUANTIDADE:</span>
            <div class="headline-xl" style="color: var(--on-surface); font-size: 28px;"><?= $quantidade ?> <span style="font-size: 18px;">un.</span></div>
        </div>
        <div>
            <span class="body-md" style="color: var(--on-surface-muted); font-weight: 700;">TIPO DE AVARIA:</span>
            <div class="headline-lg" style="color: <?= ($destino === 'descarte') ? 'var(--danger)' : 'var(--warning)' ?>; font-size: 20px;">
                <?= sanitize_html($tipoRotulo) ?>
            </div>
        </div>
    </div>

    <div>
        <span class="body-md" style="color: var(--on-surface-muted); font-weight: 700;">DESTINAÇÃO AUTOMÁTICA:</span>
        <div style="margin-top: 6px;">
            <?php if ($destino === 'lojinha'): ?>
                <span class="badge" style="background-color: #D1FAE5; color: #065F46; font-size: 17px; padding: 8px 16px; border-radius: 9999px; font-weight: 800;">
                    ✔ LOJINHA INTERNA (RECUPERAÇÃO)
                </span>
            <?php else: ?>
                <span class="badge" style="background-color: #FEE2E2; color: #991B1B; font-size: 17px; padding: 8px 16px; border-radius: 9999px; font-weight: 800;">
                    ✖ DESCARTE IRREVERSÍVEL (PERDA)
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Rodapé de Dupla Confirmação da Urna Eletrônica -->
<div class="dupla-confirmacao-rodape" style="max-width: 600px; margin: 18px auto 0 auto;">
    <a href="<?= APP_URL ?>/operador/avaria/passo2" class="btn-urna btn-urna-warning">
        CORRIGIR
    </a>
    <button type="button" class="btn-urna btn-urna-primary" id="btnConfirmarBO" onclick="confirmarApontamentoBO()">
        CONFIRMAR
    </button>
</div>

<script>
async function confirmarApontamentoBO() {
    const btn = document.getElementById('btnConfirmarBO');
    btn.disabled = true;
    btn.innerText = 'GRAVANDO...';

    // Recupera operador da sessão ou localStorage
    const operadorId = <?= (int)($_SESSION['operador_id'] ?? 1) ?>;

    const payload = {
        produto_id: <?= $produtoId ?>,
        usuario_id: operadorId,
        tipo_avaria: '<?= sanitize_html($tipoAvaria) ?>',
        quantidade: <?= $quantidade ?>,
        created_at: new Date().toISOString()
    };

    try {
        const resultado = await RepackOffline.salvarBO(payload);
        exibirSucessoFeedback("B.O. GRAVADO COM SUCESSO!", "Retornando à tela inicial...", () => {
            window.location.href = BASE_APP_URL + '/operador';
        });
    } catch (err) {
        console.error('Falha ao processar B.O.:', err);
        alert('Erro ao registrar apontamento. Tente novamente.');
        btn.disabled = false;
        btn.innerText = 'CONFIRMAR';
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
