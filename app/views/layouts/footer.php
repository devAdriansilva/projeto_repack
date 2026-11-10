<?php
declare(strict_types=1);

/**
 * Projeto Repack - Layout Footer (Urna Eletrônica / Quiosque)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md
 */
?>
</main>

<script src="/assets/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/touch-handler.js"></script>
<script src="/assets/js/app-offline.js"></script>

<script>
// Gerenciamento de Modais do Quiosque
function abrirModalOperadores() {
    document.getElementById('modalOperadoresBackdrop').classList.add('ativo');
}

function fecharModalOperadores() {
    document.getElementById('modalOperadoresBackdrop').classList.remove('ativo');
}

function selecionarOperador(id, nome) {
    fetch('/operador/selecionar-operador', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ operador_id: id, operador_nome: nome })
    }).then(res => res.json()).then(data => {
        if (data.status === 'success') {
            document.getElementById('nomeOperadorDisplay').innerText = nome;
            localStorage.setItem('repack_operador_id', id);
            localStorage.setItem('repack_operador_nome', nome);
            fecharModalOperadores();
        }
    }).catch(err => {
        // Fallback local se estiver offline
        localStorage.setItem('repack_operador_id', id);
        localStorage.setItem('repack_operador_nome', nome);
        document.getElementById('nomeOperadorDisplay').innerText = nome;
        fecharModalOperadores();
    });
}

function abrirModalGestao() {
    document.getElementById('gestaoLoginErro').style.display = 'none';
    document.getElementById('gestaoLoginErro').innerText = '';
    document.getElementById('modalGestaoBackdrop').classList.add('ativo');
}

function fecharModalGestao() {
    document.getElementById('modalGestaoBackdrop').classList.remove('ativo');
}

function realizarLoginGestao(event) {
    event.preventDefault();
    const matricula = document.getElementById('gestao_matricula').value;
    const senha = document.getElementById('gestao_senha').value;
    const erroDiv = document.getElementById('gestaoLoginErro');

    fetch('/api/gestao/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ matricula, senha })
    }).then(res => res.json()).then(data => {
        if (data.status === 'success') {
            window.location.href = '/gestao/dashboard';
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
</script>

</body>
</html>
