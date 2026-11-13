<?php
declare(strict_types=1);

/**
 * Projeto Repack - Vitrine de Ofertas da Lojinha Virtual (Colaborador)
 * Baseado estritamente em docs/FSD.md (Seções 6.3, 9, 12.2 e 13.3) e docs/DESIGN.md
 */

// Helper para categorização inteligente de bebidas
function categorizarBebida(string $nome): string {
    $n = mb_strtolower($nome, 'UTF-8');
    if (preg_match('/cerveja|pilsen|malzbier|brahma|skol|antarctica|bohemia|corona|budweiser|spaten|stella|becks|chopp|ipa|lager|puro malte/i', $n)) {
        return 'cervejas';
    }
    if (preg_match('/refrigerante|guaraná|guarana|cola|pepsi|sukita|soda|tônica|tonica|fanta|sprite/i', $n)) {
        return 'refrigerantes';
    }
    if (preg_match('/água|agua|mineral/i', $n)) {
        return 'aguas';
    }
    if (preg_match('/energético|energetico|monster|red bull|fusion|isotônico|isotonico|gatorade/i', $n)) {
        return 'energeticos';
    }
    if (preg_match('/suco|néctar|nectar|chá|cha|del valle|matte/i', $n)) {
        return 'sucos';
    }
    return 'outros';
}

// Associa a categoria a cada produto e conta totais
$contagemCategorias = [
    'todos' => count($produtos),
    'cervejas' => 0,
    'refrigerantes' => 0,
    'aguas' => 0,
    'energeticos' => 0,
    'sucos' => 0,
    'outros' => 0
];

foreach ($produtos as &$prod) {
    $cat = categorizarBebida($prod['nome']);
    $prod['categoria'] = $cat;
    if (isset($contagemCategorias[$cat])) {
        $contagemCategorias[$cat]++;
    }
}
unset($prod);

$pageTitle = 'Vitrine da Lojinha - ' . ($lote['titulo'] ?? 'Ofertas Internas');
require __DIR__ . '/../layouts/lojinha_header.php';
?>

<!-- Seletor de Perspectiva para Gestão / Master -->
<?php if (!empty($podeAlternarPerspectiva)): ?>
    <div class="card mb-4 p-3 shadow-sm" style="border: 2px solid #8B5CF6; background: #F5F3FF; border-radius: 14px;">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #EDE9FE; display: flex; align-items: center; justify-content: center; font-size: 22px; border: 1.5px solid #C4B5FD;">
                    👓
                </div>
                <div>
                    <div style="font-weight: 800; font-size: 15px; color: #5B21B6; display: flex; align-items: center; gap: 8px;">
                        Controle de Perspectiva da Gestão
                        <span class="badge" style="background: <?= !empty($simulandoColaborador) ? '#10B981' : '#6366F1' ?>; color: #FFFFFF; font-size: 11px;">
                            <?= !empty($simulandoColaborador) ? '👤 Modo Colaborador Ativo' : '👑 Modo Master Ativo' ?>
                        </span>
                    </div>
                    <div style="font-size: 13px; color: #6D28D9; margin-top: 2px;">
                        <?php if (!empty($simulandoColaborador)): ?>
                            Você está visualizando a vitrine exatamente como um <strong>colaborador comum</strong> enxerga (apenas produtos disponíveis, sem acesso a reservas ocultas).
                        <?php else: ?>
                            Você está visualizando no <strong>Modo Master</strong> (com estoque físico total e reservas ocultas visíveis para auditoria gerencial).
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="btn-group" role="group" aria-label="Alternar Perspectiva">
                    <a href="<?= APP_URL ?>/lojinha?modo=master" class="btn btn-sm <?= empty($simulandoColaborador) ? 'btn-primary active' : 'btn-outline-primary' ?>" style="border-radius: 8px 0 0 8px; font-weight: 800; padding: 7px 14px;">
                        👑 Modo Master
                    </a>
                    <a href="<?= APP_URL ?>/lojinha?modo=colaborador" class="btn btn-sm <?= !empty($simulandoColaborador) ? 'btn-primary active' : 'btn-outline-primary' ?>" style="border-radius: 0 8px 8px 0; font-weight: 800; padding: 7px 14px;">
                        👤 Modo Colaborador
                    </a>
                </div>
                <a href="<?= APP_URL ?>/gestao/dashboard" class="btn btn-sm btn-outline-dark fw-bold ms-2" style="border-radius: 8px; padding: 7px 14px;">
                    ← Voltar para Gestão
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Banner do Lote Ativo e Cota do Colaborador -->
<?php if (!empty($isMaster)): ?>
    <div class="banner-lote" style="background: linear-gradient(135deg, #1E1B4B, #312E81); border: 2px solid #818CF8;">
        <div>
            <div style="font-size: 13px; text-transform: uppercase; color: #FCD34D; font-weight: 800; letter-spacing: 0.5px;">
                👑 ACESSO MASTER - GESTÃO REPACK
            </div>
            <h2 style="font-size: 24px; font-weight: 800; margin: 4px 0 2px 0;">
                Visão Administrativa de Estoque & Retiradas
            </h2>
            <div style="font-size: 14px; color: #C7D2FE;">
                Visualização do Estoque Físico Total e Reservas Ocultas. Retiradas liberadas sem restrição de cotas ou horários de fechamento.
            </div>
        </div>

        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="banner-cota" style="background: rgba(252, 211, 77, 0.15); border-color: #FCD34D; color: #FDE68A;">
                Regra: <strong>Retirada Master (Sem Limite)</strong>
            </div>
        </div>
    </div>
