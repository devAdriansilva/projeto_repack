<?php
declare(strict_types=1);

/**
 * Projeto Repack - Comprovante Digital de Pedido Confirmado
 * Baseado estritamente em docs/FSD.md (Seções 12.2, 13.3 e 15) e docs/DESIGN.md
 */

$pageTitle = 'Comprovante do Pedido - ' . ($pedido['codigo_pedido'] ?? '');

$isGestor = (!empty($_SESSION['usuario_id']) && ($_SESSION['perfil'] ?? '') === 'gestao');
$isOperador = (!empty($_SESSION['operador_id']) || ($_SESSION['perfil'] ?? '') === 'operador');
$podeImprimir = $isGestor || $isOperador;

// Montagem antecipada e segura do texto do comprovante para compartilhamento WhatsApp / Clipboard
$itensFormatadosArr = [];
foreach ($pedido['itens'] as $it) {
    $nomeTratado = mb_convert_case(mb_strtolower($it['produto_nome'], 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    $itensFormatadosArr[] = "• " . (int)$it['quantidade'] . "x " . $nomeTratado;
}
$itensTextoSeguro = implode("\n", $itensFormatadosArr);

$textoWhatsApp = "🧾 *COMPROVANTE DE PEDIDO - LOJINHA REPACK*\n" .
    "----------------------------------------\n" .
    "📦 *Código de Retirada:* *" . $pedido['codigo_pedido'] . "*\n" .
    "👤 *Colaborador:* " . $pedido['usuario_nome'] . " (" . $pedido['usuario_matricula'] . ")\n" .
    "🏢 *Setor:* " . $pedido['usuario_setor'] . "\n" .
    "📅 *Data:* " . date('d/m/Y \à\s H:i', strtotime($pedido['created_at'])) . "\n" .
    "----------------------------------------\n" .
    "🛒 *ITENS DO PEDIDO (" . (int)$pedido['quantidade_total'] . " un):*\n" .
    $itensTextoSeguro . "\n" .
    "----------------------------------------\n" .
    "🔗 *Ver Comprovante Online:*\n" .
    APP_URL . "/lojinha/sucesso?codigo=" . urlencode($pedido['codigo_pedido']);

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
                                        <strong><?= sanitize_html(mb_convert_case(mb_strtolower($item['produto_nome'], 'UTF-8'), MB_CASE_TITLE, 'UTF-8')) ?></strong>
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

            <!-- Toast de Feedback de Cópia -->
            <div id="toastCopiado" class="alert alert-success py-2 px-3 mb-3" style="display: none; border-radius: 10px; font-size: 14px; font-weight: 700;">
                ✅ Comprovante copiado para a área de transferência! Pronto para colar no WhatsApp.
            </div>

            <!-- Botões de Ação do Comprovante para o Comprador -->
            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center btn-acao-comprovante mb-2">
                <!-- Opção 1: Salvar Comprovante (PDF) -->
                <button type="button" class="btn btn-primary py-2.5 px-4 fw-bold d-inline-flex align-items-center justify-content-center gap-2" onclick="salvarComprovantePDF()" title="Salvar comprovante digital em PDF no seu aparelho">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7h-2zm-6 .67l2.59-2.58L17 11.5l-5 5-5-5 1.41-1.41L11 12.67V3h2v9.67z"/>
                    </svg>
                    <span>Salvar Comprovante</span>
                </button>

                <!-- Opção 2: Compartilhar Pedido (WhatsApp / App Nativo) -->
                <button type="button" class="btn btn-success py-2.5 px-4 fw-bold d-inline-flex align-items-center justify-content-center gap-2" style="background-color: #10B981; border-color: #059669;" onclick="compartilharComprovante()" title="Compartilhar comprovante pelo WhatsApp ou outros aplicativos">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92c0-1.61-1.31-2.92-2.92-2.92z"/>
                    </svg>
                    <span>Compartilhar Pedido</span>
                </button>

                <!-- Opção 3: Voltar à Vitrine -->
                <a href="<?= APP_URL ?>/lojinha" class="btn btn-outline-secondary py-2.5 px-4 fw-bold text-decoration-none d-inline-flex align-items-center justify-content-center">
                    <span>Voltar à Vitrine</span>
                </a>
            </div>

            <!-- Atalhos Rápidos para Compartilhamento -->
            <div class="d-flex flex-wrap gap-2 justify-content-center btn-acao-comprovante mb-1" style="font-size: 13.5px;">
                <button type="button" class="btn btn-link text-decoration-none text-success p-0 fw-bold d-inline-flex align-items-center gap-1" onclick="abrirWhatsAppDirect()" title="Abrir conversa no WhatsApp com o comprovante">
                    <span>💬 Enviar Direto pelo WhatsApp</span>
                </button>
                <span class="text-muted">&bull;</span>
                <button type="button" class="btn btn-link text-decoration-none text-secondary p-0 fw-bold d-inline-flex align-items-center gap-1" onclick="copiarTextoComprovante()" title="Copiar texto formatado do comprovante">
                    <span>📋 Copiar Dados</span>
                </button>
            </div>

            <?php if (!empty($podeImprimir)): ?>
                <!-- Seção Restrita: Operação / Gestão Repack para Separação Física -->
                <div class="mt-4 pt-3 border-top btn-acao-comprovante text-center">
                    <div class="text-muted small fw-bold mb-2 text-uppercase" style="letter-spacing: 0.5px;">
                        ⚙️ Opções Operacionais (Gestor / Operador Repack)
                    </div>
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-primary btn-sm fw-bold px-3" onclick="imprimirComprovante('a4')" title="Imprimir folha A4 para separação">
                            🖨️ Imprimir (Folha A4)
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm fw-bold px-3" onclick="imprimirComprovante('cupom')" title="Imprimir em bobina térmica 80mm">
                            🧾 Imprimir (Cupom 80mm)
                        </button>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
const textoComprovante = <?= json_encode($textoWhatsApp) ?>;
const codigoPedido = <?= json_encode($pedido['codigo_pedido']) ?>;
const urlComprovante = <?= json_encode(APP_URL . '/lojinha/sucesso?codigo=' . urlencode($pedido['codigo_pedido'])) ?>;

// Salvar Comprovante como PDF via folha A4
function salvarComprovantePDF() {
    document.body.classList.remove('modo-print-cupom');
    document.body.classList.add('modo-print-a4');
    window.print();
    setTimeout(() => {
        document.body.classList.remove('modo-print-a4');
    }, 1000);
}

// Compartilhamento Inteligente (Nativo no Mobile com Fallback para WhatsApp)
function compartilharComprovante() {
    if (navigator.share) {
        navigator.share({
            title: 'Comprovante Pedido ' + codigoPedido,
            text: textoComprovante,
            url: urlComprovante
        }).catch(err => {
            if (err.name !== 'AbortError') {
                abrirWhatsAppDirect();
            }
        });
    } else {
        abrirWhatsAppDirect();
    }
}

// Envio Direto via Link do WhatsApp
function abrirWhatsAppDirect() {
    const urlWhats = 'https://api.whatsapp.com/send?text=' + encodeURIComponent(textoComprovante);
    window.open(urlWhats, '_blank');
}

// Copiar Texto para Área de Transferência
function copiarTextoComprovante() {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(textoComprovante).then(() => {
            exibirToastCopiado();
        }).catch(() => {
            fallbackCopyText(textoComprovante);
        });
    } else {
        fallbackCopyText(textoComprovante);
    }
}

function fallbackCopyText(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.opacity = '0';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        document.execCommand('copy');
        exibirToastCopiado();
    } catch (err) {
        prompt('Copie o comprovante abaixo:', text);
    }
    document.body.removeChild(textArea);
}

function exibirToastCopiado() {
    const toast = document.getElementById('toastCopiado');
    if (toast) {
        toast.style.display = 'block';
        setTimeout(() => {
            toast.style.display = 'none';
        }, 4000);
    }
}

<?php if (!empty($podeImprimir)): ?>
// Impressão para Gestor / Operador Repack
function imprimirComprovante(tipo) {
    document.body.classList.remove('modo-print-a4', 'modo-print-cupom');
    
    if (tipo === 'cupom') {
        document.body.classList.add('modo-print-cupom');
    } else {
        document.body.classList.add('modo-print-a4');
    }

    window.print();

    setTimeout(() => {
        document.body.classList.remove('modo-print-a4', 'modo-print-cupom');
    }, 1000);
}
<?php endif; ?>
</script>

<?php require __DIR__ . '/../layouts/lojinha_footer.php'; ?>
