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

<div style="text-align: center; margin-bottom: 12px;">
    <span class="badge" style="background-color: var(--primary); color: #FFF; font-size: 15px; font-weight: 800; padding: 6px 18px; border-radius: 9999px;">
        PASSO 3 DE 3 - CONFERÊNCIA
    </span>
    <h1 class="headline-xl" style="margin-top: 8px; margin-bottom: 4px; font-size: 26px;">CONFIRA OS DADOS DO B.O.</h1>
    <p class="body-lg" style="color: var(--on-surface-muted); margin-bottom: 0;">Verifique com atenção antes de confirmar a gravação</p>
</div>

<!-- Cartão Central de Resumo (Amplo e de Alto Contraste) -->
<div class="urna-card" style="width: 100%; text-align: center; padding: 24px 20px; border-radius: 22px; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);">
    <div style="margin-bottom: 18px;">
        <span class="body-md" style="color: var(--on-surface-muted); font-weight: 800; font-size: 14px; letter-spacing: 1px; text-transform: uppercase;">PRODUTO:</span>
        <div class="headline-lg" style="color: var(--on-surface); margin-top: 6px; font-size: 24px; font-weight: 900; line-height: 1.25; word-break: break-word;">
            <?= sanitize_html($produtoNome) ?>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; border-top: 2px solid var(--border); border-bottom: 2px solid var(--border); padding: 18px 0;">
        <div>
            <span class="body-md" style="color: var(--on-surface-muted); font-weight: 800; font-size: 14px; text-transform: uppercase;">QUANTIDADE:</span>
            <div class="headline-xl" style="color: #0F172A; font-size: 40px; font-weight: 900; margin-top: 4px;">
                <?= $quantidade ?> <span style="font-size: 20px; font-weight: 700; color: #64748B;">un.</span>
            </div>
        </div>
        <div>
            <span class="body-md" style="color: var(--on-surface-muted); font-weight: 800; font-size: 14px; text-transform: uppercase;">TIPO DE AVARIA:</span>
            <div class="headline-lg" style="color: <?= ($destino === 'descarte') ? 'var(--danger)' : '#D97706' ?>; font-size: 24px; font-weight: 900; margin-top: 4px;">
                <?= sanitize_html($tipoRotulo) ?>
            </div>
        </div>
    </div>

    <div>
        <span class="body-md" style="color: var(--on-surface-muted); font-weight: 800; font-size: 14px; text-transform: uppercase;">DESTINAÇÃO AUTOMÁTICA:</span>
        <div style="margin-top: 10px;">
            <?php if ($destino === 'lojinha'): ?>
                <span class="badge" style="background-color: #D1FAE5; color: #065F46; font-size: 20px; padding: 12px 24px; border-radius: 14px; font-weight: 900; display: inline-block;">
                    ✔ LOJINHA INTERNA (RECUPERAÇÃO)
                </span>
            <?php else: ?>
                <span class="badge" style="background-color: #FEE2E2; color: #991B1B; font-size: 20px; padding: 12px 24px; border-radius: 14px; font-weight: 900; display: inline-block;">
                    ✖ DESCARTE IRREVERSÍVEL (PERDA)
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Rodapé de Dupla Confirmação da Urna Eletrônica (Grandes Alvos 85px) -->
<div class="dupla-confirmacao-rodape" style="width: 100%; margin: 18px 0 0 0; gap: 14px;">
    <a href="<?= APP_URL ?>/operador/avaria/passo2" class="btn-urna btn-urna-warning" style="min-height: 85px; font-size: 24px; font-weight: 900; border-radius: 20px;">
        CORRIGIR
    </a>
    <button type="button" class="btn-urna btn-urna-primary" id="btnConfirmarBO" onclick="confirmarApontamentoBO()" style="min-height: 85px; font-size: 24px; font-weight: 900; border-radius: 20px;">
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
        tipo_avaria: '<?= sanitize_html($tipoAvaria) ?>',
        quantidade: <?= $quantidade ?>,
        operador_id: operadorId,
        criado_em: new Date().toISOString()
    };

    try {
        const response = await fetch('<?= APP_URL ?>/api/bo/salvar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (response.ok && data.status === 'success') {
            exibirSucessoFeedback('GRAVADO COM SUCESSO!', 'Redirecionando...', () => {
                window.location.href = '<?= APP_URL ?>/operador';
            });
        } else {
            throw new Error(data.message || 'Falha ao salvar no servidor.');
        }
    } catch (err) {
        console.warn('Falha online, gravando no IndexedDB para sincronização posterior...', err);
        if (window.RepackOffline) {
            await window.RepackOffline.salvarBOOffline(payload);
            exibirSucessoFeedback('SALVO OFFLINE!', 'Será sincronizado automaticamente.', () => {
                window.location.href = '<?= APP_URL ?>/operador';
            });
        } else {
            alert('Erro ao gravar: ' + err.message);
            btn.disabled = false;
            btn.innerText = 'CONFIRMAR';
        }
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
