<?php
declare(strict_types=1);

/**
 * Projeto Repack - Urna Eletrônica (Produção - Conferência Dupla)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md (Seção 12.1 e 13.2)
 */

require_once __DIR__ . '/../../models/Produto.php';

$produtoId = (int)($_POST['produto_id'] ?? ($_SESSION['cronometro_temp']['produto_id'] ?? 1));
$produtoNome = (string)($_POST['produto_nome'] ?? ($_SESSION['cronometro_temp']['produto_nome'] ?? ''));
$inicio = (string)($_POST['data_hora_inicio'] ?? ($_SESSION['cronometro_temp']['data_hora_inicio'] ?? ''));
$fim = (string)($_POST['data_hora_fim'] ?? ($_SESSION['cronometro_temp']['data_hora_fim'] ?? ''));
$duracao = (int)($_POST['duracao_segundos'] ?? ($_SESSION['cronometro_temp']['duracao_segundos'] ?? 0));
$pacotes = (int)($_POST['pacotes_refeitos'] ?? ($_SESSION['cronometro_temp']['pacotes_refeitos'] ?? 1));

$prod = null;
if ($produtoId > 0) {
    $prod = Produto::buscarPorId($produtoId);
    $produtoNome = $prod['nome'] ?? $produtoNome;
}
$fotoUrl = (!empty($prod['foto_url'])) ? (APP_URL . $prod['foto_url']) : null;

$_SESSION['cronometro_temp'] = [
    'produto_id' => $produtoId,
    'produto_nome' => $produtoNome,
    'data_hora_inicio' => $inicio,
    'data_hora_fim' => $fim,
    'duracao_segundos' => $duracao,
    'pacotes_refeitos' => $pacotes,
    'pausado' => (int)($_POST['pausado'] ?? 0),
    'segundos_pausa' => (int)($_POST['segundos_pausa'] ?? 0)
];

// Formatação amigável dos horários de início e término (sem tempo total decorrido)
$horaInicioFormatada = !empty($inicio) ? date('H:i', strtotime($inicio)) : '--:--';
$horaFimFormatada = !empty($fim) ? date('H:i', strtotime($fim)) : '--:--';

$pageTitle = "Produção - Conferência";
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="text-align: center; margin-bottom: 22px;">
    <h1 class="headline-xl" style="margin: 0; font-size: 54px; font-weight: 900; color: #0F172A;">
        CONFIRMAR PRODUÇÃO
    </h1>
</div>

<!-- Cartão Central de Resumo (Ultra Alto Contraste Massivo - Sem Poluição Visual) -->
<div class="urna-card" style="width: 100%; text-align: center; padding: 44px 32px; border-radius: 36px; border: 8px solid #0F172A; box-shadow: 0 14px 34px rgba(0, 0, 0, 0.22); background: #FFFFFF;">
    <div style="margin-bottom: 24px;">
        <span class="body-md" style="color: #475569; font-weight: 900; font-size: 26px; letter-spacing: 1px; text-transform: uppercase;">PRODUTO:</span>
        <?php if ($fotoUrl): ?>
            <div style="margin: 18px 0 16px 0;">
                <img src="<?= $fotoUrl ?>" alt="Foto" style="width: 100%; max-width: 400px; height: 380px; object-fit: contain; border-radius: 28px; border: 5px solid #059669; background: #FFFFFF; box-shadow: 0 10px 28px rgba(5,150,105,0.25); padding: 8px;">
            </div>
        <?php endif; ?>
        <div class="headline-lg" style="color: #0F172A; margin-top: 8px; font-size: 50px; font-weight: 900; line-height: 1.2; word-break: break-word;">
            <?= sanitize_html($produtoNome) ?>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; border-top: 5px solid #CBD5E1; padding-top: 28px;">
        <div>
            <span class="body-md" style="color: #475569; font-weight: 900; font-size: 26px; text-transform: uppercase;">PACOTES:</span>
            <div class="headline-xl" style="color: #0F172A; font-size: 104px; font-weight: 900; margin-top: 6px; line-height: 1.05;">
                <?= $pacotes ?> <span style="font-size: 36px; font-weight: 800; color: #64748B;">pcts</span>
            </div>
        </div>
        <div>
            <span class="body-md" style="color: #475569; font-weight: 900; font-size: 26px; text-transform: uppercase;">HORÁRIOS:</span>
            <div style="color: #1E40AF; font-size: 50px; font-weight: 900; margin-top: 12px; line-height: 1.2;">
                <?= $horaInicioFormatada ?> ➔ <?= $horaFimFormatada ?>
            </div>
        </div>
    </div>
</div>

<!-- Rodapé de Dupla Confirmação da Urna Eletrônica (Grandes Alvos 135px) -->
<div class="dupla-confirmacao-rodape" style="width: 100%; margin: 28px 0 0 0; gap: 20px;">
    <a href="<?= APP_URL ?>/operador/cronometro?origem=voltar" class="btn-urna btn-urna-secondary" style="min-height: 135px; font-size: 42px; font-weight: 900; border-radius: 36px; background: linear-gradient(145deg, #2563EB 0%, #1D4ED8 100%); color: #FFFFFF; border: 6px solid #1E40AF; box-shadow: 0 10px 26px rgba(37, 99, 235, 0.35);">
        ⬅ VOLTAR / CORRIGIR
    </a>
    <button type="button" class="btn-urna btn-urna-primary" id="btnConfirmarProdutividade" onclick="confirmarApontamentoProdutividade()" style="min-height: 135px; font-size: 42px; font-weight: 900; border-radius: 36px;">
        CONFIRMAR
    </button>
</div>

<script>
function formatarDataHoraLocal(d = new Date()) {
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

async function confirmarApontamentoProdutividade() {
    const btn = document.getElementById('btnConfirmarProdutividade');
    btn.disabled = true;
    btn.innerText = 'GRAVANDO...';

    const operadorId = <?= (int)($_SESSION['operador_id'] ?? 1) ?>;

    const payload = {
        produto_id: <?= $produtoId ?>,
        usuario_id: operadorId,
        data_hora_inicio: '<?= sanitize_html($inicio) ?>',
        data_hora_fim: '<?= sanitize_html($fim) ?>',
        pacotes_refeitos: <?= $pacotes ?>,
        duracao_segundos: <?= $duracao ?>,
        created_at: formatarDataHoraLocal()
    };

    try {
        const response = await fetch('<?= APP_URL ?>/api/produtividade/salvar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (response.ok && data.status === 'success') {
            localStorage.removeItem('repack_producao_ativa');
            exibirSucessoFeedback('PRODUÇÃO GRAVADA!', 'Redirecionando...', () => {
                window.location.href = '<?= APP_URL ?>/operador';
            });
        } else {
            throw new Error(data.message || 'Falha ao salvar no servidor.');
        }
    } catch (err) {
        console.warn('Falha online, gravando no IndexedDB para sincronização posterior...', err);
        if (window.RepackOffline) {
            await window.RepackOffline.salvarProdutividade(payload);
            localStorage.removeItem('repack_producao_ativa');
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
