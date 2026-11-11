<?php
declare(strict_types=1);

/**
 * Projeto Repack - Tela de Identificação do Colaborador (Lojinha)
 * Baseado estritamente em docs/FSD.md (Seções 12.2 e 15) e docs/DESIGN.md
 */

$pageTitle = 'Acesso à Lojinha dos Colaboradores - Repack';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#10B981">
    <title><?= sanitize_html($pageTitle) ?></title>
    
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/urna.css">
</head>
<body style="background-color: #F3F4F6; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">

<div style="width: 100%; max-width: 480px;">
    <!-- Logotipo / Cabeçalho da Lojinha -->
    <div class="text-center mb-4">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 72px; height: 72px; background: #10B981; border-radius: 20px; color: #FFFFFF; margin-bottom: 16px; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.4);">
            <svg width="40" height="40" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19 6h-2c0-2.76-2.24-5-5-5S7 3.24 7 6H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-7-3c1.66 0 3 1.34 3 3H9c0-1.66 1.34-3 3-3zm7 17H5V8h14v12zm-7-8c-1.66 0-3-1.34-3-3H7c0 2.76 2.24 5 5 5s5-2.24 5-5h-2c0 1.66-1.34 3-3 3z"/>
            </svg>
        </div>
        <h1 class="headline-lg" style="margin-bottom: 6px;">Lojinha dos Colaboradores</h1>
        <p class="body-md text-muted">Acesso exclusivo para compra interna de avarias leves</p>
    </div>

    <!-- Card de Identificação -->
    <div style="background: #FFFFFF; border-radius: 20px; border: 2px solid #E5E7EB; padding: 32px 28px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);">
        
        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger mb-4" role="alert" style="border-radius: 12px; font-weight: 600;">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" class="me-2" style="vertical-align: sub;">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
                <?= sanitize_html($erro) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($aviso)): ?>
            <div class="alert alert-warning mb-4" role="alert" style="border-radius: 12px; font-weight: 600;">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" class="me-2" style="vertical-align: sub;">
                    <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
                </svg>
                <?= sanitize_html($aviso) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= APP_URL ?>/lojinha/login" id="formLoginLojinha">
            <div class="mb-4">
                <label for="inputMatricula" class="form-label" style="font-weight: 700; font-size: 18px; color: #111827;">
                    Sua Matrícula Corporativa
                </label>
                <div class="input-group">
                    <span class="input-group-text" style="background: #F3F4F6; border: 2px solid #D1D5DB; border-right: none; border-radius: 12px 0 0 12px;">
                        <svg width="24" height="24" fill="#6B7280" viewBox="0 0 24 24">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    </span>
                    <input type="text" 
                           id="inputMatricula" 
                           name="matricula" 
                           class="form-control" 
                           placeholder="Ex: COLAB01" 
                           required 
                           autofocus
                           style="height: 64px; font-size: 22px; font-weight: 700; border: 2px solid #D1D5DB; border-left: none; border-radius: 0 12px 12px 0; text-transform: uppercase;">
                </div>
                <div class="form-text mt-2" style="font-size: 14px;">
                    O sistema verificará automaticamente os lotes abertos para o seu setor.
                </div>
            </div>

            <!-- Botão de Acesso Gigante (80px - Filosofia Urna Eletrônica) -->
            <button type="submit" 
                    class="btn-urna btn-urna-primary w-100" 
                    id="btnAcessarLojinha"
                    style="height: 80px; font-size: 24px; border-radius: 16px;">
                <svg width="28" height="28" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/>
                </svg>
                <span>Acessar Vitrine</span>
            </button>
        </form>

        <div class="text-center mt-4 pt-3" style="border-top: 1px solid #E5E7EB;">
            <a href="<?= APP_URL ?: '/' ?>" class="text-decoration-none text-muted" style="font-weight: 600; font-size: 15px;">
                ← Voltar à Urna Eletrônica do Repack
            </a>
        </div>
    </div>
</div>

<script src="<?= APP_URL ?>/assets/js/touch-handler.js"></script>
<script>
document.getElementById('formLoginLojinha').addEventListener('submit', function() {
    const btn = document.getElementById('btnAcessarLojinha');
    btn.disabled = true;
    btn.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        <span>Identificando...</span>
    `;
});
</script>
</body>
</html>
