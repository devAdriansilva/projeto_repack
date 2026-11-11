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
    
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/urna.css">
    <style>
        .lojinha-navbar {
            background-color: #FFFFFF;
            border-bottom: 2px solid #E5E7EB;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }
        .lojinha-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #111827;
        }
        .lojinha-brand-badge {
            background: #10B981;
            color: #FFFFFF;
            font-weight: 800;
            font-size: 14px;
            padding: 4px 10px;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .lojinha-brand-title {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            color: #111827;
        }
        .lojinha-user-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .lojinha-user-badge {
            display: flex;
            flex-direction: column;
            text-align: right;
            font-size: 14px;
            color: #4B5563;
        }
        .lojinha-user-name {
            font-weight: 700;
            color: #111827;
            font-size: 15px;
        }
        .btn-sacola-header {
            background-color: #3B82F6;
            color: #FFFFFF;
            border: none;
            border-radius: 12px;
            padding: 10px 18px;
            font-size: 16px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: background 0.2s;
            position: relative;
        }
        .btn-sacola-header:hover {
            background-color: #2563EB;
            color: #FFFFFF;
        }
        .sacola-counter {
            background: #EF4444;
            color: #FFFFFF;
            font-size: 13px;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 9999px;
            margin-left: 4px;
        }
        .btn-sair-header {
            background: transparent;
            color: #6B7280;
            border: 1px solid #D1D5DB;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-sair-header:hover {
            background: #F3F4F6;
            color: #111827;
        }
        .banner-lote {
            background: linear-gradient(135deg, #1E293B, #0F172A);
            color: #FFFFFF;
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 24px;
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
            border: 2px solid #E5E7EB;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .card-produto-lojinha:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .card-produto-lojinha.esgotado {
            opacity: 0.6;
            background: #F9FAFB;
            filter: grayscale(0.8);
        }
        .card-img-container {
            width: 100%;
            height: 180px;
            background: #F3F4F6;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 16px;
        }
        .card-img-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .card-badge-estoque {
            position: absolute;
            top: 28px;
            right: 28px;
            background: #10B981;
            color: #FFFFFF;
            font-weight: 700;
            font-size: 13px;
            padding: 4px 10px;
            border-radius: 9999px;
        }
        .card-badge-estoque.baixo {
            background: #F59E0B;
        }
        .card-badge-estoque.esgotado {
            background: #6B7280;
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
            font-size: 18px;
            margin-bottom: 24px;
        }
    </style>
</head>
<body style="background-color: #F3F4F6; min-height: 100vh;">

<?php if (!empty($nomeColab)): ?>
<!-- Barra de Navegação Superior da Lojinha -->
<nav class="lojinha-navbar">
    <a href="<?= APP_URL ?>/lojinha" class="lojinha-brand">
        <span class="lojinha-brand-badge">Repack</span>
        <h1 class="lojinha-brand-title">Lojinha dos Colaboradores</h1>
    </a>

    <div class="lojinha-user-info">
        <div class="lojinha-user-badge d-none d-md-flex">
            <span class="lojinha-user-name"><?= sanitize_html($nomeColab) ?></span>
            <span>Setor: <?= sanitize_html($setorColab) ?> (<?= sanitize_html($matriculaColab) ?>)</span>
        </div>

        <a href="<?= APP_URL ?>/lojinha/carrinho" class="btn-sacola-header" aria-label="Minha Sacola">
            <svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19 6h-2c0-2.76-2.24-5-5-5S7 3.24 7 6H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-7-3c1.66 0 3 1.34 3 3H9c0-1.66 1.34-3 3-3zm7 17H5V8h14v12zm-7-8c-1.66 0-3-1.34-3-3H7c0 2.76 2.24 5 5 5s5-2.24 5-5h-2c0 1.66-1.34 3-3 3z"/>
            </svg>
            <span>Sacola</span>
            <span class="sacola-counter" id="headerCarrinhoContador"><?= (int)$itensCarrinhoCount ?></span>
        </a>

        <a href="<?= APP_URL ?>/lojinha/logout" class="btn-sair-header" title="Sair da Lojinha">
            Sair
        </a>
    </div>
</nav>
<?php endif; ?>

<main class="container my-4">
