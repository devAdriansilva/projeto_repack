<?php
declare(strict_types=1);

/**
 * Projeto Repack - Urna Eletrônica (Home do Operador)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md (Seção 12.1)
 */

$pageTitle = "Bancada de Repack - Início";
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="text-align: center; margin-top: 12px; margin-bottom: 24px;">
    <h1 class="headline-xl" style="color: var(--on-surface); margin: 0; font-size: 38px; font-weight: 800; letter-spacing: -0.5px;">Repack</h1>
</div>

<div style="display: flex; flex-direction: column; gap: 28px; max-width: 520px; margin: 0 auto; width: 100%; padding: 0 14px; box-sizing: border-box;">
    <!-- Card 1: Registrar B.O. -->
    <a href="<?= APP_URL ?>/operador/avaria/passo1" 
       class="card-acao-operador" 
       style="display: flex; flex-direction: column; text-decoration: none; border-radius: 22px; overflow: hidden; background: #FFFFFF; border: 2px solid #D1D5DB; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.09); width: 100%; box-sizing: border-box;">
        <div class="card-acao-moldura" style="height: 210px; background: #F8FAFC; display: flex; align-items: center; justify-content: center; overflow: hidden; border-bottom: 2px solid #E2E8F0; padding: 12px; box-sizing: border-box;">
            <img src="<?= APP_URL ?>/assets/img/btn_bo.jpg" 
                 alt="Lata amassada - B.O." 
                 style="height: 185px; width: auto; max-width: 95%; object-fit: contain; border-radius: 12px; display: block;">
        </div>
        <div class="card-acao-btn card-acao-btn-bo" style="min-height: 88px; display: flex; align-items: center; justify-content: center; gap: 16px; font-size: 26px; font-weight: 800; color: #FFFFFF; letter-spacing: 0.5px; text-transform: uppercase; background-color: #2563EB; box-sizing: border-box; padding: 16px 20px;">
            <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24" style="flex-shrink: 0;">
                <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/>
            </svg>
            <span>REGISTRAR B.O.</span>
        </div>
    </a>

    <!-- Card 2: Registrar Produção (Cronômetro) -->
    <a href="<?= APP_URL ?>/operador/cronometro" 
       class="card-acao-operador" 
       style="display: flex; flex-direction: column; text-decoration: none; border-radius: 22px; overflow: hidden; background: #FFFFFF; border: 2px solid #D1D5DB; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.09); width: 100%; box-sizing: border-box;">
        <div class="card-acao-moldura" style="height: 210px; background: #F8FAFC; display: flex; align-items: center; justify-content: center; overflow: hidden; border-bottom: 2px solid #E2E8F0; padding: 12px; box-sizing: border-box;">
            <img src="<?= APP_URL ?>/assets/img/btn_producao.jpg" 
                 alt="Encolhimento térmico - Produção" 
                 style="height: 185px; width: auto; max-width: 95%; object-fit: contain; border-radius: 12px; display: block;">
        </div>
        <div class="card-acao-btn card-acao-btn-producao" style="min-height: 88px; display: flex; align-items: center; justify-content: center; gap: 16px; font-size: 26px; font-weight: 800; color: #FFFFFF; letter-spacing: 0.5px; text-transform: uppercase; background-color: #059669; box-sizing: border-box; padding: 16px 20px;">
            <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24" style="flex-shrink: 0;">
                <path d="M15 1H9v2h6V1zm-4 13h2V8h-2v6zm8.03-6.61l1.42-1.42c-.43-.51-.9-.99-1.41-1.41l-1.42 1.42C16.07 4.74 14.12 4 12 4c-4.97 0-9 4.03-9 9s4.02 9 9 9 9-4.03 9-9c0-2.12-.74-4.07-1.97-5.61zM12 20c-3.87 0-7-3.13-7-7s3.13-7 7-7 7 3.13 7 7-3.13 7-7 7z"/>
            </svg>
            <span>REGISTRAR PRODUÇÃO</span>
        </div>
    </a>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
