<?php
declare(strict_types=1);

/**
 * Projeto Repack - Urna Eletrônica (Cronômetro de Produtividade)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md (Seção 12.1 e 13.2)
 */

require_once __DIR__ . '/../../models/Produto.php';

$produtos = Produto::listarAtivos();
$produtoSelecionadoId = (int)($_SESSION['cronometro_temp']['produto_id'] ?? ($produtos[0]['id'] ?? 1));

$pageTitle = "Produtividade - Cronômetro";
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="text-align: center; margin-bottom: 20px;">
    <span class="badge" style="background-color: var(--secondary); color: #FFF; font-size: 16px; padding: 6px 16px; border-radius: 9999px;">
        CRONÔMETRO DE PRODUTIVIDADE
    </span>
    <h1 class="headline-lg" style="margin-top: 10px; margin-bottom: 4px;" id="cronometroStatusTitulo">
        SELECIONE O PRODUTO E INICIE
    </h1>
    <p class="body-md" style="color: var(--on-surface-muted);" id="cronometroStatusSubtitulo">
        Toque em "INICIAR" ao começar a reembalagem do lote
    </p>
</div>

<!-- ETAPA 1: SELEÇÃO DE PRODUTO (Visível antes do início) -->
<div id="secaoSelecaoProduto">
    <div class="produto-grid">
        <?php foreach ($produtos as $p): ?>
            <button type="button" 
                    class="produto-card-btn <?= ((int)$p['id'] === $produtoSelecionadoId) ? 'selecionado' : '' ?>" 
                    id="prodCard_<?= (int)$p['id'] ?>"
                    onclick="selecionarProduto(<?= (int)$p['id'] ?>, '<?= sanitize_html($p['nome']) ?>')">
                <div class="produto-foto-thumb">
                    <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/>
                    </svg>
                </div>
                <div>
                    <div class="body-lg" style="font-weight: 700;"><?= sanitize_html($p['nome']) ?></div>
                    <div class="body-md" style="color: var(--on-surface-muted);"><?= sanitize_html($p['pacote_padrao']) ?></div>
                </div>
            </button>
        <?php endforeach; ?>
    </div>
</div>

<!-- ETAPA 2: DISPLAY DO CRONÔMETRO -->
<div class="urna-card" style="max-width: 650px; margin: 0 auto; text-align: center;">
    <div class="body-lg" id="produtoEmExecucaoDisplay" style="font-weight: 700; color: var(--secondary); margin-bottom: 8px;">
        Produto Selecionado: <?= sanitize_html($produtos[0]['nome'] ?? 'Padrão') ?>
    </div>

    <!-- Visor Numérico Digital em headline-xl (32px+) -->
    <div class="cronometro-visor" id="tempoDisplay">00:00:00</div>

    <!-- ETAPA 3: DIGITAÇÃO DE PACOTES (Aparece ao pausar/finalizar o tempo) -->
    <div id="secaoPacotes" style="display: none; margin-top: 24px; border-top: 2px solid var(--border); padding-top: 20px;">
        <label class="body-lg" style="font-weight: 800; display: block; margin-bottom: 12px;">QUANTOS PACOTES FORAM REFEITOS?</label>
        <div class="seletor-numerico-wrapper">
            <button type="button" class="btn-counter" onclick="alterarPacotes(-1)" aria-label="Diminuir pacotes">−</button>
            <div class="counter-display" id="displayPacotes">1</div>
            <button type="button" class="btn-counter" onclick="alterarPacotes(1)" aria-label="Aumentar pacotes">+</button>
        </div>
    </div>
</div>

<!-- BOTÕES DE CONTROLE DO CRONÔMETRO (Mínimo 80px) -->
<div style="max-width: 650px; margin: 24px auto 0 auto;">
    <!-- Estado Inicial: Botão INICIAR -->
    <div id="controlesInicio" style="display: flex; gap: 16px;">
        <a href="/operador" class="btn-urna btn-urna-warning" style="flex: 1;">
            CANCELAR
        </a>
        <button type="button" class="btn-urna btn-urna-secondary" style="flex: 2;" onclick="iniciarCronometro()">
            <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z"/>
            </svg>
            INICIAR CRONÔMETRO
        </button>
    </div>

    <!-- Estado Rodando: Botão FINALIZAR -->
    <div id="controlesRodando" style="display: none;">
        <button type="button" class="btn-urna btn-urna-primary" onclick="pararCronometro()">
            <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24">
                <path d="M6 6h12v12H6z"/>
            </svg>
            FINALIZAR PROCESSO
        </button>
    </div>

    <!-- Estado Pacotes: Botões de Avanço para Conferência -->
    <div id="controlesAvançar" style="display: none;" class="dupla-confirmacao-rodape">
        <button type="button" class="btn-urna btn-urna-warning" onclick="reiniciarCronometro()">
            REINICIAR
        </button>
        <button type="button" class="btn-urna btn-urna-primary" onclick="avancarParaResumo()">
            CONFERIR RESUMO ➔
        </button>
    </div>
