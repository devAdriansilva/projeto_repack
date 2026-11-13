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
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <button type="button" class="btn btn-outline-success" style="font-weight: 700; border-radius: 12px; padding: 10px 18px;" onclick="abrirModalImportacaoCsv()">
            📥 Importar B.O. (Excel / CSV)
        </button>
        <button type="button" class="btn btn-gestao btn-gestao-primary" onclick="iniciarSessaoInventario()">
            📋 Iniciar Coleta de Inventário
        </button>
    </div>
</div>

<!-- Card Informativo de Carimbo da Última Entrada no Estoque -->
<div class="card mb-4" style="background: #FFFFFF; border: 1.5px solid #E5E7EB; border-left: 5px solid #10B981; border-radius: 14px; padding: 16px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div style="width: 44px; height: 44px; border-radius: 10px; background: #ECFDF5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; border: 1px solid #A7F3D0;">
                🕒
            </div>
            <div>
                <div style="font-size: 12px; text-transform: uppercase; font-weight: 700; color: #6B7280; letter-spacing: 0.5px;">
                    Carimbo de Última Entrada de Itens no Estoque
                </div>
                <div style="font-size: 15px; font-weight: 800; color: #111827; margin-top: 2px;">
                    <?php if (!empty($ultimaEntrada)): ?>
                        <?= sanitize_html($ultimaEntrada['data_formatada']) ?>
                        <span class="badge bg-light text-secondary border ms-1" style="font-weight: 600; font-size: 12px;"><?= sanitize_html($ultimaEntrada['tempo_relativo']) ?></span>
                        &bull; <span class="text-success"><?= sanitize_html($ultimaEntrada['descricao']) ?></span>
                        <span class="badge bg-success ms-1">+<?= (int)$ultimaEntrada['quantidade'] ?> un</span>
                        <span class="text-muted small ms-1">(<?= sanitize_html($ultimaEntrada['canal']) ?>)</span>
                    <?php else: ?>
                        <span class="text-muted">Nenhuma movimentação de entrada registrada no sistema.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if (!empty($ultimaImportacaoCsv)): ?>
            <div class="d-flex align-items-center gap-2 px-3 py-2 rounded" style="background: #F8FAFC; border: 1px solid #E2E8F0; font-size: 13px;">
                <span>📊 Último CSV: <strong><?= sanitize_html($ultimaImportacaoCsv['nome_arquivo']) ?></strong> (<?= sanitize_html($ultimaImportacaoCsv['data_formatada']) ?> &bull; <strong>+<?= (int)$ultimaImportacaoCsv['unidades_lojinha'] ?> un</strong>)</span>
            </div>
        <?php endif; ?>
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

<script src="<?= APP_URL ?>/assets/js/xlsx.mini.min.js?v=<?= APP_VERSION ?>"></script>

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

// =========================================================================
// GESTÃO DE IMPORTAÇÃO DE B.O. VIA CSV CORPORATIVO
// =========================================================================
let dadosPreviaCsv = null;
let conteudoCsvAtual = '';
let nomeArquivoCsvAtual = '';

function abrirModalImportacaoCsv() {
    document.getElementById('formImportarCsvBO').reset();
    document.getElementById('stepPreviaCsv').style.display = 'none';
    document.getElementById('stepUploadCsv').style.display = 'block';
    document.getElementById('btnAnalisarCsv').disabled = false;
    document.getElementById('btnAnalisarCsv').innerHTML = 'Analisar Relatório e Gerar Prévia ➔';
    dadosPreviaCsv = null;
    conteudoCsvAtual = '';
    nomeArquivoCsvAtual = '';

    const modal = new bootstrap.Modal(document.getElementById('modalImportarCsvBO'));
    modal.show();
}

function trocarAbaEntradaCsv(aba) {
    if (aba === 'arquivo') {
        document.getElementById('abaArquivoContainer').style.display = 'block';
        document.getElementById('abaTextoContainer').style.display = 'none';
        document.getElementById('btnAbaArquivo').classList.add('active');
        document.getElementById('btnAbaTexto').classList.remove('active');
    } else {
        document.getElementById('abaArquivoContainer').style.display = 'none';
        document.getElementById('abaTextoContainer').style.display = 'block';
        document.getElementById('btnAbaArquivo').classList.remove('active');
        document.getElementById('btnAbaTexto').classList.add('active');
    }
}

