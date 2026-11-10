<?php
declare(strict_types=1);

/**
 * Projeto Repack - Vitrine de Ofertas da Lojinha Virtual (Colaborador)
 * Baseado estritamente em docs/FSD.md (Seções 6.3, 9, 12.2 e 13.3) e docs/DESIGN.md
 */

$pageTitle = 'Vitrine da Lojinha - ' . ($lote['titulo'] ?? 'Ofertas Internas');
require __DIR__ . '/../layouts/lojinha_header.php';
?>

<!-- Banner do Lote Ativo e Cota do Colaborador -->
<div class="banner-lote">
    <div>
        <div style="font-size: 13px; text-transform: uppercase; color: #10B981; font-weight: 800; letter-spacing: 0.5px;">
            ● Lote Ativo Aberto para o Setor: <?= sanitize_html($usuario['setor']) ?>
        </div>
        <h2 style="font-size: 24px; font-weight: 800; margin: 4px 0 2px 0;">
            <?= sanitize_html($lote['titulo']) ?>
        </h2>
        <div style="font-size: 14px; color: #94A3B8;">
            Vigência: <?= date('d/m/Y H:i', strtotime($lote['data_inicio'])) ?> até <?= date('d/m/Y H:i', strtotime($lote['data_fim'])) ?>
        </div>
    </div>

    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div class="banner-cota">
            Limite por pessoa: <strong><?= (int)$resumo['limite_lote'] ?> itens</strong>
        </div>
        <div class="banner-cota" style="background: rgba(16, 185, 129, 0.2); border-color: #10B981; color: #A7F3D0;">
            Você ainda pode escolher: <strong id="cotaLimiteRestante" style="font-size: 18px; color: #FFFFFF;"><?= (int)$resumo['limite_restante'] ?></strong> itens
        </div>
    </div>
</div>

<?php if (!empty($flashSucesso)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 12px; font-weight: 600;">
        <?= sanitize_html($flashSucesso) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($flashErro)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 12px; font-weight: 600;">
        <?= sanitize_html($flashErro) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Alerta de Itens em Sacola com Reserva Ativa -->
<?php if ((int)$resumo['itens_carrinho'] > 0): ?>
    <div class="timer-trava-box d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <svg width="28" height="28" fill="currentColor" viewBox="0 0 24 24">
                <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
            </svg>
            <span>
                Você possui <strong><?= (int)$resumo['itens_carrinho'] ?> item(ns)</strong> na sacola com reserva garantida temporariamente.
            </span>
        </div>
        <a href="/lojinha/carrinho" class="btn btn-warning" style="font-weight: 700; border-radius: 10px; padding: 10px 20px;">
            Ver Sacola e Finalizar →
        </a>
    </div>
<?php endif; ?>

<!-- Grade de Produtos em Oferta -->
<div class="row g-4">
    <?php if (empty($produtos)): ?>
        <div class="col-12 text-center py-5">
            <div style="background: #FFFFFF; border-radius: 16px; padding: 48px; border: 2px dashed #D1D5DB;">
                <svg width="64" height="64" fill="#9CA3AF" viewBox="0 0 24 24" class="mb-3">
                    <path d="M19 6h-2c0-2.76-2.24-5-5-5S7 3.24 7 6H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-7-3c1.66 0 3 1.34 3 3H9c0-1.66 1.34-3 3-3zm7 17H5V8h14v12zm-7-8c-1.66 0-3-1.34-3-3H7c0 2.76 2.24 5 5 5s5-2.24 5-5h-2c0 1.66-1.34 3-3 3z"/>
                </svg>
                <h3 class="headline-lg text-muted">Nenhum produto disponível no momento</h3>
                <p class="body-md text-secondary">Novas avarias leves serão disponibilizadas assim que apontadas na bancada de repack.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($produtos as $p): 
            $saldoDisp = (int)$p['saldo_disponivel'];
            $noCarrinho = (int)$p['no_meu_carrinho'];
            $esgotado = ($saldoDisp <= 0);
            $limiteAtingido = ((int)$resumo['limite_restante'] <= 0 && $saldoDisp > 0);
        ?>
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <div class="card-produto-lojinha <?= $esgotado ? 'esgotado' : '' ?>">
                    <div>
                        <!-- Badge de Saldo Real em Vitrine -->
                        <?php if ($esgotado): ?>
                            <span class="card-badge-estoque esgotado">Esgotado</span>
                        <?php elseif ($saldoDisp <= 5): ?>
                            <span class="card-badge-estoque baixo">Últimas <?= $saldoDisp ?> un!</span>
                        <?php else: ?>
                            <span class="card-badge-estoque"><?= $saldoDisp ?> disponíveis</span>
                        <?php endif; ?>

                        <!-- Imagem do Produto -->
                        <div class="card-img-container">
                            <?php if (!empty($p['foto_url']) && file_exists(PUBLIC_PATH . $p['foto_url'])): ?>
                                <img src="<?= sanitize_html($p['foto_url']) ?>" alt="<?= sanitize_html($p['nome']) ?>">
                            <?php else: ?>
                                <svg width="72" height="72" fill="#9CA3AF" viewBox="0 0 24 24">
                                    <path d="M7 2v2h1v4.54c-.6.28-1 .88-1 1.46v9c0 1.66 1.34 3 3 3h4c1.66 0 3-1.34 3-3v-9c0-.58-.4-1.18-1-1.46V4h1V2H7zm8 15c0 .55-.45 1-1 1h-4c-.55 0-1-.45-1-1v-7h6v7zm-1-9h-4V4h4v4z"/>
                                </svg>
                            <?php endif; ?>
                        </div>

                        <!-- Descrição e Embalagem Padrão -->
                        <div class="mb-2">
                            <span class="badge bg-secondary" style="font-size: 11px;">
                                <?= sanitize_html($p['pacote_padrao']) ?>
                            </span>
                            <?php if ($noCarrinho > 0): ?>
                                <span class="badge bg-warning text-dark" style="font-size: 11px;">
                                    <?= $noCarrinho ?> na sua sacola
                                </span>
                            <?php endif; ?>
                        </div>

                        <h3 class="body-lg" style="font-weight: 700; color: #111827; margin-bottom: 8px; min-height: 54px;">
                            <?= sanitize_html($p['nome']) ?>
                        </h3>
                    </div>

                    <!-- Botão de Ação Gigante (80px - Filosofia Urna Eletrônica) -->
                    <div class="mt-3">
                        <?php if ($esgotado): ?>
                            <button type="button" class="btn-urna btn-urna-secondary w-100" disabled style="height: 80px; opacity: 0.5;">
                                <span>Esgotado</span>
                            </button>
                        <?php elseif ($limiteAtingido): ?>
                            <button type="button" class="btn-urna btn-urna-warning w-100" disabled style="height: 80px; font-size: 18px;">
                                <span>Cota do Lote Atingida</span>
                            </button>
                        <?php else: ?>
                            <button type="button" 
                                    class="btn-urna btn-urna-secondary w-100" 
                                    style="height: 80px; font-size: 20px;"
                                    onclick="adicionarAoCarrinho(<?= (int)$p['produto_id'] ?>, this)">
                                <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                                </svg>
                                <span>Adicionar à Sacola</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../layouts/lojinha_footer.php'; ?>
