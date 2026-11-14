<?php
declare(strict_types=1);

/**
 * Projeto Repack - Urna Eletrônica (Home do Operador - Escala Monumental)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md (Seção 12.1)
 */

$producaoAtiva = $_SESSION['producao_ativa'] ?? null;
$boAtivo = !empty($_SESSION['avaria_temp']['produto_id']);

$pageTitle = "Repack - Início";
require_once __DIR__ . '/../layouts/header.php';
?>

<style>
/* Regra Soberana: Smartphone e Telas Verticais (Portrait) - Botões e Ícones Verticais Monumentais */
.home-operador-container {
    width: 100% !important;
    max-width: 980px !important;
    margin: 0 auto !important;
    padding: 8px 12px !important;
    height: calc(100dvh - 56px) !important;
    min-height: calc(100dvh - 56px) !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: space-between !important;
    align-items: stretch !important;
    gap: 10px !important;
    box-sizing: border-box !important;
    overflow: hidden !important;
}

.home-cards-wrapper {
    display: flex !important;
    flex-direction: column !important; /* EMPILHADO VERTICALMENTE */
    flex: 1 1 0 !important;
    gap: 12px !important;
    min-height: 0 !important;
    width: 100% !important;
}

.btn-operador-solido {
    flex: 1 1 0 !important;
    min-height: 0 !important;
    width: 100% !important;
    display: flex !important;
    flex-direction: column !important; /* Ícone no topo, texto embaixo */
    align-items: center !important;
    justify-content: center !important;
    text-align: center !important;
    padding: 8px 14px !important;
    gap: 10px !important;
    border-radius: 28px !important;
    border-width: 6px !important;
    box-sizing: border-box !important;
    text-decoration: none !important;
}

.btn-operador-icone-wrapper {
    width: clamp(120px, 17vh, 185px) !important;
    height: clamp(120px, 17vh, 185px) !important;
    border-radius: 24px !important;
    background: #FFFFFF !important;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.22) !important;
    padding: 4px !important;
    flex-shrink: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.btn-operador-icone-wrapper.btn-operador-icone-emoji {
    background: #FFFFFF !important;
    border: none !important;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.22) !important;
}

.btn-operador-icone-wrapper.btn-operador-icone-emoji span {
    font-size: clamp(65px, 10vh, 95px) !important;
    line-height: 1 !important;
    display: block !important;
}

.btn-operador-img {
    width: 100% !important;
    height: 100% !important;
    object-fit: contain !important;
    border-radius: 20px !important;
    display: block !important;
}

.btn-operador-content {
    align-items: center !important;
    text-align: center !important;
    flex: 0 0 auto !important;
    width: 100% !important;
    gap: 4px !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
}

.btn-operador-label {
    font-size: clamp(26px, 7vw, 38px) !important;
    font-weight: 900 !important;
    justify-content: center !important;
    text-align: center !important;
    gap: 10px !important;
    line-height: 1.15 !important;
    display: flex !important;
    align-items: center !important;
    width: 100% !important;
}

.btn-operador-label svg {
    width: clamp(32px, 5vw, 44px) !important;
    height: clamp(32px, 5vw, 44px) !important;
}

/* Regra Exclusiva para Computador Desktop com Monitor Widescreen Deitado (Landscape >= 992px) */
@media (min-width: 992px) and (orientation: landscape) {
    .home-operador-container {
        padding: 20px 24px !important;
        justify-content: center !important;
        gap: 22px !important;
    }
    .home-cards-wrapper {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 22px !important;
        max-width: 1100px !important;
        margin: 0 auto !important;
        align-items: stretch !important;
        flex: 0 1 auto !important;
    }
    .btn-operador-solido {
        min-height: 280px !important;
        max-height: 350px !important;
        padding: 24px 18px !important;
    }
    .btn-operador-icone-wrapper {
        width: 135px !important;
        height: 135px !important;
    }
    .btn-operador-label {
        font-size: 24px !important;
    }
}
</style>

