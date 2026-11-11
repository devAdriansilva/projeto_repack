<?php
declare(strict_types=1);

/**
 * Projeto Repack - Layout Footer (Urna Eletrônica / Quiosque)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md
 */
?>
</main>

<script src="<?= APP_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/touch-handler.js"></script>
<script src="<?= APP_URL ?>/assets/js/app-offline.js"></script>

<script>
const BASE_APP_URL = '<?= APP_URL ?>';

// Gerenciamento de Modais do Quiosque
function abrirModalOperadores() {
    const modal = document.getElementById('modalOperadoresBackdrop');
    if (modal) modal.classList.add('ativo');
}

function fecharModalOperadores() {
    const modal = document.getElementById('modalOperadoresBackdrop');
    if (modal) modal.classList.remove('ativo');
}

function selecionarOperador(id, nome) {
    fetch(BASE_APP_URL + '/operador/selecionar-operador', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ operador_id: id, operador_nome: nome })
    }).then(res => res.json()).then(data => {
        if (data.status === 'success') {
            atualizarNomeOperadorLocal(nome, id);
            fecharModalOperadores();
        }
    }).catch(err => {
        // Fallback local se estiver offline
        atualizarNomeOperadorLocal(nome, id);
        fecharModalOperadores();
    });
}

function atualizarNomeOperadorLocal(nome, id) {
    localStorage.setItem('repack_operador_id', id);
    localStorage.setItem('repack_operador_nome', nome);
    const el1 = document.getElementById('nomeOperadorDisplay');
    if (el1) el1.innerText = nome;
    const el2 = document.getElementById('nomeOperadorDisplayModal');
    if (el2) el2.innerText = nome;
}

// Modal de Configurações e Gestão (Aberto após 5 segundos)
function abrirModalConfiguracoes() {
    const erroDiv = document.getElementById('gestaoLoginErro');
    if (erroDiv) {
        erroDiv.style.display = 'none';
        erroDiv.innerText = '';
    }
    const modal = document.getElementById('modalConfiguracoesBackdrop');
    if (modal) modal.classList.add('ativo');
}

function fecharModalConfiguracoes() {
    const modal = document.getElementById('modalConfiguracoesBackdrop');
    if (modal) modal.classList.remove('ativo');
}

// Retrocompatibilidade
function abrirModalGestao() {
    abrirModalConfiguracoes();
}

function fecharModalGestao() {
    fecharModalConfiguracoes();
}

function realizarLoginGestao(event) {
    event.preventDefault();
    const matricula = document.getElementById('gestao_matricula').value;
    const senha = document.getElementById('gestao_senha').value;
    const erroDiv = document.getElementById('gestaoLoginErro');

    fetch(BASE_APP_URL + '/api/gestao/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ matricula, senha })
    }).then(res => res.json()).then(data => {
        if (data.status === 'success') {
            window.location.href = BASE_APP_URL + '/gestao/dashboard';
        } else {
            erroDiv.innerText = data.message || 'Credenciais inválidas.';
            erroDiv.style.display = 'block';
        }
    }).catch(err => {
        erroDiv.innerText = 'Erro ao conectar. Tente novamente.';
        erroDiv.style.display = 'block';
    });
}

// Exibição de Feedback de Sucesso Temporizado (2 segundos)
function exibirSucessoFeedback(titulo, subtitulo, callback) {
    const overlay = document.getElementById('feedbackSucessoOverlay');
    if (titulo) document.getElementById('feedbackTitulo').innerText = titulo;
    if (subtitulo) document.getElementById('feedbackSubtitulo').innerText = subtitulo;
    
    overlay.classList.add('ativo');
    setTimeout(() => {
        overlay.classList.remove('ativo');
        if (typeof callback === 'function') {
            callback();
        }
    }, 2000);
}

// Controle do Botão de Engrenagem com Proteção de Toque Acidental (5 Segundos)
(function() {
    const btn = document.getElementById('btnEngrenagemQuiosque');
    const circle = document.getElementById('gearProgressCircle');
    const hint = document.getElementById('gearHintText');
    if (!btn || !circle) return;

    let pressTimer = null;
    let startTimestamp = null;
    const TOTAL_MS = 5000;
    const CIRCUMFERENCE = 125.66;

    function resetPress() {
        if (pressTimer) {
            cancelAnimationFrame(pressTimer);
            pressTimer = null;
        }
        startTimestamp = null;
        circle.style.strokeDashoffset = CIRCUMFERENCE;
        if (hint) hint.style.display = 'none';
        btn.style.transform = 'scale(1)';
    }

    function tick(timestamp) {
        if (!startTimestamp) startTimestamp = timestamp;
        const elapsed = timestamp - startTimestamp;
        const progress = Math.min(1, elapsed / TOTAL_MS);
        circle.style.strokeDashoffset = CIRCUMFERENCE * (1 - progress);

        if (hint) {
            const restante = Math.ceil((TOTAL_MS - elapsed) / 1000);
            hint.innerText = `Segure por mais ${restante}s...`;
            hint.style.display = 'block';
        }

        if (elapsed >= TOTAL_MS) {
            resetPress();
            if (navigator.vibrate) {
                navigator.vibrate([100, 50, 100]);
            }
            abrirModalConfiguracoes();
        } else {
            pressTimer = requestAnimationFrame(tick);
        }
    }

    function startPress(e) {
        if (e.type === 'touchstart') e.preventDefault();
        resetPress();
        btn.style.transform = 'scale(0.92)';
        pressTimer = requestAnimationFrame(tick);
    }

    btn.addEventListener('mousedown', startPress);
    btn.addEventListener('touchstart', startPress, { passive: false });

    btn.addEventListener('mouseup', resetPress);
    btn.addEventListener('mouseleave', resetPress);
    btn.addEventListener('touchend', resetPress);
    btn.addEventListener('touchcancel', resetPress);
    btn.addEventListener('contextmenu', (e) => e.preventDefault());
})();

// Atualização de Status Online/Offline
function atualizarStatusConexao() {
    const badges = document.querySelectorAll('#statusBadgeModal, #statusBadge');
    badges.forEach(b => {
        if (navigator.onLine) {
            b.className = 'status-badge online';
            b.innerText = '● Conectado';
        } else {
            b.className = 'status-badge offline';
            b.innerText = '○ Modo Offline';
        }
    });
}
window.addEventListener('online', atualizarStatusConexao);
window.addEventListener('offline', atualizarStatusConexao);
</script>

</body>
</html>
