<?php
declare(strict_types=1);

/**
 * Projeto Repack - Layout Header da Lojinha Virtual (Colaborador)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md
 */

$nomeColab = $_SESSION['colaborador_nome'] ?? '';
$setorColab = $_SESSION['colaborador_setor'] ?? '';
$matriculaColab = $_SESSION['colaborador_matricula'] ?? '';
$itensCarrinhoCount = $resumo['itens_carrinho'] ?? 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#10B981">
    <title><?= sanitize_html($pageTitle ?? 'Lojinha dos Colaboradores - Repack') ?></title>
    
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/img/logo-icon.svg?v=<?= APP_VERSION ?>">
    <link rel="icon" type="image/png" sizes="64x64" href="<?= APP_URL ?>/assets/img/favicon.png?v=<?= APP_VERSION ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= APP_URL ?>/assets/img/apple-touch-icon.png?v=<?= APP_VERSION ?>">
    <link rel="shortcut icon" href="<?= APP_URL ?>/assets/img/favicon.png?v=<?= APP_VERSION ?>">
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json?v=<?= APP_VERSION ?>">
    
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/urna.css">
    <style>
        .lojinha-navbar {
            background-color: #FFFFFF;
            border-bottom: 2px solid #E5E7EB;
            padding: 10px 16px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .lojinha-navbar-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        .lojinha-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #111827;
        }
        .lojinha-brand-title {
            font-size: 15px;
            font-weight: 700;
            color: #475569;
            border-color: #E2E8F0 !important;
        }
        .lojinha-user-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .lojinha-user-badge {
            display: flex;
            flex-direction: column;
            text-align: right;
            font-size: 13px;
            color: #4B5563;
            line-height: 1.25;
        }
        .lojinha-user-name {
            font-weight: 700;
            color: #111827;
            font-size: 14px;
        }
        .btn-sacola-header {
            background-color: #2563EB;
            color: #FFFFFF;
            border: none;
            border-radius: 12px;
            padding: 8px 14px;
            font-size: 15px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: background 0.2s;
            position: relative;
        }
        .btn-sacola-header:hover {
            background-color: #1D4ED8;
            color: #FFFFFF;
        }
        .sacola-counter {
            background: #EF4444;
            color: #FFFFFF;
            font-size: 12px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 9999px;
            margin-left: 2px;
        }
        .btn-gestao-header {
            background: #1F2937;
            color: #FFFFFF;
            font-weight: 700;
            border-radius: 10px;
            padding: 7px 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1.5px solid #374151;
            font-size: 13.5px;
        }
        .btn-sair-header {
            background: transparent;
            color: #6B7280;
            border: 1px solid #D1D5DB;
            border-radius: 8px;
            padding: 7px 11px;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-sair-header:hover {
            background: #F3F4F6;
            color: #111827;
        }
        .lojinha-subbar-user {
            justify-content: space-between;
            align-items: center;
            padding-top: 8px;
            padding-bottom: 2px;
            border-top: 1px dashed #E2E8F0;
            font-size: 15px;
            color: #334155;
            width: 100%;
        }
        .btn-link-pin {
            font-size: 14px;
            font-weight: 800;
            color: #2563EB;
            text-decoration: none;
            padding: 5px 12px;
            background: #EFF6FF;
            border-radius: 8px;
            border: 1px solid #BFDBFE;
            white-space: nowrap;
        }
        .banner-lote {
            background: linear-gradient(135deg, #1E293B, #0F172A);
            color: #FFFFFF;
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .banner-cota {
            background: rgba(255, 255, 255, 0.12);
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .card-produto-lojinha {
            background: #FFFFFF;
            border-radius: 16px;
            border: 2px solid #E2E8F0;
            padding: 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        }
        .card-produto-lojinha:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
            border-color: #CBD5E1;
        }
        .card-produto-lojinha.esgotado {
            opacity: 0.6;
            background: #F9FAFB;
            filter: grayscale(0.8);
        }
        .card-img-container {
            width: 100%;
            height: 170px;
            background: #F8FAFC;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 12px;
            position: relative;
        }
        .card-img-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .card-badge-estoque {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #10B981;
            color: #FFFFFF;
            font-weight: 800;
            font-size: 14px;
            padding: 4px 12px;
            border-radius: 9999px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 2;
        }
        .card-badge-estoque.baixo {
            background: #F59E0B;
        }
        .card-badge-estoque.esgotado {
            background: #6B7280;
        }
        .badge-master {
            background: #1E1B4B;
            color: #FDE68A;
            border: 1.5px solid #818CF8;
            font-size: 12.5px;
            padding: 3px 8px;
        }
        .card-produto-titulo {
            font-size: 19px;
            font-weight: 800;
            color: #000000;
            margin-bottom: 6px;
            line-height: 1.3;
            letter-spacing: -0.3px;
            min-height: 48px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .badge-no-carrinho {
            font-size: 13.5px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
        }
        .timer-trava-box {
            background: #FEF3C7;
            border: 2px solid #F59E0B;
            color: #92400E;
            padding: 14px 20px;
            border-radius: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 17px;
            margin-bottom: 20px;
        }
        .btn-urna-disabled {
            background: #E2E8F0;
            color: #64748B;
            border: 2px solid #CBD5E1;
            cursor: not-allowed;
            font-size: 18px;
            font-weight: 700;
        }

        /* ========================================================= */
        /* CONTAINER PRINCIPAL ADAPTATIVO (APROVEITAMENTO TOTAL)     */
        /* ========================================================= */
        .lojinha-main-container {
            width: 100%;
            max-width: 1440px;
            margin-left: auto;
            margin-right: auto;
            padding-left: 6px;
            padding-right: 6px;
            margin-top: 10px;
            margin-bottom: 24px;
        }
        @media (min-width: 768px) {
            .lojinha-main-container {
                padding-left: 20px;
                padding-right: 20px;
                margin-top: 24px;
                margin-bottom: 32px;
            }
        }
        @media (min-width: 1200px) {
            .lojinha-main-container {
                padding-left: 32px;
                padding-right: 32px;
            }
        }

        /* ========================================================= */
        /* OTIMIZAÇÃO ESPECIAL PARA TELAS MÓVEIS (MOBILE 2 COLUNAS)  */
        /* ========================================================= */
        @media (max-width: 768px) {
            .card-produto-lojinha {
                padding: 8px 8px 12px 8px !important;
                border-radius: 14px !important;
            }
            .card-img-container {
                height: 145px !important;
                margin-bottom: 8px !important;
                border-radius: 10px !important;
            }
            .card-badge-estoque {
                top: 6px !important;
                right: 6px !important;
                font-size: 13.5px !important;
                font-weight: 800 !important;
                padding: 3.5px 9px !important;
            }
            .badge-master {
                font-size: 12.5px !important;
                padding: 2.5px 6px !important;
            }
            .badge-no-carrinho {
                font-size: 14px !important;
                font-weight: 800 !important;
                padding: 4px 8px !important;
            }
            .card-produto-titulo {
                font-size: 18px !important;
                font-weight: 800 !important;
                color: #000000 !important;
                line-height: 1.3 !important;
                letter-spacing: -0.3px !important;
                min-height: 48px !important;
                margin-bottom: 6px !important;
            }
            .btn-urna {
                height: 56px !important;
                font-size: 18.5px !important;
                font-weight: 900 !important;
                border-radius: 10px !important;
                padding: 0 4px !important;
            }
            .btn-urna-disabled {
                font-size: 17.5px !important;
                font-weight: 800 !important;
            }
            .banner-lote {
                padding: 16px !important;
                border-radius: 14px !important;
                margin-bottom: 12px !important;
            }
            .banner-lote h2 {
                font-size: 22px !important;
                font-weight: 800 !important;
            }
            .banner-cota {
                width: 100% !important;
                text-align: center;
                font-size: 16px !important;
                font-weight: 700 !important;
                padding: 10px 14px !important;
            }
            .alert-pedido-confirmado {
                padding: 18px !important;
                border-radius: 14px !important;
            }
            .btn-ver-comprovante {
                width: 100% !important;
                text-align: center;
                padding: 14px 18px !important;
                font-size: 17.5px !important;
                font-weight: 800 !important;
            }
            .timer-trava-box {
                padding: 14px 18px !important;
                font-size: 16.5px !important;
                border-radius: 10px !important;
                margin-bottom: 12px !important;
            }
        }
    </style>
</head>
<body style="background-color: #F3F4F6; min-height: 100vh;">

<?php if (!empty($nomeColab)): ?>
<!-- Barra de Navegação Superior da Lojinha -->
<nav class="lojinha-navbar">
    <div class="lojinha-navbar-top">
        <a href="<?= APP_URL ?>/lojinha" class="lojinha-brand">
            <img src="<?= APP_URL ?>/assets/img/logo-repack-dark.svg?v=<?= APP_VERSION ?>" alt="Logo Repack" height="36" class="img-logo-lojinha">
            <span class="border-start ps-2.5 ms-1 d-none d-lg-inline lojinha-brand-title">Lojinha dos Colaboradores</span>
        </a>

        <div class="lojinha-user-info">
            <div class="lojinha-user-badge d-none d-lg-flex">
                <div>
                    <?php 
                    $tipoAcessoColab = $_SESSION['colaborador_tipo_acesso'] ?? 'comum';
                    if ($tipoAcessoColab === 'master'): ?>
                        <span class="badge bg-warning text-dark me-1" style="font-size: 11px; font-weight: 800;">👑 ACESSO MASTER</span>
                    <?php elseif ($tipoAcessoColab === 'setorial'): ?>
                        <span class="badge bg-info text-dark me-1" style="font-size: 11px; font-weight: 800;">🏢 CONTA SETORIAL</span>
                    <?php endif; ?>
                    <span class="lojinha-user-name"><?= sanitize_html($nomeColab) ?></span>
                </div>
                <span>Setor: <?= sanitize_html($setorColab) ?> (<?= sanitize_html($matriculaColab) ?>)</span>
            </div>

            <a href="<?= APP_URL ?>/lojinha/carrinho" class="btn-sacola-header" aria-label="Meu Carrinho">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                </svg>
                <span>Carrinho</span>
                <span class="sacola-counter" id="headerCarrinhoContador"><?= (int)$itensCarrinhoCount ?></span>
            </a>

            <?php if (!empty($_SESSION['usuario_id']) && ($_SESSION['perfil'] ?? '') === 'gestao'): ?>
                <a href="<?= APP_URL ?>/gestao/dashboard" class="btn btn-sm btn-gestao-header" title="Retornar ao Painel de Gestão">
                    <span>⚙️</span>
                    <span class="d-none d-sm-inline">Gestão</span>
                </a>
            <?php endif; ?>

            <a href="<?= APP_URL ?>/lojinha/alterar-pin" class="btn-sair-header d-none d-lg-inline-flex align-items-center gap-1" title="Alterar meu PIN de compras">
                <span>🔒</span>
                <span>Alterar PIN</span>
            </a>

            <a href="<?= APP_URL ?>/lojinha/logout" class="btn-sair-header" title="Sair da Lojinha">
                Sair
            </a>
        </div>
    </div>

    <!-- Sub-barra limpa para Mobile / Tablet: identificação do colaborador e atalho de PIN -->
    <div class="lojinha-subbar-user d-flex d-lg-none">
        <div class="d-flex align-items-center gap-1.5 flex-wrap">
            <span style="font-size: 13.5px;">👤</span>
            <strong class="text-dark"><?= sanitize_html($nomeColab) ?></strong>
            <span class="text-muted">&bull;</span>
            <span class="text-secondary"><?= sanitize_html($setorColab) ?> (<?= sanitize_html($matriculaColab) ?>)</span>
            <?php 
            $tipoAcessoColab = $_SESSION['colaborador_tipo_acesso'] ?? 'comum';
            if ($tipoAcessoColab === 'master'): ?>
                <span class="badge bg-warning text-dark ms-1" style="font-size: 10px; font-weight: 800;">👑 MASTER</span>
            <?php elseif ($tipoAcessoColab === 'setorial'): ?>
                <span class="badge bg-info text-dark ms-1" style="font-size: 10px; font-weight: 800;">🏢 SETORIAL</span>
            <?php endif; ?>
        </div>
        <div>
            <a href="<?= APP_URL ?>/lojinha/alterar-pin" class="btn-link-pin" title="Alterar meu PIN de compras">
                🔒 Alterar PIN
            </a>
        </div>
    </div>
</nav>
<?php endif; ?>

<main class="lojinha-main-container">
