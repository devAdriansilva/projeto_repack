<?php
declare(strict_types=1);

/**
 * Projeto Repack - Tela de Alteração Voluntária de PIN do Colaborador
 * Baseado estritamente em docs/FSD.md e docs/DESIGN.md
 */

$pageTitle = 'Alterar PIN de Compras - Repack';
require_once __DIR__ . '/../layouts/lojinha_header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm border-0" style="border-radius: 20px;">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.12); color: #10B981; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                        🔒
                    </div>
                    <div>
                        <h2 class="h4 mb-0 fw-bold" style="color: #0F172A;">Alterar Meu PIN de Compras</h2>
                        <small class="text-muted">Proteja sua cota com uma combinação numérica de 4 dígitos</small>
                    </div>
                </div>

                <?php if (!empty($erro)): ?>
                    <div class="alert alert-danger mb-4 d-flex align-items-center" role="alert" style="border-radius: 12px; font-weight: 600; font-size: 14px;">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" class="me-2 flex-shrink-0">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                        </svg>
                        <div><?= sanitize_html($erro) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= APP_URL ?>/lojinha/alterar-pin">
                    <input type="hidden" name="csrf_token" value="<?= AuthController::gerarCsrfToken() ?>">

                    <?php if (!empty($usuario['pin_hash'])): ?>
                        <div class="mb-3">
                            <label for="pin_atual" class="form-label fw-bold text-dark">PIN Atual (4 dígitos)</label>
                            <input type="password" 
                                   class="form-control text-center" 
                                   id="pin_atual" 
                                   name="pin_atual" 
                                   maxlength="4" 
                                   inputmode="numeric" 
                                   pattern="[0-9]{4}" 
                                   placeholder="••••" 
                                   required 
                                   autofocus
                                   style="height: 56px; font-size: 24px; letter-spacing: 8px; border-radius: 12px;">
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="novo_pin" class="form-label fw-bold text-dark">Novo PIN (4 dígitos)</label>
                        <input type="password" 
                               class="form-control text-center" 
                               id="novo_pin" 
                               name="novo_pin" 
                               maxlength="4" 
                               inputmode="numeric" 
                               pattern="[0-9]{4}" 
                               placeholder="••••" 
                               required
                               style="height: 56px; font-size: 24px; letter-spacing: 8px; border-radius: 12px;">
                    </div>

                    <div class="mb-4">
                        <label for="novo_pin_confirmacao" class="form-label fw-bold text-dark">Confirme o Novo PIN</label>
                        <input type="password" 
                               class="form-control text-center" 
                               id="novo_pin_confirmacao" 
                               name="novo_pin_confirmacao" 
                               maxlength="4" 
                               inputmode="numeric" 
                               pattern="[0-9]{4}" 
                               placeholder="••••" 
                               required
                               style="height: 56px; font-size: 24px; letter-spacing: 8px; border-radius: 12px;">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success fw-bold py-3" style="font-size: 16px; border-radius: 12px; background: #10B981; border: none;">
                            Atualizar PIN de Compras
                        </button>
                        <a href="<?= APP_URL ?>/lojinha" class="btn btn-outline-secondary fw-bold py-2 mt-2" style="border-radius: 12px;">
                            Cancelar e Voltar à Vitrine
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/lojinha_footer.php'; ?>