<div class="home-operador-container">
    
    <!-- Título Principal com Fundo Contrastante Monumental -->
    <div style="text-align: center; margin-bottom: 2px; width: 100%; flex-shrink: 0;">
        <div class="home-title-badge">
            <h1>REPACK</h1>
        </div>
    </div>

    <!-- Contêiner Responsivo dos Botões Operacionais (Adaptação Inteligente Smartphone x PC) -->
    <div class="home-cards-wrapper">
        <!-- Botão 1: REGISTRAR B.O. (Sólido Azul Monumental) -->
        <a href="<?= APP_URL ?>/operador/avaria/passo1" class="btn-operador-solido btn-operador-bo">
            <div class="btn-operador-icone-wrapper">
                <img src="<?= APP_URL ?>/assets/img/btn_bo.jpg?v=<?= @filemtime(__DIR__ . '/../../../public/assets/img/btn_bo.jpg') ?: time() ?>" 
                     alt="Lata amassada - B.O." 
                     class="btn-operador-img">
            </div>
            <div class="btn-operador-content">
                <div class="btn-operador-label">
                    <svg width="44" height="44" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true" style="flex-shrink: 0;">
                        <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/>
                    </svg>
                    <span>REGISTRAR B.O.</span>
                </div>
                <div id="badgeHomeBoAtivo" class="badge-em-andamento" style="<?= $boAtivo ? 'display: inline-flex;' : 'display: none;' ?> background: #DBEAFE; color: #1E40AF; border-color: #3B82F6;">
                    <span>⏸</span> <span>EM ANDAMENTO</span>
                </div>
            </div>
        </a>

        <!-- Botão 2: REGISTRAR PRODUÇÃO (Sólido Verde Monumental) -->
        <a href="<?= APP_URL ?>/operador/cronometro" class="btn-operador-solido btn-operador-producao">
            <div class="btn-operador-icone-wrapper">
                <img src="<?= APP_URL ?>/assets/img/btn_producao.jpg?v=<?= @filemtime(__DIR__ . '/../../../public/assets/img/btn_producao.jpg') ?: time() ?>" 
                     alt="Encolhimento térmico - Produção" 
                     class="btn-operador-img">
            </div>
            <div class="btn-operador-content">
                <div class="btn-operador-label">
                    <svg width="44" height="44" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true" style="flex-shrink: 0;">
                        <path d="M15 1H9v2h6V1zm-4 13h2V8h-2v6zm8.03-6.61l1.42-1.42c-.43-.51-.9-.99-1.41-1.41l-1.42 1.42C16.07 4.74 14.12 4 12 4c-4.97 0-9 4.03-9 9s4.02 9 9 9 9-4.03 9-9c0-2.12-.74-4.07-1.97-5.61zM12 20c-3.87 0-7-3.13-7-7s3.13-7 7-7 7 3.13 7 7-3.13 7-7 7z"/>
                    </svg>
                    <span>REGISTRAR PRODUÇÃO</span>
                </div>
                <div id="badgeHomeProducaoAtiva" class="badge-em-andamento" style="<?= $producaoAtiva ? 'display: inline-flex;' : 'display: none;' ?>">
                    <span>⏸</span> <span>EM ANDAMENTO</span>
                </div>
            </div>
        </a>

        <!-- Botão 3: BAIXA NA LOJINHA (Sólido Vermelho/Coral Monumental - Perdas / Vencimento) -->
        <a href="<?= APP_URL ?>/operador/baixa" class="btn-operador-solido btn-operador-baixa">
            <div class="btn-operador-icone-wrapper btn-operador-icone-emoji">
                <span>🗑️</span>
            </div>
            <div class="btn-operador-content">
                <div class="btn-operador-label">
                    <svg width="44" height="44" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true" style="flex-shrink: 0;">
                        <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                    </svg>
                    <span>BAIXA NA LOJINHA</span>
                </div>
            </div>
        </a>
    </div>

</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    // 1. Monitora rascunho de Produção Ativa
    const localProd = localStorage.getItem('repack_producao_ativa');
    const badgeProd = document.getElementById('badgeHomeProducaoAtiva');
    if (localProd && badgeProd) {
        try {
            const dataProd = JSON.parse(localProd);
            if (dataProd && dataProd.produto_id) {
                badgeProd.style.display = 'inline-flex';
            }
        } catch(e) {}
    }

    // 2. Monitora rascunho de B.O. Ativo (persistência paralela em espera)
    const localBo = localStorage.getItem('repack_bo_ativo');
    const badgeBo = document.getElementById('badgeHomeBoAtivo');
    if (localBo && badgeBo) {
        try {
            const dataBo = JSON.parse(localBo);
            if (dataBo && dataBo.produto_id) {
                badgeBo.style.display = 'inline-flex';
            }
        } catch(e) {}
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