</div>

<script>
let produtoIdSelecionado = <?= $produtoSelecionadoId ?>;
let produtoNomeSelecionado = "<?= addslashes(sanitize_html($produtos[0]['nome'] ?? 'Produto')) ?>";

let timerInterval = null;
let timestampInicio = null;
let timestampFim = null;
let segundosDecorridos = 0;
let pacotesMontados = 1;

function selecionarProduto(id, nome) {
    if (timestampInicio) return; // Não troca se já estiver rodando
    document.querySelectorAll('.produto-card-btn').forEach(btn => btn.classList.remove('selecionado'));
    const btn = document.getElementById('prodCard_' + id);
    if (btn) btn.classList.add('selecionado');
    produtoIdSelecionado = id;
    produtoNomeSelecionado = nome;
    document.getElementById('produtoEmExecucaoDisplay').innerText = 'Produto: ' + nome;
}

function formatarTempo(totalSegundos) {
    const horas = Math.floor(totalSegundos / 3600);
    const minutos = Math.floor((totalSegundos % 3600) / 60);
    const segs = totalSegundos % 60;
    return [horas, minutos, segs].map(v => String(v).padStart(2, '0')).join(':');
}

function iniciarCronometro() {
    timestampInicio = new Date().toISOString();
    localStorage.setItem('repack_timer_inicio', timestampInicio);
    localStorage.setItem('repack_timer_produto_id', produtoIdSelecionado);
    localStorage.setItem('repack_timer_produto_nome', produtoNomeSelecionado);

    document.getElementById('secaoSelecaoProduto').style.display = 'none';
    document.getElementById('controlesInicio').style.display = 'none';
    document.getElementById('controlesRodando').style.display = 'block';

    document.getElementById('cronometroStatusTitulo').innerText = 'REEMBALAGEM EM ANDAMENTO';
    document.getElementById('cronometroStatusSubtitulo').innerText = 'Cronômetro ativo. Pressione FINALIZAR ao concluir o trabalho.';

    segundosDecorridos = 0;
    timerInterval = setInterval(() => {
        segundosDecorridos++;
        document.getElementById('tempoDisplay').innerText = formatarTempo(segundosDecorridos);
    }, 1000);
}

function pararCronometro() {
    clearInterval(timerInterval);
    timestampFim = new Date().toISOString();

    document.getElementById('controlesRodando').style.display = 'none';
    document.getElementById('secaoPacotes').style.display = 'block';
    document.getElementById('controlesAvançar').style.display = 'grid';

    document.getElementById('cronometroStatusTitulo').innerText = 'PROCESSO CONCLUÍDO';
    document.getElementById('cronometroStatusSubtitulo').innerText = 'Informe a quantidade de pacotes prontos para prosseguir.';
}

function alterarPacotes(delta) {
    pacotesMontados = Math.max(1, pacotesMontados + delta);
    document.getElementById('displayPacotes').innerText = pacotesMontados;
}

function reiniciarCronometro() {
    if (confirm('Deseja realmente descartar este tempo e reiniciar?')) {
        clearInterval(timerInterval);
        timestampInicio = null;
        timestampFim = null;
        segundosDecorridos = 0;
        pacotesMontados = 1;
        document.getElementById('tempoDisplay').innerText = '00:00:00';
        document.getElementById('displayPacotes').innerText = '1';
        document.getElementById('secaoPacotes').style.display = 'none';
        document.getElementById('controlesAvançar').style.display = 'none';
        document.getElementById('secaoSelecaoProduto').style.display = 'block';
        document.getElementById('controlesInicio').style.display = 'flex';
        document.getElementById('cronometroStatusTitulo').innerText = 'SELECIONE O PRODUTO E INICIE';
        document.getElementById('cronometroStatusSubtitulo').innerText = 'Toque em "INICIAR" ao começar a reembalagem do lote';
    }
}

function avancarParaResumo() {
    // Salva na sessão via form POST dinâmico
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/operador/cronometro/resumo';

    const campos = {
        produto_id: produtoIdSelecionado,
        produto_nome: produtoNomeSelecionado,
        data_hora_inicio: timestampInicio,
        data_hora_fim: timestampFim,
        duracao_segundos: segundosDecorridos,
        pacotes_refeitos: pacotesMontados
    };

    for (const [chave, valor] of Object.entries(campos)) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = chave;
        input.value = valor;
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
