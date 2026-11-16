<?php
declare(strict_types=1);

/**
 * Projeto Repack - Urna Eletrônica (Produção - Conferência de Lote / Turno Consolidado)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md (Seção 12.1 e 13.2)
 */

require_once __DIR__ . '/../../models/Produto.php';

// Processa o payload do lote vindo do POST ou da sessão
$rawPayload = $_POST['payload_lote'] ?? null;
$dadosLote = null;

if (!empty($rawPayload)) {
    $dadosLote = json_decode($rawPayload, true);
    if (!empty($dadosLote)) {
        $_SESSION['lote_producao_temp'] = $dadosLote;
    }
}

if (!$dadosLote && !empty($_SESSION['lote_producao_temp'])) {
    $dadosLote = $_SESSION['lote_producao_temp'];
}

// Fallback de retrocompatibilidade para apontamento unitário avulso se houver
if (!$dadosLote && !empty($_POST['produto_id'])) {
    $produtoId = (int)$_POST['produto_id'];
    $pacotes = (int)($_POST['pacotes_refeitos'] ?? 1);
    $prod = Produto::buscarPorId($produtoId);
    $dadosLote = [
        'turno' => 'avulso',
        'hora_inicio' => date('H:i', strtotime($_POST['data_hora_inicio'] ?? 'now')),
        'hora_fim' => date('H:i', strtotime($_POST['data_hora_fim'] ?? 'now')),
        'duracao_total_segundos' => (int)($_POST['duracao_segundos'] ?? 60),
        'itens' => [
            [
                'produto_id' => $produtoId,
                'codigo' => $prod['codigo'] ?? '',
                'nome' => $prod['nome'] ?? 'Produto',
                'embalagem' => $prod['pacote_padrao'] ?? '',
                'foto_url' => !empty($prod['foto_url']) ? (APP_URL . $prod['foto_url']) : null,
                'pacotes' => $pacotes
            ]
        ]
    ];
}

$itens = $dadosLote['itens'] ?? [];
$horaInicio = $dadosLote['hora_inicio'] ?? '07:00';
$horaFim = $dadosLote['hora_fim'] ?? '12:00';
$duracaoTotalSegundos = (int)($dadosLote['duracao_total_segundos'] ?? 18000);

$totalPacotes = 0;
foreach ($itens as $it) {
    $totalPacotes += (int)($it['pacotes'] ?? 0);
}

// Formatação amigável de duração nativa PHP
$horas = (int)floor($duracaoTotalSegundos / 3600);
$minutos = (int)floor(($duracaoTotalSegundos % 3600) / 60);
$duracaoFormatada = sprintf('%dh %02dmin', $horas, $minutos);

$segPorPacote = ($totalPacotes > 0) ? (int)round($duracaoTotalSegundos / $totalPacotes) : 0;
$minMedio = (int)($segPorPacote / 60);
$segMedio = (int)($segPorPacote % 60);
$tempoMedioTexto = ($minMedio > 0 ? "{$minMedio} min " : "") . "{$segMedio} s / pct";

$pageTitle = "Produção - Conferência do Lote";
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="text-align: center; margin-bottom: 20px;">
    <h1 class="headline-xl" style="margin: 0; font-size: 50px; font-weight: 900; color: #0F172A;">
        CONFIRMAR PRODUÇÃO
    </h1>
    <span style="display: inline-block; font-size: 22px; font-weight: 900; color: #059669; background: #ECFDF5; border: 2.5px solid #10B981; padding: 6px 22px; border-radius: 9999px; margin-top: 8px;">
        CONFERÊNCIA DO LOTE / TURNO
    </span>
</div>

