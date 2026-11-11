<?php
declare(strict_types=1);

/**
 * Projeto Repack - Urna Eletrônica (Home do Operador)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md (Seção 12.1)
 */

$pageTitle = "Urna Eletrônica - Início";
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="text-align: center; margin-bottom: 40px;">
    <h1 class="headline-xl" style="color: var(--on-surface); margin-bottom: 8px;">ÁREA DE REEMBALAGEM</h1>
    <p class="body-lg" style="color: var(--on-surface-muted);">Selecione a operação desejada com um toque</p>
</div>

<div style="display: flex; flex-direction: column; gap: 36px; max-width: 600px; margin: 0 auto; width: 100%;">
    <!-- Botão 1: Registrar Avaria -->
    <a href="<?= APP_URL ?>/operador/avaria/passo1" class="btn-urna btn-urna-secondary" style="height: 100px; font-size: 26px;">
        <svg width="40" height="40" fill="currentColor" viewBox="0 0 24 24">
            <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/>
        </svg>
        <span>REGISTRAR AVARIA</span>
    </a>

    <!-- Botão 2: Registrar Produção (Cronômetro) -->
    <a href="<?= APP_URL ?>/operador/cronometro" class="btn-urna btn-urna-secondary" style="height: 100px; font-size: 26px;">
        <svg width="40" height="40" fill="currentColor" viewBox="0 0 24 24">
            <path d="M15 1H9v2h6V1zm-4 13h2V8h-2v6zm8.03-6.61l1.42-1.42c-.43-.51-.9-.99-1.41-1.41l-1.42 1.42C16.07 4.74 14.12 4 12 4c-4.97 0-9 4.03-9 9s4.02 9 9 9 9-4.03 9-9c0-2.12-.74-4.07-1.97-5.61zM12 20c-3.87 0-7-3.13-7-7s3.13-7 7-7 7 3.13 7 7-3.13 7-7 7z"/>
        </svg>
        <span>REGISTRAR PRODUÇÃO</span>
    </a>
</div>

<!-- Navegação Rápida entre Módulos do Sistema Repack -->
<div style="margin-top: 56px; border-top: 1px solid var(--border); padding-top: 24px; text-align: center;">
    <p class="body-sm text-muted" style="margin-bottom: 16px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
        Acesso Rápido aos Demais Módulos
    </p>
    <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
        <a href="<?= APP_URL ?>/lojinha" class="btn-urna btn-urna-outline" style="max-width: 280px; min-height: 56px; font-size: 16px; padding: 12px 20px;">
            🛒 Lojinha dos Colaboradores
        </a>
        <button type="button" onclick="abrirModalGestao()" class="btn-urna btn-urna-outline" style="max-width: 280px; min-height: 56px; font-size: 16px; padding: 12px 20px;">
            📊 Gestão & Relatórios
        </button>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
