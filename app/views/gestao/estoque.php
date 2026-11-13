<?php
declare(strict_types=1);

/**
 * Projeto Repack - View Gestao Estoque & Reservas com Inventário por Coleta e Conciliação
 * Baseado estritamente em docs/FSD.md (Seção 6.4, 11.2, 12.3 e 17) e docs/DESIGN.md
 */

// Prepara array JSON seguro dos produtos para busca instantânea no modal de inventário
$produtosCatalogoJson = array_map(function($p) {
    return [
        'id' => (int)$p['id'],
        'codigo' => (string)$p['codigo'],
        'nome' => (string)$p['nome'],
        'pacote_padrao' => (string)($p['pacote_padrao'] ?? 'Unidade')
    ];
}, $todosProdutos);

require __DIR__ . '/../layouts/gestao_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="headline-lg mb-1">Gestão de Estoque da Lojinha</h1>
        <p class="body-md text-muted mb-0">Controle de saldos físicos recebidos do repack, balanços de inventário e reservas ocultas.</p>
    </div>
    <div>
        <button type="button" class="btn btn-gestao btn-gestao-primary" onclick="iniciarSessaoInventario()">
            📋 Iniciar Coleta de Inventário
        </button>
    </div>
</div>

<!-- Tabela Principal de Saldos -->
<div class="card-gestao">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="headline-lg mb-0" style="font-size: 20px;">Visão Geral de Saldos Físicos</h2>
        <span class="badge bg-light text-dark border p-2">
            Fórmula: <strong>Disponível = Total Físico - Reserva Gestão</strong> (Sempre em Unidades)
        </span>
    </div>

    <div class="table-responsive">
        <table class="table-gestao">
            <thead>
                <tr>
                    <th style="width: 85px;">Foto</th>
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
                            Nenhum produto com saldo em estoque. Clique em <strong>"Iniciar Coleta de Inventário"</strong> para cadastrar o estoque inicial.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($estoqueGeral as $item): ?>
                        <tr>
                            <td style="width: 85px;">
                                <?php if (!empty($item['foto_url'])): ?>
                                    <img src="<?= APP_URL . sanitize_html($item['foto_url']) ?>" alt="Foto" style="width: 68px; height: 68px; object-fit: contain; border-radius: 10px; border: 2px solid var(--gestao-border); background: #FFFFFF; padding: 2px;">
                                <?php else: ?>
                                    <div style="width: 68px; height: 68px; background: #E5E7EB; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 26px;">
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
                                        onclick="abrirInventarioDiretoItem(<?= (int)$item['produto_id'] ?>, '<?= sanitize_html(addslashes($item['nome'])) ?>', '<?= sanitize_html($item['codigo']) ?>')" title="Inventariar este produto">
                                    📝 Contar
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

