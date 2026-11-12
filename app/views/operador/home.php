<?php
declare(strict_types=1);

/**
 * Projeto Repack - Urna Eletrônica (Home do Operador - Escala Monumental)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md (Seção 12.1)
 */

$producaoAtiva = $_SESSION['producao_ativa'] ?? null;

$pageTitle = "Repack - Início";
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="home-operador-container">
    
    <!-- Título Principal com Fundo Contrastante Monumental -->
    <div style="text-align: center; margin-bottom: 2px; width: 100%; flex-shrink: 0;">
        <div class="home-title-badge">
            <h1>REPACK</h1>
        </div>
    </div>

    <!-- Botão 1: REGISTRAR B.O. (Sólido Azul Monumental) -->
    <a href="<?= APP_URL ?>/operador/avaria/passo1" class="btn-operador-solido btn-operador-bo">
        <div class="btn-operador-icone-wrapper">
            <img src="<?= APP_URL ?>/assets/img/btn_bo.jpg?v=<?= @filemtime(__DIR__ . '/../../../public/assets/img/btn_bo.jpg') ?: time() ?>" 
                 alt="Lata amassada - B.O." 
                 class="btn-operador-img">
        </div>
        <div class="btn-operador-label">
            <span>REGISTRAR B.O.</span>
        </div>
    </a>

    <!-- Botão 2: REGISTRAR PRODUÇÃO (Sólido Verde Monumental) -->
    <a href="<?= APP_URL ?>/operador/cronometro?novo=1" class="btn-operador-solido btn-operador-producao">
        <div class="btn-operador-icone-wrapper">
            <img src="<?= APP_URL ?>/assets/img/btn_producao.jpg?v=<?= @filemtime(__DIR__ . '/../../../public/assets/img/btn_producao.jpg') ?: time() ?>" 
                 alt="Encolhimento térmico - Produção" 
                 class="btn-operador-img">
        </div>
        <div class="btn-operador-label">
            <span>REGISTRAR PRODUÇÃO</span>
            <div id="badgeHomeProducaoAtiva" style="<?= $producaoAtiva ? 'display: inline-flex;' : 'display: none;' ?> align-items: center; gap: 8px; background: #FEF3C7; color: #92400E; border: 3px solid #F59E0B; padding: 4px 16px; border-radius: 9999px; font-size: 16px; font-weight: 900; margin-left: 10px;">
                <span>⏸</span> <span>EM ANDAMENTO</span>
            </div>
        </div>
    </a>

    <!-- Botão 3: BAIXA NA LOJINHA (Sólido Vermelho/Coral Monumental - Perdas / Vencimento) -->
    <a href="<?= APP_URL ?>/operador/baixa" class="btn-operador-solido btn-operador-baixa">
        <div class="btn-operador-icone-wrapper btn-operador-icone-emoji">
            <span style="font-size: 48px; line-height: 1;">🗑️</span>
        </div>
        <div class="btn-operador-label">
            <span>BAIXA NA LOJINHA</span>
        </div>
    </a>

</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    const local = localStorage.getItem('repack_producao_ativa');
    const badge = document.getElementById('badgeHomeProducaoAtiva');
    if (local && badge) {
        try {
            const data = JSON.parse(local);
            if (data && data.produto_id) {
                badge.style.display = 'inline-flex';
            }
        } catch(e) {}
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
