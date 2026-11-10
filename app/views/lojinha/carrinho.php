<?php
declare(strict_types=1);

/**
 * Projeto Repack - Sacola / Carrinho com Trava Temporária de 10 minutos
 * Baseado estritamente em docs/FSD.md (Seções 12.2, 13.3 e 14) e docs/DESIGN.md
 */

$pageTitle = 'Minha Sacola - Lojinha dos Colaboradores';
require __DIR__ . '/../layouts/lojinha_header.php';

$totalItensCarrinho = 0;
foreach ($itensCarrinho as $item) {
    $totalItensCarrinho += (int)$item['quantidade'];
}
$segundosRestantes = (int)($resumo['segundos_restantes'] ?? 0);
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="headline-lg mb-1">Minha Sacola de Compras</h2>
        <p class="body-md text-muted mb-0">Confira seus itens reservados antes de finalizar o pedido</p>
    </div>

    <a href="/lojinha" class="btn btn-outline-secondary" style="font-weight: 700; border-radius: 12px; padding: 12px 20px;">
        ← Continuar Escolhendo
    </a>
</div>

<?php if (!empty($flashSucesso)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 12px; font-weight: 600;">
        <?= sanitize_html($flashSucesso) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($flashErro)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 12px; font-weight: 600;">
        <?= sanitize_html($flashErro) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (empty($itensCarrinho)): ?>
    <!-- Carrinho Vazio -->
    <div class="text-center py-5" style="background: #FFFFFF; border-radius: 20px; border: 2px dashed #D1D5DB; padding: 48px 24px;">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 80px; height: 80px; background: #F3F4F6; border-radius: 50%; color: #9CA3AF; margin-bottom: 20px;">
            <svg width="44" height="44" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19 6h-2c0-2.76-2.24-5-5-5S7 3.24 7 6H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-7-3c1.66 0 3 1.34 3 3H9c0-1.66 1.34-3 3-3zm7 17H5V8h14v12zm-7-8c-1.66 0-3-1.34-3-3H7c0 2.76 2.24 5 5 5s5-2.24 5-5h-2c0 1.66-1.34 3-3 3z"/>
            </svg>
        </div>
        <h3 class="headline-lg text-muted mb-2">Sua sacola está vazia</h3>
        <p class="body-md text-secondary mb-4">Você ainda não reservou nenhum produto deste lote ou o tempo de reserva expirou.</p>
        <a href="/lojinha" class="btn-urna btn-urna-secondary d-inline-flex" style="height: 80px; padding: 0 40px; font-size: 22px; text-decoration: none;">
            <span>Explorar Vitrine de Produtos</span>
        </a>
    </div>
<?php else: ?>

    <!-- Caixa de Contagem Regressiva da Trava de 10 Minutos -->
    <div class="timer-trava-box" id="caixaTimer">
        <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24" style="flex-shrink: 0;">
            <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm4.2 14.2L11 13V7h1.5v5.2l4.5 2.7-.8 1.3z"/>
        </svg>
        <div>
            <span>Sua reserva temporária expira em: </span>
            <strong id="displayCronometro" style="font-size: 24px; font-family: monospace; letter-spacing: 1px; color: #B45309;">
                --:--
            </strong>
            <span class="d-none d-md-inline text-muted" style="font-size: 14px; font-weight: normal; margin-left: 10px;">
                (Ao expirar, os itens voltam automaticamente para a vitrine pública)
            </span>
        </div>
    </div>

    <div class="row g-4">
        <!-- Lista de Itens Reservados -->
        <div class="col-12 col-lg-8">
            <div style="background: #FFFFFF; border-radius: 16px; border: 2px solid #E5E7EB; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                <div style="padding: 16px 20px; background: #F8FAFC; border-bottom: 2px solid #E5E7EB; font-weight: 700; color: #475569;">
                    Itens em Separação Provisória
                </div>

                <div class="p-3">
                    <?php foreach ($itensCarrinho as $item): ?>
                        <div class="d-flex align-items-center justify-content-between p-3 mb-2" style="background: #F9FAFB; border-radius: 12px; border: 1px solid #E5E7EB; flex-wrap: wrap; gap: 12px;">
                            <div class="d-flex align-items-center gap-3">
                                <div style="width: 64px; height: 64px; background: #FFFFFF; border-radius: 8px; border: 1px solid #D1D5DB; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                    <?php if (!empty($item['foto_url']) && file_exists(PUBLIC_PATH . $item['foto_url'])): ?>
                                        <img src="<?= sanitize_html($item['foto_url']) ?>" alt="<?= sanitize_html($item['produto_nome']) ?>" style="max-width: 100%; max-height: 100%;">
                                    <?php else: ?>
                                        <svg width="32" height="32" fill="#9CA3AF" viewBox="0 0 24 24">
                                            <path d="M7 2v2h1v4.54c-.6.28-1 .88-1 1.46v9c0 1.66 1.34 3 3 3h4c1.66 0 3-1.34 3-3v-9c0-.58-.4-1.18-1-1.46V4h1V2H7zm8 15c0 .55-.45 1-1 1h-4c-.55 0-1-.45-1-1v-7h6v7zm-1-9h-4V4h4v4z"/>
                                        </svg>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <h4 class="mb-1" style="font-size: 18px; font-weight: 700; color: #111827;">
                                        <?= sanitize_html($item['produto_nome']) ?>
                                    </h4>
                                    <div class="text-muted" style="font-size: 13px;">
                                        Código: <strong><?= sanitize_html($item['produto_codigo']) ?></strong> | Embalagem: <?= sanitize_html($item['pacote_padrao']) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-4">
                                <div style="font-size: 20px; font-weight: 800; color: #111827;">
                                    <?= (int)$item['quantidade'] ?> un
                                </div>

                                <form method="POST" action="/lojinha/carrinho/remover" onsubmit="return confirm('Deseja liberar este item da sua sacola?');">
                                    <input type="hidden" name="carrinho_id" value="<?= (int)$item['carrinho_id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger" title="Remover item da sacola" style="border-radius: 8px; padding: 8px 12px;">
                                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="p-3 bg-light d-flex justify-content-between align-items-center border-top">
                    <form method="POST" action="/lojinha/carrinho/esvaziar" onsubmit="return confirm('Tem certeza que deseja liberar todos os itens da sua reserva?');">
                        <button type="submit" class="btn btn-link text-danger text-decoration-none" style="font-weight: 600; font-size: 14px;">
                            Esvaziar toda a sacola
                        </button>
                    </form>

                    <div style="font-size: 18px; font-weight: 700; color: #111827;">
                        Total na sacola: <span class="badge bg-primary" style="font-size: 18px;"><?= $totalItensCarrinho ?> itens</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Painel de Fechamento do Pedido (Checkout) -->
        <div class="col-12 col-lg-4">
            <div style="background: #FFFFFF; border-radius: 16px; border: 2px solid #E5E7EB; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); position: sticky; top: 90px;">
                <h3 class="headline-lg mb-3" style="font-size: 22px;">Resumo do Pedido</h3>

                <div class="d-flex justify-content-between mb-2" style="font-size: 16px;">
                    <span class="text-muted">Itens nesta sacola:</span>
                    <strong><?= $totalItensCarrinho ?> itens</strong>
                </div>

                <div class="d-flex justify-content-between mb-2" style="font-size: 16px;">
                    <span class="text-muted">Já comprados neste lote:</span>
                    <strong><?= (int)$resumo['itens_comprados'] ?> itens</strong>
                </div>

                <div class="d-flex justify-content-between mb-3 pb-3" style="font-size: 16px; border-bottom: 2px dashed #E5E7EB;">
                    <span class="text-muted">Limite máximo individual:</span>
                    <strong><?= (int)$resumo['limite_lote'] ?> itens</strong>
                </div>

                <div class="alert alert-info py-2 px-3 mb-4" style="border-radius: 10px; font-size: 14px;">
                    <strong>Retirada física:</strong> Na bancada de Repack após conferência do comprovante.
                </div>

                <!-- Formulário de Finalização Transacional (Checkout) -->
                <form method="POST" action="/lojinha/checkout" id="formCheckout">
                    <!-- Botão Confirmar Gigante (80px - Verde Primary) -->
                    <button type="submit" 
                            class="btn-urna btn-urna-primary w-100" 
                            id="btnFinalizarPedido"
                            style="height: 80px; font-size: 24px; border-radius: 16px;">
                        <svg width="28" height="28" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                        </svg>
                        <span>Finalizar Pedido</span>
                    </button>
                </form>

                <div class="text-center mt-3">
                    <a href="/lojinha" class="text-muted text-decoration-none" style="font-weight: 600; font-size: 14px;">
                        Continuar adicionando itens
                    </a>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<script>
// Cronômetro regressivo da Trava Temporária de 10 minutos
let segundosRestantes = <?= $segundosRestantes ?>;

function atualizarCronometro() {
    if (segundosRestantes <= 0) {
        document.getElementById('displayCronometro').innerText = '00:00 (Expirado)';
        document.getElementById('caixaTimer').style.background = '#FEE2E2';
        document.getElementById('caixaTimer').style.borderColor = '#EF4444';
        document.getElementById('caixaTimer').style.color = '#B91C1C';
        
        const btnFinalizar = document.getElementById('btnFinalizarPedido');
        if (btnFinalizar) {
            btnFinalizar.disabled = true;
        }

        alert('O tempo de reserva temporária de 10 minutos expirou. Os produtos retornaram à vitrine.');
        window.location.reload();
        return;
    }

    const min = Math.floor(segundosRestantes / 60);
    const seg = segundosRestantes % 60;
    const formatado = String(min).padStart(2, '0') + ':' + String(seg).padStart(2, '0');

    const display = document.getElementById('displayCronometro');
    if (display) {
        display.innerText = formatado;
    }

    segundosRestantes--;
    setTimeout(atualizarCronometro, 1000);
}

if (segundosRestantes > 0) {
    atualizarCronometro();
}

document.getElementById('formCheckout')?.addEventListener('submit', function() {
    const btn = document.getElementById('btnFinalizarPedido');
    btn.disabled = true;
    btn.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        <span>Gravando Pedido...</span>
    `;
});
</script>

<?php require __DIR__ . '/../layouts/lojinha_footer.php'; ?>