<!-- Seção de Históricos Recentes -->
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
<!-- MODAL DE INVENTÁRIO: COLETA SUCESSIVA & CONCILIAÇÃO FINAL      -->
<!-- ============================================================= -->
<div class="modal fade" id="modalInventarioColeta" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.25);">
            <div class="modal-header" style="border-bottom: 2px solid var(--gestao-border); padding: 18px 24px; background: #F8FAFC;">
                <div>
                    <h5 class="modal-title headline-lg m-0" style="font-size: 20px;">
                        📋 Sessão de Inventário & Contagem Física
                    </h5>
                    <span class="text-muted small">Todos os lançamentos são expressos e contabilizados estritamente em <strong>unidades</strong>.</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar" onclick="confirmarFechamentoInventario(event)"></button>
            </div>

            <!-- FASE 1: COLETA CONTÍNUA DE ITENS -->
            <div id="faseColetaInventario" style="display: block;">
                <div class="modal-body" style="padding: 24px;">
                    <!-- Bloco de Busca Inteligente do Produto -->
                    <div class="p-3 mb-3" style="background: #F1F5F9; border-radius: 14px; border: 2px solid #E2E8F0;">
                        <label for="inputBuscaProdutoInv" class="form-label fw-bold small text-uppercase mb-1" style="color: #334155;">
                            🔍 Buscar Produto (Digite Código SKU ou Nome da Bebida)
                        </label>
                        <div class="position-relative">
                            <input type="text" 
                                   id="inputBuscaProdutoInv" 
                                   class="form-control" 
                                   placeholder="Ex: 9068, Brahma, Skol 350ml, Guaraná..." 
                                   style="height: 50px; font-size: 17px; font-weight: 600; border-radius: 10px;"
                                   autocomplete="off"
                                   oninput="buscarSugestoesInventario(this.value)">
                            <button type="button" 
                                    id="btnLimparBuscaInv" 
                                    class="btn position-absolute top-50 end-0 translate-middle-y me-2 text-muted" 
                                    style="display: none; background: transparent; border: none; font-size: 20px;" 
                                    onclick="limparBuscaInventario()">✕</button>
                        </div>

                        <!-- Menu de Sugestões / Resultados -->
                        <div id="sugestoesInventarioWrapper" style="display: none; max-height: 240px; overflow-y: auto; background: #FFFFFF; border: 2px solid #CBD5E1; border-radius: 10px; margin-top: 6px; box-shadow: 0 4px 14px rgba(0,0,0,0.1);">
                            <div id="listaSugestoesInv"></div>
                        </div>
                    </div>

                    <!-- Bloco do Produto Selecionado para Entrada de Unidades -->
                    <div id="blocoEntradaUnidades" style="display: none; background: #FFFFFF; border: 2px solid #10B981; border-radius: 14px; padding: 18px; margin-bottom: 20px;">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <div>
                                <span class="badge bg-success" style="font-size: 11px;">PRODUTO SELECIONADO</span>
                                <h4 id="displayInvNome" class="m-0 mt-1" style="font-size: 20px; font-weight: 800; color: #0F172A;"></h4>
                                <span id="displayInvCodigo" class="text-muted small fw-bold"></span>
                            </div>
                            <div class="text-end">
                                <span class="text-muted small d-block">Esperado no Sistema:</span>
                                <strong id="displayInvSaldoSistema" style="font-size: 20px; color: #1E293B;">0 un</strong>
                            </div>
                        </div>

                        <div class="row g-2 align-items-end mt-2">
                            <div class="col-12 col-md-5">
                                <label for="inputQtdColetada" class="form-label fw-bold small text-uppercase mb-1">
                                    Unidades Contadas Nesta Parcela *
                                </label>
                                <input type="number" 
                                       id="inputQtdColetada" 
                                       class="form-control" 
                                       min="1" 
                                       value="1" 
                                       inputmode="numeric" 
                                       pattern="[0-9]*"
                                       style="height: 52px; font-size: 24px; font-weight: 800; text-align: center;">
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="d-flex gap-1 flex-wrap">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="somarUnidadesInput(1)">+1</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="somarUnidadesInput(6)">+6</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="somarUnidadesInput(12)">+12</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="somarUnidadesInput(24)">+24</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="somarUnidadesInput(48)">+48</button>
                                </div>
                            </div>
                            <div class="col-12 col-md-3">
                                <button type="button" class="btn btn-gestao btn-gestao-primary w-100" style="height: 52px; font-size: 16px; font-weight: 800;" onclick="adicionarItemColetado()">
                                    ➕ Adicionar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tabela de Itens Coletados na Sessão -->
                    <div class="card p-3 border" style="border-radius: 12px; background: #F8FAFC;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold m-0 text-uppercase" style="font-size: 14px; color: #475569;">
                                📦 Itens Coletados nesta Sessão (<span id="contagemItensSessao">0</span>)
                            </h6>
                            <button type="button" class="btn btn-link btn-sm text-danger text-decoration-none p-0" onclick="limparTodaSessao()" id="btnLimparTudo" style="display: none;">
                                Limpar Sessão
                            </button>
                        </div>

                        <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                            <table class="table table-sm table-hover align-middle m-0" style="background: #FFFFFF; border-radius: 8px; overflow: hidden;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produto</th>
                                        <th>Código</th>
                                        <th style="text-align: center;">Total Contado</th>
                                        <th style="text-align: center;">Esperado Sistema</th>
                                        <th style="width: 140px; text-align: right;">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="tabelaItensColetadosBody">
                                    <tr id="linhaVaziaSessao">
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            Nenhum produto coletado ainda. Utilize o campo de busca acima para encontrar os itens contados.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style="border-top: 1px solid var(--gestao-border); padding: 16px 24px; justify-content: space-between;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar / Pausar</button>
                    <button type="button" class="btn btn-gestao btn-gestao-primary" id="btnIrParaConfronto" onclick="avancarParaConfronto()" disabled style="padding: 10px 24px; font-size: 16px;">
                        Finalizar Coleta e Confrontar Estoque ➔
                    </button>
                </div>
            </div>

            <!-- FASE 2: CONFRONTO, AVERIGUAÇÃO E CONCILIAÇÃO FINAL -->
            <div id="faseConfrontoInventario" style="display: none;">
                <form action="<?= APP_URL ?>/gestao/estoque/inventario" method="POST" id="formSalvarInventarioLote">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="payload_inventario_json" id="inputPayloadInventarioJson" value="">

                    <div class="modal-body" style="padding: 24px;">
                        <div class="alert alert-info py-2 px-3 small mb-3">
                            <strong>📊 Matriz de Confronto de Estoque:</strong> Revise as quantidades apuradas. Você pode ajustar individualmente o novo saldo de cada produto antes de confirmar.
                        </div>

                        <div class="table-responsive mb-3" style="max-height: 360px; overflow-y: auto;">
                            <table class="table table-bordered align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Produto</th>
                                        <th style="width: 130px; text-align: center;">Esperado Sistema</th>
                                        <th style="width: 160px; text-align: center;">Total Coletado</th>
                                        <th style="width: 130px; text-align: center;">Diferença</th>
                                        <th style="width: 180px; text-align: center;">Saldo Final Apurado *</th>
                                    </tr>
                                </thead>
                                <tbody id="tabelaConfrontoBody"></tbody>
                            </table>
                        </div>

                        <div class="mb-2">
                            <label for="inputMotivoConfronto" class="form-label fw-bold small text-uppercase text-muted">
                                Observações do Balanço (Opcional)
                            </label>
                            <input type="text" 
                                   id="inputMotivoConfronto" 
                                   name="motivo_global" 
                                   class="form-control" 
                                   placeholder="Inventário periódico de rotina" 
                                   value="Inventário periódico de rotina" 
                                   style="height: 44px;">
                        </div>
                    </div>

                    <div class="modal-footer" style="border-top: 1px solid var(--gestao-border); padding: 16px 24px; justify-content: space-between;">
                        <button type="button" class="btn btn-outline-secondary" onclick="voltarParaColeta()">
                            ← Voltar e Coletar Mais Itens
                        </button>
                        <button type="submit" class="btn btn-gestao btn-gestao-primary" id="btnConfirmarConciliacao" style="padding: 12px 28px; font-size: 16px;">
                            ✅ Gravar e Conciliar Estoque Definitivo
                        </button>
                    </div>
                </form>
            </div>
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
                        <label for="reserva_quantidade" class="form-label fw-bold small text-uppercase">Quantidade Reservada * (Unidades)</label>
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
                        <em>Atenção: A baixa abate o saldo total físico de forma permanente. Lançamento sempre em unidades.</em>
                    </div>

                    <div class="mb-3">
                        <label for="baixa_quantidade" class="form-label fw-bold small text-uppercase">Quantidade Perdida / Quebrada * (Unidades)</label>
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
// Catálogo completo para busca instantânea e mapa de saldos atuais
const PRODUTOS_CATALOGO = <?= json_encode($produtosCatalogoJson, JSON_UNESCAPED_UNICODE) ?>;
const MAPA_SALDOS_ESTOQUE = {
    <?php foreach ($estoqueGeral as $item): ?>
        "<?= (int)$item['produto_id'] ?>": <?= (int)$item['quantidade_total'] ?>,
    <?php endforeach; ?>
};

