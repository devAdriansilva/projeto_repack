<?php
declare(strict_types=1);

/**
 * Projeto Repack - Tela Dedicada de Login da Gestão Administrativa
 * Baseado estritamente em docs/FSD.md (Seção 15) e docs/DESIGN.md
 */

$pageTitle = 'Login da Gestão - Projeto Repack';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0F172A">
    <title><?= sanitize_html($pageTitle) ?></title>
    
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/urna.css">
    <link rel="icon" type="image/x-icon" href="<?= APP_URL ?>/favicon.ico">
    <link rel="apple-touch-icon" href="<?= APP_URL ?>/assets/img/apple-touch-icon.png">
</head>
<body style="background: radial-gradient(circle at top, #1E293B 0%, #0F172A 60%, #070B14 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">

<div style="width: 100%; max-width: 440px;">
    <!-- Logotipo Oficial Integrada ([R] + EPACK) -->
    <div class="text-center mb-4">
        <div style="display: inline-flex; align-items: center; justify-content: center; margin-bottom: 14px;">
            <img src="<?= APP_URL ?>/assets/img/logo-repack.svg?v=<?= APP_VERSION ?>" alt="Logo Repack" height="52" style="filter: drop-shadow(0 6px 16px rgba(0,0,0,0.6));">
        </div>
        <h1 style="color: #F8FAFC; font-size: 24px; font-weight: 800; letter-spacing: -0.3px; margin-bottom: 6px;">Painel de Gestão</h1>
        <p style="color: #94A3B8; font-size: 14px; margin: 0; font-weight: 500;">Acesso restrito à Coordenação e Administração</p>
    </div>

    <!-- Card de Autenticação -->
    <div style="background: #0F172A; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.12); padding: 32px 28px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5);">
        
        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger mb-4 d-flex align-items-center" role="alert" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #FCA5A5; border-radius: 12px; font-size: 13.5px; font-weight: 600; padding: 12px 14px;">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" class="me-2 flex-shrink-0">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
                <div><?= sanitize_html($erro) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($aviso)): ?>
            <div class="alert alert-warning mb-4 d-flex align-items-center" role="alert" style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.4); color: #FCD34D; border-radius: 12px; font-size: 13.5px; font-weight: 600; padding: 12px 14px;">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" class="me-2 flex-shrink-0">
                    <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
                </svg>
                <div><?= sanitize_html($aviso) ?></div>
            </div>
        <?php endif; ?>

        <form action="<?= APP_URL ?>/gestao/login" method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= AuthController::gerarCsrfToken() ?>">

            <!-- Campo Matrícula -->
            <div class="mb-3">
                <label for="matricula" style="color: #CBD5E1; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">Matrícula do Gestor</label>
                <div class="input-group">
                    <span class="input-group-text" style="background: rgba(255, 255, 255, 0.05); border: 1.5px solid rgba(255, 255, 255, 0.2); border-right: none; color: #94A3B8; border-radius: 12px 0 0 12px;">👤</span>
                    <input type="text" 
                           class="form-control" 
                           id="matricula" 
                           name="matricula" 
                           placeholder="Ex: GEST01" 
                           value="<?= sanitize_html($matricula ?? '') ?>" 
                           required 
                           autofocus
                           style="background: rgba(255, 255, 255, 0.08); border: 1.5px solid rgba(255, 255, 255, 0.2); color: #FFFFFF; font-size: 16px; font-weight: 600; border-radius: 0 12px 12px 0; padding: 12px 14px;">
                </div>
            </div>

            <!-- Campo Senha -->
            <div class="mb-4">
                <label for="senha" style="color: #CBD5E1; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">Senha de Acesso</label>
                <div class="input-group">
                    <span class="input-group-text" style="background: rgba(255, 255, 255, 0.05); border: 1.5px solid rgba(255, 255, 255, 0.2); border-right: none; color: #94A3B8; border-radius: 12px 0 0 12px;">🔒</span>
                    <input type="password" 
                           class="form-control" 
                           id="senha" 
                           name="senha" 
                           placeholder="Digite sua senha" 
                           required
                           style="background: rgba(255, 255, 255, 0.08); border: 1.5px solid rgba(255, 255, 255, 0.2); color: #FFFFFF; font-size: 16px; font-weight: 600; border-radius: 0 12px 12px 0; padding: 12px 14px;">
                </div>
            </div>

            <!-- Botão Entrar -->
            <button type="submit" 
                    class="btn w-100" 
                    style="background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%); color: #FFFFFF; font-size: 16px; font-weight: 800; padding: 14px; border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35); transition: all 0.2s;">
                Acessar Painel de Gestão
            </button>
        </form>

        <div class="text-center mt-3" style="font-size: 12px; color: #64748B;">
            Ambiente Seguro • Autenticação Criptografada
        </div>
    </div>

    <!-- Navegação Secundária -->
    <div class="text-center mt-4">
        <a href="<?= APP_URL ?: '/' ?>" style="color: #94A3B8; text-decoration: none; font-size: 13.5px; font-weight: 600; transition: color 0.15s;" onmouseover="this.style.color='#FFFFFF'" onmouseout="this.style.color='#94A3B8'">
            ← Retornar à Bancada de Repack (Operador)
        </a>
    </div>

    <!-- Rodapé Institucional -->
    <footer class="text-center mt-4">
        <p style="color: #475569; font-size: 12px; margin: 0;">
            Projeto Repack <?= APP_VERSION ?> • Desenvolvido por <strong>AdrianSilvaDev</strong>
        </p>
    </footer>
</div>

</body>
</html>
