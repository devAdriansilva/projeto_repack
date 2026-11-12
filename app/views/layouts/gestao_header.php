<?php
declare(strict_types=1);

/**
 * Projeto Repack - Layout Gestao Header
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

$nomeGestor = $_SESSION['nome'] ?? 'Coordenadora';
$matriculaGestor = $_SESSION['matricula'] ?? 'GESTÃO';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize_html($pageTitle ?? 'Gestão - ' . APP_NAME) ?></title>
    
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
        }

        .navbar-gestao {
            background-color: #1F2937;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 12px 24px;
        }

        .navbar-gestao .nav-link {
            color: #D1D5DB !important;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .navbar-gestao .nav-link:hover {
            color: #FFFFFF !important;
            background-color: #374151;
        }

        .navbar-gestao .nav-link.active {
            color: #FFFFFF !important;
            background-color: var(--gestao-secondary);
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
    </style>
</head>
<body class="gestao-body">

<!-- Barra de Navegação Superior da Gestão -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-gestao">
    <div class="container-fluid px-0 d-flex align-items-center justify-content-between">
        <!-- Lado Esquerdo: Menu Hambúrguer (Mobile) e Marca do Sistema -->
        <div class="d-flex align-items-center gap-2">
            <button class="navbar-toggler me-1" type="button" data-bs-toggle="collapse" data-bs-target="#navGestao" aria-controls="navGestao" aria-expanded="false" aria-label="Alternar navegação" style="border: 2px solid rgba(255,255,255,0.25); padding: 6px 10px;">
                <span class="navbar-toggler-icon"></span>
            </button>

            <a class="navbar-brand d-flex align-items-center gap-2 m-0" href="<?= APP_URL ?>/gestao/dashboard">
                <span style="display: inline-block; width: 14px; height: 14px; background: var(--gestao-primary); border-radius: 3px;"></span>
                <strong>Gestão do Repack</strong>
                <span class="badge bg-secondary ms-1 d-none d-sm-inline" style="font-size: 11px; font-weight: 500; opacity: 0.85;"><?= APP_VERSION ?></span>
            </a>
        </div>

        <!-- Botão de Alto Destaque: Retorno à Área de Reembalagem (Sempre visível e destacado no topo) -->
        <div class="d-flex align-items-center gap-2">
            <a href="<?= APP_URL ?: '/' ?>" class="btn btn-sm" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); color: #FFFFFF; font-weight: 800; border-radius: 10px; padding: 7px 14px; border: 1.5px solid #047857; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.35); text-decoration: none; display: inline-flex; align-items: center; gap: 6px;" title="Retornar ao Terminal do Operador">
                <span style="font-size: 15px;">🏭</span>
                <span class="d-none d-md-inline">Área de Reembalagem</span>
                <span class="d-inline d-md-none">Bancada</span>
            </a>
        </div>

        <div class="collapse navbar-collapse w-100 mt-2 mt-lg-0" id="navGestao">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-1 ps-lg-3">
                <li class="nav-item">
                    <a class="nav-link <?= ($rotaAtual === '/gestao/dashboard' || $rotaAtual === '/gestao') ? 'active' : '' ?>" href="<?= APP_URL ?>/gestao/dashboard">
                        📊 Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($rotaAtual, '/gestao/produtos') === 0 ? 'active' : '' ?>" href="<?= APP_URL ?>/gestao/produtos">
                        📦 Produtos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($rotaAtual, '/gestao/lotes') === 0 ? 'active' : '' ?>" href="<?= APP_URL ?>/gestao/lotes">
                        🏷️ Lotes de Venda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($rotaAtual, '/gestao/estoque') === 0 ? 'active' : '' ?>" href="<?= APP_URL ?>/gestao/estoque">
                        🏪 Estoque & Reservas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($rotaAtual, '/gestao/bo') === 0 ? 'active' : '' ?>" href="<?= APP_URL ?>/gestao/bo">
                        📋 Correção de B.O.
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($rotaAtual, '/gestao/separacao') === 0 ? 'active' : '' ?>" href="<?= APP_URL ?>/gestao/separacao">
                        📝 Separação
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($rotaAtual, '/gestao/relatorios') === 0 ? 'active' : '' ?>" href="<?= APP_URL ?>/gestao/relatorios">
                        📈 Relatórios
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-3 pt-2 pt-lg-0 border-top border-lg-0 border-secondary">
                <span class="text-white-50 small d-none d-xl-inline">
                    👤 <strong><?= sanitize_html($nomeGestor) ?></strong> (<?= sanitize_html($matriculaGestor) ?>)
                </span>

                <a href="<?= APP_URL ?>/gestao/logout" class="btn btn-sm btn-outline-danger" title="Encerrar sessão administrativa">
                    Sair
                </a>
            </div>
        </div>
    </div>
</nav>

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