// Sessão de Coleta em Memória: [ produto_id => { id, nome, codigo, saldo_sistema, total_coletado, parcelas: [] } ]
let sessaoColeta = {};
let produtoAtivoColeta = null;

function iniciarSessaoInventario() {
    document.getElementById('faseColetaInventario').style.display = 'block';
    document.getElementById('faseConfrontoInventario').style.display = 'none';
    limparBuscaInventario();
    renderizarTabelaColeta();
    const modal = new bootstrap.Modal(document.getElementById('modalInventarioColeta'));
    modal.show();
    setTimeout(() => {
        document.getElementById('inputBuscaProdutoInv').focus();
    }, 400);
}

function abrirInventarioDiretoItem(produtoId, nome, codigo) {
    iniciarSessaoInventario();
    const prod = PRODUTOS_CATALOGO.find(p => p.id === produtoId) || { id: produtoId, nome, codigo };
    selecionarProdutoParaColeta(prod);
}

function buscarSugestoesInventario(termo) {
    termo = (termo || '').trim().toLowerCase();
    const btnLimpar = document.getElementById('btnLimparBuscaInv');
    const wrapper = document.getElementById('sugestoesInventarioWrapper');
    const lista = document.getElementById('listaSugestoesInv');

    btnLimpar.style.display = termo !== '' ? 'block' : 'none';

    if (termo === '') {
        wrapper.style.display = 'none';
        lista.innerHTML = '';
        return;
    }

    const filtrados = PRODUTOS_CATALOGO.filter(p => 
        p.codigo.toLowerCase().includes(termo) || p.nome.toLowerCase().includes(termo)
    );

    if (filtrados.length === 0) {
        lista.innerHTML = `<div class="p-3 text-muted text-center small">Nenhum produto cadastrado com "${termo}".</div>`;
        wrapper.style.display = 'block';
        return;
    }

    lista.innerHTML = '';
    filtrados.slice(0, 10).forEach(p => {
        const saldo = MAPA_SALDOS_ESTOQUE[p.id] || 0;
        const item = document.createElement('div');
        item.className = 'p-2 border-bottom d-flex justify-content-between align-items-center';
        item.style.cursor = 'pointer';
        item.innerHTML = `
            <div>
                <strong style="color: #0F172A; font-size: 15px;">${p.nome}</strong>
                <div class="text-muted small">Código: <strong>${p.codigo}</strong> &bull; Saldo Atual: <span class="badge bg-light text-dark border">${saldo} un</span></div>
            </div>
            <span class="btn btn-sm btn-outline-success">Selecionar ➔</span>
        `;
        item.onclick = () => {
            selecionarProdutoParaColeta(p);
            wrapper.style.display = 'none';
        };
        lista.appendChild(item);
    });
    wrapper.style.display = 'block';
}

