<?php
declare(strict_types=1);

/**
 * Projeto Repack - Tela de Identificação e Controle de PIN da Lojinha dos Colaboradores
 * Baseado estritamente em docs/FSD.md (Seções 12.2, 13.3 e 15) e docs/DESIGN.md
 */

$pageTitle = 'Acesso à Lojinha dos Colaboradores - Repack';
$etapa = $etapa ?? 'identificar';
$matricula = $matricula ?? '';
$nomeColaborador = $nomeColaborador ?? '';
$setorColaborador = $setorColaborador ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#10B981">
    <title><?= sanitize_html($pageTitle) ?></title>
    
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/img/logo-icon.svg?v=<?= APP_VERSION ?>">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/urna.css">
    <style>
        .pin-digit-input {
            height: 68px;
            font-size: 32px;
            font-weight: 800;
            letter-spacing: 12px;
            text-align: center;
            border-radius: 14px;
            border: 2px solid #CBD5E1;
            background: #F8FAFC;
            transition: all 0.2s ease;
        }
        .pin-digit-input:focus {
            border-color: #10B981;
            background: #FFFFFF;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2);
            outline: none;
        }
    </style>
</head>
<body style="background-color: #F1F5F9; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

<div style="width: 100%; max-width: 480px;">
    <!-- Logotipo Integrado da Lojinha -->
    <div class="text-center mb-4">
        <div style="display: inline-flex; align-items: center; justify-content: center; margin-bottom: 14px;">
            <img src="<?= APP_URL ?>/assets/img/logo-repack-dark.svg?v=<?= APP_VERSION ?>" alt="Logo Repack" height="60" style="filter: drop-shadow(0 4px 10px rgba(16,185,129,0.25));">
        </div>
        <h1 style="color: #0F172A; font-size: 26px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 4px;">Lojinha dos Colaboradores</h1>
        <p style="color: #64748B; font-size: 14.5px; margin: 0; font-weight: 500;">Acesso exclusivo para compra interna de avarias leves</p>
    </div>

    <!-- Card de Identificação e PIN -->
    <div style="background: #FFFFFF; border-radius: 22px; border: 2px solid #E2E8F0; padding: 32px 28px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.06);">
        
        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger mb-4 d-flex align-items-center" role="alert" style="border-radius: 12px; font-weight: 600; font-size: 14px; background: #FEF2F2; border: 1.5px solid #FCA5A5; color: #991B1B;">
                <svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24" class="me-2 flex-shrink-0">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
                <div><?= sanitize_html($erro) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($aviso)): ?>
            <div class="alert alert-warning mb-4 d-flex align-items-center" role="alert" style="border-radius: 12px; font-weight: 600; font-size: 14px; background: #FFFBEB; border: 1.5px solid #FCD34D; color: #92400E;">
                <svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24" class="me-2 flex-shrink-0">
                    <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
                </svg>
                <div><?= sanitize_html($aviso) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($etapa === 'identificar'): ?>
            <!-- ETAPA 1: INFORME DA MATRÍCULA -->
            <form method="POST" action="<?= APP_URL ?>/lojinha/login" id="formLoginLojinha">
                <input type="hidden" name="acao" value="identificar">

                <div class="mb-4">
                    <label for="inputMatricula" class="form-label" style="font-weight: 700; font-size: 17px; color: #0F172A; margin-bottom: 8px;">
                        Sua Matrícula Corporativa
                    </label>
                    <div class="input-group">
                        <span class="input-group-text" style="background: #F8FAFC; border: 2px solid #CBD5E1; border-right: none; border-radius: 14px 0 0 14px;">
                            <svg width="24" height="24" fill="#64748B" viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        </span>
                        <input type="text" 
                               id="inputMatricula" 
                               name="matricula" 
                               class="form-control" 
                               placeholder="Ex: COLAB01" 
                               value="<?= sanitize_html($matricula) ?>"
                               required 
                               autofocus
                               style="height: 64px; font-size: 20px; font-weight: 700; border: 2px solid #CBD5E1; border-left: none; border-radius: 0 14px 14px 0; text-transform: uppercase;">
                    </div>
                    <div class="form-text mt-2" style="font-size: 13.5px; color: #64748B;">
                        O sistema validará automaticamente os lotes e cotas disponíveis para seu setor.
                    </div>
                </div>

                <button type="submit" 
                        class="btn-urna btn-urna-primary w-100" 
                        id="btnAcessarLojinha"
                        style="height: 76px; font-size: 22px; border-radius: 14px;">
                    <svg width="26" height="26" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/>
                    </svg>
                    <span>Avançar</span>
                </button>
            </form>

        <?php elseif ($etapa === 'criar_pin'): ?>
            <!-- ETAPA 2A: CRIAÇÃO DO PRIMEIRO PIN (AUTOCADASTRO GUIADO) -->
            <div class="mb-3 text-center p-3 rounded-3" style="background: #ECFDF5; border: 1.5px solid #A7F3D0;">
                <div style="font-size: 13px; font-weight: 700; color: #065F46; text-transform: uppercase; letter-spacing: 0.5px;">Primeiro Acesso</div>
                <div style="font-size: 18px; font-weight: 800; color: #047857; margin-top: 2px;">
                    Olá, <?= sanitize_html($nomeColaborador) ?>!
                </div>
                <div style="font-size: 13px; color: #065F46; margin-top: 2px;">
                    Setor: <strong><?= sanitize_html($setorColaborador) ?></strong> (Matrícula: <?= sanitize_html($matricula) ?>)
                </div>
            </div>

            <p style="font-size: 14px; color: #475569; text-align: center; margin-bottom: 20px;">
                Para proteger suas cotas de compra, <strong>crie seu PIN numérico de 4 dígitos</strong>:
            </p>

            <form method="POST" action="<?= APP_URL ?>/lojinha/login" id="formCriarPin">
                <input type="hidden" name="acao" value="salvar_pin">
                <input type="hidden" name="matricula" value="<?= sanitize_html($matricula) ?>">

                <div class="mb-3">
                    <label for="inputNovoPin" class="form-label" style="font-weight: 700; font-size: 14px; color: #0F172A;">
                        Digite o seu novo PIN (4 números)
                    </label>
                    <input type="password" 
                           id="inputNovoPin" 
                           name="pin" 
                           class="form-control pin-digit-input" 
                           maxlength="4" 
                           inputmode="numeric" 
                           pattern="[0-9]{4}" 
                           placeholder="••••" 
                           required 
                           autofocus>
                </div>

                <div class="mb-4">
                    <label for="inputConfirmaPin" class="form-label" style="font-weight: 700; font-size: 14px; color: #0F172A;">
                        Confirme o seu PIN (digite novamente)
                    </label>
                    <input type="password" 
                           id="inputConfirmaPin" 
                           name="pin_confirmacao" 
                           class="form-control pin-digit-input" 
                           maxlength="4" 
                           inputmode="numeric" 
                           pattern="[0-9]{4}" 
                           placeholder="••••" 
                           required>
                </div>

                <button type="submit" 
                        class="btn-urna btn-urna-primary w-100" 
                        id="btnSalvarPin"
                        style="height: 76px; font-size: 21px; border-radius: 14px; background: #059669;">
                    <span>Salvar PIN e Acessar Vitrine</span>
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="<?= APP_URL ?>/lojinha/login" style="font-size: 13.5px; color: #64748B; text-decoration: none; font-weight: 600;">
                    ← Informar outra matrícula
                </a>
            </div>

        <?php elseif ($etapa === 'digitar_pin'): ?>
            <!-- ETAPA 2B: INFORMAR O PIN DE 4 DÍGITOS JÁ CADASTRADO -->
            <div class="mb-3 text-center p-3 rounded-3" style="background: #F8FAFC; border: 1.5px solid #E2E8F0;">
                <div style="font-size: 18px; font-weight: 800; color: #0F172A;">
                    Olá, <?= sanitize_html($nomeColaborador) ?>!
                </div>
                <div style="font-size: 13px; color: #64748B; margin-top: 2px;">
                    Setor: <strong><?= sanitize_html($setorColaborador) ?></strong> (Matrícula: <?= sanitize_html($matricula) ?>)
                </div>
            </div>

            <form method="POST" action="<?= APP_URL ?>/lojinha/login" id="formAutenticarPin">
                <input type="hidden" name="acao" value="autenticar">
                <input type="hidden" name="matricula" value="<?= sanitize_html($matricula) ?>">

                <div class="mb-4">
                    <label for="inputPinLogin" class="form-label" style="font-weight: 700; font-size: 15px; color: #0F172A; text-align: center; display: block;">
                        Digite seu PIN de 4 dígitos
                    </label>
                    <input type="password" 
                           id="inputPinLogin" 
                           name="pin" 
                           class="form-control pin-digit-input" 
                           maxlength="4" 
                           inputmode="numeric" 
                           pattern="[0-9]{4}" 
                           placeholder="••••" 
                           required 
                           autofocus>
                </div>

                <button type="submit" 
                        class="btn-urna btn-urna-primary w-100" 
                        id="btnConfirmarPin"
                        style="height: 76px; font-size: 22px; border-radius: 14px;">
                    <span>Entrar na Vitrine</span>
                </button>
            </form>

            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top" style="font-size: 13px;">
                <a href="<?= APP_URL ?>/lojinha/login" style="color: #64748B; text-decoration: none; font-weight: 600;">
                    ← Trocar matrícula
                </a>
                <span style="color: #94A3B8;">•</span>
                <span style="color: #64748B;">Esqueceu o PIN? Procure a Gestão.</span>
            </div>
        <?php endif; ?>

    </div>

    <!-- Navegação de Retorno ao Repack -->
    <div class="text-center mt-4">
        <a href="<?= APP_URL ?: '/' ?>" style="color: #64748B; text-decoration: none; font-size: 14px; font-weight: 600; transition: color 0.15s;" onmouseover="this.style.color='#0F172A'" onmouseout="this.style.color='#64748B'">
            ← Retornar à Bancada de Repack
        </a>
    </div>
</div>

<script src="<?= APP_URL ?>/assets/js/touch-handler.js"></script>
</body>
</html>
