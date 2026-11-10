/**
 * Projeto Repack - Otimizador de Toque para Luvas Anti-Corte
 * Baseado estritamente em docs/FSD.md e docs/DESIGN.md
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Garante touch-action manipulation em elementos clicáveis
    const interactiveElements = document.querySelectorAll('button, a, .btn-urna, .btn-counter, input, select');
    interactiveElements.forEach(el => {
        el.style.touchAction = 'manipulation';
    });

    // 2. Previne zoom involuntário por duplo toque no ambiente quiosque
    let lastTouchEnd = 0;
    document.addEventListener('touchend', (event) => {
        const now = Date.now();
        if (now - lastTouchEnd <= 300) {
            // Evita duplo toque causando zoom na tela do operador
            event.preventDefault();
        }
        lastTouchEnd = now;
    }, { passive: false });

    // 3. Efeito visual tátil instantâneo para luvas industriais
    document.addEventListener('pointerdown', (e) => {
        const target = e.target.closest('.btn-urna, .btn-counter, .btn-trocar-operador');
        if (target) {
            target.classList.add('is-active-touch');
        }
    });

    const removeTouchState = () => {
        document.querySelectorAll('.is-active-touch').forEach(el => {
            el.classList.remove('is-active-touch');
        });
    };

    document.addEventListener('pointerup', removeTouchState);
    document.addEventListener('pointercancel', removeTouchState);
});