<?php elseif (!empty($isSetorial)): ?>
    <div class="banner-lote" style="background: linear-gradient(135deg, #0F172A, #0369A1); border: 2px solid #38BDF8;">
        <div>
            <div style="font-size: 13px; text-transform: uppercase; color: #38BDF8; font-weight: 800; letter-spacing: 0.5px;">
                🏢 CONTA SETORIAL CORPORATIVA: <?= sanitize_html($usuario['nome']) ?>
            </div>
            <h2 style="font-size: 24px; font-weight: 800; margin: 4px 0 2px 0;">
                Requisições Institucionais do Setor <?= sanitize_html($usuario['setor']) ?>
            </h2>
            <div style="font-size: 14px; color: #BAE6FD;">
                Esta conta representa as demandas oficiais do setor para eventos e ações internas. Múltiplos pedidos autorizados no ciclo.
            </div>
        </div>

        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="banner-cota" style="background: rgba(56, 189, 248, 0.15); border-color: #38BDF8; color: #E0F2FE;">
                Modalidade: <strong>Múltiplos Pedidos Liberados</strong>
            </div>
        </div>
    </div>
<?php else: ?>
    <?php if (!empty($resumo['ja_comprou_no_lote'])): ?>
        <!-- Aviso de Pedido Já Concluído no Lote (Regra 1B) -->
        <div class="alert alert-success d-flex justify-content-between align-items-center flex-wrap gap-3 p-4 mb-4 shadow-sm" style="border-radius: 16px; border: 2px solid #10B981; background: #ECFDF5;">
            <div>
                <div style="font-size: 20px; font-weight: 800; color: #065F46;">
                    ✅ Seu pedido para este lote já foi confirmado!
                </div>
                <div style="font-size: 15px; color: #047857; margin-top: 4px;">
                    <?php if (!empty($ultimoPedido)): ?>
                        Código de Retirada: <strong style="font-size: 16px; letter-spacing: 0.5px;"><?= sanitize_html($ultimoPedido['codigo_pedido']) ?></strong> (<?= (int)$ultimoPedido['quantidade_total'] ?> itens).
                    <?php endif; ?>
                    Cada colaborador tem direito a 1 pedido por lote de vendas. Aguarde a abertura do próximo ciclo para novas compras.
                </div>
            </div>
            <?php if (!empty($ultimoPedido)): ?>
                <a href="<?= APP_URL ?>/lojinha/sucesso?codigo=<?= urlencode($ultimoPedido['codigo_pedido']) ?>" class="btn btn-success" style="font-weight: 700; border-radius: 12px; padding: 12px 22px; font-size: 16px;">
                    📄 Ver Comprovante do Pedido →
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

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
                Limite por pessoa: <strong><?= (int)$resumo['limite_lote'] ?> itens (1 pedido)</strong>
            </div>
            <div class="banner-cota" style="background: rgba(16, 185, 129, 0.2); border-color: #10B981; color: #A7F3D0;">
                Você ainda pode escolher: <strong id="cotaLimiteRestante" style="font-size: 18px; color: #FFFFFF;"><?= (int)$resumo['limite_restante'] ?></strong> itens
            </div>
        </div>
    </div>
<?php endif; ?>

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
        <a href="<?= APP_URL ?>/lojinha/carrinho" class="btn btn-warning" style="font-weight: 700; border-radius: 10px; padding: 10px 20px;">
            Ver Sacola e Finalizar →
        </a>
    </div>
<?php endif; ?>

