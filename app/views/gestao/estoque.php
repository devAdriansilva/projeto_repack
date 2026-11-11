<?php
declare(strict_types=1);

/**
 * Projeto Repack - View Gestao Estoque & Reservas
 * Baseado estritamente em docs/FSD.md (Seção 6.4, 11.2, 12.3 e 17) e docs/DESIGN.md
 */

require __DIR__ . '/../layouts/gestao_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="headline-lg mb-1">Gestão de Estoque da Lojinha</h1>
        <p class="body-md text-muted mb-0">Controle de saldos físicos recebidos do repack, reservas ocultas e baixas administrativas.</p>
    </div>
</div>

<!-- Tabela Principal de Saldos -->
<div class="card-gestao">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="headline-lg mb-0" style="font-size: 20px;">Visão Geral de Saldos</h2>
        <span class="badge bg-light text-dark border p-2">
            Fórmula: <strong>Disponível = Total Físico - Reserva Gestão</strong>
        </span>
    </div>

    <div class="table-responsive">
        <table class="table-gestao">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Produto / Código</th>
                    <th>Saldo Físico Total</th>
                    <th>Reserva Gestão (Oculta)</th>
                    <th>Disponível para Venda</th>
                    <th style="width: 260px; text-align: right;">Ações de Controle</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($estoqueGeral)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            Nenhum produto com saldo em estoque.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($estoqueGeral as $item): ?>
                        <tr>
                            <td style="width: 60px;">
                                <?php if (!empty($item['foto_url'])): ?>
                                    <img src="<?= sanitize_html($item['foto_url']) ?>" alt="Foto" style="width: 44px; height: 44px; object-fit: cover; border-radius: 8px; border: 1px solid var(--gestao-border);">
                                <?php else: ?>
                                    <div style="width: 44px; height: 44px; background: #E5E7EB; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                        🍾
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= sanitize_html($item['nome']) ?></strong>
                                <div class="text-muted small"><?= sanitize_html($item['codigo']) ?> &bull; <?= sanitize_html($item['pacote_padrao']) ?></div>
                            </td>
                            <td>
                                <strong style="font-size: 17px;"><?= (int)$item['quantidade_total'] ?></strong> un
                            </td>
                            <td>
                                <span class="badge bg-warning text-dark" style="font-size: 14px;">
                                    <?= (int)$item['quantidade_reservada_gestao'] ?> un
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= (int)$item['saldo_disponivel'] > 0 ? 'bg-success' : 'bg-secondary' ?>" style="font-size: 15px; padding: 6px 12px;">
                                    <?= (int)$item['saldo_disponivel'] ?> un
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <button type="button" class="btn btn-sm btn-outline-warning me-1" 
                                        onclick="abrirModalReserva(<?= (int)$item['produto_id'] ?>, '<?= sanitize_html(addslashes($item['nome'])) ?>', <?= (int)$item['quantidade_total'] ?>, <?= (int)$item['quantidade_reservada_gestao'] ?>)" title="Ajustar reserva oculta">
                                    🛡️ Reserva
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="abrirModalBaixa(<?= (int)$item['produto_id'] ?>, '<?= sanitize_html(addslashes($item['nome'])) ?>', <?= (int)$item['quantidade_total'] ?>)" title="Registrar perda/baixa">
                                    📉 Baixa
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Seção de Históricos Recentes -->
<div class="row g-4 mt-1">
    <!-- Histórico de Reservas Ocultas -->
    <div class="col-12 col-lg-6">
        <div class="card-gestao">
            <h2 class="headline-lg mb-3" style="font-size: 18px;">Histórico de Ajustes de Reserva</h2>
            <?php if (empty($historicoReservas)): ?>
                <p class="text-muted small mb-0">Nenhum ajuste manual de reserva registrado.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-gestao">
                        <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>Produto</th>
                                <th>Reserva</th>
                                <th>Motivo / Responsável</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historicoReservas as $hr): ?>
                                <tr>
                                    <td class="small"><?= date('d/m/Y H:i', strtotime($hr['created_at'])) ?></td>
                                    <td>
                                        <strong class="small"><?= sanitize_html($hr['produto_nome']) ?></strong>
                                    </td>
                                    <td><span class="badge bg-warning text-dark"><?= (int)$hr['quantidade'] ?> un</span></td>
                                    <td class="small">
                                        <?= sanitize_html($hr['motivo']) ?><br>
                                        <span class="text-muted" style="font-size: 11px;">Por: <?= sanitize_html($hr['usuario_nome']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Histórico de Baixas Administrativas -->
    <div class="col-12 col-lg-6">
        <div class="card-gestao">
            <h2 class="headline-lg mb-3" style="font-size: 18px;">Histórico de Baixas Administrativas</h2>
            <?php if (empty($historicoBaixas)): ?>
                <p class="text-muted small mb-0">Nenhuma baixa administrativa registrada.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-gestao">
                        <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>Produto</th>
                                <th>Baixa</th>
                                <th>Motivo / Responsável</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historicoBaixas as $hb): ?>
                                <tr>
                                    <td class="small"><?= date('d/m/Y H:i', strtotime($hb['created_at'])) ?></td>
                                    <td>
                                        <strong class="small"><?= sanitize_html($hb['produto_nome']) ?></strong>
                                    </td>
                                    <td><span class="badge bg-danger"><?= (int)$hb['quantidade'] ?> un</span></td>
                                    <td class="small">
                                        <?= sanitize_html($hb['motivo']) ?><br>
                                        <span class="text-muted" style="font-size: 11px;">Por: <?= sanitize_html($hb['usuario_nome']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Ajustar Reserva Oculta -->
<div class="modal fade" id="modalReserva" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
            <div class="modal-header" style="border-bottom: 1px solid var(--gestao-border); padding: 20px 24px;">
                <h5 class="modal-title headline-lg" style="font-size: 20px;">Ajustar Reserva Oculta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="/gestao/estoque/reserva" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="produto_id" id="reserva_produto_id" value="">

                <div class="modal-body" style="padding: 24px;">
                    <div class="alert alert-info py-2 px-3 small mb-3">
                        Produto: <strong id="reserva_produto_nome"></strong><br>
                        Saldo Total Físico Atual: <strong id="reserva_total_fisico"></strong> un.
                    </div>

                    <div class="mb-3">
                        <label for="reserva_quantidade" class="form-label fw-bold small text-uppercase">Quantidade Reservada *</label>
                        <input type="number" class="form-control" id="reserva_quantidade" name="quantidade_reserva" required min="0" style="height: 48px;">
                        <div class="form-text">As unidades reservadas não são exibidas nem vendidas na lojinha virtual.</div>
                    </div>

                    <div class="mb-3">
                        <label for="reserva_motivo" class="form-label fw-bold small text-uppercase">Motivo do Ajuste (Opcional)</label>
                        <textarea class="form-control" id="reserva_motivo" name="motivo" rows="3" placeholder="Ex: Reserva para evento interno ou contingência de segurança..."></textarea>
                    </div>
                </div>

                <div class="modal-footer" style="border-top: 1px solid var(--gestao-border); padding: 16px 24px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-gestao btn-gestao-warning">Confirmar Reserva</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Registrar Baixa Administrativa -->
<div class="modal fade" id="modalBaixa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
            <div class="modal-header" style="border-bottom: 1px solid var(--gestao-border); padding: 20px 24px;">
                <h5 class="modal-title headline-lg text-danger" style="font-size: 20px;">Registrar Baixa Administrativa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="/gestao/estoque/baixa" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="produto_id" id="baixa_produto_id" value="">

                <div class="modal-body" style="padding: 24px;">
                    <div class="alert alert-danger py-2 px-3 small mb-3">
                        Produto: <strong id="baixa_produto_nome"></strong><br>
                        Saldo Total Físico Atual: <strong id="baixa_total_fisico"></strong> un.<br>
                        <em>Atenção: A baixa abate o saldo total físico de forma permanente.</em>
                    </div>

                    <div class="mb-3">
                        <label for="baixa_quantidade" class="form-label fw-bold small text-uppercase">Quantidade Perdida / Quebrada *</label>
                        <input type="number" class="form-control" id="baixa_quantidade" name="quantidade_baixa" required min="1" style="height: 48px;">
                    </div>

                    <div class="mb-3">
                        <label for="baixa_motivo" class="form-label fw-bold small text-uppercase">Justificativa Formal Obrigatória *</label>
                        <textarea class="form-control" id="baixa_motivo" name="motivo" required rows="3" placeholder="Ex: Quebra de garrafa na prateleira da sala de espera, avaria tardia por vazamento..."></textarea>
                    </div>
                </div>

                <div class="modal-footer" style="border-top: 1px solid var(--gestao-border); padding: 16px 24px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-gestao btn-gestao-danger">Confirmar Baixa Definitiva</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalReserva(produtoId, produtoNome, totalFisico, reservaAtual) {
    document.getElementById('reserva_produto_id').value = produtoId;
    document.getElementById('reserva_produto_nome').innerText = produtoNome;
    document.getElementById('reserva_total_fisico').innerText = totalFisico;
    document.getElementById('reserva_quantidade').value = reservaAtual;
    document.getElementById('reserva_quantidade').max = totalFisico;
    document.getElementById('reserva_motivo').value = '';

    const modal = new bootstrap.Modal(document.getElementById('modalReserva'));
    modal.show();
}

function abrirModalBaixa(produtoId, produtoNome, totalFisico) {
    document.getElementById('baixa_produto_id').value = produtoId;
    document.getElementById('baixa_produto_nome').innerText = produtoNome;
    document.getElementById('baixa_total_fisico').innerText = totalFisico;
    document.getElementById('baixa_quantidade').value = '1';
    document.getElementById('baixa_quantidade').max = totalFisico;
    document.getElementById('baixa_motivo').value = '';

    const modal = new bootstrap.Modal(document.getElementById('modalBaixa'));
    modal.show();
}
</script>

<?php require __DIR__ . '/../layouts/gestao_footer.php'; ?>
