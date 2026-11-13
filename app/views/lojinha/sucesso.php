<?php
declare(strict_types=1);

/**
 * Projeto Repack - Comprovante Digital de Pedido Confirmado
 * Baseado estritamente em docs/FSD.md (Seções 12.2, 13.3 e 15) e docs/DESIGN.md
 */

$pageTitle = 'Comprovante do Pedido - ' . ($pedido['codigo_pedido'] ?? '');
require __DIR__ . '/../layouts/lojinha_header.php';
?>

<style>
/* Estilos Especiais para Impressão Dual: A4 vs Cupom Fiscal 80mm */
@media print {
    body {
        background: #FFFFFF !important;
        color: #000000 !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .navbar, header, footer, .btn-acao-comprovante, .nav-gestao, .lojinha-header {
        display: none !important;
    }
    .card-comprovante-print {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        width: 100% !important;
        margin: 0 !important;
    }

    /* Modo A4: Página Única Compacta */
    body.modo-print-a4 {
        font-size: 13px !important;
    }
    body.modo-print-a4 .card-comprovante-print {
        max-width: 100% !important;
        page-break-inside: avoid !important;
    }
    body.modo-print-a4 .box-codigo-retirada {
        padding: 10px !important;
        margin-bottom: 16px !important;
    }
    body.modo-print-a4 .box-codigo-retirada .headline-xl {
        font-size: 26px !important;
    }
    body.modo-print-a4 .table td, 
    body.modo-print-a4 .table th {
        padding: 6px 10px !important;
        font-size: 12px !important;
    }

    /* Modo Cupom Fiscal: Bobina Térmica 80mm */
    body.modo-print-cupom {
        width: 80mm !important;
        max-width: 80mm !important;
        font-family: 'Courier New', Courier, monospace !important;
        font-size: 12px !important;
    }
    body.modo-print-cupom .card-comprovante-print {
        width: 76mm !important;
        max-width: 76mm !important;
        margin: 0 auto !important;
    }
    body.modo-print-cupom .icone-sucesso-destaque {
        display: none !important;
    }
    body.modo-print-cupom .box-codigo-retirada {
        border: 1px dashed #000 !important;
        padding: 8px 4px !important;
        margin-bottom: 12px !important;
    }
    body.modo-print-cupom .box-codigo-retirada .headline-xl {
        font-size: 22px !important;
    }
    body.modo-print-cupom .table {
        font-size: 11px !important;
    }
    body.modo-print-cupom .table td, 
    body.modo-print-cupom .table th {
        padding: 4px 2px !important;
    }
}
</style>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <!-- Card Principal do Comprovante -->
        <div class="card-comprovante-print" style="background: #FFFFFF; border-radius: 20px; border: 2px solid #E5E7EB; padding: 32px 28px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); text-align: center;">
            
            <!-- Ícone de Sucesso Gigante (Primary #10B981) -->
            <div class="icone-sucesso-destaque" style="display: inline-flex; align-items: center; justify-content: center; width: 76px; height: 76px; background: #D1FAE5; border-radius: 50%; color: #10B981; margin-bottom: 16px;">
                <svg width="44" height="44" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
            </div>

            <h2 class="headline-lg mb-1" style="color: #065F46; font-size: 26px;">
                Pedido Confirmado com Sucesso!
            </h2>
            <p class="body-md text-muted mb-3" style="font-size: 14px;">
                Sua compra interna foi registrada no sistema e os itens foram reservados definitivamente.
            </p>

            <!-- Número do Pedido em Destaque Absoluto -->
            <div class="box-codigo-retirada" style="background: #F8FAFC; border: 2px dashed #CBD5E1; border-radius: 14px; padding: 16px; margin-bottom: 24px;">
                <div style="font-size: 13px; text-transform: uppercase; color: #64748B; font-weight: 700; letter-spacing: 1px;">
                    Código de Retirada
                </div>
                <div class="headline-xl" style="color: #1E293B; margin-top: 4px; font-family: monospace; font-size: 32px; font-weight: 900;">
                    <?= sanitize_html($pedido['codigo_pedido']) ?>
                </div>
                <div style="font-size: 12px; color: #94A3B8; margin-top: 4px;">
                    Realizado em: <?= date('d/m/Y \à\s H:i:s', strtotime($pedido['created_at'])) ?>
                </div>
            </div>

            <!-- Dados do Comprador e Lote -->
            <div class="row g-2 text-start mb-3" style="background: #F9FAFB; border-radius: 12px; padding: 14px 18px; border: 1px solid #E5E7EB; font-size: 14px;">
                <div class="col-12 col-md-6">
                    <div class="text-muted" style="font-size: 12px;">Colaborador:</div>
                    <div style="font-weight: 700; color: #111827;">
                        <?= sanitize_html($pedido['usuario_nome']) ?> (<?= sanitize_html($pedido['usuario_matricula']) ?>)
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="text-muted" style="font-size: 12px;">Setor Autorizado:</div>
                    <div style="font-weight: 700; color: #111827;">
                        <?= sanitize_html($pedido['usuario_setor']) ?>
                    </div>
                </div>
                <div class="col-12">
                    <div class="text-muted" style="font-size: 12px;">Lote de Venda:</div>
                    <div style="font-weight: 600; color: #374151;">
                        <?= sanitize_html($pedido['lote_titulo']) ?>
                    </div>
                </div>
            </div>

            <!-- Tabela dos Itens Confirmados -->
            <div class="text-start mb-4">
                <h4 style="font-size: 16px; font-weight: 700; margin-bottom: 8px; color: #111827;">
                    Itens Adquiridos
                </h4>
                <div class="table-responsive" style="border: 1px solid #E5E7EB; border-radius: 10px; overflow: hidden;">
                    <table class="table mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Produto</th>
                                <th>Código</th>
                                <th class="text-end">Quantidade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedido['itens'] as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= sanitize_html($item['produto_nome']) ?></strong>
                                    </td>
                                    <td class="text-muted" style="font-size: 12px;">
                                        <?= sanitize_html($item['produto_codigo']) ?>
                                    </td>
                                    <td class="text-end" style="font-weight: 800; font-size: 15px;">
                                        <?= (int)$item['quantidade'] ?> un
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="2" class="text-end">Total de Itens:</th>
                                <th class="text-end" style="font-size: 17px; color: #10B981; font-weight: 900;">
                                    <?= (int)$pedido['quantidade_total'] ?> un
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Botões de Ação do Comprovante com Escolha de Impressora -->
            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center btn-acao-comprovante">
                <!-- Opção 1: Impressora Regular (Folha A4 em 1 Página) -->
                <button type="button" class="btn btn-outline-primary py-2 px-3 fw-bold" onclick="imprimirComprovante('a4')" title="Imprimir em folha sulfite padrão A4 (cabe em 1 página)">
                    🖨️ Imprimir (Folha A4)
                </button>

                <!-- Opção 2: Impressora de Cupom Fiscal (Térmica 80mm) -->
                <button type="button" class="btn btn-outline-secondary py-2 px-3 fw-bold" onclick="imprimirComprovante('cupom')" title="Imprimir em bobina térmica de 80mm">
                    🧾 Imprimir (Cupom 80mm)
                </button>

                <!-- Voltar à Vitrine -->
                <a href="<?= APP_URL ?>/lojinha" class="btn btn-success py-2 px-4 fw-bold text-decoration-none">
                    <span>Voltar à Vitrine</span>
                </a>
            </div>

        </div>
    </div>
</div>

<script>
function imprimirComprovante(tipo) {
    document.body.classList.remove('modo-print-a4', 'modo-print-cupom');
    
    if (tipo === 'cupom') {
        document.body.classList.add('modo-print-cupom');
    } else {
        document.body.classList.add('modo-print-a4');
    }

    window.print();

    // Restaura classes após envio para a impressora
    setTimeout(() => {
        document.body.classList.remove('modo-print-a4', 'modo-print-cupom');
    }, 1000);
}
</script>

<?php require __DIR__ . '/../layouts/lojinha_footer.php'; ?>