<!-- ============================================================= -->
<!-- BARRA DE PESQUISA E FILTROS DE CATEGORIA                      -->
<!-- ============================================================= -->
<div class="card p-3 mb-4 shadow-sm border-0" style="border-radius: 16px; background: #FFFFFF;">
    <!-- Campo de Busca em Tempo Real -->
    <div class="position-relative mb-3">
        <span class="position-absolute top-50 translate-middle-y ps-3 text-muted" style="pointer-events: none;">
            🔍
        </span>
        <input type="text" 
               id="inputBuscaVitrine" 
               class="form-control" 
               placeholder="Buscar bebida por nome ou formato (ex: Brahma, Lata, 600ml...)" 
               style="height: 52px; font-size: 17px; font-weight: 600; padding-left: 44px; border-radius: 12px; border: 2px solid #E2E8F0;"
               oninput="filtrarVitrine()">
        <button type="button" 
                id="btnLimparBusca" 
                class="btn position-absolute top-50 end-0 translate-middle-y me-2 text-muted" 
                style="display: none; background: transparent; border: none; font-size: 18px;"
                onclick="limparBusca()">
            ✕
        </button>
    </div>

    <!-- Pílulas de Categoria (Tags de 1 Clique) -->
    <div class="d-flex align-items-center gap-2 flex-wrap" id="containerCategorias">
        <button type="button" class="btn-categoria-filtro active" data-categoria="todos" onclick="selecionarCategoria('todos', this)">
            🏷️ Todos (<?= $contagemCategorias['todos'] ?>)
        </button>
        <?php if ($contagemCategorias['cervejas'] > 0): ?>
            <button type="button" class="btn-categoria-filtro" data-categoria="cervejas" onclick="selecionarCategoria('cervejas', this)">
                🍺 Cervejas (<?= $contagemCategorias['cervejas'] ?>)
            </button>
        <?php endif; ?>
        <?php if ($contagemCategorias['refrigerantes'] > 0): ?>
            <button type="button" class="btn-categoria-filtro" data-categoria="refrigerantes" onclick="selecionarCategoria('refrigerantes', this)">
                🥤 Refrigerantes (<?= $contagemCategorias['refrigerantes'] ?>)
            </button>
        <?php endif; ?>
        <?php if ($contagemCategorias['aguas'] > 0): ?>
            <button type="button" class="btn-categoria-filtro" data-categoria="aguas" onclick="selecionarCategoria('aguas', this)">
                💧 Águas (<?= $contagemCategorias['aguas'] ?>)
            </button>
        <?php endif; ?>
        <?php if ($contagemCategorias['energeticos'] > 0): ?>
            <button type="button" class="btn-categoria-filtro" data-categoria="energeticos" onclick="selecionarCategoria('energeticos', this)">
                ⚡ Energéticos & Isotônicos (<?= $contagemCategorias['energeticos'] ?>)
            </button>
        <?php endif; ?>
        <?php if ($contagemCategorias['sucos'] > 0): ?>
            <button type="button" class="btn-categoria-filtro" data-categoria="sucos" onclick="selecionarCategoria('sucos', this)">
                🧃 Sucos & Chás (<?= $contagemCategorias['sucos'] ?>)
            </button>
        <?php endif; ?>
        <?php if ($contagemCategorias['outros'] > 0): ?>
            <button type="button" class="btn-categoria-filtro" data-categoria="outros" onclick="selecionarCategoria('outros', this)">
                📦 Outros (<?= $contagemCategorias['outros'] ?>)
            </button>
        <?php endif; ?>
    </div>
</div>

<style>
.btn-categoria-filtro {
    border-radius: 9999px;
    padding: 8px 18px;
    font-size: 15px;
    font-weight: 700;
    border: 2px solid #E2E8F0;
    background: #F8FAFC;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
    white-space: nowrap;
}
.btn-categoria-filtro:hover {
    border-color: #3B82F6;
    color: #1D4ED8;
    background: #EFF6FF;
}
.btn-categoria-filtro.active {
    background: #2563EB;
    border-color: #1D4ED8;
    color: #FFFFFF;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
}
</style>

