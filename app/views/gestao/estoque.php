<?php
declare(strict_types=1);

/**
 * Projeto Repack - View Gestao Estoque & Reservas com Inventário / Balanço
 * Baseado estritamente em docs/FSD.md (Seção 6.4, 11.2, 12.3 e 17) e docs/DESIGN.md
 */

require __DIR__ . '/../layouts/gestao_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="headline-lg mb-1">Gestão de Estoque da Lojinha</h1>
        <p class="body-md text-muted mb-0">Controle de saldos físicos recebidos do repack, balanços de inventário e reservas ocultas.</p>
    </div>
    <div>
        <button type="button" class="btn btn-gestao btn-gestao-primary" onclick="abrirModalInventarioGeral()">
            📋 Realizar Inventário / Balanço
        </button>
    </div>
</div>

<!-- Tabela Principal de Saldos -->
<div class="card-gestao">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="headline-lg mb-0" style="font-size: 20px;">Visão Geral de Saldos Físicos</h2>
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
                    <th style="width: 310px; text-align: right;">Ações de Controle</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($estoqueGeral)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            Nenhum produto com saldo em estoque. Clique em <strong>"Realizar Inventário / Balanço"</strong> para dar entrada no inventário inicial.
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
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                        onclick="abrirModalInventarioItem(<?= (int)$item['produto_id'] ?>, '<?= sanitize_html(addslashes($item['nome'])) ?>', '<?= sanitize_html($item['codigo']) ?>', <?= (int)$item['quantidade_total'] ?>)" title="Inventário / Ajustar Saldo Físico">
                                    📝 Inventário
                                </button>
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

<!-- Seção de Históricos Recentes (Três Colunas com Inventário) -->
<div class="row g-4 mt-1">
    <!-- Histórico de Inventários / Balanços -->
    <div class="col-12 col-xl-4 col-lg-12">
        <div class="card-gestao h-100">
            <h2 class="headline-lg mb-3" style="font-size: 18px;">📋 Histórico de Inventários</h2>
            <?php if (empty($historicoInventarios)): ?>
                <p class="text-muted small mb-0">Nenhum inventário registrado.</p>
            <?php else: ?>
                <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                    <table class="table-gestao">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Produto</th>
                                <th>Novo Saldo</th>
                                <th>Ajuste</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historicoInventarios as $hi): 
                                $dif = (int)$hi['diferenca'];
                                $difClass = $dif > 0 ? 'text-success' : ($dif < 0 ? 'text-danger' : 'text-muted');
                                $difSinal = $dif > 0 ? '+' : '';
                            ?>
                                <tr>
                                    <td class="small" style="white-space: nowrap;"><?= date('d/m H:i', strtotime($hi['created_at'])) ?></td>
                                    <td>
                                        <strong class="small d-block"><?= sanitize_html($hi['produto_nome']) ?></strong>
                                        <span class="text-muted" style="font-size: 11px;"><?= sanitize_html($hi['motivo']) ?></span>
                                    </td>
                                    <td><span class="badge bg-primary"><?= (int)$hi['novo_saldo'] ?> un</span></td>
                                    <td class="small fw-bold <?= $difClass ?>" style="white-space: nowrap;">
                                        <?= $difSinal . $dif ?> un
                                        <?php if (!empty($hi['detalhes_contagem'])): ?>
                                            <div style="font-size: 10px; font-weight: normal; color: #64748B;">(<?= sanitize_html($hi['detalhes_contagem']) ?>)</div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Histórico de Reservas Ocultas -->
    <div class="col-12 col-xl-4 col-lg-6">
        <div class="card-gestao h-100">
            <h2 class="headline-lg mb-3" style="font-size: 18px;">🛡️ Histórico de Reservas</h2>
            <?php if (empty($historicoReservas)): ?>
                <p class="text-muted small mb-0">Nenhum ajuste manual de reserva registrado.</p>
            <?php else: ?>
                <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                    <table class="table-gestao">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Produto</th>
                                <th>Reserva</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historicoReservas as $hr): ?>
                                <tr>
                                    <td class="small" style="white-space: nowrap;"><?= date('d/m H:i', strtotime($hr['created_at'])) ?></td>
                                    <td>
                                        <strong class="small d-block"><?= sanitize_html($hr['produto_nome']) ?></strong>
                                        <span class="text-muted" style="font-size: 11px;"><?= sanitize_html($hr['motivo']) ?></span>
                                    </td>
                                    <td><span class="badge bg-warning text-dark"><?= (int)$hr['quantidade'] ?> un</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Histórico de Baixas Administrativas -->
    <div class="col-12 col-xl-4 col-lg-6">
        <div class="card-gestao h-100">
            <h2 class="headline-lg mb-3" style="font-size: 18px;">📉 Histórico de Baixas</h2>
            <?php if (empty($historicoBaixas)): ?>
                <p class="text-muted small mb-0">Nenhuma baixa administrativa registrada.</p>
            <?php else: ?>
                <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                    <table class="table-gestao">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Produto</th>
                                <th>Baixa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historicoBaixas as $hb): ?>
                                <tr>
                                    <td class="small" style="white-space: nowrap;"><?= date('d/m H:i', strtotime($hb['created_at'])) ?></td>
                                    <td>
                                        <strong class="small d-block"><?= sanitize_html($hb['produto_nome']) ?></strong>
                                        <span class="text-muted" style="font-size: 11px;"><?= sanitize_html($hb['motivo']) ?></span>
                                    </td>
                                    <td><span class="badge bg-danger"><?= (int)$hb['quantidade'] ?> un</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================= -->