function limparBuscaInventario() {
    const input = document.getElementById('inputBuscaProdutoInv');
    input.value = '';
    document.getElementById('btnLimparBuscaInv').style.display = 'none';
    document.getElementById('sugestoesInventarioWrapper').style.display = 'none';
    input.focus();
}

function selecionarProdutoParaColeta(prod) {
    produtoAtivoColeta = prod;
    const saldo = MAPA_SALDOS_ESTOQUE[prod.id] || 0;

    document.getElementById('displayInvNome').innerText = prod.nome;
    document.getElementById('displayInvCodigo').innerText = 'SKU ' + prod.codigo;
    document.getElementById('displayInvSaldoSistema').innerText = saldo + ' un';

    const inputQtd = document.getElementById('inputQtdColetada');
    inputQtd.value = '1';

    document.getElementById('blocoEntradaUnidades').style.display = 'block';
    inputQtd.focus();
    inputQtd.select();
}

function somarUnidadesInput(qtd) {
    const input = document.getElementById('inputQtdColetada');
    let val = parseInt(input.value || '0', 10);
    input.value = Math.max(1, val + qtd);
}

function adicionarItemColetado() {
    if (!produtoAtivoColeta) return;

    const inputQtd = document.getElementById('inputQtdColetada');
    const qtd = parseInt(inputQtd.value || '1', 10);
    if (qtd <= 0) {
        alert('A quantidade unitária deve ser maior que zero.');
        return;
    }

    const id = produtoAtivoColeta.id;
    const saldoSistema = MAPA_SALDOS_ESTOQUE[id] || 0;

    if (!sessaoColeta[id]) {
        sessaoColeta[id] = {
            produto_id: id,
            nome: produtoAtivoColeta.nome,
            codigo: produtoAtivoColeta.codigo,
            saldo_sistema: saldoSistema,
            total_coletado: 0,
            parcelas: []
        };
    }

    sessaoColeta[id].total_coletado += qtd;
    sessaoColeta[id].parcelas.push(qtd);

    // Feedback rápido e limpeza para a próxima contagem
    document.getElementById('blocoEntradaUnidades').style.display = 'none';
    limparBuscaInventario();
    produtoAtivoColeta = null;

    renderizarTabelaColeta();
}

