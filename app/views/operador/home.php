<?php
declare(strict_types=1);

/**
 * Projeto Repack - Urna Eletrônica (Home do Operador)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md (Seção 12.1)
 */

$pageTitle = "Bancada de Repack - Início";
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="home-operador-container">
    
    <!-- Título Repack com Destaque e Identidade Visual Forte -->
    <div class="home-operador-header">
        <div class="brand-repack-badge">
            <h1>REPACK</h1>
            <span class="brand-sub">Bancada de Operações</span>
        </div>
    </div>

    <!-- Grade de Ações do Operador (Botões Sólidos e Amplos de Alta Usabilidade) -->
    <div class="home-operador-actions">
        
        <!-- Botão 1: REGISTRAR B.O. (Sólido Azul) -->
        <a href="<?= APP_URL ?>/operador/avaria/passo1" class="btn-operador-solido btn-operador-bo">
            <div class="btn-operador-icone-wrapper">
                <img src="<?= APP_URL ?>/assets/img/btn_bo.jpg?v=<?= @filemtime(__DIR__ . '/../../../public/assets/img/btn_bo.jpg') ?: time() ?>" 
                     alt="Lata amassada - B.O." 
                     class="btn-operador-img">
            </div>
            <div class="btn-operador-label">
                <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true" style="flex-shrink: 0;">
                    <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/>
                </svg>
                <span>REGISTRAR B.O.</span>
            </div>
        </a>

        <!-- Botão 2: REGISTRAR PRODUÇÃO (Sólido Verde) -->
        <a href="<?= APP_URL ?>/operador/cronometro" class="btn-operador-solido btn-operador-producao">
            <div class="btn-operador-icone-wrapper">
                <img src="<?= APP_URL ?>/assets/img/btn_producao.jpg?v=<?= @filemtime(__DIR__ . '/../../../public/assets/img/btn_producao.jpg') ?: time() ?>" 
                     alt="Encolhimento térmico - Produção" 
                     class="btn-operador-img">
            </div>
            <div class="btn-operador-label">
                <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true" style="flex-shrink: 0;">
                    <path d="M15 1H9v2h6V1zm-4 13h2V8h-2v6zm8.03-6.61l1.42-1.42c-.43-.51-.9-.99-1.41-1.41l-1.42 1.42C16.07 4.74 14.12 4 12 4c-4.97 0-9 4.03-9 9s4.02 9 9 9 9-4.03 9-9c0-2.12-.74-4.07-1.97-5.61zM12 20c-3.87 0-7-3.13-7-7s3.13-7 7-7 7 3.13 7 7-3.13 7-7 7z"/>
                </svg>
                <span>REGISTRAR PRODUÇÃO</span>
            </div>
        </a>

    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