async function analisarArquivoCsv() {
    const inputArquivo = document.getElementById('inputCsvFile');
    const inputTexto = document.getElementById('inputCsvTexto');
    const btn = document.getElementById('btnAnalisarCsv');

    let conteudo = '';
    let nomeArquivo = 'relatorio_bo.csv';

    if (inputArquivo.files && inputArquivo.files.length > 0) {
        const file = inputArquivo.files[0];
        nomeArquivo = file.name;
        const ext = nomeArquivo.split('.').pop().toLowerCase();

        if (ext === 'xlsx' || ext === 'xls') {
            btn.disabled = true;
            btn.innerHTML = '⏳ Lendo planilha Excel...';

            if (typeof XLSX === 'undefined') {
                alert('Erro: Biblioteca de leitura do Excel não carregada. Por favor, recarregue a página.');
                btn.disabled = false;
                btn.innerHTML = 'Analisar Relatório e Gerar Prévia ➔';
                return;
            }

            try {
                const arrayBuffer = await file.arrayBuffer();
                const workbook = XLSX.read(arrayBuffer, { type: 'array' });
                if (!workbook.SheetNames || workbook.SheetNames.length === 0) {
                    throw new Error('Nenhuma aba encontrada no arquivo Excel.');
                }
                const firstSheetName = workbook.SheetNames[0];
                const worksheet = workbook.Sheets[firstSheetName];
                conteudo = XLSX.utils.sheet_to_csv(worksheet);
            } catch (errXlsx) {
                alert('Falha ao processar arquivo Excel: ' + errXlsx.message);
                btn.disabled = false;
                btn.innerHTML = 'Analisar Relatório e Gerar Prévia ➔';
                return;
            }
        } else {
            conteudo = await file.text();
        }
    } else if (inputTexto.value.trim() !== '') {
        conteudo = inputTexto.value.trim();
        nomeArquivo = 'relatorio_colado_' + (new Date().toISOString().slice(0, 10)) + '.csv';
    }

    if (!conteudo.trim()) {
        alert('Por favor, selecione um arquivo Excel (.xlsx, .xls), CSV ou cole as linhas do relatório corporativo.');
        btn.disabled = false;
        btn.innerHTML = 'Analisar Relatório e Gerar Prévia ➔';
        return;
    }

    conteudoCsvAtual = conteudo;
    nomeArquivoCsvAtual = nomeArquivo;

    btn.disabled = true;
    btn.innerHTML = '⏳ Processando linhas e cruzando SKUs...';

    try {
        const formData = new FormData();
        formData.append('conteudo_csv', conteudo);

        const resp = await fetch('<?= APP_URL ?>/api/gestao/bo/previa-csv', {
            method: 'POST',
            body: formData
        });

        const json = await resp.json();

        if (!resp.ok || json.status !== 'success') {
            throw new Error(json.message || 'Falha ao analisar o arquivo.');
        }

        dadosPreviaCsv = json;
        renderizarPreviaCsv(json);

        document.getElementById('stepUploadCsv').style.display = 'none';
        document.getElementById('stepPreviaCsv').style.display = 'block';
    } catch (err) {
        alert('Erro na análise da planilha: ' + err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Analisar Relatório e Gerar Prévia ➔';
    }
}

function renderizarPreviaCsv(dados) {
    document.getElementById('previaNomeArquivo').innerText = nomeArquivoCsvAtual;
    document.getElementById('previaTotalLinhas').innerText = dados.total_linhas;
    document.getElementById('previaTotalUnidades').innerText = dados.total_unidades;
    document.getElementById('previaUnidadesLojinha').innerText = dados.unidades_lojinha;
    document.getElementById('previaProdsLojinha').innerText = dados.produtos_lojinha_distintos;
    document.getElementById('previaUnidadesDescarte').innerText = dados.unidades_descarte;
    document.getElementById('previaProdsDescarte').innerText = dados.produtos_descarte_distintos;

    // Renderiza tabela de motivos com selects para flexibilidade
    const tbodyMotivos = document.getElementById('tbodyPreviaMotivos');
    tbodyMotivos.innerHTML = '';

    dados.motivos.forEach(m => {
        const tr = document.createElement('tr');
        const badgeCor = m.destino === 'lojinha' ? 'success' : 'danger';
        const labelDestino = m.destino === 'lojinha' ? 'Lojinha (Venda)' : 'Descarte (Perda)';

        tr.innerHTML = `
            <td><strong style="font-size: 15px;">${escapeHtml(m.motivo)}</strong></td>
            <td class="text-center"><span class="badge bg-secondary">${m.total_ocorrencias} reg</span></td>
            <td class="text-center"><strong style="font-size: 16px;">${m.total_unidades}</strong> un</td>
            <td>
                <select class="form-select select-destino-motivo" data-motivo="${escapeHtml(m.motivo)}" onchange="recalcularDestinosPrevia()" style="font-weight: 700; border-radius: 8px;">
                    <option value="lojinha" ${m.destino === 'lojinha' ? 'selected' : ''} style="color: #059669; font-weight: 700;">🟢 Direcionar para Lojinha (Venda)</option>
                    <option value="descarte" ${m.destino === 'descarte' ? 'selected' : ''} style="color: #DC2626; font-weight: 700;">🔴 Registrar como Descarte (Perda)</option>
                </select>
            </td>
        `;
        tbodyMotivos.appendChild(tr);
    });

    // Renderiza produtos da lojinha
    const tbodyProds = document.getElementById('tbodyPreviaProdutosLojinha');
    tbodyProds.innerHTML = '';

    if (dados.produtos_lojinha.length === 0) {
        tbodyProds.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Nenhum item direcionado para a lojinha com a configuração atual.</td></tr>';
    } else {
        dados.produtos_lojinha.forEach(p => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><code>${escapeHtml(p.codigo)}</code></td>
                <td><strong>${escapeHtml(p.nome)}</strong></td>
                <td class="text-center">${p.estoque_atual} un</td>
                <td class="text-center text-success"><strong style="font-size: 16px;">+${p.quantidade}</strong></td>
                <td class="text-center"><span class="badge bg-success" style="font-size: 14px;">${p.novo_estoque} un</span></td>
            `;
            tbodyProds.appendChild(tr);
        });
    }

    // Alerta de novos produtos cadastrados sob demanda
    const boxNovos = document.getElementById('boxProdutosNovos');
    if (dados.produtos_novos && dados.produtos_novos.length > 0) {
        boxNovos.style.display = 'block';
        boxNovos.innerHTML = `ℹ️ <strong>${dados.produtos_novos.length} SKU(s) novo(s)</strong> serão cadastrados automaticamente no catálogo de produtos do Repack.`;
    } else {
        boxNovos.style.display = 'none';
    }
}

async function recalcularDestinosPrevia() {
    const selects = document.querySelectorAll('.select-destino-motivo');
    const destinos = {};
    selects.forEach(s => {
        destinos[s.getAttribute('data-motivo')] = s.value;
    });

    try {
        const formData = new FormData();
        formData.append('conteudo_csv', conteudoCsvAtual);
        formData.append('destinos_customizados', JSON.stringify(destinos));

        const resp = await fetch('<?= APP_URL ?>/api/gestao/bo/previa-csv', {
            method: 'POST',
            body: formData
        });

        const json = await resp.json();
        if (resp.ok && json.status === 'success') {
            dadosPreviaCsv = json;
            renderizarPreviaCsv(json);
        }
    } catch (e) {
        console.error('Falha ao recalcular prévia:', e);
    }
}

function submeterImportacaoCsvFinal() {
    const selects = document.querySelectorAll('.select-destino-motivo');
    const destinos = {};
    selects.forEach(s => {
        destinos[s.getAttribute('data-motivo')] = s.value;
    });

    document.getElementById('hiddenConteudoCsv').value = conteudoCsvAtual;
    document.getElementById('hiddenNomeArquivoCsv').value = nomeArquivoCsvAtual;
    document.getElementById('hiddenDestinosCustomizados').value = JSON.stringify(destinos);

    const btn = document.getElementById('btnConfirmarImportacaoFinal');
    btn.disabled = true;
    btn.innerHTML = '⏳ Abastecendo Estoque da Lojinha...';

    document.getElementById('formImportarCsvFinal').submit();
}

function voltarUploadCsv() {
    document.getElementById('stepPreviaCsv').style.display = 'none';
    document.getElementById('stepUploadCsv').style.display = 'block';
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>

<!-- ========================================================================= -->
<!-- MODAL DE IMPORTAÇÃO DE B.O. VIA CSV CORPORATIVO                           -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalImportarCsvBO" tabindex="-1" aria-labelledby="modalImportarCsvBOLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);">
            
            <div class="modal-header" style="background: linear-gradient(135deg, #0F172A, #1E293B); color: #FFFFFF; padding: 20px 24px; border-top-left-radius: 20px; border-top-right-radius: 20px;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; border: 1px solid #10B981;">
                        📥
                    </div>
                    <div>
                        <h5 class="modal-title" id="modalImportarCsvBOLabel" style="font-weight: 800; font-size: 20px;">Importar Relatório de B.O. Corporativo (Excel / CSV)</h5>
                        <p class="mb-0 text-muted" style="font-size: 13px; color: #94A3B8 !important;">Alimente o estoque da Lojinha diretamente a partir da planilha de quebras (Excel .xlsx / .xls ou .csv)</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4" style="background: #F8FAFC;">
                
                <!-- ETAPA 1: SELEÇÃO DO ARQUIVO OU TEXTO -->
                <div id="stepUploadCsv">
                    <form id="formImportarCsvBO" onsubmit="event.preventDefault(); analisarArquivoCsv();">
                        
                        <?php if (!empty($ultimaImportacaoCsv)): ?>
                            <div class="alert alert-info d-flex align-items-center gap-3 p-3 mb-3" style="border-radius: 12px; border-left: 5px solid #2563EB; background: #EFF6FF;">
                                <div style="font-size: 26px;">ℹ️</div>
                                <div>
                                    <div style="font-weight: 800; font-size: 14px; color: #1E40AF;">
                                        Última importação registrada: <?= sanitize_html($ultimaImportacaoCsv['data_formatada']) ?> (<?= sanitize_html($ultimaImportacaoCsv['tempo_relativo']) ?>)
                                    </div>
                                    <div style="font-size: 13px; color: #1E3A8A; margin-top: 2px;">
                                        Arquivo: <strong><?= sanitize_html($ultimaImportacaoCsv['nome_arquivo']) ?></strong> &bull; Abasteceu <strong>+<?= (int)$ultimaImportacaoCsv['unidades_lojinha'] ?> unidades</strong> na Lojinha (por <?= sanitize_html($ultimaImportacaoCsv['usuario_nome'] ?? 'Gestão') ?>).
                                        <div class="text-danger fw-bold mt-1" style="font-size: 12px;">
                                            ⚠️ Verifique o período do relatório antes de enviar para não duplicar entradas no estoque!
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="card p-3 mb-4 shadow-sm border-0" style="border-radius: 16px; background: #FFFFFF;">
                            
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <button type="button" id="btnAbaArquivo" class="btn btn-sm btn-outline-primary active" style="font-weight: 700; border-radius: 8px;" onclick="trocarAbaEntradaCsv('arquivo')">
                                    📁 Selecionar Arquivo Excel / CSV
                                </button>
                                <button type="button" id="btnAbaTexto" class="btn btn-sm btn-outline-secondary" style="font-weight: 700; border-radius: 8px;" onclick="trocarAbaEntradaCsv('texto')">
                                    📋 Colar Linhas da Planilha
                                </button>
                            </div>

                            <!-- Aba Arquivo -->
                            <div id="abaArquivoContainer">
                                <label class="form-label" style="font-weight: 700; color: #334155;">Escolha o arquivo Excel (.xlsx, .xls) ou CSV exportado do sistema corporativo:</label>
                                <input type="file" id="inputCsvFile" class="form-control" accept=".xlsx,.xls,.csv,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" style="padding: 12px; border-radius: 12px; border: 2px dashed #CBD5E1; font-weight: 600;">
                                <div class="form-text mt-2 text-muted">
                                    Dica: Aceita arquivos do <strong>Excel (.xlsx, .xls)</strong> ou planilhas <strong>.CSV</strong>. As colunas essenciais são <strong>Código</strong>, <strong>Quantidade</strong> e <strong>Motivo</strong>.
                                </div>
                            </div>

                            <!-- Aba Texto Colado -->
                            <div id="abaTextoContainer" style="display: none;">
                                <label class="form-label" style="font-weight: 700; color: #334155;">Cole as linhas do relatório com cabeçalho:</label>
                                <textarea id="inputCsvTexto" class="form-control font-monospace" rows="8" placeholder="Código,Quantidade,Descrição,Motivo...&#10;9084,1,GUARANA CHP ANTARCTICA,Amassada&#10;9069,2,BRAHMA CHOPP,Amassada" style="border-radius: 12px; font-size: 13px;"></textarea>
                            </div>

                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" style="border-radius: 10px; font-weight: 600;">Cancelar</button>
                            <button type="submit" id="btnAnalisarCsv" class="btn btn-success px-4" style="border-radius: 10px; font-weight: 700; height: 50px; font-size: 16px;">
                                Analisar Relatório e Gerar Prévia ➔
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ETAPA 2: PRÉVIA ANALÍTICA E CLASSIFICAÇÃO DE MOTIVOS -->
                <div id="stepPreviaCsv" style="display: none;">
                    
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <span class="badge bg-primary" id="previaNomeArquivo" style="font-size: 13px;">relatorio.csv</span>
                            <span class="text-muted ms-2" style="font-size: 14px;"><strong id="previaTotalLinhas">0</strong> registros / <strong id="previaTotalUnidades">0</strong> unidades computadas</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="voltarUploadCsv()" style="font-weight: 600;">
                            ← Trocar Arquivo
                        </button>
                    </div>

                    <!-- Cards de Destinação Consolidada -->
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <div class="p-3" style="background: #ECFDF5; border-radius: 14px; border: 2px solid #10B981;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div style="font-size: 13px; font-weight: 800; color: #047857; text-transform: uppercase;">● Abastecerá a Lojinha</div>
                                        <div style="font-size: 32px; font-weight: 900; color: #065F46;" id="previaUnidadesLojinha">0</div>
                                        <div style="font-size: 13px; color: #047857;">unidades recuperáveis (<span id="previaProdsLojinha">0</span> SKUs distintos)</div>
                                    </div>
                                    <div style="font-size: 40px;">🛍️</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="p-3" style="background: #FEF2F2; border-radius: 14px; border: 2px solid #EF4444;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div style="font-size: 13px; font-weight: 800; color: #B91C1C; text-transform: uppercase;">● Direcionado para Descarte</div>
                                        <div style="font-size: 32px; font-weight: 900; color: #991B1B;" id="previaUnidadesDescarte">0</div>
                                        <div style="font-size: 13px; color: #B91C1C;">unidades de perda física (<span id="previaProdsDescarte">0</span> SKUs distintos)</div>
                                    </div>
                                    <div style="font-size: 40px;">🗑️</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="boxProdutosNovos" class="alert alert-info py-2 px-3 mb-3" style="border-radius: 10px; font-size: 14px; display: none;"></div>

                    <!-- Mapeamento Flexível de Motivos de Avaria -->
                    <div class="card p-3 mb-4 shadow-sm border-0" style="border-radius: 16px; background: #FFFFFF;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0" style="font-weight: 800; color: #1E293B; font-size: 16px;">
                                ⚙️ Matriz de Destinação de Motivos (Flexível para novos motivos)
                            </h6>
                            <span class="badge bg-light text-muted border">Pré-classificado automaticamente</span>
                        </div>
                        <p class="text-muted small mb-3">Você pode alterar livremente a destinação de qualquer motivo nos seletores abaixo. Os saldos e a Lojinha se adaptam instantaneamente.</p>

                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Motivo Constante no CSV</th>
                                        <th class="text-center" style="width: 140px;">Ocorrências</th>
                                        <th class="text-center" style="width: 140px;">Qtd Total</th>
                                        <th style="width: 320px;">Destinação no Repack</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyPreviaMotivos"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tabela de Produtos que entrarão na Lojinha -->
                    <div class="card p-3 mb-4 shadow-sm border-0" style="border-radius: 16px; background: #FFFFFF;">
                        <h6 class="mb-2" style="font-weight: 800; color: #1E293B; font-size: 16px;">
                            📦 Produtos que terão Estoque Incrementado na Lojinha
                        </h6>
                        <div class="table-responsive" style="max-height: 260px; overflow-y: auto;">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>SKU</th>
                                        <th>Descrição do Produto</th>
                                        <th class="text-center">Estoque Atual</th>
                                        <th class="text-center">Incremento (+)</th>
                                        <th class="text-center">Novo Saldo</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyPreviaProdutosLojinha"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Formulário de Confirmação Definitiva -->
                    <form id="formImportarCsvFinal" method="POST" action="<?= APP_URL ?>/gestao/estoque/importar-csv">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="conteudo_csv" id="hiddenConteudoCsv" value="">
                        <input type="hidden" name="nome_arquivo" id="hiddenNomeArquivoCsv" value="">
                        <input type="hidden" name="destinos_customizados" id="hiddenDestinosCustomizados" value="">

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 border-top">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="forcar_reimportacao" value="1" id="checkForcarReimportacao">
                                <label class="form-check-label text-muted small" for="checkForcarReimportacao">
                                    Forçar reimportação mesmo se o arquivo já tiver sido importado anteriormente
                                </label>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary px-3" onclick="voltarUploadCsv()" style="border-radius: 10px; font-weight: 600;">Voltar</button>
                                <button type="button" id="btnConfirmarImportacaoFinal" class="btn btn-success px-4" onclick="submeterImportacaoCsvFinal()" style="border-radius: 12px; font-weight: 800; height: 50px; font-size: 16px; background: linear-gradient(135deg, #10B981, #059669); border: none; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
                                    ✅ Confirmar Importação e Abastecer Lojinha
                                </button>
                            </div>
                        </div>
                    </form>

                </div>

            </div>

        </div>
    </div>
</div>


<?php require __DIR__ . '/../layouts/gestao_footer.php'; ?>
