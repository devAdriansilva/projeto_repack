<?php
declare(strict_types=1);

/**
 * Projeto Repack - Layout Gestao Header
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../models/Usuario.php';

$nomeGestor = trim((string)($_SESSION['nome'] ?? ''));
$matriculaGestor = trim((string)($_SESSION['matricula'] ?? ''));

// Fallback resiliente ao banco de dados se a sessão perdeu as strings
if (($nomeGestor === '' || $matriculaGestor === '') && !empty($_SESSION['usuario_id'])) {
    $u = Usuario::buscarPorId((int)$_SESSION['usuario_id']);
    if ($u) {
        $nomeGestor = $u['nome'];
        $matriculaGestor = $u['matricula'];
        $_SESSION['nome'] = $nomeGestor;
        $_SESSION['matricula'] = $matriculaGestor;
    }
}

if ($nomeGestor === '') {
    $nomeGestor = 'Coordenadora';
}
if ($matriculaGestor === '') {
    $matriculaGestor = 'GESTÃO';
}
$csrfToken = AuthController::gerarCsrfToken();
$rotaAtual = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
if (APP_URL !== '' && strpos($rotaAtual, APP_URL) === 0) {
    $rotaAtual = substr($rotaAtual, strlen(APP_URL));
}
$rotaAtual = '/' . trim($rotaAtual, '/');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#10B981">
    <title><?= sanitize_html($pageTitle ?? 'Gestão - ' . APP_NAME) ?></title>
    
    <link rel="icon" type="image/png" sizes="64x64" href="<?= APP_URL ?>/assets/img/favicon.png?v=<?= APP_VERSION ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= APP_URL ?>/assets/img/apple-touch-icon.png?v=<?= APP_VERSION ?>">
    <link rel="shortcut icon" href="<?= APP_URL ?>/assets/img/favicon.png?v=<?= APP_VERSION ?>">
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json?v=<?= APP_VERSION ?>">
    
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/urna.css">
    
    <style>
        :root {
            --gestao-bg: #F3F4F6;
            --gestao-surface: #FFFFFF;
            --gestao-primary: #10B981;
            --gestao-secondary: #3B82F6;
            --gestao-warning: #F59E0B;
            --gestao-danger: #EF4444;
            --gestao-text: #111827;
            --gestao-muted: #6B7280;
            --gestao-border: #D1D5DB;
        }

        body.gestao-body {
            background-color: var(--gestao-bg);
            color: var(--gestao-text);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        .navbar-gestao {
            background-color: #0F172A;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            padding: 8px 18px;
        }

        .navbar-gestao .nav-link {
            color: #CBD5E1 !important;
            font-weight: 600;
            font-size: 13.5px;
            padding: 6px 13px;
            border-radius: 8px;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: all 0.15s ease;
            text-decoration: none;
            line-height: 1.4;
            border: 1.5px solid rgba(255, 255, 255, 0.22);
            background-color: rgba(255, 255, 255, 0.04);
        }

        .navbar-gestao .nav-link:hover {
            color: #FFFFFF !important;
            background-color: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.45);
        }

        .navbar-gestao .nav-link.active {
            color: #FFFFFF !important;
            background-color: #2563EB;
            border-color: #60A5FA;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.5);
        }

        .gestao-container {
            max-width: 1400px;
            margin: 24px auto;
            padding: 0 20px;
            width: 100%;
            flex: 1;
        }

        .card-gestao {
            background-color: var(--gestao-surface);
            border: 1px solid var(--gestao-border);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }

        .card-metric {
            border-left: 5px solid var(--gestao-secondary);
            transition: transform 0.15s ease-in-out;
        }

        .card-metric:hover {
            transform: translateY(-2px);
        }

        .table-gestao {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        .table-gestao th {
            background-color: #F9FAFB;
            color: var(--gestao-muted);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 16px;
            border-bottom: 2px solid var(--gestao-border);
            border-top: none;
        }

        .table-gestao td {
            padding: 14px 16px;
            vertical-align: middle;
            border-bottom: 1px solid var(--gestao-border);
            font-size: 15px;
        }

        .table-gestao tr:last-child td {
            border-bottom: none;
        }

        .badge-status {
            padding: 6px 12px;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            display: inline-block;
        }

        .badge-status.planejado { background-color: #E5E7EB; color: #374151; }
        .badge-status.aberto { background-color: #D1FAE5; color: #065F46; }
        .badge-status.encerrado { background-color: #FEE2E2; color: #991B1B; }
        .badge-status.lojinha { background-color: #D1FAE5; color: #065F46; }
        .badge-status.descarte { background-color: #FEE2E2; color: #991B1B; }

        .btn-gestao {
            font-weight: 600;
            border-radius: 8px;
            padding: 10px 18px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-gestao-primary {
            background-color: var(--gestao-primary);
            color: #FFFFFF;
            border: none;
        }
        .btn-gestao-primary:hover {
            background-color: #059669;
            color: #FFFFFF;
        }

        .btn-gestao-secondary {
            background-color: var(--gestao-secondary);
            color: #FFFFFF;
            border: none;
        }
        .btn-gestao-secondary:hover {
            background-color: #2563EB;
            color: #FFFFFF;
        }

        .btn-gestao-warning {
            background-color: var(--gestao-warning);
            color: #FFFFFF;
            border: none;
        }
        .btn-gestao-warning:hover {
            background-color: #D97706;
            color: #FFFFFF;
        }

        .btn-gestao-danger {
            background-color: var(--gestao-danger);
            color: #FFFFFF;
            border: none;
        }
        .btn-gestao-danger:hover {
            background-color: #DC2626;
            color: #FFFFFF;
        }

        /* Drawer Lateral Direito (Entrada da Direita para Esquerda sem encobrir o cabeçalho) */
        .drawer-backdrop-gestao {
            position: fixed;
            top: 70px; /* Inicia logo abaixo da barra superior */
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.65);
            backdrop-filter: blur(3px);
            z-index: 1040;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.25s ease, visibility 0.25s ease;
        }
        .drawer-backdrop-gestao.ativo {
            opacity: 1;
            visibility: visible;
        }

        .drawer-lateral-direita {
            position: fixed;
            top: 70px; /* Mantém o cabeçalho 100% visível no topo! */
            right: 0;
            bottom: 0;
            width: min(360px, 92vw);
            background: #0B1120;
            border-left: 2px solid rgba(255, 255, 255, 0.15);
            box-shadow: -8px 0 32px rgba(0, 0, 0, 0.6);
            z-index: 1045;
            display: flex;
            flex-direction: column;
            transform: translateX(100%); /* Escondido à direita */
            transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
            box-sizing: border-box;
        }
        .drawer-lateral-direita.aberto {
            transform: translateX(0); /* Desliza da direita para a esquerda */
        }

        .drawer-nav-item {
            width: 100%;
            font-size: 18px;
            font-weight: 800;
            padding: 14px 20px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            gap: 16px;
            color: #CBD5E1 !important;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.05);
            border: 1.5px solid rgba(255, 255, 255, 0.12);
            transition: all 0.15s ease;
            box-sizing: border-box;
            min-height: 58px;
        }
        .drawer-nav-item:hover, .drawer-nav-item:active {
            background: rgba(255, 255, 255, 0.12);
            color: #FFFFFF !important;
            border-color: rgba(255, 255, 255, 0.35);
        }
        .drawer-nav-item.active {
            background: #2563EB !important;
            border-color: #60A5FA !important;
            color: #FFFFFF !important;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.45);
        }
        .drawer-nav-item span:first-child {
            font-size: 26px;
            line-height: 1;
            width: 32px;
            text-align: center;
            flex-shrink: 0;
        }
        .drawer-nav-lojinha {
            background: rgba(139, 92, 246, 0.15) !important;
            border-color: rgba(139, 92, 246, 0.45) !important;
            color: #DDD6FE !important;
        }
        .drawer-nav-repack {
            background: rgba(16, 185, 129, 0.15) !important;
            border-color: rgba(16, 185, 129, 0.45) !important;
            color: #A7F3D0 !important;
        }

        /* Ergonomia Mobile Geral Ampliada para Toda a Interface de Gestão */
        @media (max-width: 991px) {
            body.gestao-body {
                font-size: 16px;
            }
            .gestao-container {
                margin: 18px auto !important;
                padding: 0 16px !important;
            }
            .card-gestao {
                padding: 22px 18px !important;
                border-radius: 18px !important;
                margin-bottom: 22px !important;
            }
            h1.headline-lg {
                font-size: 26px !important;
                font-weight: 900 !important;
            }
            h2.headline-lg {
                font-size: 21px !important;
                font-weight: 900 !important;
            }
            h3.headline-lg {
                font-size: 18px !important;
                font-weight: 900 !important;
            }
            .body-md {
                font-size: 15.5px !important;
                line-height: 1.45 !important;
            }
            .live-indicator {
                font-size: 14px !important;
                padding: 8px 16px !important;
                font-weight: 800 !important;
            }
            .card-metric {
                border-left-width: 6px !important;
                padding: 22px 18px !important;
                border-radius: 18px !important;
            }
            .card-metric .text-muted.small.fw-bold {
                font-size: 14.5px !important;
                font-weight: 800 !important;
                letter-spacing: 0.5px !important;
            }
            .card-metric .headline-xl {
                font-size: clamp(38px, 9vw, 48px) !important;
                font-weight: 900 !important;
                line-height: 1.05 !important;
            }
            .card-metric .text-muted.small {
                font-size: 15px !important;
                font-weight: 600 !important;
            }
            .pill-filter {
                padding: 10px 18px !important;
                font-size: 15px !important;
                font-weight: 800 !important;
                border-radius: 25px !important;
                margin: 4px 3px !important;
                min-height: 46px !important;
                display: inline-flex !important;
                align-items: center !important;
            }
            .btn-gestao {
                padding: 12px 20px !important;
                font-size: 16px !important;
                font-weight: 800 !important;
                border-radius: 12px !important;
                min-height: 50px !important;
            }
            .btn-sm {
                padding: 8px 16px !important;
                font-size: 14.5px !important;
                font-weight: 700 !important;
                min-height: 44px !important;
                border-radius: 10px !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            .form-control, .form-select, .form-control-sm, .form-select-sm {
                min-height: 52px !important;
                font-size: 16px !important;
                padding: 12px 16px !important;
                border-radius: 12px !important;
            }
            .form-label, .form-label.small {
                font-size: 14.5px !important;
                font-weight: 800 !important;
                margin-bottom: 6px !important;
                color: #1E293B !important;
            }
            .table-gestao th, .table-feed th {
                padding: 14px 16px !important;
                font-size: 13.5px !important;
                font-weight: 800 !important;
            }
            .table-gestao td, .table-feed td {
                padding: 16px 16px !important;
                font-size: 15.5px !important;
            }
            .badge-status {
                padding: 8px 14px !important;
                font-size: 13px !important;
                font-weight: 800 !important;
            }
        }
    </style>
</head>
<body class="gestao-body">

<!-- Barra de Navegação Superior da Gestão (Fixa no topo com Z-Index soberano) -->
<nav class="navbar navbar-expand-xl navbar-dark navbar-gestao" style="position: sticky; top: 0; z-index: 1050; min-height: 70px; padding: 10px 16px;">
    <div class="container-fluid px-0 d-flex align-items-center justify-content-between">
        
        <!-- LATERAL ESQUERDA: Logomarca / Ícone da Lojinha & Repack + Título do Módulo -->
        <a class="navbar-brand d-flex align-items-center gap-2 m-0 text-white" href="<?= APP_URL ?>/gestao/dashboard">
            <!-- Emblema da Logomarca (Preparado para inserção de imagem de marca oficial) -->
            <div class="brand-logo-badge" style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, #10B981 0%, #059669 100%); display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 10px rgba(16,185,129,0.45); flex-shrink: 0; border: 2.5px solid rgba(255,255,255,0.3);">
                <span style="font-size: 26px; line-height: 1;">🏪</span>
            </div>
            <div class="d-flex flex-column" style="line-height: 1.15;">
                <div class="d-flex align-items-center gap-1.5">
                    <strong style="letter-spacing: -0.2px; font-size: 19px; font-weight: 900;">Gestão do Repack</strong>
                    <span class="badge bg-secondary ms-1 d-none d-sm-inline" style="font-size: 10px; font-weight: 600; opacity: 0.9;"><?= APP_VERSION ?></span>
                </div>
                <span style="font-size: 13px; color: #CBD5E1; font-weight: 700;">Lojinha & Operação</span>
            </div>
        </a>

        <!-- DESKTOP (>= 1200px): Links Horizontais Centrais + Atalhos Rápidos + Usuário -->
        <div class="d-none d-xl-flex align-items-center gap-3">
            <!-- Módulos de Gestão Centrais -->
            <ul class="navbar-nav d-flex flex-row align-items-center gap-1 mb-0">
                <li class="nav-item">
                    <a class="nav-link <?= ($rotaAtual === '/gestao/dashboard' || $rotaAtual === '/gestao') ? 'active' : '' ?>" href="<?= APP_URL ?>/gestao/dashboard">
                        <span>📊</span><span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($rotaAtual, '/gestao/produtos') === 0 ? 'active' : '' ?>" href="<?= APP_URL ?>/gestao/produtos">
                        <span>📦</span><span>Produtos</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($rotaAtual, '/gestao/lotes') === 0 ? 'active' : '' ?>" href="<?= APP_URL ?>/gestao/lotes">
                        <span>🏷️</span><span>Lotes de Vendas</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($rotaAtual, '/gestao/estoque') === 0 ? 'active' : '' ?>" href="<?= APP_URL ?>/gestao/estoque">
                        <span>🏪</span><span>Estoque Lojinha</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($rotaAtual, '/gestao/bo') === 0 ? 'active' : '' ?>" href="<?= APP_URL ?>/gestao/bo">
                        <span>📋</span><span>Ajuste B.O.</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($rotaAtual, '/gestao/separacao') === 0 ? 'active' : '' ?>" href="<?= APP_URL ?>/gestao/separacao">
                        <span>📝</span><span>Pedidos</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($rotaAtual, '/gestao/relatorios') === 0 ? 'active' : '' ?>" href="<?= APP_URL ?>/gestao/relatorios">
                        <span>📈</span><span>Relatórios</span>
                    </a>
                </li>
            </ul>

            <!-- Atalhos Rápidos da Gestão no Desktop -->
            <div class="d-flex align-items-center gap-2">
                <a href="<?= APP_URL ?>/lojinha" class="btn btn-sm text-nowrap" style="background: rgba(139, 92, 246, 0.12); color: #C4B5FD; border: 1px solid rgba(139, 92, 246, 0.35); font-size: 13px; font-weight: 700; border-radius: 8px; padding: 5px 11px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s;" title="Acessar Vitrine da Lojinha">
                    <span style="font-size: 14px;">🛍️</span>
                    <span>Lojinha</span>
                </a>

                <a href="<?= APP_URL ?: '/' ?>" class="btn btn-sm text-nowrap" style="background: rgba(16, 185, 129, 0.12); color: #6EE7B7; border: 1.5px solid rgba(16, 185, 129, 0.45); font-size: 13px; font-weight: 700; border-radius: 8px; padding: 5px 12px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 7px; transition: all 0.2s;" title="Retornar à Bancada de Repack">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                        <path d="M2 10h4v4H2z" fill="currentColor" fill-opacity="0.3"/>
                        <path d="M6 8h10a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H6"/>
                        <path d="M12 16v4a2 2 0 0 1-2 2H9a1 1 0 0 1-1-1v-5"/>
                        <path d="M1 9c-.5.5-.5 1.5 0 2" stroke="#F59E0B" stroke-width="2"/>
                        <path d="M1 13c-.5.5-.5 1.5 0 2" stroke="#EF4444" stroke-width="2"/>
                    </svg>
                    <span>Repack</span>
                </a>
            </div>

            <div class="vr bg-secondary mx-1" style="height: 20px; opacity: 0.25;"></div>

            <!-- Crachá Desktop do Usuário -->
            <div class="d-inline-flex align-items-center gap-2 px-2.5 py-1 rounded-pill flex-shrink-0" style="background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.12);">
                <div style="width: 22px; height: 22px; background: #3B82F6; color: #FFFFFF; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800;">
                    👤
                </div>
                <span style="color: #F1F5F9; font-weight: 700; font-size: 13px; white-space: nowrap;">
                    <?= sanitize_html($nomeGestor) ?>
                </span>
                <span class="badge" style="background: #FEF3C7; color: #92400E; font-size: 11px; font-weight: 800; border: 1px solid #F59E0B; padding: 2px 6px; border-radius: 4px;">
                    <?= sanitize_html($matriculaGestor) ?>
                </span>
            </div>

            <!-- Botão Sair Desktop -->
            <a href="<?= APP_URL ?>/gestao/logout" class="btn btn-sm btn-outline-danger fw-bold px-2.5 py-1 d-inline-flex align-items-center gap-1 flex-shrink-0" style="border-radius: 7px; font-size: 12.5px; border-color: rgba(239, 68, 68, 0.4); color: #FCA5A5; text-decoration: none;" title="Encerrar sessão administrativa">
                <span>Sair</span>
            </a>
        </div>

        <!-- LATERAL DIREITA NO SMARTPHONE: Botão Hambúrguer que abre o Drawer vindo da Direita -->
        <div class="d-flex d-xl-none align-items-center">
            <button class="btn" type="button" id="btnToggleDrawerGestao" aria-label="Abrir Menu da Gestão" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border: 2px solid rgba(255,255,255,0.35); border-radius: 12px; background: rgba(255,255,255,0.1); color: #FFFFFF; padding: 0;">
                <svg id="iconDrawerHamb" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
                <svg id="iconDrawerClose" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

    </div>
</nav>

<!-- Backdrop Suave abaixo do cabeçalho (Fecha a gaveta ao tocar fora) -->
<div id="drawerBackdropGestao" class="drawer-backdrop-gestao d-xl-none" onclick="fecharDrawerGestao()"></div>

<!-- Drawer Lateral da Direita (Entrada da Direita para a Esquerda sem encobrir o cabeçalho) -->
<aside id="drawerGestaoLateral" class="drawer-lateral-direita d-xl-none">
    <!-- Cabeçalho Interno do Drawer -->
    <div class="d-flex align-items-center justify-content-between p-3 border-bottom" style="border-color: rgba(255, 255, 255, 0.12) !important; background: #070B14; min-height: 60px;">
        <div class="d-flex align-items-center gap-2">
            <span style="font-size: 22px;">📋</span>
            <span style="color: #FFFFFF; font-weight: 900; font-size: 16px; letter-spacing: 0.5px;">MENU DE GESTÃO</span>
        </div>
        <button type="button" class="btn text-white-50 p-1" onclick="fecharDrawerGestao()" style="font-size: 22px; line-height: 1; border: none; background: transparent; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">✕</button>
    </div>

    <!-- Lista de Abas e Módulos com Rolagem Suave -->
    <div class="p-3 d-flex flex-column gap-2" style="overflow-y: auto; flex: 1;">
        <!-- 7 Módulos de Gestão -->
        <a class="drawer-nav-item <?= ($rotaAtual === '/gestao/dashboard' || $rotaAtual === '/gestao') ? 'active' : '' ?>" href="<?= APP_URL ?>/gestao/dashboard">
            <span>📊</span><span>Dashboard</span>
        </a>
        <a class="drawer-nav-item <?= strpos($rotaAtual, '/gestao/produtos') === 0 ? 'active' : '' ?>" href="<?= APP_URL ?>/gestao/produtos">
            <span>📦</span><span>Produtos</span>
        </a>
        <a class="drawer-nav-item <?= strpos($rotaAtual, '/gestao/lotes') === 0 ? 'active' : '' ?>" href="<?= APP_URL ?>/gestao/lotes">
            <span>🏷️</span><span>Lotes de Vendas</span>
        </a>
        <a class="drawer-nav-item <?= strpos($rotaAtual, '/gestao/estoque') === 0 ? 'active' : '' ?>" href="<?= APP_URL ?>/gestao/estoque">
            <span>🏪</span><span>Estoque Lojinha</span>
        </a>
        <a class="drawer-nav-item <?= strpos($rotaAtual, '/gestao/bo') === 0 ? 'active' : '' ?>" href="<?= APP_URL ?>/gestao/bo">
            <span>📋</span><span>Ajuste B.O.</span>
        </a>
        <a class="drawer-nav-item <?= strpos($rotaAtual, '/gestao/separacao') === 0 ? 'active' : '' ?>" href="<?= APP_URL ?>/gestao/separacao">
            <span>📝</span><span>Pedidos</span>
        </a>
        <a class="drawer-nav-item <?= strpos($rotaAtual, '/gestao/relatorios') === 0 ? 'active' : '' ?>" href="<?= APP_URL ?>/gestao/relatorios">
            <span>📈</span><span>Relatórios</span>
        </a>

        <div class="my-2 border-top" style="border-color: rgba(255, 255, 255, 0.12) !important;"></div>

        <!-- Abas Integradas Lojinha e Repack -->
        <a class="drawer-nav-item drawer-nav-lojinha <?= strpos($rotaAtual, '/lojinha') === 0 ? 'active' : '' ?>" href="<?= APP_URL ?>/lojinha">
            <span>🛒</span><span>Lojinha (Vitrine)</span>
        </a>
        <a class="drawer-nav-item drawer-nav-repack" href="<?= APP_URL ?: '/' ?>">
            <span>🏭</span><span>Repack (Bancada)</span>
        </a>
    </div>

    <!-- Rodapé do Drawer com Atalho de Sair -->
    <div class="p-3 border-top" style="border-color: rgba(255, 255, 255, 0.12) !important; background: #070B14;">
        <a href="<?= APP_URL ?>/gestao/logout" class="btn btn-danger w-100 d-flex align-items-center justify-content-center gap-2 fw-bold" style="border-radius: 12px; padding: 13px 18px; font-size: 16px; min-height: 54px;">
            <span>🚪</span> <span>Encerrar Sessão</span>
        </a>
    </div>
</aside>

<script>
// Gerenciador do Menu Lateral Offcanvas da Gestão (Mobile)
function abrirDrawerGestao() {
    document.getElementById('drawerGestaoLateral')?.classList.add('aberto');
    document.getElementById('drawerBackdropGestao')?.classList.add('ativo');
    const iconHamb = document.getElementById('iconDrawerHamb');
    const iconClose = document.getElementById('iconDrawerClose');
    if (iconHamb) iconHamb.style.display = 'none';
    if (iconClose) iconClose.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function fecharDrawerGestao() {
    document.getElementById('drawerGestaoLateral')?.classList.remove('aberto');
    document.getElementById('drawerBackdropGestao')?.classList.remove('ativo');
    const iconHamb = document.getElementById('iconDrawerHamb');
    const iconClose = document.getElementById('iconDrawerClose');
    if (iconHamb) iconHamb.style.display = 'block';
    if (iconClose) iconClose.style.display = 'none';
    document.body.style.overflow = '';
}

function toggleDrawerGestao() {
    const drawer = document.getElementById('drawerGestaoLateral');
    if (drawer && drawer.classList.contains('aberto')) {
        fecharDrawerGestao();
    } else {
        abrirDrawerGestao();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('btnToggleDrawerGestao')?.addEventListener('click', toggleDrawerGestao);
    // Fecha gaveta com tecla ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') fecharDrawerGestao();
    });
});
</script>

