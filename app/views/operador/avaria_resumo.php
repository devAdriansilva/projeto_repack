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
    'micro_furo' => 'Micro Furo',
    'quebrada' => 'Quebrada',
    'validade' => 'Validade'
];
$tipoRotulo = $mapaTipos[$tipoAvaria] ?? $tipoAvaria;
$destino = ApontamentoBO::determinarDestino($tipoAvaria);

$_SESSION['avaria_temp']['tipo_avaria'] = $tipoAvaria;

$pageTitle = "Avaria - Conferência";
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="text-align: center; margin-bottom: 12px;">
    <h1 style="margin: 0; font-size: 22px; line-height: 1.2; font-weight: 900; color: #0F172A;">
        CONFIRMAR B.O.
    </h1>
    <span style="display: inline-block; font-size: 13px; font-weight: 800; color: #DC2626; background: #FEF2F2; border: 2px solid #EF4444; padding: 3px 12px; border-radius: 9999px; margin-top: 4px;">
        CONFERÊNCIA DA AVARIA
    </span>
</div>

<!-- Cartão Central de Resumo (Harmonizado para Mobile) -->
<div class="urna-card" style="width: 100%; text-align: center; padding: 14px 12px; border-radius: 16px; border: 2.5px solid #0F172A; box-shadow: 0 3px 10px rgba(0, 0, 0, 0.06); background: #FFFFFF;">
    <div style="margin-bottom: 12px;">
        <span style="color: #64748B; font-weight: 900; font-size: 13px; letter-spacing: 0.5px; text-transform: uppercase;">PRODUTO:</span>
        <?php if (!empty($produto['foto_url'])): ?>
            <div style="margin: 6px 0 8px 0;">
                <img src="<?= APP_URL . sanitize_html($produto['foto_url']) ?>" alt="Foto" style="width: 100%; max-width: 160px; height: 120px; object-fit: contain; border-radius: 12px; border: 2px solid #10B981; background: #FFFFFF; box-shadow: 0 3px 10px rgba(16,185,129,0.18); padding: 3px;">
            </div>
        <?php endif; ?>
        <div style="color: #0F172A; margin-top: 6px; font-size: 18px; font-weight: 900; line-height: 1.2; word-break: break-word;">
            <?= sanitize_html($produtoNome) ?>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 12px; border-top: 1.5px solid #E2E8F0; padding-top: 12px;">
        <!-- Quantidade em Destaque -->
        <div>
            <span style="color: #64748B; font-weight: 900; font-size: 13px; text-transform: uppercase;">QUANTIDADE:</span>
            <div style="color: #0F172A; font-size: 42px; font-weight: 900; margin-top: 2px; line-height: 1.05;">
                <?= $quantidade ?> <span style="font-size: 18px; font-weight: 800; color: #64748B;">un.</span>
            </div>
        </div>

        <!-- Tipo de Avaria com Largura Total (Sem Truncamento) -->
        <div style="border-top: 1.5px dashed #CBD5E1; padding-top: 10px;">
            <span style="color: #64748B; font-weight: 900; font-size: 13px; text-transform: uppercase;">TIPO DE AVARIA:</span>
            <div style="color: <?= ($destino === 'descarte') ? '#DC2626' : '#D97706' ?>; font-size: 22px; font-weight: 900; margin-top: 4px; line-height: 1.2; word-break: break-word;">
                <?= sanitize_html($tipoRotulo) ?>
            </div>
            <div style="margin-top: 4px;">
                <?= ($destino === 'descarte') 
                    ? '<span style="display: inline-block; font-size: 12px; font-weight: 800; color: #DC2626; background: #FEF2F2; border: 1.5px solid #EF4444; padding: 2px 10px; border-radius: 9999px;">DESTINO: DESCARTE</span>' 
                    : '<span style="display: inline-block; font-size: 12px; font-weight: 800; color: #D97706; background: #FEF3C7; border: 1.5px solid #F59E0B; padding: 2px 10px; border-radius: 9999px;">DESTINO: LOJINHA</span>' ?>
            </div>
        </div>
    </div>
</div>

<!-- Rodapé de Dupla Confirmação da Urna Eletrônica (66px) -->
<div class="dupla-confirmacao-rodape" style="width: 100%; margin: 14px 0 0 0; gap: 10px;">
    <a href="<?= APP_URL ?>/operador/avaria/passo2" class="btn-urna btn-urna-secondary" style="min-height: 66px; font-size: 20px; font-weight: 900; border-radius: 16px; background: linear-gradient(145deg, #2563EB 0%, #1D4ED8 100%); color: #FFFFFF; border: 2.5px solid #1E40AF; box-shadow: 0 3px 10px rgba(37, 99, 235, 0.2); text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink: 0;">
            <line x1="21" y1="12" x2="3" y2="12"></line>
            <polyline points="11 4 3 12 11 20"></polyline>
        </svg>
        <span>CORRIGIR</span>
    </a>
    <button type="button" class="btn-urna btn-urna-primary" id="btnConfirmarBO" onclick="confirmarApontamentoBO()" style="min-height: 66px; font-size: 20px; font-weight: 900; border-radius: 16px;">
        CONFIRMAR
    </button>
</div>

<script>
function formatarDataHoraLocal(d = new Date()) {
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

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
        created_at: formatarDataHoraLocal(),
        criado_em: formatarDataHoraLocal()
    };

    try {
        const response = await fetch('<?= APP_URL ?>/api/bo/salvar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (response.ok && data.status === 'success') {
            localStorage.removeItem('repack_bo_ativo');
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
            localStorage.removeItem('repack_bo_ativo');
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
