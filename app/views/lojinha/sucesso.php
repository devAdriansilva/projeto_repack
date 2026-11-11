<?php
declare(strict_types=1);

/**
 * Projeto Repack - Comprovante Digital de Pedido Confirmado
 * Baseado estritamente em docs/FSD.md (Seções 12.2, 13.3 e 15) e docs/DESIGN.md
 */

$pageTitle = 'Comprovante do Pedido - ' . ($pedido['codigo_pedido'] ?? '');
require __DIR__ . '/../layouts/lojinha_header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <!-- Card Principal do Comprovante -->
        <div style="background: #FFFFFF; border-radius: 20px; border: 2px solid #E5E7EB; padding: 40px 32px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); text-align: center;">
            
            <!-- Ícone de Sucesso Gigante (Primary #10B981) -->
            <div style="display: inline-flex; align-items: center; justify-content: center; width: 88px; height: 88px; background: #D1FAE5; border-radius: 50%; color: #10B981; margin-bottom: 20px;">
                <svg width="52" height="52" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
            </div>

            <h2 class="headline-lg" style="color: #065F46; margin-bottom: 8px;">
                Pedido Confirmado com Sucesso!
            </h2>
            <p class="body-md text-muted mb-4">
                Sua compra interna foi registrada no sistema e os itens foram reservados definitivamente.
            </p>

            <!-- Número do Pedido em Destaque Absoluto (headline-xl 32px) -->
            <div style="background: #F8FAFC; border: 2px dashed #CBD5E1; border-radius: 16px; padding: 20px; margin-bottom: 32px;">
                <div style="font-size: 14px; text-transform: uppercase; color: #64748B; font-weight: 700; letter-spacing: 1px;">
                    Código de Retirada
                </div>
                <div class="headline-xl" style="color: #1E293B; margin-top: 6px; font-family: monospace;">
                    <?= sanitize_html($pedido['codigo_pedido']) ?>
                </div>
                <div style="font-size: 13px; color: #94A3B8; margin-top: 4px;">
                    Realizado em: <?= date('d/m/Y \à\s H:i:s', strtotime($pedido['created_at'])) ?>
                </div>
            </div>

            <!-- Dados do Comprador e Lote -->
            <div class="row g-3 text-start mb-4" style="background: #F9FAFB; border-radius: 12px; padding: 16px 20px; border: 1px solid #E5E7EB;">
                <div class="col-12 col-md-6">
                    <div class="text-muted" style="font-size: 13px;">Colaborador:</div>
                    <div style="font-weight: 700; color: #111827; font-size: 16px;">
                        <?= sanitize_html($pedido['usuario_nome']) ?> (<?= sanitize_html($pedido['usuario_matricula']) ?>)
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="text-muted" style="font-size: 13px;">Setor Autorizado:</div>
                    <div style="font-weight: 700; color: #111827; font-size: 16px;">
                        <?= sanitize_html($pedido['usuario_setor']) ?>
                    </div>
                </div>
                <div class="col-12">
                    <div class="text-muted" style="font-size: 13px;">Lote de Venda:</div>
                    <div style="font-weight: 600; color: #374151; font-size: 15px;">
                        <?= sanitize_html($pedido['lote_titulo']) ?>
                    </div>
                </div>
            </div>

            <!-- Tabela dos Itens Confirmados -->
            <div class="text-start mb-4">
                <h4 style="font-size: 18px; font-weight: 700; margin-bottom: 12px; color: #111827;">
                    Itens Adquiridos
                </h4>
                <div class="table-responsive" style="border: 1px solid #E5E7EB; border-radius: 12px; overflow: hidden;">
                    <table class="table mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Produto</th>
                                <th>Embalagem</th>
                                <th class="text-end">Quantidade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedido['itens'] as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= sanitize_html($item['produto_nome']) ?></strong>
                                        <div class="text-muted" style="font-size: 12px;"><?= sanitize_html($item['produto_codigo']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= sanitize_html($item['pacote_padrao']) ?></span>
                                    </td>
                                    <td class="text-end" style="font-weight: 800; font-size: 16px;">
                                        <?= (int)$item['quantidade'] ?> un
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="2" class="text-end">Total de Itens:</th>
                                <th class="text-end" style="font-size: 18px; color: #10B981;">
                                    <?= (int)$pedido['quantidade_total'] ?> un
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Orientações de Retirada Física -->
            <div class="alert alert-warning text-start mb-4" style="border-radius: 12px; font-size: 15px;">
                <div class="d-flex gap-2">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24" style="flex-shrink: 0;">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                    </svg>
                    <div>
                        <strong>Instruções de Retirada:</strong>
                        <ul class="mb-0 ps-3 mt-1">
                            <li>Dirija-se à bancada do setor de <strong>Repack</strong> no galpão logístico.</li>
                            <li>Apresente este código (<strong><?= sanitize_html($pedido['codigo_pedido']) ?></strong>) ao operador responsável.</li>
                            <li>A conferência física será realizada com base na lista de separação emitida pela Gestão.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Botões de Ação do Comprovante (Filosofia Urna Eletrônica) -->
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <button type="button" class="btn btn-outline-secondary" onclick="window.print()" style="font-weight: 700; border-radius: 12px; padding: 14px 28px; font-size: 16px;">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" class="me-1">
                        <path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/>
                    </svg>
                    Imprimir Comprovante
                </button>

                <a href="<?= APP_URL ?>/lojinha" class="btn-urna btn-urna-primary d-inline-flex align-items-center justify-content-center" style="height: 64px; padding: 0 32px; font-size: 20px; text-decoration: none; border-radius: 12px;">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24" class="me-2">
                        <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                    </svg>
                    <span>Voltar à Vitrine</span>
                </a>
            </div>

        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/lojinha_footer.php'; ?>
