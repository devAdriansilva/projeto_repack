<?php
declare(strict_types=1);

/**
 * Projeto Repack - View Gestao Dashboard com Telemetria em Tempo Real e Filtros
 * Baseado estritamente em docs/FSD.md (Seções 6.4, 8, 12.3, 14, 17 e 22) e docs/DESIGN.md
 */

require __DIR__ . '/../layouts/gestao_header.php';

$periodoAtual = $filtrosAtivos['periodo'] ?? 'hoje';
$dataInicioAtual = $filtrosAtivos['data_inicio'] ?? '';
$dataFimAtual = $filtrosAtivos['data_fim'] ?? '';
$tipoAvariaAtual = $filtrosAtivos['tipo_avaria'] ?? '';
$destinoAtual = $filtrosAtivos['destino'] ?? '';
$produtoIdAtual = !empty($filtrosAtivos['produto_id']) ? (int)$filtrosAtivos['produto_id'] : '';
$usuarioIdAtual = !empty($filtrosAtivos['usuario_id']) ? (int)$filtrosAtivos['usuario_id'] : '';

// Labels amigáveis para tipos de avaria
$labelsTipoAvaria = [
    'amassada' => 'Lata/Item Amassado',
    'def_rotulo' => 'Defeito no Rótulo',
    'emba_danificada' => 'Embalagem Danificada',
    'furo' => 'Furo / Rasgo Aberto',
    'micro_furo' => 'Micro Furo'
];
?>

<style>
    .live-indicator {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border-radius: 9999px;
        background-color: #ECFDF5;
        color: #065F46;
        font-size: 13px;
        font-weight: 700;
        border: 1px solid #A7F3D0;
    }
    .live-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #10B981;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: pulse-green 2s infinite;
    }
    @keyframes pulse-green {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }
    .pill-filter {
        border-radius: 20px;
        padding: 6px 14px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        color: #4B5563;
        background: #F3F4F6;
        border: 1px solid #E5E7EB;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .pill-filter:hover {
        background: #E5E7EB;
        color: #1F2937;
    }
    .pill-filter.active {
        background: #1E3A8A;
        color: #FFFFFF;
        border-color: #1E3A8A;
        box-shadow: 0 2px 4px rgba(30, 58, 138, 0.2);
    }
    .badge-origem-online {
        background-color: #E0E7FF;
        color: #3730A3;
        font-size: 11px;
        font-weight: 600;
        padding: 3px 6px;
        border-radius: 4px;
    }
    .badge-origem-offline {
        background-color: #FEF3C7;
        color: #92400E;
        font-size: 11px;
        font-weight: 600;
        padding: 3px 6px;
        border-radius: 4px;
    }
    .table-feed th {
        font-size: 12px;
        padding: 8px 12px;
    }
    .table-feed td {
        font-size: 13px;
        padding: 10px 12px;
    }
</style>

<!-- Cabeçalho Principal com Ações e Telemetria -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h1 class="headline-lg mb-0" style="color: var(--gestao-text); font-size: 24px;">Painel Consolidado da Gestão</h1>
            <span class="live-indicator" id="badgeLiveStatus" title="Monitoramento conectado em tempo real">
                <span class="live-dot"></span>
                <span>AO VIVO</span>
            </span>
        </div>
        <p class="body-md text-muted mb-0">Telemetria de produtividade, acompanhamento de avarias e saldos da lojinha em tempo real.</p>
    </div>
    
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <div class="d-flex align-items-center gap-2 bg-white px-3 py-1 rounded border shadow-sm">
            <div class="form-check form-switch m-0" style="min-height: auto;">
                <input class="form-check-input" type="checkbox" role="switch" id="switchAutoRefresh" checked style="cursor: pointer;">
                <label class="form-check-label small fw-bold text-muted" for="switchAutoRefresh" style="cursor: pointer;">
                    Auto-atualizar (30s)
                </label>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" id="btnManualRefresh" title="Atualizar agora">
                🔄 Atualizar
            </button>
            <span class="text-muted small border-start ps-2" id="txtLastSync" style="font-size: 11px;">
                <?= date('H:i:s') ?>
            </span>
        </div>
        <a href="<?= APP_URL ?>/gestao/bo" class="btn btn-gestao btn-gestao-secondary text-nowrap" style="padding: 6px 12px; font-size: 13px;">
            📋 Retificar B.O.
        </a>
        <a href="<?= APP_URL ?>/gestao/lotes" class="btn btn-gestao btn-gestao-primary text-nowrap" style="padding: 6px 12px; font-size: 13px;">
            🏷️ Lotes
        </a>
    </div>