function somarDiretoNaLinha(produtoId, qtd) {
    if (sessaoColeta[produtoId]) {
        sessaoColeta[produtoId].total_coletado += qtd;
        sessaoColeta[produtoId].parcelas.push(qtd);
        renderizarTabelaColeta();
    }
}

function removerItemSessao(produtoId) {
    if (sessaoColeta[produtoId]) {
        delete sessaoColeta[produtoId];
        renderizarTabelaColeta();
    }
}

function limparTodaSessao() {
    if (confirm('Deseja limpar todos os itens contados nesta sessão?')) {
        sessaoColeta = {};
        renderizarTabelaColeta();
    }
}

function renderizarTabelaColeta() {
    const tbody = document.getElementById('tabelaItensColetadosBody');
    const chaves = Object.keys(sessaoColeta);
    const contagem = chaves.length;

    document.getElementById('contagemItensSessao').innerText = contagem;
    document.getElementById('btnLimparTudo').style.display = contagem > 0 ? 'inline-block' : 'none';
    document.getElementById('btnIrParaConfronto').disabled = contagem === 0;

    if (contagem === 0) {
        tbody.innerHTML = `
            <tr id="linhaVaziaSessao">
                <td colspan="5" class="text-center py-4 text-muted">
                    Nenhum produto coletado ainda. Utilize o campo de busca acima para encontrar os itens contados.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = '';
    chaves.forEach(id => {
        const item = sessaoColeta[id];
        const historicoTexto = item.parcelas.length > 1 ? `<div class="text-muted" style="font-size: 11px;">(${item.parcelas.join(' + ')})</div>` : '';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <strong>${item.nome}</strong>
                ${historicoTexto}
            </td>
            <td><code>${item.codigo}</code></td>
            <td style="text-align: center;">
                <span class="badge bg-primary" style="font-size: 16px; padding: 6px 12px;">
                    ${item.total_coletado} un
                </span>
            </td>
            <td style="text-align: center;">
                <span class="text-muted">${item.saldo_sistema} un</span>
            </td>
            <td style="text-align: right;">
                <button type="button" class="btn btn-outline-success btn-sm me-1" onclick="somarDiretoNaLinha(${item.produto_id}, 1)" title="Somar +1 unidade">+1</button>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removerItemSessao(${item.produto_id})" title="Remover da contagem">✕</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function avancarParaConfronto() {
    const chaves = Object.keys(sessaoColeta);
    if (chaves.length === 0) return;

    const tbody = document.getElementById('tabelaConfrontoBody');
    tbody.innerHTML = '';

    chaves.forEach(id => {
        const item = sessaoColeta[id];
        const dif = item.total_coletado - item.saldo_sistema;
        const difClass = dif > 0 ? 'text-success' : (dif < 0 ? 'text-danger' : 'text-muted');
        const sinal = dif > 0 ? '+' : '';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <strong>${item.nome}</strong><br>
                <code class="small">${item.codigo}</code>
            </td>
            <td style="text-align: center; font-size: 16px;">
                ${item.saldo_sistema} un
            </td>
            <td style="text-align: center; font-size: 18px; font-weight: 800; color: #1D4ED8;">
                ${item.total_coletado} un
                ${item.parcelas.length > 1 ? `<div style="font-size: 10px; font-weight: normal; color: #64748B;">(${item.parcelas.join(' + ')})</div>` : ''}
            </td>
            <td style="text-align: center; font-weight: 800; font-size: 17px;" class="${difClass}" id="confronto_dif_${item.produto_id}">
                ${sinal}${dif} un
            </td>
            <td style="text-align: center;">
                <input type="number" 
                       class="form-control form-control-sm text-center fw-bold input-apurado-confronto" 
                       id="confronto_apurado_${item.produto_id}" 
                       data-id="${item.produto_id}" 
                       data-esperado="${item.saldo_sistema}" 
                       value="${item.total_coletado}" 
                       min="0" 
                       style="height: 42px; font-size: 18px; color: #0F172A;"
                       oninput="recalcularDiferencaConfronto(${item.produto_id}, this.value, ${item.saldo_sistema})">
            </td>
        `;
        tbody.appendChild(tr);
    });

    document.getElementById('faseColetaInventario').style.display = 'none';
    document.getElementById('faseConfrontoInventario').style.display = 'block';
}