<!-- MODAL DE INVENTÁRIO / BALANÇO COM INCREMENTOS PARCIAIS        -->
<!-- ============================================================= -->
<div class="modal fade" id="modalInventario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
            <div class="modal-header" style="border-bottom: 1px solid var(--gestao-border); padding: 20px 24px;">
                <h5 class="modal-title headline-lg" style="font-size: 20px;">
                    📋 Inventário & Balanço de Estoque
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            
            <form action="<?= APP_URL ?>/gestao/estoque/inventario" method="POST" id="formInventario">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="detalhes_contagem" id="inv_detalhes_contagem" value="">
                
                <div class="modal-body" style="padding: 24px;">
                    <!-- Seletor de Produto -->
                    <div class="mb-3">
                        <label for="inv_produto_id" class="form-label fw-bold small text-uppercase">Selecionar Produto *</label>
                        <select class="form-select" id="inv_produto_id" name="produto_id" required style="height: 48px;" onchange="aoSelecionarProdutoInventario(this.value)">
                            <option value="">-- Escolha o produto a inventariar --</option>
                            <?php foreach ($todosProdutos as $p): ?>
                                <option value="<?= (int)$p['id'] ?>" data-nome="<?= sanitize_html($p['nome']) ?>" data-codigo="<?= sanitize_html($p['codigo']) ?>" data-embalagem="<?= sanitize_html($p['pacote_padrao']) ?>">
                                    <?= sanitize_html($p['nome']) ?> (<?= sanitize_html($p['codigo']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Card Informativo do Saldo Atual -->
                    <div class="alert alert-secondary py-2 px-3 small mb-3 d-flex justify-content-between align-items-center" id="cardInfoSaldoAtual" style="display: none !important;">
                        <div>
                            Produto: <strong id="inv_display_produto"></strong> (<span id="inv_display_codigo"></span>)<br>
                            Embalagem: <span id="inv_display_embalagem"></span>
                        </div>
                        <div class="text-end">
                            <span class="text-muted d-block" style="font-size: 11px;">Saldo Atual no Sistema:</span>
                            <strong style="font-size: 18px; color: #1E293B;" id="inv_display_saldo_atual">0 un</strong>
                        </div>
                    </div>

                    <!-- Modo de Aplicação -->
                    <div class="mb-3 p-3" style="background: #F8FAFC; border: 2px solid #E2E8F0; border-radius: 12px;">
                        <label class="form-label fw-bold small text-uppercase d-block mb-2">Tipo de Aplicação da Contagem:</label>
                        <div class="d-flex gap-3 flex-wrap">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_operacao" id="tipoSubstituicao" value="substituicao" checked onchange="recalcularResultadoInventario()">
                                <label class="form-check-label fw-bold text-dark" for="tipoSubstituicao">
                                    Substituir pelo Total Contado <span class="text-muted fw-normal">(Inventário Geral / Balanço)</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_operacao" id="tipoIncremento" value="incremento" onchange="recalcularResultadoInventario()">
                                <label class="form-check-label fw-bold text-dark" for="tipoIncremento">
                                    Somar ao Estoque Atual <span class="text-muted fw-normal">(Entrada Avulsa / Lote Encontrado)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Calculadora / Somador de Incrementos Parciais -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Contagem / Quantidade Contada *</label>
                        
                        <div class="input-group mb-2">
                            <input type="number" 
                                   class="form-control" 
                                   id="inv_quantidade_contada" 
                                   name="quantidade_contada" 
                                   required 
                                   min="0" 
                                   value="0" 
                                   style="height: 54px; font-size: 24px; font-weight: 800; text-align: center;"
                                   oninput="aoDigitarQuantidadeDireta(this.value)">
                            <button type="button" class="btn btn-outline-danger" onclick="zerarContadorInventario()" title="Zerar contagem">
                                Zerar
                            </button>
                        </div>

                        <!-- Botões de Incremento Rápido Parcial -->
                        <div class="d-flex gap-2 flex-wrap align-items-center mb-2">
                            <span class="small fw-bold text-muted">Somar Parcial:</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="somarParcial(1)">+1 un</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="somarParcial(6)">+6 (Fardo)</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="somarParcial(12)">+12 (Fardo)</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="somarParcial(24)">+24 (Caixa)</button>
                        </div>

                        <!-- Fita / Histórico da Soma Parcial -->
                        <div id="fitaSomaParcial" class="small text-muted p-2 rounded" style="background: #F1F5F9; border: 1px dashed #CBD5E1; min-height: 32px;">
                            <em>Clique nos botões acima para somar caixas e fardos sucessivos...</em>
                        </div>
                    </div>

                    <!-- Resumo Dinâmico do Impacto -->
                    <div class="p-3 mb-3" style="background: #EFF6FF; border: 2px solid #BFDBFE; border-radius: 12px;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="small text-muted d-block">Resultado Final após Gravação:</span>
                                <strong id="inv_display_novo_saldo" style="font-size: 22px; color: #1D4ED8;">0 un</strong>
                            </div>
                            <div class="text-end">
                                <span class="small text-muted d-block">Ajuste / Diferença:</span>
                                <strong id="inv_display_diferenca" style="font-size: 20px; color: #64748B;">0 un</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Motivo / Justificativa -->
                    <div class="mb-3">
                        <label for="inv_motivo" class="form-label fw-bold small text-uppercase">Motivo do Ajuste *</label>
                        <input type="text" class="form-control" id="inv_motivo" name="motivo" required placeholder="Ex: Inventário de implantação, contagem semanal de bancada, avaria recuperada..." style="height: 44px;">
                    </div>
                </div>

                <div class="modal-footer" style="border-top: 1px solid var(--gestao-border); padding: 16px 24px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-gestao btn-gestao-primary" id="btnSalvarInventario">Confirmar e Gravar Saldo</button>
                </div>
            </form>
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
            <form action="<?= APP_URL ?>/gestao/estoque/reserva" method="POST">
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
            <form action="<?= APP_URL ?>/gestao/estoque/baixa" method="POST">
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
// Mapa de saldos atuais carregados da tabela
const MAPA_SALDOS_ESTOQUE = {
    <?php foreach ($estoqueGeral as $item): ?>
        "<?= (int)$item['produto_id'] ?>": <?= (int)$item['quantidade_total'] ?>,
    <?php endforeach; ?>
};

let historicoParcelas = [];
let saldoAtualProdutoSelecionado = 0;

function abrirModalInventarioGeral() {
    zerarFormularioInventario();
    const modal = new bootstrap.Modal(document.getElementById('modalInventario'));
    modal.show();
}

function abrirModalInventarioItem(produtoId, nome, codigo, saldoAtual) {
    zerarFormularioInventario();
    const select = document.getElementById('inv_produto_id');
    select.value = produtoId;
    aoSelecionarProdutoInventario(produtoId);
    
    const modal = new bootstrap.Modal(document.getElementById('modalInventario'));
    modal.show();
}

function zerarFormularioInventario() {
    document.getElementById('inv_produto_id').value = '';
    document.getElementById('cardInfoSaldoAtual').style.setProperty('display', 'none', 'important');
    saldoAtualProdutoSelecionado = 0;
    zerarContadorInventario();
    document.getElementById('inv_motivo').value = '';
    document.getElementById('tipoSubstituicao').checked = true;
    recalcularResultadoInventario();
}

function aoSelecionarProdutoInventario(produtoId) {
    const cardInfo = document.getElementById('cardInfoSaldoAtual');
    if (!produtoId) {
        cardInfo.style.setProperty('display', 'none', 'important');
        saldoAtualProdutoSelecionado = 0;
        recalcularResultadoInventario();
        return;
    }

    const select = document.getElementById('inv_produto_id');
    const opt = select.options[select.selectedIndex];
    const nome = opt.getAttribute('data-nome') || '';
    const codigo = opt.getAttribute('data-codigo') || '';
    const emb = opt.getAttribute('data-embalagem') || '';

    saldoAtualProdutoSelecionado = MAPA_SALDOS_ESTOQUE[produtoId] || 0;

    document.getElementById('inv_display_produto').innerText = nome;
    document.getElementById('inv_display_codigo').innerText = codigo;
    document.getElementById('inv_display_embalagem').innerText = emb;
    document.getElementById('inv_display_saldo_atual').innerText = saldoAtualProdutoSelecionado + ' un';

    cardInfo.style.setProperty('display', 'flex', 'important');
    recalcularResultadoInventario();
}

function somarParcial(qtd) {
    historicoParcelas.push(qtd);
    const input = document.getElementById('inv_quantidade_contada');
    const valorAtual = parseInt(input.value || '0', 10);
    input.value = valorAtual + qtd;
    atualizarFitaParcelas();
    recalcularResultadoInventario();
}

function zerarContadorInventario() {
    historicoParcelas = [];
    document.getElementById('inv_quantidade_contada').value = '0';
    atualizarFitaParcelas();
    recalcularResultadoInventario();
}

function aoDigitarQuantidadeDireta(val) {
    historicoParcelas = [];
    const num = parseInt(val || '0', 10);
    if (num > 0) {
        historicoParcelas.push(num);
    }
    atualizarFitaParcelas();
    recalcularResultadoInventario();
}

function atualizarFitaParcelas() {
    const fita = document.getElementById('fitaSomaParcial');
    const inputDetalhes = document.getElementById('inv_detalhes_contagem');
    if (historicoParcelas.length === 0) {
        fita.innerHTML = '<em>Nenhuma parcela somada ainda...</em>';
        inputDetalhes.value = '';
    } else {
        const texto = historicoParcelas.join(' + ') + ' = ' + historicoParcelas.reduce((a, b) => a + b, 0) + ' un';
        fita.innerHTML = '<strong>Soma das Parciais:</strong> ' + texto;
        inputDetalhes.value = historicoParcelas.join(' + ');
    }
}

function recalcularResultadoInventario() {
    const qtdContada = parseInt(document.getElementById('inv_quantidade_contada').value || '0', 10);
    const isIncremento = document.getElementById('tipoIncremento').checked;

    let novoSaldo = 0;
    let diferenca = 0;

    if (isIncremento) {
        novoSaldo = saldoAtualProdutoSelecionado + qtdContada;
        diferenca = qtdContada;
    } else {
        novoSaldo = qtdContada;
        diferenca = novoSaldo - saldoAtualProdutoSelecionado;
    }

    document.getElementById('inv_display_novo_saldo').innerText = novoSaldo + ' un';

    const elDif = document.getElementById('inv_display_diferenca');
    const sinal = diferenca > 0 ? '+' : '';
    elDif.innerText = sinal + diferenca + ' un';
    if (diferenca > 0) {
        elDif.style.color = '#059669';
    } else if (diferenca < 0) {
        elDif.style.color = '#DC2626';
    } else {
        elDif.style.color = '#64748B';
    }
}

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
