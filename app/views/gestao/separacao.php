<?php
declare(strict_types=1);

/**
 * Projeto Repack - View Lista de Separação Física
 * Baseado estritamente em docs/FSD.md (Seções 6, 8, 16 e 22.1) e docs/DESIGN.md
 */

require_once __DIR__ . '/../layouts/gestao_header.php';
?>

<style>
    /* Estilos específicos da tela de separação */
    .separacao-header-card {
        background: #FFFFFF;
        border: 1px solid var(--gestao-border);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
    }

    .badge-pedido-status {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 4px 10px;
        border-radius: 9999px;
    }
    .badge-pedido-status.confirmado { background: #DBEAFE; color: #1E40AF; }
    .badge-pedido-status.separado { background: #FEF3C7; color: #92400E; }
    .badge-pedido-status.entregue { background: #D1FAE5; color: #065F46; }
    .badge-pedido-status.cancelado { background: #FEE2E2; color: #991B1B; }

    .card-colaborador {
        background: #FFFFFF;
        border: 1px solid var(--gestao-border);
        border-radius: 12px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        overflow: hidden;
        transition: border-color 0.15s ease;
    }

    .card-colaborador.separado-completo {
        border-color: #10B981;
        background-color: #FAFFFD;
    }

    .header-colaborador {
        background: #F9FAFB;
        padding: 14px 20px;
        border-bottom: 1px solid var(--gestao-border);
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }

    .check-item-separado:checked + span {
        text-decoration: line-through;
        color: #9CA3AF;
    }

    .signature-box {
        display: none;
    }

    /* Otimização para impressão da prancheta física de galpão */
    @media print {
        body {
            background-color: #FFFFFF !important;
            color: #000000 !important;
            font-size: 12pt;
        }

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

        .separacao-header-card {
            border: none !important;
            padding: 0 0 15px 0 !important;
            margin-bottom: 15px !important;
            border-bottom: 2px solid #000000 !important;
        }

        .card-colaborador {
            border: 1px solid #000000 !important;
            margin-bottom: 20px !important;
            page-break-inside: avoid;
        }

        .header-colaborador {
            background-color: #EEEEEE !important;
            border-bottom: 1px solid #000000 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .table-gestao th,
        .table-gestao td {
            border-bottom: 1px solid #CCCCCC !important;
            padding: 6px 8px !important;
        }

        .signature-box {
            display: block !important;
            margin-top: 15px;
            padding-top: 25px;
            border-top: 1px dashed #666666;
            font-size: 11pt;
        }

        .check-box-print {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 1.5px solid #000000;
            vertical-align: middle;
            margin-right: 8px;
        }
    }
</style>

<!-- Topo e Filtros da Lista de Separação -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 no-print">
    <div>
        <h1 class="h3 fw-bold mb-1 d-flex align-items-center gap-2">
            <span>📋</span> Lista de Separação Física
        </h1>
        <p class="text-muted mb-0">
            Conferência, separação no galpão e comprovante de retirada física por lote.
        </p>
    </div>

    <div class="d-flex align-items-center gap-2">
        <button type="button" onclick="window.print()" class="btn btn-gestao btn-gestao-secondary" title="Imprimir lista para prancheta física de galpão">
            🖨️ Imprimir Lista
        </button>

        <?php if (!empty($loteId)): ?>
            <a href="<?= APP_URL ?>/gestao/separacao/exportar?lote_id=<?= (int)$loteId ?>" class="btn btn-gestao btn-outline-secondary" title="Exportar para Excel / CSV">
                📥 Exportar CSV
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Barra de Seleção de Lote e Filtros -->
<div class="separacao-header-card no-print">
    <form method="GET" action="<?= APP_URL ?>/gestao/separacao" class="row g-3 align-items-end">
        <div class="col-md-5">
            <label for="lote_id" class="form-label fw-bold">Selecione o Lote de Venda:</label>
            <select name="lote_id" id="lote_id" class="form-select form-select-lg" onchange="this.form.submit()">
                <?php if (empty($lotes)): ?>
                    <option value="">Nenhum lote de venda cadastrado</option>
                <?php else: ?>
                    <?php foreach ($lotes as $l): ?>
                        <option value="<?= (int)$l['id'] ?>" <?= ((int)$l['id'] === (int)$loteId) ? 'selected' : '' ?>>
                            <?= sanitize_html($l['titulo']) ?> (Status: <?= strtoupper(sanitize_html($l['status'])) ?> | Vigência: <?= date('d/m/Y', strtotime($l['data_inicio'])) ?> até <?= date('d/m/Y', strtotime($l['data_fim'])) ?>)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label for="status" class="form-label fw-bold">Status dos Pedidos:</label>
            <select name="status" id="status" class="form-select" onchange="this.form.submit()">
                <option value="todos" <?= ($statusFiltro === null) ? 'selected' : '' ?>>Todos (Exceto Cancelados)</option>
                <option value="confirmado" <?= ($statusFiltro === 'confirmado') ? 'selected' : '' ?>>Confirmados (Aguardando Separação)</option>
                <option value="separado" <?= ($statusFiltro === 'separado') ? 'selected' : '' ?>>Separados (Prontos para Retirada)</option>
                <option value="entregue" <?= ($statusFiltro === 'entregue') ? 'selected' : '' ?>>Entregues (Finalizados)</option>
            </select>
        </div>

        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-gestao btn-gestao-primary w-100 justify-content-center">
                🔍 Filtrar
            </button>
            <a href="<?= APP_URL ?>/gestao/separacao" class="btn btn-outline-secondary">Limpar</a>
        </div>
    </form>
</div>

<!-- Cabeçalho Exclusivo para Impressão -->
<div class="d-none d-print-block mb-3">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
        <div>
            <h2 class="h4 fw-bold mb-0">PROJETO REPACK - LISTA DE SEPARAÇÃO FÍSICA NO GALPÃO</h2>
            <p class="mb-0 small text-muted">Emissão em: <?= date('d/m/Y H:i:s') ?> | Usuário Gestor: <?= sanitize_html($_SESSION['nome'] ?? 'Gestão') ?></p>
        </div>
        <div class="text-end">
            <span class="fw-bold">LOTE: <?= sanitize_html($loteSelecionado['titulo'] ?? "Lote #{$loteId}") ?></span>
        </div>
    </div>
</div>

<!-- Cartões de Resumo do Lote -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card-gestao card-metric p-3 mb-0">
            <div class="text-muted small fw-bold text-uppercase">Total de Pedidos</div>
            <div class="fs-3 fw-bold text-dark mt-1"><?= (int)$estatisticas['total_pedidos'] ?></div>
            <div class="small text-muted"><?= (int)$estatisticas['total_colaboradores'] ?> colaborador(es)</div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card-gestao card-metric p-3 mb-0" style="border-left-color: var(--gestao-primary);">
            <div class="text-muted small fw-bold text-uppercase">Unidades a Separar</div>
            <div class="fs-3 fw-bold text-dark mt-1"><?= (int)$estatisticas['total_itens'] ?> un.</div>
            <div class="small text-muted">Volume físico total</div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card-gestao card-metric p-3 mb-0" style="border-left-color: var(--gestao-warning);">
            <div class="text-muted small fw-bold text-uppercase">Aguardando Separação</div>
            <div class="fs-3 fw-bold text-warning mt-1"><?= (int)$estatisticas['total_confirmados'] ?></div>
            <div class="small text-muted">Pedidos pendentes</div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card-gestao card-metric p-3 mb-0" style="border-left-color: #8B5CF6;">
            <div class="text-muted small fw-bold text-uppercase">Separados / Entregues</div>
            <div class="fs-3 fw-bold text-success mt-1">
                <?= (int)$estatisticas['total_separados'] ?> / <?= (int)$estatisticas['total_entregues'] ?>
            </div>
            <div class="small text-muted">Conferidos / Concluídos</div>
        </div>
    </div>
</div>

<!-- Listagem Consolidada Agrupada por Colaborador -->
<?php if (empty($separacaoAgrupada)): ?>
    <div class="card-gestao text-center py-5">
        <div style="font-size: 48px; margin-bottom: 12px;">📦</div>
        <h4 class="fw-bold">Nenhum pedido encontrado para separação neste lote</h4>
        <p class="text-muted mb-0">
            Verifique se o lote selecionado possui compras confirmadas ou ajuste o filtro de status.
        </p>
    </div>
<?php else: ?>
    <?php foreach ($separacaoAgrupada as $index => $colaborador): ?>
        <div class="card-colaborador" id="card-colab-<?= (int)$colaborador['usuario_id'] ?>">
            <!-- Cabeçalho do Colaborador -->
            <div class="header-colaborador">
                <div>
                    <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <span>👤</span>
                        <span><?= sanitize_html($colaborador['nome']) ?></span>
                        <span class="badge bg-secondary text-white fw-normal" style="font-size: 12px;">
                            Matrícula: <?= sanitize_html($colaborador['matricula']) ?>
                        </span>
                    </h5>
                    <span class="text-muted small">
                        Setor: <strong><?= sanitize_html($colaborador['setor']) ?></strong> &bull;
                        Total de Itens: <strong><?= (int)$colaborador['total_geral_itens'] ?> un.</strong>
                    </span>
                </div>

                <div class="d-flex align-items-center gap-2 no-print">
                    <span class="text-muted small">
                        <?= count($colaborador['pedidos']) ?> pedido(s)
                    </span>
                </div>
            </div>

            <!-- Tabela de Pedidos e Itens -->
            <div class="p-3">
                <?php foreach ($colaborador['pedidos'] as $pedido): ?>
                    <div class="mb-3 p-2 rounded" style="background-color: #FFFFFF; border: 1px solid #E5E7EB;">
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <div>
                                <span class="fw-bold text-primary font-monospace" style="font-size: 15px;">
                                    <?= sanitize_html($pedido['codigo_pedido']) ?>
                                </span>
                                <span class="text-muted small ms-2">
                                    📅 <?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?>
                                </span>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <span class="badge-pedido-status <?= sanitize_html($pedido['status']) ?>" id="badge-status-<?= (int)$pedido['pedido_id'] ?>">
                                    <?= strtoupper(sanitize_html($pedido['status'])) ?>
                                </span>

                                <!-- Ações Rápidas de Separação (No-Print) -->
                                <div class="dropdown no-print">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle py-0 px-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 13px;">
                                        Alterar Status
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <button type="button" class="dropdown-item btn-mudar-status" data-pedido-id="<?= (int)$pedido['pedido_id'] ?>" data-status="confirmado">
                                                🟡 Marcar Confirmado
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item btn-mudar-status" data-pedido-id="<?= (int)$pedido['pedido_id'] ?>" data-status="separado">
                                                📦 Marcar como Separado
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item btn-mudar-status" data-pedido-id="<?= (int)$pedido['pedido_id'] ?>" data-status="entregue">
                                                ✅ Marcar como Entregue
                                            </button>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button type="button" class="dropdown-item text-danger btn-mudar-status" data-pedido-id="<?= (int)$pedido['pedido_id'] ?>" data-status="cancelado">
                                                ❌ Cancelar Pedido
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Tabela de Itens Físicos -->
                        <div class="table-responsive">
                            <table class="table table-sm table-gestao align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;" class="text-center">Conf.</th>
                                        <th style="width: 70px;">Foto</th>
                                        <th style="width: 120px;">Código</th>
                                        <th>Produto</th>
                                        <th style="width: 130px;">Embalagem</th>
                                        <th style="width: 90px;" class="text-center">Qtd.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pedido['itens'] as $item): ?>
                                        <tr>
                                            <td class="text-center">
                                                <!-- Checkbox para tablet no-print -->
                                                <input type="checkbox" class="form-check-input check-item-separado no-print" style="width: 22px; height: 22px; cursor: pointer;">
                                                <!-- Quadrado impresso para prancheta -->
                                                <span class="d-none d-print-inline-block check-box-print"></span>
                                            </td>
                                            <td>
                                                <?php if (!empty($item['foto_url'])): ?>
                                                    <img src="<?= APP_URL . sanitize_html($item['foto_url']) ?>" alt="Foto" style="width: 42px; height: 42px; object-fit: contain; border-radius: 6px; border: 1px solid #E5E7EB;">
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border p-2">Sem foto</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="font-monospace fw-bold text-secondary">
                                                <?= sanitize_html($item['codigo']) ?>
                                            </td>
                                            <td>
                                                <span class="fw-semibold text-dark"><?= sanitize_html($item['nome']) ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?= sanitize_html($item['pacote_padrao']) ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="fw-bold fs-6 text-primary"><?= (int)$item['quantidade'] ?></span> un.
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Linha de Assinatura para Prancheta Impressa (Conforme docs/FSD.md Seção 22.1) -->
                <div class="signature-box">
                    <div class="row align-items-end text-center">
                        <div class="col-4">
                            <div class="border-top border-dark pt-1 small">
                                <strong>Separado por (Galpão)</strong><br>
                                Data: ____/____/________
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border-top border-dark pt-1 small">
                                <strong>Conferido por (Repack)</strong><br>
                                Data: ____/____/________
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border-top border-dark pt-1 small">
                                <strong>Visto do Colaborador (Recebimento)</strong><br>
                                Matrícula: <?= sanitize_html($colaborador['matricula']) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '<?= $csrfToken ?>';
    const loteId = '<?= (int)$loteId ?>';

    // Interação de checkboxes para conferência física (marcação em tela)
    document.querySelectorAll('.check-item-separado').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const tr = this.closest('tr');
            if (this.checked) {
                tr.style.backgroundColor = '#ECFDF5';
            } else {
                tr.style.backgroundColor = '';
            }
        });
    });

    // Alteração assíncrona de status do pedido
    document.querySelectorAll('.btn-mudar-status').forEach(function(btn) {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const pedidoId = this.getAttribute('data-pedido-id');
            const status = this.getAttribute('data-status');

            try {
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                formData.append('pedido_id', pedidoId);
                formData.append('status', status);
                formData.append('lote_id', loteId);

                const response = await fetch('<?= APP_URL ?>/gestao/separacao/status', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();
                if (data.status === 'success') {
                    const badge = document.getElementById('badge-status-' + pedidoId);
                    if (badge) {
                        badge.className = 'badge-pedido-status ' + status;
                        badge.textContent = status.toUpperCase();
                    }
                } else {
                    alert('Erro ao atualizar status: ' + (data.message || 'Falha desconhecida'));
                }
            } catch (err) {
                console.error(err);
                alert('Falha na comunicação com o servidor.');
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/gestao_footer.php'; ?>