<!-- ============================================================= -->
<!-- CARTÃO DE RESUMO CONSOLIDADO DO LOTE                          -->
<!-- ============================================================= -->
<div class="urna-card" style="width: 100%; padding: 28px 20px; border-radius: 34px; border: 7px solid #0F172A; box-shadow: 0 14px 34px rgba(0, 0, 0, 0.18); background: #FFFFFF; margin-bottom: 24px;">
    
    <!-- Linha 1: Período e Duração Total com Fontes Monumentais -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; border-bottom: 4px solid #E2E8F0; padding-bottom: 18px; text-align: center;">
        <div>
            <span style="font-size: 22px; font-weight: 900; color: #64748B; text-transform: uppercase;">HORÁRIO DO LOTE:</span>
            <div style="color: #1E40AF; font-size: 38px; font-weight: 900; margin-top: 4px;">
                <?= sanitize_html($horaInicio) ?> ➔ <?= sanitize_html($horaFim) ?>
            </div>
        </div>
        <div>
            <span style="font-size: 22px; font-weight: 900; color: #64748B; text-transform: uppercase;">DURAÇÃO TOTAL:</span>
            <div style="color: #0F172A; font-size: 38px; font-weight: 900; margin-top: 4px;">
                <?= $duracaoFormatada ?>
            </div>
        </div>
    </div>

    <!-- Linha 2: Volume Total e Tempo Médio Recalculados em Tempo Real -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 20px 0; border-bottom: 4px solid #E2E8F0; text-align: center;">
        <div style="border-right: 3px solid #E2E8F0; padding-right: 8px;">
            <span style="font-size: 20px; font-weight: 900; color: #64748B; text-transform: uppercase;">TOTAL REEMBALADO:</span>
            <div style="font-size: 64px; font-weight: 900; color: #0F172A; line-height: 1.05; margin-top: 4px;">
                <span id="displayTotalReembaladoResumo"><?= $totalPacotes ?></span> <span style="font-size: 24px; color: #64748B; font-weight: 800;">pcts</span>
            </div>
        </div>
        <div>
            <span style="font-size: 20px; font-weight: 900; color: #64748B; text-transform: uppercase;">TEMPO MÉDIO:</span>
            <div style="font-size: 38px; font-weight: 900; color: #059669; line-height: 1.15; margin-top: 10px;">
                <span id="displayTempoMedioResumo"><?= $tempoMedioTexto ?></span>
            </div>
        </div>
    </div>

    <!-- Linha 3: Relação Interativa dos Itens (Com Botões +, - e Excluir) -->
    <div style="padding-top: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
            <div style="font-size: 24px; font-weight: 900; color: #0F172A; text-transform: uppercase;">
                ITENS DO LOTE (<span id="displayContadorItens"><?= count($itens) ?></span>):
            </div>
            <span style="font-size: 16px; font-weight: 800; color: #64748B;">
                Ajuste as quantidades se necessário:
            </span>
        </div>

        <div id="containerItensResumo" style="display: flex; flex-direction: column; gap: 14px; max-height: 480px; overflow-y: auto; padding-right: 2px;">
            <!-- Renderizado dinamicamente via JS com controles interativos -->
        </div>

        <div id="avisoLoteVazioResumo" style="display: none; text-align: center; padding: 28px 16px; background: #FEF2F2; border: 3px dashed #EF4444; border-radius: 20px; margin-top: 10px;">
            <div style="font-size: 24px; font-weight: 900; color: #991B1B;">
                ⚠️ Todos os itens foram removidos do lote!
            </div>
            <div style="font-size: 18px; font-weight: 700; color: #DC2626; margin-top: 4px;">
                Clique em "CORRIGIR" abaixo para adicionar novos produtos.
            </div>
        </div>
    </div>
</div>

<!-- ============================================================= -->
<!-- RODAPÉ DE DUPLA CONFIRMAÇÃO DA URNA ELETRÔNICA               -->
<!-- ============================================================= -->
<div class="dupla-confirmacao-rodape" style="width: 100%; margin: 26px 0 0 0; gap: 20px;">
    <a href="<?= APP_URL ?>/operador/cronometro?origem=voltar" class="btn-urna btn-urna-secondary" style="min-height: 135px; font-size: 40px; font-weight: 900; border-radius: 36px; background: linear-gradient(145deg, #2563EB 0%, #1D4ED8 100%); color: #FFFFFF; border: 6px solid #1E40AF; box-shadow: 0 10px 26px rgba(37, 99, 235, 0.35); text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 14px;">
        <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink: 0;">
            <line x1="21" y1="12" x2="3" y2="12"></line>
            <polyline points="11 4 3 12 11 20"></polyline>
        </svg>
        <span>CORRIGIR</span>
    </a>
    <button type="button" class="btn-urna btn-urna-primary" id="btnConfirmarLote" onclick="confirmarLoteProducao()" style="min-height: 135px; font-size: 42px; font-weight: 900; border-radius: 36px;">
        CONFIRMAR
    </button>
</div>

<script>
// Array mutável e reativo de itens na tela de resumo
let itensLoteResumo = <?= json_encode($itens, JSON_UNESCAPED_UNICODE) ?>;
const duracaoTotalSegundos = <?= $duracaoTotalSegundos ?>;
const turnoLote = '<?= sanitize_html($dadosLote['turno'] ?? 'manha') ?>';
const horaInicioLote = '<?= sanitize_html($horaInicio) ?>';
const horaFimLote = '<?= sanitize_html($horaFim) ?>';

window.addEventListener('DOMContentLoaded', () => {
    renderizarItensResumo();
});

