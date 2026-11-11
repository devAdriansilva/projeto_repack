<?php
declare(strict_types=1);

/**
 * Projeto Repack - Urna Eletrônica (Home do Operador)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md (Seção 12.1)
 */

$pageTitle = "Bancada de Repack - Início";
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="text-align: center; margin-bottom: 32px;">
    <h1 class="headline-xl" style="color: var(--on-surface); margin: 0; font-weight: 800; letter-spacing: -0.5px;">Repack</h1>
</div>

<div style="display: flex; flex-direction: column; gap: 24px; max-width: 580px; margin: 0 auto; width: 100%;">
    <!-- Botão 1: Registrar B.O. -->
    <a href="<?= APP_URL ?>/operador/avaria/passo1" class="btn-operador-card">
        <img src="<?= APP_URL ?>/assets/img/btn_bo.jpg" alt="Lata amassada - B.O.">
        <span>REGISTRAR B.O.</span>
    </a>

    <!-- Botão 2: Registrar Produção (Cronômetro) -->
    <a href="<?= APP_URL ?>/operador/cronometro" class="btn-operador-card">
        <img src="<?= APP_URL ?>/assets/img/btn_producao.jpg" alt="Encolhimento térmico - Produção">
        <span>REGISTRAR PRODUÇÃO</span>
    </a>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
