<?php
declare(strict_types=1);

/**
 * Projeto Repack - View Gestao Correção de B.O.
 * Baseado estritamente em docs/FSD.md (Seção 6.4, 11.2, 12.3, 14 e 17) e docs/DESIGN.md
 */

require __DIR__ . '/../layouts/gestao_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="headline-lg mb-1">Auditoria e Retificação de B.O.</h1>
        <p class="body-md text-muted mb-0">Correção de apontamentos operacionais com registro de justificativa e rebalanceamento de estoque.</p>
    </div>
</div>

<!-- Barra de Filtros de Pesquisa -->
<div class="card-gestao mb-4">
    <form action="<?= APP_URL ?>/gestao/bo" method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-md-3">
            <label for="filtro_inicio" class="form-label fw-bold small text-uppercase">Data Início</label>
            <input type="date" class="form-control" id="filtro_inicio" name="data_inicio" value="<?= sanitize_html($filtros['data_inicio'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-3">
            <label for="filtro_fim" class="form-label fw-bold small text-uppercase">Data Término</label>
            <input type="date" class="form-control" id="filtro_fim" name="data_fim" value="<?= sanitize_html($filtros['data_fim'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-3">
            <label for="filtro_produto" class="form-label fw-bold small text-uppercase">Produto</label>
            <select class="form-select" id="filtro_produto" name="produto_id">
                <option value="">Todos os produtos</option>
                <?php foreach ($produtos as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" <?= (!empty($filtros['produto_id']) && (int)$filtros['produto_id'] === (int)$p['id']) ? 'selected' : '' ?>>
                        <?= sanitize_html($p['nome']) ?> (<?= sanitize_html($p['codigo']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label for="filtro_operador" class="form-label fw-bold small text-uppercase">Operador</label>
            <select class="form-select" id="filtro_operador" name="usuario_id">
                <option value="">Todos os operadores</option>
                <?php foreach ($operadores as $op): ?>
                    <option value="<?= (int)$op['id'] ?>" <?= (!empty($filtros['usuario_id']) && (int)$filtros['usuario_id'] === (int)$op['id']) ? 'selected' : '' ?>>
                        <?= sanitize_html($op['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label for="filtro_destino" class="form-label fw-bold small text-uppercase">Destino</label>
            <select class="form-select" id="filtro_destino" name="destino">
                <option value="">Todos</option>
                <option value="lojinha" <?= ($filtros['destino'] ?? '') === 'lojinha' ? 'selected' : '' ?>>Lojinha (Leves)</option>
                <option value="descarte" <?= ($filtros['destino'] ?? '') === 'descarte' ? 'selected' : '' ?>>Descarte (Furo)</option>
            </select>
        </div>
        <div class="col-12 col-md-9 text-end">
            <a href="<?= APP_URL ?>/gestao/bo" class="btn btn-outline-secondary me-2">Limpar Filtros</a>
            <button type="submit" class="btn btn-gestao btn-gestao-secondary">
                🔍 Filtrar Registros
            </button>
        </div>
    </form>
</div>

<!-- Tabela de Apontamentos de B.O. -->
<div class="card-gestao">
    <div class="table-responsive">
        <table class="table-gestao">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data/Hora</th>
                    <th>Produto</th>
                    <th>Operador</th>
                    <th>Tipo de Avaria</th>
                    <th>Qtd</th>
                    <th>Destino</th>
                    <th>Auditoria / Correção</th>
                    <th style="width: 120px; text-align: right;">Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($apontamentos)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            Nenhum apontamento de B.O. localizado para os filtros informados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($apontamentos as $bo): ?>
                        <tr>
                            <td><span class="badge bg-light text-dark border">#<?= (int)$bo['id'] ?></span></td>
                            <td class="small"><?= date('d/m/Y H:i', strtotime($bo['created_at'])) ?></td>
                            <td>
                                <strong><?= sanitize_html($bo['produto_nome']) ?></strong>
                                <div class="text-muted small"><?= sanitize_html($bo['produto_codigo']) ?></div>
                            </td>
                            <td>
                                <?= sanitize_html($bo['operador_nome']) ?>
                                <div class="text-muted small"><?= sanitize_html($bo['operador_matricula']) ?></div>
                            </td>
                            <td>
                                <strong class="small"><?= sanitize_html(str_replace('_', ' ', ucfirst($bo['tipo_avaria']))) ?></strong>
                            </td>
                            <td>
                                <strong style="font-size: 16px;"><?= (int)$bo['quantidade'] ?></strong> un
                            </td>
                            <td>
                                <span class="badge-status <?= $bo['destino'] === 'lojinha' ? 'lojinha' : 'descarte' ?>">
                                    <?= $bo['destino'] === 'lojinha' ? 'Lojinha' : 'Descarte' ?>
                                </span>
                            </td>
                            <td class="small">
                                <?php if (!empty($bo['corrigido_por_usuario_id'])): ?>
                                    <div class="text-warning fw-bold">⚠️ Retificado</div>
                                    <div class="text-muted" style="font-size: 11px;">
                                        Por: <?= sanitize_html($bo['corretor_nome'] ?? 'Gestão') ?><br>
                                        Motivo: <?= sanitize_html($bo['motivo_correcao'] ?? '') ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 12px;">Original (Operador)</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <button type="button" class="btn btn-sm btn-outline-warning" 
                                        onclick='abrirModalCorrecao(<?= json_encode($bo, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Retificar apontamento">
                                    ✏️ Corrigir
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de Retificação de B.O. -->
<div class="modal fade" id="modalCorrecao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
            <div class="modal-header" style="border-bottom: 1px solid var(--gestao-border); padding: 20px 24px;">
                <h5 class="modal-title headline-lg text-warning" style="font-size: 20px;">Retificar Apontamento de B.O.</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= APP_URL ?>/gestao/bo/corrigir" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="bo_id" id="corrigir_bo_id" value="">

                <div class="modal-body" style="padding: 24px;">
                    <div class="alert alert-secondary py-2 px-3 small mb-3">
                        Apontamento: <strong id="corrigir_bo_rotulo"></strong><br>
                        Produto: <strong id="corrigir_produto_nome"></strong><br>
                        Operador Original: <strong id="corrigir_operador_nome"></strong>
                    </div>

                    <div class="mb-3">
                        <label for="corrigir_quantidade" class="form-label fw-bold small text-uppercase">Quantidade Correta *</label>
                        <input type="number" class="form-control" id="corrigir_quantidade" name="quantidade" required min="1" style="height: 48px;">
                    </div>

                    <div class="mb-3">
                        <label for="corrigir_tipo" class="form-label fw-bold small text-uppercase">Tipo de Avaria Correto *</label>
                        <select class="form-select" id="corrigir_tipo" name="tipo_avaria" required style="height: 48px;">
                            <optgroup label="Destino Lojinha (Avarias Leves)">
                                <option value="amassada">Amassada</option>
                                <option value="def_rotulo">Defeito no Rótulo</option>
                                <option value="emba_danificada">Embalagem Danificada</option>
                            </optgroup>
                            <optgroup label="Destino Descarte (Perdas Irreversíveis)">
                                <option value="furo">Furo</option>
                                <option value="micro_furo">Micro Furo</option>
                            </optgroup>
                        </select>
                        <div class="form-text">Alterar entre avaria leve e furo ajustará o saldo de estoque automaticamente.</div>
                    </div>

                    <div class="mb-3">
                        <label for="corrigir_motivo" class="form-label fw-bold small text-uppercase">Motivo da Retificação (Obrigatório) *</label>
                        <textarea class="form-control" id="corrigir_motivo" name="motivo_correcao" required rows="3" placeholder="Ex: Operador digitou 100 ao invés de 10 por engano na bancada..."></textarea>
                    </div>
                </div>

                <div class="modal-footer" style="border-top: 1px solid var(--gestao-border); padding: 16px 24px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-gestao btn-gestao-warning">Salvar Retificação</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalCorrecao(bo) {
    document.getElementById('corrigir_bo_id').value = bo.id;
    document.getElementById('corrigir_bo_rotulo').innerText = '#' + bo.id + ' (' + bo.created_at + ')';
    document.getElementById('corrigir_produto_nome').innerText = bo.produto_nome + ' (' + bo.produto_codigo + ')';
    document.getElementById('corrigir_operador_nome').innerText = bo.operador_nome;
    document.getElementById('corrigir_quantidade').value = bo.quantidade;
    document.getElementById('corrigir_tipo').value = bo.tipo_avaria;
    document.getElementById('corrigir_motivo').value = bo.motivo_correcao || '';

    const modal = new bootstrap.Modal(document.getElementById('modalCorrecao'));
    modal.show();
}
</script>

<?php require __DIR__ . '/../layouts/gestao_footer.php'; ?>