function renderizarItensResumo() {
    const container = document.getElementById('containerItensResumo');
    const avisoVazio = document.getElementById('avisoLoteVazioResumo');
    const btnConfirmar = document.getElementById('btnConfirmarLote');
    const displayContador = document.getElementById('displayContadorItens');

    displayContador.innerText = itensLoteResumo.length;

    if (itensLoteResumo.length === 0) {
        container.innerHTML = '';
        avisoVazio.style.display = 'block';
        btnConfirmar.disabled = true;
        btnConfirmar.style.opacity = '0.5';
        atualizarTotaisResumo();
        return;
    }

    avisoVazio.style.display = 'none';
    btnConfirmar.disabled = false;
    btnConfirmar.style.opacity = '1';

    let html = '';
    itensLoteResumo.forEach((item, index) => {
        const fotoThumb = item.foto_url 
            ? `<img src="${escapeHtml(item.foto_url)}" style="width: 58px; height: 58px; object-fit: contain; border-radius: 12px; border: 2px solid #CBD5E1; background: #FFF; padding: 2px; flex-shrink: 0;" alt="Foto">`
            : `<div style="width: 58px; height: 58px; border-radius: 12px; background: #E2E8F0; display: flex; align-items: center; justify-content: center; font-size: 26px; flex-shrink: 0;">📦</div>`;

        html += `
            <div style="display: flex; align-items: center; justify-content: space-between; background: #F8FAFC; border: 3px solid #CBD5E1; border-radius: 20px; padding: 12px 16px; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 12px; flex: 1; min-width: 0; text-align: left;">
                    ${fotoThumb}
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                            <span style="background: #0F172A; color: #FFFFFF; font-size: 15px; font-weight: 900; padding: 2px 8px; border-radius: 6px;">
                                SKU ${escapeHtml(item.codigo)}
                            </span>
                            <span style="font-size: 13px; color: #64748B; font-weight: 700;">
                                ${escapeHtml(item.embalagem || '')}
                            </span>
                        </div>
                        <div style="font-size: 20px; font-weight: 900; color: #0F172A; line-height: 1.2; word-break: break-word;">
                            ${escapeHtml(item.nome)}
                        </div>
                    </div>
                </div>

                <!-- Controles Interativos de Quantidade e Exclusão (+, -, ✕) -->
                <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                    <!-- Botão Menos -->
                    <button type="button" 
                            onclick="alterarPacotesResumo(${index}, -1)" 
                            style="width: 48px; height: 48px; border-radius: 14px; font-size: 28px; font-weight: 900; display: flex; align-items: center; justify-content: center; border: 3px solid #0F172A; background: #FFFFFF; color: #0F172A; cursor: pointer; touch-action: manipulation;" 
                            aria-label="Diminuir pacotes">
                        −
                    </button>

                    <!-- Quantidade em Destaque -->
                    <div style="min-width: 65px; height: 48px; background: #ECFDF5; border: 2.5px solid #10B981; border-radius: 14px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 0 6px;">
                        <span style="font-size: 22px; font-weight: 900; color: #065F46; line-height: 1;">${item.pacotes}</span>
                        <span style="font-size: 11px; font-weight: 800; color: #059669; line-height: 1; margin-top: 1px;">pcts</span>
                    </div>

                    <!-- Botão Mais -->
                    <button type="button" 
                            onclick="alterarPacotesResumo(${index}, 1)" 
                            style="width: 48px; height: 48px; border-radius: 14px; font-size: 26px; font-weight: 900; display: flex; align-items: center; justify-content: center; border: 3px solid #0F172A; background: #FFFFFF; color: #0F172A; cursor: pointer; touch-action: manipulation;" 
                            aria-label="Aumentar pacotes">
                        +
                    </button>

                    <!-- Botão Excluir -->
                    <button type="button" 
                            onclick="removerItemResumo(${index})" 
                            style="width: 48px; height: 48px; border-radius: 14px; font-size: 22px; font-weight: 900; display: flex; align-items: center; justify-content: center; border: 2.5px solid #EF4444; background: #FEF2F2; color: #DC2626; cursor: pointer; margin-left: 4px;" 
                            aria-label="Excluir item do lote">
                        ✕
                    </button>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    atualizarTotaisResumo();
}

function alterarPacotesResumo(index, delta) {
    if (!itensLoteResumo[index]) return;
    const atual = parseInt(itensLoteResumo[index].pacotes, 10) || 1;
    const novo = Math.max(1, atual + delta);
    itensLoteResumo[index].pacotes = novo;

    renderizarItensResumo();
    sincronizarLoteLocalStorage();
}

function removerItemResumo(index) {
    if (!itensLoteResumo[index]) return;
    const nome = itensLoteResumo[index].nome;
    if (confirm(`Remover "${nome}" do lote?`)) {
        itensLoteResumo.splice(index, 1);
        renderizarItensResumo();
        sincronizarLoteLocalStorage();
    }
}

function atualizarTotaisResumo() {
    const totalPacotes = itensLoteResumo.reduce((acc, it) => acc + parseInt(it.pacotes, 10), 0);
    document.getElementById('displayTotalReembaladoResumo').innerText = totalPacotes;

    if (totalPacotes > 0 && duracaoTotalSegundos > 0) {
        const segPorPacote = Math.round(duracaoTotalSegundos / totalPacotes);
        const min = Math.floor(segPorPacote / 60);
        const s = segPorPacote % 60;
        let texto = '';
        if (min > 0) {
            texto += `${min} min `;
        }
        texto += `${s} s / pct`;
        document.getElementById('displayTempoMedioResumo').innerText = texto;
    } else {
        document.getElementById('displayTempoMedioResumo').innerText = '--';
    }
}

function sincronizarLoteLocalStorage() {
    try {
        const rascunho = {
            turno: turnoLote,
            hora_inicio: horaInicioLote,
            hora_fim: horaFimLote,
            itens: itensLoteResumo
        };
        localStorage.setItem('repack_lote_ativo', JSON.stringify(rascunho));
    } catch(e) {}
}

async function confirmarLoteProducao() {
    if (itensLoteResumo.length === 0) {
        alert('O lote está vazio. Adicione pelo menos um produto antes de confirmar.');
        return;
    }

    const btn = document.getElementById('btnConfirmarLote');
    btn.disabled = true;
    btn.innerText = 'GRAVANDO...';

    const operadorId = <?= (int)($_SESSION['operador_id'] ?? 1) ?>;
    const hoje = new Date().toISOString().split('T')[0];

    const horaIni = horaInicioLote;
    const horaFim = horaFimLote;

    const dataHoraInicio = `${hoje} ${horaIni}:00`;
    let dataHoraFim = `${hoje} ${horaFim}:00`;

    if (horaFim <= horaIni) {
        const dFim = new Date(new Date(`${hoje}T${horaFim}:00`).getTime() + 24 * 3600 * 1000);
        dataHoraFim = `${dFim.getFullYear()}-${String(dFim.getMonth()+1).padStart(2,'0')}-${String(dFim.getDate()).padStart(2,'0')} ${horaFim}:00`;
    }

    const payloadItens = itensLoteResumo.map(it => ({
        produto_id: parseInt(it.produto_id, 10),
        pacotes_refeitos: parseInt(it.pacotes, 10)
    }));

    const payload = {
        usuario_id: operadorId,
        data_hora_inicio: dataHoraInicio,
        data_hora_fim: dataHoraFim,
        itens: payloadItens,
        created_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
    };

    try {
        const response = await fetch('<?= APP_URL ?>/api/produtividade/salvar-lote', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (response.ok && data.status === 'success') {
            try { localStorage.removeItem('repack_lote_ativo'); } catch(e) {}
            try { localStorage.removeItem('repack_producao_ativa'); } catch(e) {}
            
            exibirSucessoFeedback('LOTE GRAVADO!', `${payloadItens.length} itens registrados com sucesso.`, () => {
                window.location.href = '<?= APP_URL ?>/operador';
            });
        } else {
            throw new Error(data.message || 'Falha ao salvar o lote.');
        }
    } catch (err) {
        console.warn('Falha na gravação online do lote:', err);
        if (window.RepackOffline) {
            try {
                for (const item of payloadItens) {
                    await window.RepackOffline.salvarProdutividade({
                        produto_id: item.produto_id,
                        usuario_id: operadorId,
                        data_hora_inicio: dataHoraInicio,
                        data_hora_fim: dataHoraFim,
                        pacotes_refeitos: item.pacotes_refeitos,
                        duracao_segundos: Math.max(1, Math.round(duracaoTotalSegundos / payloadItens.length)),
                        created_at: payload.created_at
                    });
                }
                try { localStorage.removeItem('repack_lote_ativo'); } catch(e) {}
                try { localStorage.removeItem('repack_producao_ativa'); } catch(e) {}
                exibirSucessoFeedback('SALVO OFFLINE!', 'O lote será sincronizado automaticamente.', () => {
                    window.location.href = '<?= APP_URL ?>/operador';
                });
                return;
            } catch(eOffline) {
                console.error('Falha no fallback offline:', eOffline);
            }
        }

        alert('Erro ao gravar lote: ' + err.message);
        btn.disabled = false;
        btn.innerText = 'CONFIRMAR';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text || '';
    return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