<!-- Grade de Produtos em Oferta -->
<div class="row g-4" id="gradeProdutosVitrine">
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
            $totalFisico = (int)($p['quantidade_total'] ?? 0);
            $reservaGestao = (int)($p['quantidade_reservada_gestao'] ?? 0);
            $noCarrinho = (int)$p['no_meu_carrinho'];
            
            // Para Master, esgotado é quando o total físico for 0; para os demais quando o saldo disponível for 0
            $esgotado = (!empty($isMaster)) ? ($totalFisico <= 0) : ($saldoDisp <= 0);
            $jaComprouNoLote = !empty($resumo['ja_comprou_no_lote']);
            $limiteAtingido = (empty($isMaster) && empty($isSetorial) && (int)$resumo['limite_restante'] <= 0 && $saldoDisp > 0);
            $cat = $p['categoria'];
        ?>
            <div class="col-12 col-md-6 col-lg-4 col-xl-3 card-produto-wrapper" 
                 data-categoria="<?= $cat ?>" 
                 data-nome="<?= mb_strtolower($p['nome'], 'UTF-8') ?>"
                 data-codigo="<?= mb_strtolower($p['codigo'], 'UTF-8') ?>"
                 data-pacote="<?= mb_strtolower($p['pacote_padrao'] ?? '', 'UTF-8') ?>">
                <div class="card-produto-lojinha <?= $esgotado ? 'esgotado' : '' ?>">
                    <div>
                        <!-- Badge de Saldo Real em Vitrine -->
                        <?php if (!empty($isMaster)): ?>
                            <span class="card-badge-estoque" style="background: #1E1B4B; color: #FDE68A; border: 1.5px solid #818CF8; font-size: 12px; padding: 4px 10px;">
                                Físico: <?= $totalFisico ?> un | Res: <?= $reservaGestao ?> un
                            </span>
                        <?php elseif ($esgotado): ?>
                            <span class="card-badge-estoque esgotado">Esgotado</span>
                        <?php elseif ($saldoDisp <= 5): ?>
                            <span class="card-badge-estoque baixo">Últimas <?= $saldoDisp ?> un!</span>
                        <?php else: ?>
                            <span class="card-badge-estoque"><?= $saldoDisp ?> disponíveis</span>
                        <?php endif; ?>

                        <!-- Imagem do Produto -->
                        <div class="card-img-container">
                            <?php if (!empty($p['foto_url'])): ?>
                                <img src="<?= APP_URL . sanitize_html($p['foto_url']) ?>" alt="<?= sanitize_html($p['nome']) ?>">
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
                        <?php if ($jaComprouNoLote): ?>
                            <button type="button" class="btn-urna w-100" disabled style="height: 80px; font-size: 18px; background: #E2E8F0; color: #64748B; border: 2px solid #CBD5E1; cursor: not-allowed;">
                                <span>Pedido Já Realizado</span>
                            </button>
                        <?php elseif ($esgotado): ?>
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

<!-- Mensagem quando nenhum produto corresponde aos filtros aplicados -->
<div id="msgNenhumFiltro" class="col-12 text-center py-5" style="display: none;">
    <div style="background: #FFFFFF; border-radius: 16px; padding: 40px; border: 2px dashed #CBD5E1;">
        <div style="font-size: 48px; margin-bottom: 12px;">🔍</div>
        <h3 class="headline-lg" style="color: #334155;">Nenhum produto encontrado</h3>
        <p class="body-md text-muted">Tente buscar por outro termo ou selecione a categoria "Todos".</p>
        <button type="button" class="btn btn-outline-primary mt-2" onclick="limparFiltros()">Limpar Filtros</button>
    </div>
</div>

<script>
let categoriaAtiva = 'todos';

function selecionarCategoria(cat, btn) {
    categoriaAtiva = cat;
    document.querySelectorAll('.btn-categoria-filtro').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    filtrarVitrine();
}

function filtrarVitrine() {
    const input = document.getElementById('inputBuscaVitrine');
    const btnLimpar = document.getElementById('btnLimparBusca');
    const termo = (input.value || '').trim().toLowerCase();
    
    if (btnLimpar) {
        btnLimpar.style.display = termo !== '' ? 'block' : 'none';
    }

    const cards = document.querySelectorAll('.card-produto-wrapper');
    let visiveis = 0;

    cards.forEach(card => {
        const catCard = card.getAttribute('data-categoria') || 'outros';
        const nome = card.getAttribute('data-nome') || '';
        const codigo = card.getAttribute('data-codigo') || '';
        const pacote = card.getAttribute('data-pacote') || '';

        const bateuCategoria = (categoriaAtiva === 'todos' || catCard === categoriaAtiva);
        const bateuBusca = (termo === '' || nome.includes(termo) || codigo.includes(termo) || pacote.includes(termo));

        if (bateuCategoria && bateuBusca) {
            card.style.display = 'block';
            visiveis++;
        } else {
            card.style.display = 'none';
        }
    });

    const msgNenhum = document.getElementById('msgNenhumFiltro');
    if (msgNenhum) {
        msgNenhum.style.display = (visiveis === 0 && cards.length > 0) ? 'block' : 'none';
    }
}

function limparBusca() {
    const input = document.getElementById('inputBuscaVitrine');
    if (input) {
        input.value = '';
        input.focus();
    }
    filtrarVitrine();
}

function limparFiltros() {
    limparBusca();
    const btnTodos = document.querySelector('.btn-categoria-filtro[data-categoria="todos"]');
    selecionarCategoria('todos', btnTodos);
}
</script>

<?php require __DIR__ . '/../layouts/lojinha_footer.php'; ?>
