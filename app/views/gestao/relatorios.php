<?php
declare(strict_types=1);

/**
 * Projeto Repack - View Relatórios e Extratos Analíticos
 * Baseado estritamente em docs/FSD.md (Seções 6, 8 e 22) e docs/DESIGN.md
 */

require_once __DIR__ . '/../layouts/gestao_header.php';
?>

<style>
    .relatorio-header-card {
        background: #FFFFFF;
        border: 1px solid var(--gestao-border);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
    }

    .progress-stacked-repack {
        height: 28px;
        border-radius: 8px;
        overflow: hidden;
        display: flex;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
    }
    .progress-bar-lojinha {
        background-color: var(--gestao-primary);
        color: #FFFFFF;
        font-weight: 700;
        font-size: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: width 0.4s ease;
    }
    .progress-bar-descarte {
        background-color: var(--gestao-danger);
        color: #FFFFFF;
        font-weight: 700;
        font-size: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: width 0.4s ease;
    }

    .badge-destino-lojinha {
        background-color: #D1FAE5;
        color: #065F46;
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 11px;
        text-transform: uppercase;
    }
    .badge-destino-descarte {
        background-color: #FEE2E2;
        color: #991B1B;
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 11px;
        text-transform: uppercase;
    }

    @media print {
        .navbar-gestao,
        .btn-gestao,
        .no-print,
        .alert,
        footer {
            display: none !important;
        }

        .gestao-container {
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .card-gestao {
            border: 1px solid #000000 !important;
            box-shadow: none !important;
            page-break-inside: avoid;
            margin-bottom: 20px !important;
        }

        .table-gestao th,
        .table-gestao td {
            border-bottom: 1px solid #999999 !important;
            padding: 6px 8px !important;
        }
    }
</style>

<!-- Topo da Página de Relatórios -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 no-print">
    <div>
        <h1 class="h3 fw-bold mb-1 d-flex align-items-center gap-2">
            <span>📊</span> Relatórios e Extratos Analíticos
        </h1>
        <p class="text-muted mb-0">
            Movimentação sintética de avarias/recuperação de estoque e produtividade operacional da equipe de bancada.
        </p>
    </div>

    <div class="d-flex align-items-center gap-2">
        <button type="button" onclick="window.print()" class="btn btn-gestao btn-gestao-secondary">
            🖨️ Imprimir Relatório
        </button>

        <div class="dropdown">
            <button class="btn btn-gestao btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                📥 Exportar CSV
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li>
                    <a class="dropdown-item" href="<?= APP_URL ?>/gestao/relatorios/exportar?tipo=bo&data_inicio=<?= urlencode($dataInicio) ?>&data_fim=<?= urlencode($dataFim) ?>">
                        📦 Movimentação de Repack (B.O.)
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="<?= APP_URL ?>/gestao/relatorios/exportar?tipo=produtividade&data_inicio=<?= urlencode($dataInicio) ?>&data_fim=<?= urlencode($dataFim) ?>">
                        ⏱️ Produtividade da Equipe
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- Filtro de Período -->
<div class="relatorio-header-card no-print">
    <form method="GET" action="<?= APP_URL ?>/gestao/relatorios" class="row g-3 align-items-end">
        <input type="hidden" name="filtrar" value="1">
        <div class="col-md-3">
            <label for="data_inicio" class="form-label fw-bold">Data Início:</label>
            <input type="date" name="data_inicio" id="data_inicio" class="form-control" value="<?= sanitize_html($dataInicio) ?>">
        </div>

        <div class="col-md-3">
            <label for="data_fim" class="form-label fw-bold">Data Fim:</label>
            <input type="date" name="data_fim" id="data_fim" class="form-control" value="<?= sanitize_html($dataFim) ?>">
        </div>

        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-gestao btn-gestao-primary w-100 justify-content-center">
                🔍 Atualizar Período
            </button>
        </div>

        <div class="col-md-3 text-md-end">
            <div class="btn-group btn-group-sm" role="group">
                <a href="<?= APP_URL ?>/gestao/relatorios?data_inicio=<?= date('Y-m-d') ?>&data_fim=<?= date('Y-m-d') ?>&filtrar=1" class="btn btn-outline-secondary">Hoje</a>
                <a href="<?= APP_URL ?>/gestao/relatorios?data_inicio=<?= date('Y-m-d', strtotime('-7 days')) ?>&data_fim=<?= date('Y-m-d') ?>&filtrar=1" class="btn btn-outline-secondary">7 Dias</a>
                <a href="<?= APP_URL ?>/gestao/relatorios?data_inicio=<?= date('Y-m-01') ?>&data_fim=<?= date('Y-m-t') ?>&filtrar=1" class="btn btn-outline-secondary">Este Mês</a>
                <a href="<?= APP_URL ?>/gestao/relatorios?data_inicio=&data_fim=&filtrar=1" class="btn btn-outline-secondary">Tudo</a>
            </div>
        </div>
    </form>
</div>

<!-- Cabeçalho de Impressão -->
<div class="d-none d-print-block mb-3">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
        <div>
            <h2 class="h4 fw-bold mb-0">PROJETO REPACK - RELATÓRIOS CONSOLIDADOS DE GESTÃO</h2>
            <p class="mb-0 small text-muted">
                Período: <?= !empty($dataInicio) ? date('d/m/Y', strtotime($dataInicio)) : 'Início' ?> até <?= !empty($dataFim) ? date('d/m/Y', strtotime($dataFim)) : 'Hoje' ?> &bull; Emissão: <?= date('d/m/Y H:i:s') ?>
            </p>
        </div>
    </div>
</div>

<!-- ============================================================= -->
<!-- 1. RELATÓRIO SINTÉTICO DE MOVIMENTAÇÃO DE REPACK (Seção 22.2) -->
<!-- ============================================================= -->
<div class="card-gestao mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
            <span>♻️</span> 1. Relatório Sintético de Movimentação de Repack
        </h4>
        <span class="badge bg-light text-dark border">
            Total de Registros: <?= (int)$relatorioBO['total_registros'] ?>
        </span>
    </div>

    <!-- Indicadores Principais de Perdas vs. Recuperação -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="p-3 rounded border text-center" style="background-color: #F9FAFB;">
                <div class="text-muted small fw-bold text-uppercase">Total Recebido / Avariado</div>
                <div class="fs-2 fw-bold text-dark mt-1"><?= number_format($relatorioBO['total_unidades'], 0, ',', '.') ?> <span class="fs-6 fw-normal text-muted">un.</span></div>
                <div class="small text-muted">100% do volume triado</div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="p-3 rounded border text-center" style="background-color: #ECFDF5; border-color: #A7F3D0 !important;">
                <div class="text-success small fw-bold text-uppercase">Encaminhado à Lojinha (Recuperado)</div>
                <div class="fs-2 fw-bold text-success mt-1"><?= number_format($relatorioBO['unidades_lojinha'], 0, ',', '.') ?> <span class="fs-6 fw-normal">un.</span></div>
                <div class="small fw-bold text-success"><?= $relatorioBO['percentual_lojinha'] ?>% do total</div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="p-3 rounded border text-center" style="background-color: #FEF2F2; border-color: #FECACA !important;">
                <div class="text-danger small fw-bold text-uppercase">Descarte Absoluto (Perda Irrecuperável)</div>
                <div class="fs-2 fw-bold text-danger mt-1"><?= number_format($relatorioBO['unidades_descarte'], 0, ',', '.') ?> <span class="fs-6 fw-normal">un.</span></div>
                <div class="small fw-bold text-danger"><?= $relatorioBO['percentual_descarte'] ?>% do total</div>
            </div>
        </div>
    </div>

    <!-- Barra Visual de Proporção Lojinha vs Descarte -->
    <div class="mb-4">
        <label class="form-label fw-bold small text-muted text-uppercase mb-1">
            Proporção de Destino das Avarias Triadas:
        </label>
        <div class="progress-stacked-repack">
            <?php if ($relatorioBO['total_unidades'] > 0): ?>
                <div class="progress-bar-lojinha" style="width: <?= $relatorioBO['percentual_lojinha'] ?>%;">
                    <?= $relatorioBO['percentual_lojinha'] ?>% Lojinha (<?= $relatorioBO['unidades_lojinha'] ?> un.)
                </div>
                <div class="progress-bar-descarte" style="width: <?= $relatorioBO['percentual_descarte'] ?>%;">
                    <?= $relatorioBO['percentual_descarte'] ?>% Descarte (<?= $relatorioBO['unidades_descarte'] ?> un.)
                </div>
            <?php else: ?>
                <div class="w-100 bg-secondary text-white text-center py-1 small">Sem movimentações no período selecionado</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detalhamento por Tipo de Avaria e por Produto -->
    <div class="row g-4">
        <div class="col-lg-5">
            <h6 class="fw-bold text-dark mb-2">📌 Ocorrências por Tipo de Avaria</h6>
            <div class="table-responsive">
                <table class="table table-sm table-gestao align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tipo de Avaria</th>
                            <th>Destino</th>
                            <th class="text-end">Qtd (un.)</th>
                            <th class="text-end">% Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($relatorioBO['por_tipo'])): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Nenhum dado</td></tr>
                        <?php else: ?>
                            <?php foreach ($relatorioBO['por_tipo'] as $tipo): ?>
                                <tr>
                                    <td class="fw-semibold">
                                        <?= sanitize_html(ucwords(str_replace('_', ' ', $tipo['tipo_avaria']))) ?>
                                    </td>
                                    <td>
                                        <span class="badge-destino-<?= sanitize_html($tipo['destino']) ?>">
                                            <?= strtoupper(sanitize_html($tipo['destino'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold">
                                        <?= number_format($tipo['quantidade_total'], 0, ',', '.') ?>
                                    </td>
                                    <td class="text-end text-muted">
                                        <?= $tipo['percentual'] ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-7">
            <h6 class="fw-bold text-dark mb-2">📦 Movimentação Consolidada por Produto</h6>
            <div class="table-responsive">
                <table class="table table-sm table-gestao align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Produto</th>
                            <th class="text-end">Total</th>
                            <th class="text-end text-success">Lojinha</th>
                            <th class="text-end text-danger">Descarte</th>
                            <th class="text-end">Taxa Recup.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($relatorioBO['por_produto'])): ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">Nenhum produto com apontamentos</td></tr>
                        <?php else: ?>
                            <?php foreach ($relatorioBO['por_produto'] as $pr): ?>
                                <tr>
                                    <td class="font-monospace fw-bold text-secondary"><?= sanitize_html($pr['codigo']) ?></td>
                                    <td class="fw-semibold"><?= sanitize_html($pr['nome']) ?></td>
                                    <td class="text-end fw-bold"><?= (int)$pr['total_unidades'] ?></td>
                                    <td class="text-end text-success"><?= (int)$pr['unidades_lojinha'] ?></td>
                                    <td class="text-end text-danger"><?= (int)$pr['unidades_descarte'] ?></td>
                                    <td class="text-end">
                                        <span class="fw-bold <?= ($pr['taxa_recuperacao'] >= 70) ? 'text-success' : (($pr['taxa_recuperacao'] >= 40) ? 'text-warning' : 'text-danger') ?>">
                                            <?= $pr['taxa_recuperacao'] ?>%
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
</div>

<!-- ============================================================= -->
<!-- 2. EXTRATO ANALÍTICO DE PRODUTIVIDADE DA EQUIPE (Seção 22.3) -->
<!-- ============================================================= -->
<div class="card-gestao mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
            <span>⏱️</span> 2. Extrato Analítico de Produtividade da Equipe
        </h4>
        <span class="badge bg-light text-dark border">
            Total de Ciclos: <?= (int)$relatorioProd['total_ciclos'] ?>
        </span>
    </div>

    <!-- Indicadores Gerais de Produtividade -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="p-3 rounded border text-center" style="background-color: #F9FAFB;">
                <div class="text-muted small fw-bold text-uppercase">Pacotes Montados</div>
                <div class="fs-2 fw-bold text-dark mt-1"><?= number_format($relatorioProd['total_pacotes'], 0, ',', '.') ?> <span class="fs-6 fw-normal text-muted">pct.</span></div>
                <div class="small text-muted">Produção total reembalada</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="p-3 rounded border text-center" style="background-color: #EFF6FF; border-color: #BFDBFE !important;">
                <div class="text-primary small fw-bold text-uppercase">Tempo Total de Bancada</div>
                <div class="fs-2 fw-bold text-primary mt-1"><?= $relatorioProd['total_horas'] ?> <span class="fs-6 fw-normal">h</span></div>
                <div class="small text-muted"><?= number_format($relatorioProd['total_segundos'], 0, ',', '.') ?> segundos cronometrados</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="p-3 rounded border text-center" style="background-color: #ECFDF5; border-color: #A7F3D0 !important;">
                <div class="text-success small fw-bold text-uppercase">Produtividade Média Equipe</div>
                <div class="fs-2 fw-bold text-success mt-1"><?= $relatorioProd['media_geral_pacotes_por_hora'] ?> <span class="fs-6 fw-normal">pct/h</span></div>
                <div class="small text-muted">Média ponderada por hora</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="p-3 rounded border text-center" style="background-color: #FFFBEB; border-color: #FDE68A !important;">
                <div class="text-warning small fw-bold text-uppercase">Tempo Médio por Pacote</div>
                <div class="fs-2 fw-bold text-warning mt-1"><?= $relatorioProd['tempo_medio_geral_por_pacote'] ?> <span class="fs-6 fw-normal">s</span></div>
                <div class="small text-muted">Segundos médios por pacote</div>
            </div>
        </div>
    </div>

    <!-- Tabelas de Produtividade por Operador e Tempo por Produto -->
    <div class="row g-4">
        <!-- Tabela por Operador -->
        <div class="col-lg-6">
            <h6 class="fw-bold text-dark mb-2">👤 Produtividade Média por Operador</h6>
            <div class="table-responsive">
                <table class="table table-sm table-gestao align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Matrícula</th>
                            <th>Operador</th>
                            <th class="text-center">Ciclos</th>
                            <th class="text-end">Pacotes</th>
                            <th class="text-end">Horas</th>
                            <th class="text-end text-primary">Pacotes/Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($relatorioProd['por_operador'])): ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">Nenhum registro de produtividade</td></tr>
                        <?php else: ?>
                            <?php foreach ($relatorioProd['por_operador'] as $op): ?>
                                <tr>
                                    <td class="font-monospace text-muted small"><?= sanitize_html($op['matricula']) ?></td>
                                    <td class="fw-semibold text-dark"><?= sanitize_html($op['nome']) ?></td>
                                    <td class="text-center"><?= (int)$op['total_ciclos'] ?></td>
                                    <td class="text-end fw-bold"><?= (int)$op['total_pacotes'] ?></td>
                                    <td class="text-end text-muted"><?= $op['total_horas'] ?> h</td>
                                    <td class="text-end fw-bold text-primary">
                                        <?= $op['pacotes_por_hora'] ?> un/h
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabela por Produto -->
        <div class="col-lg-6">
            <h6 class="fw-bold text-dark mb-2">📦 Tempo Médio de Ciclo por Produto</h6>
            <div class="table-responsive">
                <table class="table table-sm table-gestao align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Produto</th>
                            <th class="text-center">Embalagem</th>
                            <th class="text-end">Pacotes</th>
                            <th class="text-end">Tempo Médio/Pacote</th>
                            <th class="text-end text-primary">Ritmo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($relatorioProd['por_produto'])): ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">Nenhum registro por produto</td></tr>
                        <?php else: ?>
                            <?php foreach ($relatorioProd['por_produto'] as $prod): ?>
                                <tr>
                                    <td class="font-monospace fw-bold text-secondary"><?= sanitize_html($prod['codigo']) ?></td>
                                    <td class="fw-semibold text-dark"><?= sanitize_html($prod['nome']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border"><?= sanitize_html($prod['pacote_padrao']) ?></span>
                                    </td>
                                    <td class="text-end fw-bold"><?= (int)$prod['total_pacotes'] ?></td>
                                    <td class="text-end fw-bold text-warning">
                                        <?= $prod['tempo_medio_pacote_segundos'] ?> s
                                    </td>
                                    <td class="text-end text-primary">
                                        <?= $prod['pacotes_por_hora'] ?> pct/h
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

<?php require_once __DIR__ . '/../layouts/gestao_footer.php'; ?>