<!-- Sub-barra Móvel de Identificação do Usuário (Mobile apenas, d-xl-none) -->
<div class="d-flex d-xl-none align-items-center justify-content-between px-3 py-2" style="background: linear-gradient(135deg, #1E3A8A 0%, #1D4ED8 100%); border-bottom: 2px solid #3B82F6; min-height: 54px;">
    <div class="d-flex align-items-center gap-2">
        <div style="width: 36px; height: 36px; background: #FFFFFF; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25);">
            <span style="font-size: 18px; line-height: 1;">👤</span>
        </div>
        <div class="d-flex flex-column" style="line-height: 1.15;">
            <span style="color: #FFFFFF; font-weight: 800; font-size: 15px;">
                <?= sanitize_html($nomeGestor) ?>
            </span>
            <div class="d-flex align-items-center gap-1 mt-1">
                <span class="badge" style="background: #FEF3C7; color: #92400E; font-size: 11px; font-weight: 800; border: 1px solid #F59E0B; padding: 2px 6px; border-radius: 4px;">
                    MATRÍCULA: <?= sanitize_html($matriculaGestor) ?>
                </span>
            </div>
        </div>
    </div>
    <a href="<?= APP_URL ?>/gestao/logout" class="btn btn-danger fw-bold d-flex align-items-center gap-1" style="background: #DC2626; border: 1px solid #B91C1C; border-radius: 20px; padding: 6px 14px; font-size: 13.5px; color: #FFFFFF; min-height: 38px;" title="Encerrar sessão administrativa">
        <span>Sair</span> ➔
    </a>
</div>

<!-- Conteúdo Principal -->
<div class="gestao-container">

    <!-- Mensagens de Flash / Feedback -->
    <?php if (!empty($_SESSION['flash_sucesso'])): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert" style="border-radius: 8px;">
            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
            <div><strong>Sucesso!</strong> <?= sanitize_html($_SESSION['flash_sucesso']) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        <?php unset($_SESSION['flash_sucesso']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_erro'])): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert" style="border-radius: 8px;">
            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
            <div><strong>Atenção:</strong> <?= sanitize_html($_SESSION['flash_erro']) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        <?php unset($_SESSION['flash_erro']); ?>
    <?php endif; ?>