</div>

<!-- Card de Filtros Avançados por Período e Categorias -->
<div class="card-gestao mb-4 p-3" style="background: #FFFFFF;">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
        <div class="d-flex flex-wrap align-items-center gap-1">
            <span class="small fw-bold text-uppercase text-muted me-2">Período Rápido:</span>
            <a href="?periodo=hoje" class="pill-filter <?= $periodoAtual === 'hoje' ? 'active' : '' ?>">
                ⚡ Hoje (Tempo Real)
            </a>
            <a href="?periodo=ontem" class="pill-filter <?= $periodoAtual === 'ontem' ? 'active' : '' ?>">
                Ontem
            </a>
            <a href="?periodo=7dias" class="pill-filter <?= $periodoAtual === '7dias' ? 'active' : '' ?>">
                Últimos 7 dias
            </a>
            <a href="?periodo=mes" class="pill-filter <?= $periodoAtual === 'mes' ? 'active' : '' ?>">
                Mês Atual
            </a>
            <a href="?periodo=tudo" class="pill-filter <?= $periodoAtual === 'tudo' ? 'active' : '' ?>">
                Todo o Histórico
            </a>
            <button type="button" class="pill-filter <?= $periodoAtual === 'custom' ? 'active' : '' ?>" id="btnToggleCustomDates" onclick="toggleCustomFilter()">
                📅 Intervalo Específico <?= $periodoAtual === 'custom' ? '▾' : '▸' ?>
            </button>
        </div>

        <?php if ($periodoAtual !== 'hoje' || !empty($tipoAvariaAtual) || !empty($destinoAtual) || !empty($produtoIdAtual) || !empty($usuarioIdAtual)): ?>
            <a href="<?= APP_URL ?>/gestao/dashboard?periodo=hoje" class="btn btn-sm btn-link text-danger text-decoration-none fw-bold p-0">
                ✕ Redefinir para Hoje
            </a>
        <?php endif; ?>
    </div>

    <!-- Formulário de Filtro Granular (Categorias, Operador, Produto e Datas) -->
    <form action="<?= APP_URL ?>/gestao/dashboard" method="GET" id="formFiltroDashboard" class="row g-2 align-items-end">
        <input type="hidden" name="periodo" id="inputPeriodo" value="<?= sanitize_html($periodoAtual) ?>">

        <!-- Bloco de Datas Customizadas (visível se custom ou expandido) -->
        <div class="col-12 col-sm-6 col-md-2" id="boxDataInicio" style="<?= $periodoAtual === 'custom' ? '' : 'display: none;' ?>">
            <label for="filtro_inicio" class="form-label small fw-bold text-muted mb-1">Data Início</label>
            <input type="date" class="form-control form-control-sm" id="filtro_inicio" name="data_inicio" value="<?= sanitize_html($dataInicioAtual) ?>">
        </div>
        <div class="col-12 col-sm-6 col-md-2" id="boxDataFim" style="<?= $periodoAtual === 'custom' ? '' : 'display: none;' ?>">
            <label for="filtro_fim" class="form-label small fw-bold text-muted mb-1">Data Término</label>
            <input type="date" class="form-control form-control-sm" id="filtro_fim" name="data_fim" value="<?= sanitize_html($dataFimAtual) ?>">
        </div>

        <!-- Filtro por Categoria de Avaria -->
        <div class="col-12 col-sm-6 col-md-2">
            <label for="filtro_categoria" class="form-label small fw-bold text-muted mb-1">Categoria de Avaria</label>
            <select class="form-select form-select-sm" id="filtro_categoria" name="tipo_avaria">
                <option value="">Todas as categorias</option>
                <?php foreach ($labelsTipoAvaria as $slug => $rotulo): ?>
                    <option value="<?= $slug ?>" <?= $tipoAvariaAtual === $slug ? 'selected' : '' ?>>
                        <?= sanitize_html($rotulo) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Filtro por Destino -->
        <div class="col-12 col-sm-6 col-md-2">
            <label for="filtro_destino" class="form-label small fw-bold text-muted mb-1">Destino do B.O.</label>
            <select class="form-select form-select-sm" id="filtro_destino" name="destino">
                <option value="">Todos os destinos</option>
                <option value="lojinha" <?= $destinoAtual === 'lojinha' ? 'selected' : '' ?>>Lojinha (Leves)</option>
                <option value="descarte" <?= $destinoAtual === 'descarte' ? 'selected' : '' ?>>Descarte (Furo/Rasgo)</option>
            </select>
        </div>

        <!-- Filtro por Operador -->
        <div class="col-12 col-sm-6 col-md-2">
            <label for="filtro_operador" class="form-label small fw-bold text-muted mb-1">Operador / Bancada</label>
            <select class="form-select form-select-sm" id="filtro_operador" name="usuario_id">
                <option value="">Todos os operadores</option>
                <?php foreach ($operadoresAtivos as $op): ?>
                    <option value="<?= (int)$op['id'] ?>" <?= $usuarioIdAtual === (int)$op['id'] ? 'selected' : '' ?>>
                        <?= sanitize_html($op['nome']) ?> (<?= sanitize_html($op['matricula']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Filtro por Produto -->
        <div class="col-12 col-sm-6 col-md-2">
            <label for="filtro_produto" class="form-label small fw-bold text-muted mb-1">Produto</label>
            <select class="form-select form-select-sm" id="filtro_produto" name="produto_id">
                <option value="">Todos os produtos</option>
                <?php foreach ($todosProdutos as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" <?= $produtoIdAtual === (int)$p['id'] ? 'selected' : '' ?>>
                        <?= sanitize_html($p['nome']) ?> (<?= sanitize_html($p['codigo']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Botão Aplicar -->
        <div class="col-12 col-sm-6 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold">
                🔍 Filtrar
            </button>
        </div>
    </form>
</div>

<!-- Grade de Indicadores Principais (Calculados dinamicamente com base no filtro) -->
<div class="row g-3 mb-4">
    <!-- Card 1: Recuperação para Lojinha -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-gestao card-metric h-100 p-3" style="border-left-color: var(--gestao-primary);">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small fw-bold text-uppercase">Recuperados (Lojinha)</span>
                <span class="badge bg-success bg-opacity-10 text-success fw-bold" id="badgeTaxaRecup"><?= $taxaRecuperacao ?>% taxa</span>
            </div>
            <div class="d-flex align-items-baseline gap-2">
                <span class="headline-xl" id="valTotalRecuperado" style="color: var(--gestao-primary); font-size: 32px;">
                    <?= number_format($totalRecuperado, 0, ',', '.') ?>
                </span>
                <span class="text-muted small">unidades</span>
            </div>
            <div class="mt-2 small text-muted" id="txtTotalAvarias">
                Total de avarias no filtro: <strong><?= number_format($totalAvarias, 0, ',', '.') ?> un</strong>
            </div>
        </div>
    </div>

    <!-- Card 2: Descarte Irreversível -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-gestao card-metric h-100 p-3" style="border-left-color: var(--gestao-danger);">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small fw-bold text-uppercase">Descarte Irreversível</span>
                <span class="badge bg-danger bg-opacity-10 text-danger fw-bold">Perda Furo</span>
            </div>
            <div class="d-flex align-items-baseline gap-2">
                <span class="headline-xl" id="valTotalDescarte" style="color: var(--gestao-danger); font-size: 32px;">
                    <?= number_format($totalDescarte, 0, ',', '.') ?>
                </span>
                <span class="text-muted small">unidades</span>
            </div>
            <div class="mt-2 small text-muted">
                Furos, rasgos abertos e contaminações
            </div>
        </div>
    </div>

    <!-- Card 3: Produtividade na Bancada -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-gestao card-metric h-100 p-3" style="border-left-color: var(--gestao-warning);">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small fw-bold text-uppercase">Produtividade Repack</span>
                <span class="badge bg-warning bg-opacity-10 text-dark fw-bold" id="badgeRitmoHora"><?= $pacotesPorHora ?> pct/h</span>
            </div>
            <div class="d-flex align-items-baseline gap-2">
                <span class="headline-xl" id="valTotalPacotes" style="color: var(--gestao-text); font-size: 32px;">
                    <?= number_format($totalPacotes, 0, ',', '.') ?>
                </span>
                <span class="text-muted small">pacotes montados</span>
            </div>
            <div class="mt-2 small text-muted" id="txtTempoMedio">
                Tempo Médio: <strong><?= $tempoMedioPorPacote ?> seg/pct</strong> em <?= $totalCiclos ?> ciclo(s)
            </div>
        </div>
    </div>

    <!-- Card 4: Saldo Disponível na Lojinha -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-gestao card-metric h-100 p-3" style="border-left-color: var(--gestao-secondary);">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small fw-bold text-uppercase">Disponível p/ Venda</span>
                <span class="badge bg-primary bg-opacity-10 text-primary fw-bold">Vitrine</span>
            </div>
            <div class="d-flex align-items-baseline gap-2">
                <span class="headline-xl" id="valEstoqueDisponivel" style="color: var(--gestao-secondary); font-size: 32px;">
                    <?= number_format($estoqueDisponivel, 0, ',', '.') ?>
                </span>
                <span class="text-muted small">unidades</span>
            </div>
            <div class="mt-1 small text-muted">
                Físico: <?= number_format($estoqueTotal, 0, ',', '.') ?> | Reservado: <?= number_format($estoqueReservado, 0, ',', '.') ?>
            </div>
            <?php if (!empty($ultimaEntrada)): ?>
                <div class="mt-2 pt-2 border-top small" style="font-size: 11px; color: #475569;" title="<?= sanitize_html($ultimaEntrada['descricao']) ?>">
                    🕒 Última entrada: <strong><?= sanitize_html($ultimaEntrada['data_formatada']) ?></strong> (<?= sanitize_html($ultimaEntrada['tempo_relativo']) ?> &bull; +<?= (int)$ultimaEntrada['quantidade'] ?> un)
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Duplo Feed em Tempo Real: B.O. (Avarias) vs Produtividade (Bancada) -->
<div class="row g-4 mb-4">
    <!-- Coluna 1: Lançamentos de B.O. em Tempo Real -->
    <div class="col-12 col-xl-6">
        <div class="card-gestao h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span style="font-size: 20px;">🚨</span>
                    <h2 class="headline-lg mb-0" style="font-size: 18px;">Lançamentos de B.O. (Avarias)</h2>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary" id="badgeContagemBO"><?= count($ultimosBOs) ?> registros</span>
                    <a href="<?= APP_URL ?>/gestao/bo" class="small fw-bold text-decoration-none">Ver auditoria →</a>
                </div>
            </div>

            <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                <table class="table-gestao table-feed" id="tabelaBO">
                    <thead>
                        <tr>
                            <th>Horário</th>
                            <th>Produto</th>
                            <th>Operador</th>
                            <th>Qtd</th>
                            <th>Categoria / Avaria</th>
                            <th>Destino</th>
                        </tr>
                    </thead>
                    <tbody id="corpoTabelaBO">
                        <?php if (empty($ultimosBOs)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Nenhum apontamento de avaria registrado para o filtro selecionado.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ultimosBOs as $bo): ?>
                                <tr>
                                    <td class="text-nowrap">
                                        <strong><?= date('H:i', strtotime($bo['created_at'])) ?></strong>
                                        <div class="text-muted" style="font-size: 11px;"><?= date('d/m', strtotime($bo['created_at'])) ?></div>
                                    </td>
                                    <td>
                                        <strong class="d-block text-truncate" style="max-width: 140px;" title="<?= sanitize_html($bo['produto_nome']) ?>">
                                            <?= sanitize_html($bo['produto_nome']) ?>
                                        </strong>
                                        <span class="text-muted" style="font-size: 11px;"><?= sanitize_html($bo['produto_codigo']) ?></span>
                                    </td>
                                    <td>
                                        <span class="d-block text-truncate" style="max-width: 110px;" title="<?= sanitize_html($bo['operador_nome']) ?>">
                                            <?= sanitize_html($bo['operador_nome']) ?>
                                        </span>
                                        <span class="text-muted" style="font-size: 11px;"><?= sanitize_html($bo['operador_matricula'] ?? '') ?></span>
                                    </td>
                                    <td>
                                        <strong style="font-size: 15px; color: #111827;"><?= (int)$bo['quantidade'] ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= sanitize_html($labelsTipoAvaria[$bo['tipo_avaria']] ?? ucfirst(str_replace('_', ' ', $bo['tipo_avaria']))) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-status <?= $bo['destino'] === 'lojinha' ? 'lojinha' : 'descarte' ?>">
                                            <?= $bo['destino'] === 'lojinha' ? 'Lojinha' : 'Descarte' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Coluna 2: Produtividade na Bancada em Tempo Real -->
    <div class="col-12 col-xl-6">
        <div class="card-gestao h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span style="font-size: 20px;">⏱️</span>
                    <h2 class="headline-lg mb-0" style="font-size: 18px;">Produtividade da Bancada (Cronômetro)</h2>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary" id="badgeContagemProd"><?= count($ultimosProd) ?> ciclos</span>
                    <a href="<?= APP_URL ?>/gestao/relatorios" class="small fw-bold text-decoration-none">Extrato analítico →</a>
                </div>
            </div>

            <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                <table class="table-gestao table-feed" id="tabelaProd">
                    <thead>
                        <tr>
                            <th>Horário</th>
                            <th>Operador</th>
                            <th>Produto</th>
                            <th>Pacotes</th>
                            <th>Duração</th>
                            <th>Ritmo</th>
                        </tr>
                    </thead>
                    <tbody id="corpoTabelaProd">
                        <?php if (empty($ultimosProd)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Nenhum apontamento de produtividade registrado para o filtro selecionado.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ultimosProd as $pr): 
                                $minutos = floor((int)$pr['duracao_segundos'] / 60);
                                $segundosRestantes = (int)$pr['duracao_segundos'] % 60;
                                $duracaoFmt = sprintf('%02d:%02d', $minutos, $segundosRestantes);
                                $ritmo = (int)$pr['pacotes_refeitos'] > 0 
                                    ? round((int)$pr['duracao_segundos'] / (int)$pr['pacotes_refeitos'], 1) 
                                    : 0;
                            ?>
                                <tr>
                                    <td class="text-nowrap">
                                        <strong><?= date('H:i', strtotime($pr['created_at'])) ?></strong>
                                        <div class="text-muted" style="font-size: 11px;"><?= date('d/m', strtotime($pr['created_at'])) ?></div>
                                    </td>
                                    <td>
                                        <strong class="d-block text-truncate" style="max-width: 120px;" title="<?= sanitize_html($pr['operador_nome']) ?>">
                                            <?= sanitize_html($pr['operador_nome']) ?>
                                        </strong>
                                        <span class="text-muted" style="font-size: 11px;"><?= sanitize_html($pr['operador_matricula'] ?? '') ?></span>
                                    </td>
                                    <td>
                                        <span class="d-block text-truncate" style="max-width: 130px;" title="<?= sanitize_html($pr['produto_nome']) ?>">
                                            <?= sanitize_html($pr['produto_nome']) ?>
                                        </span>
                                        <span class="text-muted" style="font-size: 11px;"><?= sanitize_html($pr['pacote_padrao'] ?? 'Unidade') ?></span>
                                    </td>
                                    <td>
                                        <strong style="font-size: 15px; color: var(--gestao-primary);">
                                            <?= (int)$pr['pacotes_refeitos'] ?>
                                        </strong>
                                        <span class="text-muted" style="font-size: 11px;">pct</span>
                                    </td>
                                    <td class="text-nowrap">
                                        <span class="badge bg-light text-dark border">
                                            ⏳ <?= $duracaoFmt ?>
                                        </span>
                                    </td>
                                    <td class="text-nowrap">
                                        <span class="fw-bold" style="color: <?= $ritmo <= 45 ? '#059669' : '#D97706' ?>;">
                                            <?= $ritmo ?>s
                                        </span>
                                        <span class="text-muted" style="font-size: 10px;">/pct</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Linha Inferior: Distribuição de Categorias, Top Estoque e Configurações de Bancada -->
<div class="row g-4">
    <!-- Distribuição das Avarias por Categoria no Período -->
    <div class="col-12 col-lg-5">
        <div class="card-gestao h-100">
            <h2 class="headline-lg mb-2" style="font-size: 18px;">📊 Distribuição de Avarias por Categoria</h2>
            <p class="text-muted small mb-3">Volume acumulado no filtro selecionado para análise de causa-raiz.</p>

            <?php if (empty($distribuicaoCategoriasBO)): ?>
                <div class="text-center py-4 text-muted small">
                    Nenhuma avaria apontada para este período.
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php 
                    $maxQtd = max(array_column($distribuicaoCategoriasBO, 'total_qtd') ?: [1]);
                    foreach ($distribuicaoCategoriasBO as $cat): 
                        $pctBarra = round(((int)$cat['total_qtd'] / $maxQtd) * 100);
                        $isLojinha = $cat['destino'] === 'lojinha';
                    ?>
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small fw-bold">
                                    <?= sanitize_html($labelsTipoAvaria[$cat['tipo_avaria']] ?? ucfirst(str_replace('_', ' ', $cat['tipo_avaria']))) ?>
                                </span>
                                <span class="small text-muted">
                                    <strong><?= (int)$cat['total_qtd'] ?> un</strong> (<?= (int)$cat['total_ocorrencias'] ?> reg)
                                    <span class="badge-status <?= $isLojinha ? 'lojinha' : 'descarte' ?> ms-1" style="font-size: 10px; padding: 2px 6px;">
                                        <?= $isLojinha ? 'Lojinha' : 'Descarte' ?>
                                    </span>
                                </span>
                            </div>
                            <div class="progress" style="height: 10px; border-radius: 6px;">
                                <div class="progress-bar <?= $isLojinha ? 'bg-success' : 'bg-danger' ?>" 
                                     role="progressbar" 
                                     style="width: <?= $pctBarra ?>%;" 
                                     aria-valuenow="<?= $pctBarra ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Maiores Saldos na Lojinha -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card-gestao h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="headline-lg mb-0" style="font-size: 18px;">🏪 Maiores Saldos na Lojinha</h2>
                <a href="<?= APP_URL ?>/gestao/estoque" class="small fw-bold text-decoration-none">Gerenciar →</a>
            </div>
            <p class="text-muted small mb-3">Itens aptos na vitrine dos colaboradores.</p>

            <?php if (empty($topEstoque)): ?>
                <p class="text-muted small mb-0">Nenhum produto com saldo em estoque no momento.</p>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($topEstoque as $prod): ?>
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="small d-block text-truncate" style="max-width: 180px;"><?= sanitize_html($prod['nome']) ?></strong>
                                <span class="text-muted" style="font-size: 11px;">Físico: <?= (int)$prod['quantidade_total'] ?> | Res: <?= (int)$prod['quantidade_reservada_gestao'] ?></span>
                            </div>
                            <span class="badge bg-success rounded-pill" style="font-size: 13px;">
                                <?= (int)$prod['disponivel'] ?> un
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- Operador Padrão da Bancada & Lotes -->
    <div class="col-12 col-md-6 col-lg-3">
        <!-- Card Operador Padrão da Bancada -->
        <div class="card-gestao mb-3" style="border-left: 4px solid var(--gestao-primary);">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <h3 class="headline-lg mb-0" style="font-size: 16px;">⭐ Operador da Bancada</h3>
                <span class="badge bg-light text-primary border" style="font-size: 10px;">Repack</span>
            </div>
            <p class="text-muted small mb-2" style="font-size: 11px;">Operador conectado automaticamente na urna física.</p>
            <div class="d-flex gap-2">
                <select id="selectOperadorPadraoGestao" class="form-select form-select-sm">
                    <?php foreach ($operadoresAtivos as $op): ?>
                        <option value="<?= (int)$op['id'] ?>" <?= ((int)$op['id'] === (int)($operadorPadrao['id'] ?? 0)) ? 'selected' : '' ?>>
                            <?= sanitize_html($op['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-sm btn-primary text-nowrap" onclick="salvarPadraoGestao()">
                    Salvar
                </button>
            </div>
            <div id="msgPadraoGestao" class="small fw-bold text-success mt-1" style="display: none; font-size: 11px;"></div>
        </div>

        <!-- Card Lotes de Venda Ativos -->
        <div class="card-gestao">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h3 class="headline-lg mb-0" style="font-size: 16px;">Lotes de Venda</h3>
                <a href="<?= APP_URL ?>/gestao/lotes" class="small fw-bold text-decoration-none">Abrir →</a>
            </div>
            <div class="d-flex align-items-center justify-content-between p-2 rounded" style="background-color: #F9FAFB; border: 1px solid var(--gestao-border);">
                <div>
                    <span class="text-muted small d-block" style="font-size: 11px;">Status da Vitrine</span>
                    <strong style="font-size: 15px;">
                        <?= $lotesAtivos > 0 ? "{$lotesAtivos} lote(s) ativo(s)" : 'Nenhum lote aberto' ?>
                    </strong>
                </div>
                <span class="badge-status <?= $lotesAtivos > 0 ? 'aberto' : 'planejado' ?>">
                    <?= $lotesAtivos > 0 ? 'No Ar' : 'Fechada' ?>
                </span>
            </div>
        </div>
    </div>
</div>

<script>
// Alternador do formulário de datas personalizadas
function toggleCustomFilter() {
    const boxInicio = document.getElementById('boxDataInicio');
    const boxFim = document.getElementById('boxDataFim');
    const inputPeriodo = document.getElementById('inputPeriodo');
    
    if (boxInicio.style.display === 'none') {
        boxInicio.style.display = 'block';
        boxFim.style.display = 'block';
        inputPeriodo.value = 'custom';
    } else {
        boxInicio.style.display = 'none';
        boxFim.style.display = 'none';
        inputPeriodo.value = 'hoje';
    }
}

// Configuração de Telemetria e Auto-Refresh em Tempo Real
let timerAutoRefresh = null;
const INTERVALO_REFRESH_MS = 30000; // 30 segundos

function executarSincronizacaoLive() {
    const switchEl = document.getElementById('switchAutoRefresh');
    const txtLastSync = document.getElementById('txtLastSync');
    
    // Constrói URL com todos os parâmetros do formulário atual mais format=json
    const form = document.getElementById('formFiltroDashboard');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    params.set('format', 'json');

    fetch('<?= APP_URL ?>/gestao/dashboard?' + params.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            // Atualiza hora da sincronização
            txtLastSync.innerText = data.timestamp;

            // 1. Atualiza métricas numéricas do cabeçalho
            if (data.metricas) {
                const m = data.metricas;
                document.getElementById('valTotalRecuperado').innerText = Number(m.totalRecuperado).toLocaleString('pt-BR');
                document.getElementById('valTotalDescarte').innerText = Number(m.totalDescarte).toLocaleString('pt-BR');
                document.getElementById('valTotalPacotes').innerText = Number(m.totalPacotes).toLocaleString('pt-BR');
                document.getElementById('valEstoqueDisponivel').innerText = Number(m.estoqueDisponivel).toLocaleString('pt-BR');
                document.getElementById('badgeTaxaRecup').innerText = m.taxaRecuperacao + '% taxa';
                document.getElementById('badgeRitmoHora').innerText = m.pacotesPorHora + ' pct/h';
                document.getElementById('txtTotalAvarias').innerHTML = 'Total de avarias no filtro: <strong>' + Number(m.totalAvarias).toLocaleString('pt-BR') + ' un</strong>';
                document.getElementById('txtTempoMedio').innerHTML = 'Tempo Médio: <strong>' + m.tempoMedioPorPacote + ' seg/pct</strong> em ' + m.totalCiclos + ' ciclo(s)';
            }

            // 2. Atualiza Feed de B.O.
            if (data.ultimosBOs) {
                document.getElementById('badgeContagemBO').innerText = data.ultimosBOs.length + ' registros';
                renderizarTabelaBO(data.ultimosBOs);
            }

            // 3. Atualiza Feed de Produtividade
            if (data.ultimosProd) {
                document.getElementById('badgeContagemProd').innerText = data.ultimosProd.length + ' ciclos';
                renderizarTabelaProd(data.ultimosProd);
            }
        }
    })
    .catch(err => {
        console.warn('Erro ao atualizar telemetria ao vivo:', err);
    });
}

function renderizarTabelaBO(itens) {
    const tbody = document.getElementById('corpoTabelaBO');
    if (!tbody) return;

    if (itens.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Nenhum apontamento de avaria registrado para o filtro selecionado.</td></tr>';
        return;
    }

    const labels = {
        'amassada': 'Lata/Item Amassado',
        'def_rotulo': 'Defeito no Rótulo',
        'emba_danificada': 'Embalagem Danificada',
        'furo': 'Furo / Rasgo Aberto',
        'micro_furo': 'Micro Furo'
    };

    let html = '';
    itens.forEach(bo => {
        const d = new Date(bo.created_at.replace(' ', 'T'));
        const horaFmt = ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2);
        const dataFmt = ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth() + 1)).slice(-2);
        const isLojinha = bo.destino === 'lojinha';
        const labelTipo = labels[bo.tipo_avaria] || bo.tipo_avaria;

        html += `
            <tr>
                <td class="text-nowrap">
                    <strong>${horaFmt}</strong>
                    <div class="text-muted" style="font-size: 11px;">${dataFmt}</div>
                </td>
                <td>
                    <strong class="d-block text-truncate" style="max-width: 140px;" title="${escapeHtml(bo.produto_nome)}">
                        ${escapeHtml(bo.produto_nome)}
                    </strong>
                    <span class="text-muted" style="font-size: 11px;">${escapeHtml(bo.produto_codigo)}</span>
                </td>
                <td>
                    <span class="d-block text-truncate" style="max-width: 110px;" title="${escapeHtml(bo.operador_nome)}">
                        ${escapeHtml(bo.operador_nome)}
                    </span>
                    <span class="text-muted" style="font-size: 11px;">${escapeHtml(bo.operador_matricula || '')}</span>
                </td>
                <td>
                    <strong style="font-size: 15px; color: #111827;">${bo.quantidade}</strong>
                </td>
                <td>
                    <span class="badge bg-light text-dark border">${escapeHtml(labelTipo)}</span>
                </td>
                <td>
                    <span class="badge-status ${isLojinha ? 'lojinha' : 'descarte'}">
                        ${isLojinha ? 'Lojinha' : 'Descarte'}
                    </span>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

function renderizarTabelaProd(itens) {
    const tbody = document.getElementById('corpoTabelaProd');
    if (!tbody) return;

    if (itens.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Nenhum apontamento de produtividade registrado para o filtro selecionado.</td></tr>';
        return;
    }

    let html = '';
    itens.forEach(pr => {
        const d = new Date(pr.created_at.replace(' ', 'T'));
        const horaFmt = ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2);
        const dataFmt = ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth() + 1)).slice(-2);
        const min = Math.floor(pr.duracao_segundos / 60);
        const seg = pr.duracao_segundos % 60;
        const duracaoFmt = ('0' + min).slice(-2) + ':' + ('0' + seg).slice(-2);
        const ritmo = pr.pacotes_refeitos > 0 ? (pr.duracao_segundos / pr.pacotes_refeitos).toFixed(1) : 0;
        const corRitmo = ritmo <= 45 ? '#059669' : '#D97706';

        html += `
            <tr>
                <td class="text-nowrap">
                    <strong>${horaFmt}</strong>
                    <div class="text-muted" style="font-size: 11px;">${dataFmt}</div>
                </td>
                <td>
                    <strong class="d-block text-truncate" style="max-width: 120px;" title="${escapeHtml(pr.operador_nome)}">
                        ${escapeHtml(pr.operador_nome)}
                    </strong>
                    <span class="text-muted" style="font-size: 11px;">${escapeHtml(pr.operador_matricula || '')}</span>
                </td>
                <td>
                    <span class="d-block text-truncate" style="max-width: 130px;" title="${escapeHtml(pr.produto_nome)}">
                        ${escapeHtml(pr.produto_nome)}
                    </span>
                    <span class="text-muted" style="font-size: 11px;">${escapeHtml(pr.pacote_padrao || 'Unidade')}</span>
                </td>
                <td>
                    <strong style="font-size: 15px; color: var(--gestao-primary);">${pr.pacotes_refeitos}</strong>
                    <span class="text-muted" style="font-size: 11px;">pct</span>
                </td>
                <td class="text-nowrap">
                    <span class="badge bg-light text-dark border">⏳ ${duracaoFmt}</span>
                </td>
                <td class="text-nowrap">
                    <span class="fw-bold" style="color: ${corRitmo};">${ritmo}s</span>
                    <span class="text-muted" style="font-size: 10px;">/pct</span>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Inicializador dos timers
document.addEventListener('DOMContentLoaded', () => {
    const switchAuto = document.getElementById('switchAutoRefresh');
    const btnManual = document.getElementById('btnManualRefresh');

    function gerenciarTimer() {
        if (switchAuto && switchAuto.checked) {
            if (!timerAutoRefresh) {
                timerAutoRefresh = setInterval(executarSincronizacaoLive, INTERVALO_REFRESH_MS);
            }
        } else {
            if (timerAutoRefresh) {
                clearInterval(timerAutoRefresh);
                timerAutoRefresh = null;
            }
        }
    }

    if (switchAuto) {
        switchAuto.addEventListener('change', gerenciarTimer);
        gerenciarTimer();
    }

    if (btnManual) {
        btnManual.addEventListener('click', () => {
            executarSincronizacaoLive();
        });
    }
});

// Operador Padrão Global
function salvarPadraoGestao() {
    const select = document.getElementById('selectOperadorPadraoGestao');
    const msg = document.getElementById('msgPadraoGestao');
    if (!select) return;
    const opId = parseInt(select.value, 10);
    const opNome = select.options[select.selectedIndex].text.trim();

    fetch('<?= APP_URL ?>/api/operador/definir-padrao', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ operador_id: opId })
    }).then(res => res.json()).then(data => {
        if (data.status === 'success') {
            msg.innerText = '✅ Padrão: ' + opNome;
            msg.style.display = 'block';
            setTimeout(() => { msg.style.display = 'none'; }, 4000);
        } else {
            alert(data.message || 'Erro ao salvar operador padrão.');
        }
    }).catch(() => {
        alert('Erro ao conectar ao servidor.');
    });
}
</script>

<?php require __DIR__ . '/../layouts/gestao_footer.php'; ?>
