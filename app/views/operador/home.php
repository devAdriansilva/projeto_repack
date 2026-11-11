<?php
declare(strict_types=1);

/**
 * Projeto Repack - Urna Eletrônica (Home do Operador)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md (Seção 12.1)
 */

$pageTitle = "Bancada de Repack - Início";
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: calc(100dvh - 80px); padding: 16px 12px; box-sizing: border-box; width: 100%; max-width: 440px; margin: 0 auto; gap: 20px;">
    
    <!-- Insígnia com Fundo Estilizado do Repack -->
    <div style="width: 100%; text-align: center;">
        <div class="brand-header-badge" style="display: inline-flex; align-items: center; justify-content: center; padding: 12px 36px; background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); border-radius: 16px; box-shadow: 0 4px 14px rgba(15, 23, 42, 0.2); border: 1.5px solid rgba(255, 255, 255, 0.12);">
            <span style="font-size: 26px; font-weight: 900; letter-spacing: 1.5px; color: #FFFFFF;">REPACK</span>
        </div>
    </div>

    <!-- Card 1: Registrar B.O. (Proporção 4:3 com Imagem Ampla e Botão Forte) -->
    <a href="<?= APP_URL ?>/operador/avaria/passo1" 
       class="card-acao-operador" 
       style="display: flex; flex-direction: column; text-decoration: none; border-radius: 22px; overflow: hidden; background: #FFFFFF; border: 2.5px solid #CBD5E1; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08); width: 100%; height: 270px; box-sizing: border-box;">
        <div style="height: 185px; background: #FFFFFF; display: flex; align-items: center; justify-content: center; padding: 12px; box-sizing: border-box;">
            <img src="<?= APP_URL ?>/assets/img/btn_bo.jpg?v=2" 
                 alt="Lata amassada - B.O." 
                 style="height: 160px; width: 160px; max-width: 90%; object-fit: contain; border-radius: 14px; display: block;">
        </div>
        <div class="card-acao-btn card-acao-btn-bo" style="height: 85px; min-height: 85px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; gap: 14px; font-size: 25px; font-weight: 900; color: #FFFFFF; letter-spacing: 0.5px; text-transform: uppercase; background-color: #2563EB; box-sizing: border-box; padding: 14px 18px;">
            <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24" style="flex-shrink: 0;">
                <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/>
            </svg>
            <span>REGISTRAR B.O.</span>
        </div>
    </a>

    <!-- Card 2: Registrar Produção (Cronômetro) -->
    <a href="<?= APP_URL ?>/operador/cronometro" 
       class="card-acao-operador" 
       style="display: flex; flex-direction: column; text-decoration: none; border-radius: 22px; overflow: hidden; background: #FFFFFF; border: 2.5px solid #CBD5E1; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08); width: 100%; height: 270px; box-sizing: border-box;">
        <div style="height: 185px; background: #FFFFFF; display: flex; align-items: center; justify-content: center; padding: 12px; box-sizing: border-box;">
            <img src="<?= APP_URL ?>/assets/img/btn_producao.jpg?v=2" 
                 alt="Encolhimento térmico - Produção" 
                 style="height: 160px; width: 160px; max-width: 90%; object-fit: contain; border-radius: 14px; display: block;">
        </div>
        <div class="card-acao-btn card-acao-btn-producao" style="height: 85px; min-height: 85px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; gap: 14px; font-size: 25px; font-weight: 900; color: #FFFFFF; letter-spacing: 0.5px; text-transform: uppercase; background-color: #059669; box-sizing: border-box; padding: 14px 18px;">
            <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24" style="flex-shrink: 0;">
                <path d="M15 1H9v2h6V1zm-4 13h2V8h-2v6zm8.03-6.61l1.42-1.42c-.43-.51-.9-.99-1.41-1.41l-1.42 1.42C16.07 4.74 14.12 4 12 4c-4.97 0-9 4.03-9 9s4.02 9 9 9 9-4.03 9-9c0-2.12-.74-4.07-1.97-5.61zM12 20c-3.87 0-7-3.13-7-7s3.13-7 7-7 7 3.13 7 7-3.13 7-7 7z"/>
            </svg>
            <span>REGISTRAR PRODUÇÃO</span>
        </div>
    </a>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
