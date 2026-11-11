<?php
declare(strict_types=1);

/**
 * Projeto Repack - View Gestao Dashboard
 * Baseado estritamente em docs/FSD.md (Seção 6.4 e 12.3) e docs/DESIGN.md
 */

require __DIR__ . '/../layouts/gestao_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="headline-lg mb-1" style="color: var(--gestao-text);">Painel Consolidado da Gestão</h1>
        <p class="body-md text-muted mb-0">Visão panorâmica de avarias, estoques da lojinha e produtividade do repack.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= APP_URL ?>/gestao/bo" class="btn btn-gestao btn-gestao-secondary">
            📋 Retificar B.O.
        </a>
        <a href="<?= APP_URL ?>/gestao/lotes" class="btn btn-gestao btn-gestao-primary">
            🏷️ Gerenciar Lotes
        </a>
    </div>
</div>

<!-- Grade de Indicadores Principais -->
<div class="row g-4 mb-4">
    <!-- Card 1: Recuperação para Lojinha -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card-gestao card-metric h-100" style="border-left-color: var(--gestao-primary);">
            <div class="text-muted small fw-bold text-uppercase mb-2">Recuperados para Lojinha</div>
            <div class="d-flex align-items-baseline gap-2">
                <span class="headline-xl" style="color: var(--gestao-primary);"><?= number_format($totalRecuperado, 0, ',', '.') ?></span>
                <span class="text-muted">unidades</span>
            </div>
            <div class="mt-2 small text-muted">
                Taxa de Recuperação: <strong class="text-success"><?= $taxaRecuperacao ?>%</strong>
            </div>
        </div>
    </div>

    <!-- Card 2: Descarte Absoluto -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card-gestao card-metric h-100" style="border-left-color: var(--gestao-danger);">
            <div class="text-muted small fw-bold text-uppercase mb-2">Descarte Irreversível</div>
            <div class="d-flex align-items-baseline gap-2">
                <span class="headline-xl" style="color: var(--gestao-danger);"><?= number_format($totalDescarte, 0, ',', '.') ?></span>
                <span class="text-muted">unidades</span>
            </div>
            <div class="mt-2 small text-muted">
                Itens com Furo / Micro Furo
            </div>
        </div>
    </div>

    <!-- Card 3: Saldo Disponível na Lojinha -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card-gestao card-metric h-100" style="border-left-color: var(--gestao-secondary);">
            <div class="text-muted small fw-bold text-uppercase mb-2">Disponível para Venda</div>
            <div class="d-flex align-items-baseline gap-2">
                <span class="headline-xl" style="color: var(--gestao-secondary);"><?= number_format($estoqueDisponivel, 0, ',', '.') ?></span>
                <span class="text-muted">un. na vitrine</span>
            </div>
            <div class="mt-2 small text-muted">
                Total Físico: <?= number_format($estoqueTotal, 0, ',', '.') ?> | Reserva: <?= number_format($estoqueReservado, 0, ',', '.') ?>
            </div>
        </div>
    </div>

    <!-- Card 4: Produtividade Operacional -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card-gestao card-metric h-100" style="border-left-color: var(--gestao-warning);">
            <div class="text-muted small fw-bold text-uppercase mb-2">Produtividade na Bancada</div>
            <div class="d-flex align-items-baseline gap-2">
                <span class="headline-xl" style="color: var(--gestao-text);"><?= number_format($totalPacotes, 0, ',', '.') ?></span>
                <span class="text-muted">pacotes refeitos</span>
            </div>
            <div class="mt-2 small text-muted">
                Tempo Médio: <strong><?= $tempoMedioPorPacote ?> seg/pct</strong>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Coluna Esquerda: Últimos Apontamentos de B.O. -->
    <div class="col-12 col-lg-8">
        <div class="card-gestao">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="headline-lg mb-0" style="font-size: 20px;">Últimos Apontamentos de B.O.</h2>
                <a href="<?= APP_URL ?>/gestao/bo" class="small fw-bold text-decoration-none">Ver todos com filtros →</a>
            </div>

            <?php if (empty($ultimosBOs)): ?>
                <div class="text-center py-4 text-muted">
                    <p class="mb-0">Nenhum apontamento de avaria registrado até o momento.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-gestao">
                        <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>Produto</th>
                                <th>Operador</th>
                                <th>Qtd</th>
                                <th>Tipo</th>
                                <th>Destino</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimosBOs as $bo): ?>
                                <tr>
                                    <td><?= date('d/m H:i', strtotime($bo['created_at'])) ?></td>
                                    <td>
                                        <strong><?= sanitize_html($bo['produto_nome']) ?></strong>
                                        <div class="text-muted small"><?= sanitize_html($bo['produto_codigo']) ?></div>
                                    </td>
                                    <td><?= sanitize_html($bo['operador_nome']) ?></td>
                                    <td><strong style="font-size: 16px;"><?= (int)$bo['quantidade'] ?></strong></td>
                                    <td>
                                        <span class="small"><?= sanitize_html(str_replace('_', ' ', ucfirst($bo['tipo_avaria']))) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge-status <?= $bo['destino'] === 'lojinha' ? 'lojinha' : 'descarte' ?>">
                                            <?= $bo['destino'] === 'lojinha' ? 'Lojinha' : 'Descarte' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Coluna Direita: Top Estoque & Lotes -->
    <div class="col-12 col-lg-4">
        <!-- Card Lotes Ativos -->
        <div class="card-gestao mb-4">
            <h2 class="headline-lg mb-3" style="font-size: 18px;">Lotes de Venda Ativos</h2>
            <div class="d-flex align-items-center justify-content-between p-3 rounded" style="background-color: #F9FAFB; border: 1px solid var(--gestao-border);">
                <div>
                    <span class="text-muted small d-block">Status de Abertura</span>
                    <strong style="font-size: 18px;">
                        <?= $lotesAtivos > 0 ? "{$lotesAtivos} lote(s) aberto(s)" : 'Nenhum lote aberto' ?>
                    </strong>
                </div>
                <span class="badge-status <?= $lotesAtivos > 0 ? 'aberto' : 'planejado' ?>">
                    <?= $lotesAtivos > 0 ? 'No Ar' : 'Fechada' ?>
                </span>
            </div>
            <div class="mt-3 text-end">
                <a href="<?= APP_URL ?>/gestao/lotes" class="btn btn-sm btn-outline-secondary">Configurar Lotes →</a>
            </div>
        </div>

        <!-- Card Saldo dos Principais Produtos -->
        <div class="card-gestao">
            <h2 class="headline-lg mb-3" style="font-size: 18px;">Maiores Saldos na Lojinha</h2>
            <?php if (empty($topEstoque)): ?>
                <p class="text-muted small mb-0">Nenhum produto com saldo em estoque no momento.</p>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($topEstoque as $prod): ?>
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="small d-block"><?= sanitize_html($prod['nome']) ?></strong>
                                <span class="text-muted" style="font-size: 12px;">Físico: <?= (int)$prod['quantidade_total'] ?> | Res: <?= (int)$prod['quantidade_reservada_gestao'] ?></span>
                            </div>
                            <span class="badge bg-success rounded-pill" style="font-size: 14px;">
                                <?= (int)$prod['disponivel'] ?> un
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <div class="mt-3 text-end">
                <a href="<?= APP_URL ?>/gestao/estoque" class="btn btn-sm btn-outline-secondary">Gerenciar Reservas →</a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/gestao_footer.php'; ?>
