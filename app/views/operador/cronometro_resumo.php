<?php
declare(strict_types=1);

/**
 * Projeto Repack - Urna Eletrônica (Cronômetro - Conferência Dupla)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md (Seção 12.1 e 13.2)
 */

require_once __DIR__ . '/../../models/Produto.php';

$produtoId = (int)($_POST['produto_id'] ?? ($_SESSION['cronometro_temp']['produto_id'] ?? 1));
$produtoNome = (string)($_POST['produto_nome'] ?? ($_SESSION['cronometro_temp']['produto_nome'] ?? ''));
$inicio = (string)($_POST['data_hora_inicio'] ?? ($_SESSION['cronometro_temp']['data_hora_inicio'] ?? ''));
$fim = (string)($_POST['data_hora_fim'] ?? ($_SESSION['cronometro_temp']['data_hora_fim'] ?? ''));
$duracao = (int)($_POST['duracao_segundos'] ?? ($_SESSION['cronometro_temp']['duracao_segundos'] ?? 0));
$pacotes = (int)($_POST['pacotes_refeitos'] ?? ($_SESSION['cronometro_temp']['pacotes_refeitos'] ?? 1));

if (empty($produtoNome)) {
    $prod = Produto::buscarPorId($produtoId);
    $produtoNome = $prod['nome'] ?? 'Produto';
}

$_SESSION['cronometro_temp'] = [
    'produto_id' => $produtoId,
    'produto_nome' => $produtoNome,
    'data_hora_inicio' => $inicio,
    'data_hora_fim' => $fim,
    'duracao_segundos' => $duracao,
    'pacotes_refeitos' => $pacotes
];

// Formatação amigável do tempo
$horas = floor($duracao / 3600);
$minutos = floor(($duracao % 3600) / 60);
$segundos = $duracao % 60;
$tempoFormatado = sprintf("%02d:%02d:%02d", $horas, $minutos, $segundos);

$pageTitle = "Produtividade - Conferência";
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="text-align: center; margin-bottom: 24px;">
    <span class="badge" style="background-color: var(--primary); color: #FFF; font-size: 16px; padding: 6px 16px; border-radius: 9999px;">
        CONFERÊNCIA DE PRODUTIVIDADE
    </span>
    <h1 class="headline-xl" style="margin-top: 10px; margin-bottom: 8px;">CONFIRA O CICLO DE PRODUÇÃO</h1>
    <p class="body-lg" style="color: var(--on-surface-muted);">Valide as informações antes de confirmar o apontamento</p>
</div>

<!-- Cartão Central de Resumo -->
<div class="urna-card" style="max-width: 650px; margin: 0 auto; text-align: center;">
    <div style="margin-bottom: 20px;">
        <span class="body-md" style="color: var(--on-surface-muted); font-weight: 700; text-transform: uppercase;">PRODUTO REEMBALADO:</span>
        <div class="headline-lg" style="color: var(--on-surface); margin-top: 4px;"><?= sanitize_html($produtoNome) ?></div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); padding: 16px 0;">
        <div>
            <span class="body-md" style="color: var(--on-surface-muted); font-weight: 700;">TEMPO DECORRIDO:</span>
            <div class="headline-xl" style="color: var(--secondary); font-family: monospace;"><?= $tempoFormatado ?></div>
        </div>
        <div>
            <span class="body-md" style="color: var(--on-surface-muted); font-weight: 700;">PACOTES CONCLUÍDOS:</span>
            <div class="headline-xl" style="color: var(--primary);">
                <?= $pacotes ?> <span style="font-size: 20px;">pcts</span>
            </div>
        </div>
    </div>

    <div>
        <span class="body-md" style="color: var(--on-surface-muted); font-weight: 700;">STATUS DO CICLO:</span>
        <div style="margin-top: 6px;">
            <span class="badge" style="background-color: #D1FAE5; color: #065F46; font-size: 18px; padding: 8px 18px; border-radius: 9999px; font-weight: 700;">
                ✔ PRONTO PARA REGISTRO
            </span>
        </div>
    </div>
</div>

<!-- Rodapé de Dupla Confirmação -->
<div class="dupla-confirmacao-rodape" style="max-width: 650px; margin: 24px auto 0 auto;">
    <a href="<?= APP_URL ?>/operador/cronometro?origem=voltar" class="btn-urna btn-urna-warning">
        CORRIGIR
    </a>
    <button type="button" class="btn-urna btn-urna-primary" id="btnConfirmarProdutividade" onclick="confirmarApontamentoProdutividade()">
        CONFIRMAR
    </button>
</div>

<script>
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
        created_at: new Date().toISOString()
    };

    try {
        const resultado = await RepackOffline.salvarProdutividade(payload);
        exibirSucessoFeedback("PRODUTIVIDADE GRAVADA!", "Retornando à tela inicial...", () => {
            localStorage.removeItem('repack_timer_inicio');
            localStorage.removeItem('repack_timer_produto_id');
            localStorage.removeItem('repack_timer_produto_nome');
            window.location.href = BASE_APP_URL + '/operador';
        });
    } catch (err) {
        console.error('Falha ao processar produtividade:', err);
        alert('Erro ao registrar produtividade. Tente novamente.');
        btn.disabled = false;
        btn.innerText = 'CONFIRMAR';
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