function recalcularDiferencaConfronto(produtoId, valApurado, saldoSistema) {
    const apurado = parseInt(valApurado || '0', 10);
    const dif = apurado - saldoSistema;
    const el = document.getElementById('confronto_dif_' + produtoId);
    if (el) {
        const sinal = dif > 0 ? '+' : '';
        el.innerText = `${sinal}${dif} un`;
        el.className = 'text-center fw-bold ' + (dif > 0 ? 'text-success' : (dif < 0 ? 'text-danger' : 'text-muted'));
    }
}

function voltarParaColeta() {
    document.getElementById('faseConfrontoInventario').style.display = 'none';
    document.getElementById('faseColetaInventario').style.display = 'block';
}

function confirmarFechamentoInventario(e) {
    if (Object.keys(sessaoColeta).length > 0) {
        if (!confirm('Você possui itens coletados nesta sessão. Deseja realmente fechar?')) {
            e.preventDefault();
        }
    }
}

// Submissão da Conciliação de Todos os Itens
document.getElementById('formSalvarInventarioLote')?.addEventListener('submit', function(e) {
    const inputs = document.querySelectorAll('.input-apurado-confronto');
    const loteApurado = [];

    inputs.forEach(inp => {
        const pid = parseInt(inp.getAttribute('data-id'), 10);
        const novoSaldo = parseInt(inp.value || '0', 10);
        const detalhes = sessaoColeta[pid] && sessaoColeta[pid].parcelas ? sessaoColeta[pid].parcelas.join(' + ') : null;

        loteApurado.push({
            produto_id: pid,
            novo_saldo: novoSaldo,
            detalhes_contagem: detalhes
        });
    });

    if (loteApurado.length === 0) {
        e.preventDefault();
        alert('Nenhum item válido para conciliação.');
        return;
    }

    document.getElementById('inputPayloadInventarioJson').value = JSON.stringify(loteApurado);
    const btn = document.getElementById('btnConfirmarConciliacao');
    btn.disabled = true;
    btn.innerText = 'Gravando Estoque Conciliado...';
});

// Funções dos modais de Reserva e Baixa
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
