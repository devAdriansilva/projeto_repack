<?php
declare(strict_types=1);

/**
 * Projeto Repack - View Gestao Lotes de Venda
 * Baseado estritamente em docs/FSD.md (Seção 6.3, 11.2, 12.3 e 14) e docs/DESIGN.md
 */

require __DIR__ . '/../layouts/gestao_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="headline-lg mb-1">Lotes de Venda da Lojinha</h1>
        <p class="body-md text-muted mb-0">Controle de abertura da vitrine por horário, setores elegíveis e limites por comprador.</p>
    </div>
    <button type="button" class="btn btn-gestao btn-gestao-primary" onclick="abrirModalLote()">
        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
        Novo Lote
    </button>
</div>

<div class="card-gestao">
    <div class="table-responsive">
        <table class="table-gestao">
            <thead>
                <tr>
                    <th>Título / Descrição</th>
                    <th>Período de Vigência</th>
                    <th>Setores Autorizados</th>
                    <th>Limite / Pessoa</th>
                    <th>Pedidos / Itens</th>
                    <th>Status</th>
                    <th style="width: 220px; text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lotes)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            Nenhum lote de venda cadastrado até o momento.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($lotes as $lote): ?>
                        <tr>
                            <td>
                                <strong><?= sanitize_html($lote['titulo']) ?></strong>
                                <?php if (!empty($lote['descricao'])): ?>
                                    <div class="text-muted small"><?= sanitize_html($lote['descricao']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small">
                                    <strong>Início:</strong> <?= date('d/m/Y H:i', strtotime($lote['data_inicio'])) ?><br>
                                    <strong>Fim:</strong> <?= date('d/m/Y H:i', strtotime($lote['data_fim'])) ?>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1" style="max-width: 250px;">
                                    <?php foreach ($lote['setores_lista'] as $setor): ?>
                                        <span class="badge bg-light text-dark border small"><?= sanitize_html($setor) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary" style="font-size: 13px;">
                                    Até <?= (int)$lote['limite_itens_por_comprador'] ?> itens
                                </span>
                            </td>
                            <td>
                                <div class="small">
                                    <strong><?= (int)$lote['total_pedidos'] ?></strong> pedidos<br>
                                    <span class="text-muted"><?= (int)$lote['total_itens_vendidos'] ?> itens vendidos</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge-status <?= $lote['status'] ?>">
                                    <?= ucfirst($lote['status']) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div class="btn-group btn-group-sm mb-1" role="group">
                                    <?php if ($lote['status'] !== 'aberto'): ?>
                                        <form action="<?= APP_URL ?>/gestao/lotes/status" method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="id" value="<?= (int)$lote['id'] ?>">
                                            <input type="hidden" name="status" value="aberto">
                                            <button type="submit" class="btn btn-outline-success" title="Abrir lote agora">
                                                Abrir
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($lote['status'] === 'aberto'): ?>
                                        <form action="<?= APP_URL ?>/gestao/lotes/status" method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="id" value="<?= (int)$lote['id'] ?>">
                                            <input type="hidden" name="status" value="encerrado">
                                            <button type="submit" class="btn btn-outline-danger" title="Encerrar lote">
                                                Encerrar
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <button type="button" class="btn btn-outline-secondary" onclick='editarLote(<?= json_encode($lote, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Editar lote">
                                        ✏️
                                    </button>

                                    <form action="<?= APP_URL ?>/gestao/lotes/excluir" method="POST" class="d-inline" onsubmit="return confirm('Deseja realmente excluir este lote?');">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <input type="hidden" name="id" value="<?= (int)$lote['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger" title="Excluir lote">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de Cadastro / Edição de Lote -->
<div class="modal fade" id="modalLote" tabindex="-1" aria-labelledby="modalLoteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
            <div class="modal-header" style="border-bottom: 1px solid var(--gestao-border); padding: 20px 24px;">
                <h5 class="modal-title headline-lg" id="modalLoteLabel" style="font-size: 20px;">Planejar Lote de Venda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= APP_URL ?>/gestao/lotes/salvar" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="id" id="lote_id" value="">

                <div class="modal-body" style="padding: 24px;">
                    <div class="mb-3">
                        <label for="lote_titulo" class="form-label fw-bold small text-uppercase">Título do Lote *</label>
                        <input type="text" class="form-control" id="lote_titulo" name="titulo" required placeholder="Ex: Lote Especial Repack - Novembro" style="height: 48px;">
                    </div>

                    <div class="mb-3">
                        <label for="lote_descricao" class="form-label fw-bold small text-uppercase">Descrição / Observações</label>
                        <textarea class="form-control" id="lote_descricao" name="descricao" rows="2" placeholder="Orientações aos colaboradores..."></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="lote_inicio" class="form-label fw-bold small text-uppercase">Data e Hora de Início *</label>
                            <input type="datetime-local" class="form-control" id="lote_inicio" name="data_inicio" required style="height: 48px;">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="lote_fim" class="form-label fw-bold small text-uppercase">Data e Hora de Término *</label>
                            <input type="datetime-local" class="form-control" id="lote_fim" name="data_fim" required style="height: 48px;">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="lote_limite" class="form-label fw-bold small text-uppercase">Limite Máximo de Itens por Pessoa *</label>
                            <input type="number" class="form-control" id="lote_limite" name="limite_itens_por_comprador" required min="1" max="50" value="5" style="height: 48px;">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="lote_status" class="form-label fw-bold small text-uppercase">Status do Lote *</label>
                            <select class="form-select" id="lote_status" name="status" style="height: 48px;">
                                <option value="planejado">Planejado (Agendado)</option>
                                <option value="aberto">Aberto (Vitrine no Ar)</option>
                                <option value="encerrado">Encerrado</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase d-block">Setores Autorizados *</label>
                        <div class="d-flex flex-wrap gap-3 p-3 rounded" style="background-color: #F9FAFB; border: 1px solid var(--gestao-border);">
                            <?php foreach ($setoresDisponiveis as $setor): ?>
                                <div class="form-check">
                                    <input class="form-check-input check-setor" type="checkbox" name="setores_autorizados[]" value="<?= sanitize_html($setor) ?>" id="setor_<?= md5($setor) ?>">
                                    <label class="form-check-label" for="setor_<?= md5($setor) ?>">
                                        <?= sanitize_html($setor) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-text">Apenas colaboradores pertencentes a estes setores poderão visualizar o catálogo.</div>
                    </div>
                </div>

                <div class="modal-footer" style="border-top: 1px solid var(--gestao-border); padding: 16px 24px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-gestao btn-gestao-primary">Salvar Lote</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalLote() {
    document.getElementById('modalLoteLabel').innerText = 'Planejar Novo Lote de Venda';
    document.getElementById('lote_id').value = '';
    document.getElementById('lote_titulo').value = '';
    document.getElementById('lote_descricao').value = '';
    
    // Configura datas padrão: início agora, fim amanhã mesmo horário
    const agora = new Date();
    const amanha = new Date(agora.getTime() + 24 * 60 * 60 * 1000);
    document.getElementById('lote_inicio').value = agora.toISOString().slice(0, 16);
    document.getElementById('lote_fim').value = amanha.toISOString().slice(0, 16);
    
    document.getElementById('lote_limite').value = '5';
    document.getElementById('lote_status').value = 'planejado';
    
    document.querySelectorAll('.check-setor').forEach(cb => cb.checked = true);

    const modal = new bootstrap.Modal(document.getElementById('modalLote'));
    modal.show();
}

function editarLote(lote) {
    document.getElementById('modalLoteLabel').innerText = 'Editar Lote #' + lote.id;
    document.getElementById('lote_id').value = lote.id;
    document.getElementById('lote_titulo').value = lote.titulo;
    document.getElementById('lote_descricao').value = lote.descricao || '';
    
    document.getElementById('lote_inicio').value = lote.data_inicio.replace(' ', 'T').slice(0, 16);
    document.getElementById('lote_fim').value = lote.data_fim.replace(' ', 'T').slice(0, 16);
    document.getElementById('lote_limite').value = lote.limite_itens_por_comprador;
    document.getElementById('lote_status').value = lote.status;
    
    const setoresMarcados = lote.setores_lista || [];
    document.querySelectorAll('.check-setor').forEach(cb => {
        cb.checked = setoresMarcados.includes(cb.value);
    });

    const modal = new bootstrap.Modal(document.getElementById('modalLote'));
    modal.show();
}
</script>

<?php require __DIR__ . '/../layouts/gestao_footer.php'; ?>
