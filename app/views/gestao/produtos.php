<?php
declare(strict_types=1);

/**
 * Projeto Repack - View Gestao Produtos com Filtro de Disponibilidade na Lojinha
 * Baseado estritamente em docs/FSD.md (Seção 12.3 e 21) e docs/DESIGN.md
 */

require __DIR__ . '/../layouts/gestao_header.php';

// Contagem rápida de itens disponíveis na loja
$totalDisponiveis = 0;
foreach ($produtos as $p) {
    $disp = max(0, (int)$p['quantidade_total'] - (int)$p['quantidade_reservada_gestao']);
    if ($disp > 0) {
        $totalDisponiveis++;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="headline-lg mb-1">Catálogo de Produtos</h1>
        <p class="body-md text-muted mb-0">Cadastro de bebidas, fotos e acompanhamento de disponibilidade para a vitrine da Lojinha.</p>
    </div>
    <button type="button" class="btn btn-gestao btn-gestao-primary" onclick="abrirModalProduto()">
        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
        Novo Produto
    </button>
</div>

<!-- Barra de Filtros Rápidos e Busca no Catálogo -->
<div class="card-gestao p-3 mb-4" style="background: #FFFFFF;">
    <div class="row g-2 align-items-center justify-content-between">
        <div class="col-12 col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0">🔍</span>
                <input type="text" id="buscaProduto" class="form-control border-start-0" placeholder="Buscar por nome ou código..." oninput="filtrarTabelaProdutos()">
            </div>
        </div>
        <div class="col-12 col-md-7 d-flex flex-wrap justify-content-md-end gap-1">
            <button type="button" class="btn btn-sm btn-outline-secondary active btn-filtro-prod" data-filtro="todos" onclick="definirFiltro('todos', this)">
                Todos (<?= count($produtos) ?>)
            </button>
            <button type="button" class="btn btn-sm btn-outline-success btn-filtro-prod" data-filtro="disponiveis" onclick="definirFiltro('disponiveis', this)">
                🟢 Na Lojinha (<?= $totalDisponiveis ?>)
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary btn-filtro-prod" data-filtro="sem_estoque" onclick="definirFiltro('sem_estoque', this)">
                ⚪ Sem Estoque
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary btn-filtro-prod" data-filtro="ativos" onclick="definirFiltro('ativos', this)">
                Ativos
            </button>
            <button type="button" class="btn btn-sm btn-outline-warning btn-filtro-prod" data-filtro="inativos" onclick="definirFiltro('inativos', this)">
                Inativos
            </button>
        </div>
    </div>
</div>

<div class="card-gestao">
    <div class="table-responsive">
        <table class="table-gestao" id="tabelaProdutos">
            <thead>
                <tr>
                    <th style="width: 70px;">Foto</th>
                    <th>Código</th>
                    <th>Nome do Produto</th>
                    <th>Embalagem Padrão</th>
                    <th>Saldo na Lojinha</th>
                    <th>Status</th>
                    <th style="width: 150px; text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($produtos)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            Nenhum produto cadastrado no catálogo.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($produtos as $p): 
                        $disp = max(0, (int)$p['quantidade_total'] - (int)$p['quantidade_reservada_gestao']);
                    ?>
                        <tr class="linha-produto" 
                            data-ativo="<?= $p['ativo'] ? '1' : '0' ?>"
                            data-saldo="<?= $disp ?>"
                            data-termo="<?= mb_strtolower($p['nome'] . ' ' . $p['codigo'], 'UTF-8') ?>"
                            style="<?= $p['ativo'] ? '' : 'opacity: 0.6; background-color: #F9FAFB;' ?>">
                            <td>
                                <?php if (!empty($p['foto_url'])): ?>
                                    <img src="<?= APP_URL . sanitize_html($p['foto_url']) ?>" alt="Foto" style="width: 48px; height: 48px; object-fit: contain; border-radius: 8px; border: 1px solid var(--gestao-border); background: #FFF;">
                                <?php else: ?>
                                    <div style="width: 48px; height: 48px; background: #E5E7EB; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #9CA3AF; font-size: 20px;">
                                        🍾
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= sanitize_html($p['codigo']) ?></span></td>
                            <td>
                                <strong><?= sanitize_html($p['nome']) ?></strong>
                            </td>
                            <td><?= sanitize_html($p['pacote_padrao']) ?></td>
                            <td>
                                <?php if ($disp > 0): ?>
                                    <span class="badge bg-success" style="font-size: 13px; font-weight: 700;">
                                        <?= $disp ?> un disponível
                                    </span>
                                    <div class="text-muted" style="font-size: 11px;">
                                        Físico: <?= (int)$p['quantidade_total'] ?> | Res: <?= (int)$p['quantidade_reservada_gestao'] ?>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border" style="font-size: 12px;">
                                        Sem estoque
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-status <?= $p['ativo'] ? 'aberto' : 'encerrado' ?>">
                                    <?= $p['ativo'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <button type="button" class="btn btn-sm btn-outline-secondary me-1" 
                                        onclick='editarProduto(<?= json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Editar produto">
                                    ✏️
                                </button>
                                <form action="<?= APP_URL ?>/gestao/produtos/toggle" method="POST" class="d-inline" onsubmit="return confirm('Deseja alternar a ativação deste produto?');">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $p['ativo'] ? 'btn-outline-warning' : 'btn-outline-success' ?>" title="<?= $p['ativo'] ? 'Desativar' : 'Ativar' ?>">
                                        <?= $p['ativo'] ? 'Desativar' : 'Ativar' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de Cadastro / Edição de Produto -->
<div class="modal fade" id="modalProduto" tabindex="-1" aria-labelledby="modalProdutoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
            <div class="modal-header" style="border-bottom: 1px solid var(--gestao-border); padding: 20px 24px;">
                <h5 class="modal-title headline-lg" id="modalProdutoLabel" style="font-size: 20px;">Cadastrar Produto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= APP_URL ?>/gestao/produtos/salvar" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="id" id="prod_id" value="">

                <div class="modal-body" style="padding: 24px;">
                    <div class="mb-3">
                        <label for="prod_codigo" class="form-label fw-bold small text-uppercase">Código do Produto (SKU) *</label>
                        <input type="text" class="form-control" id="prod_codigo" name="codigo" required placeholder="Ex: CERV-600-01" style="height: 48px;">
                    </div>

                    <div class="mb-3">
                        <label for="prod_nome" class="form-label fw-bold small text-uppercase">Nome do Produto *</label>
                        <input type="text" class="form-control" id="prod_nome" name="nome" required placeholder="Ex: Cerveja Pilsen 600ml" style="height: 48px;">
                    </div>

                    <div class="mb-3">
                        <label for="prod_pacote" class="form-label fw-bold small text-uppercase">Embalagem Padrão *</label>
                        <select class="form-select" id="prod_pacote" name="pacote_padrao" style="height: 48px;">
                            <option value="Unidade">Unidade (Garrafa / Lata)</option>
                            <option value="Fardo 6un">Fardo com 6 unidades</option>
                            <option value="Fardo 12un">Fardo com 12 unidades</option>
                            <option value="Caixa 24un">Caixa com 24 unidades</option>
                            <option value="Pacote">Pacote Avulso</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="prod_foto" class="form-label fw-bold small text-uppercase">Foto do Produto (PNG ou JPG, máx 2MB)</label>
                        <input type="file" class="form-control" id="prod_foto" name="foto" accept="image/png,image/jpeg,image/webp">
                        <div id="previewFotoAtual" class="mt-2" style="display: none;">
                            <span class="small text-muted d-block mb-1">Foto atual cadastrada:</span>
                            <img id="imgFotoAtual" src="" alt="Foto Atual" style="width: 80px; height: 80px; object-fit: contain; border-radius: 8px; border: 1px solid var(--gestao-border); background: #FFF;">
                        </div>
                    </div>

                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="prod_ativo" name="ativo" value="1" checked>
                        <label class="form-check-label fw-bold" for="prod_ativo">Produto Ativo no Catálogo</label>
                    </div>
                </div>

                <div class="modal-footer" style="border-top: 1px solid var(--gestao-border); padding: 16px 24px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-gestao btn-gestao-primary">Salvar Produto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let filtroAtual = 'todos';

function definirFiltro(tipo, btn) {
    filtroAtual = tipo;
    document.querySelectorAll('.btn-filtro-prod').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filtrarTabelaProdutos();
}

function filtrarTabelaProdutos() {
    const termo = (document.getElementById('buscaProduto').value || '').trim().toLowerCase();
    const linhas = document.querySelectorAll('.linha-produto');

    linhas.forEach(tr => {
        const trTermo = tr.getAttribute('data-termo') || '';
        const trAtivo = tr.getAttribute('data-ativo') === '1';
        const trSaldo = parseInt(tr.getAttribute('data-saldo'), 10) || 0;

        let bateTexto = termo === '' || trTermo.includes(termo);
        let bateFiltro = true;

        if (filtroAtual === 'disponiveis') {
            bateFiltro = trSaldo > 0;
        } else if (filtroAtual === 'sem_estoque') {
            bateFiltro = trSaldo <= 0;
        } else if (filtroAtual === 'ativos') {
            bateFiltro = trAtivo;
        } else if (filtroAtual === 'inativos') {
            bateFiltro = !trAtivo;
        }

        if (bateTexto && bateFiltro) {
            tr.style.display = '';
        } else {
            tr.style.display = 'none';
        }
    });
}

function abrirModalProduto() {
    document.getElementById('modalProdutoLabel').innerText = 'Cadastrar Novo Produto';
    document.getElementById('prod_id').value = '';
    document.getElementById('prod_codigo').value = '';
    document.getElementById('prod_nome').value = '';
    document.getElementById('prod_pacote').value = 'Unidade';
    document.getElementById('prod_ativo').checked = true;
    document.getElementById('prod_foto').value = '';
    document.getElementById('previewFotoAtual').style.display = 'none';

    const modal = new bootstrap.Modal(document.getElementById('modalProduto'));
    modal.show();
}

function editarProduto(prod) {
    document.getElementById('modalProdutoLabel').innerText = 'Editar Produto #' + prod.id;
    document.getElementById('prod_id').value = prod.id;
    document.getElementById('prod_codigo').value = prod.codigo;
    document.getElementById('prod_nome').value = prod.nome;
    document.getElementById('prod_pacote').value = prod.pacote_padrao || 'Unidade';
    document.getElementById('prod_ativo').checked = Boolean(Number(prod.ativo));
    document.getElementById('prod_foto').value = '';

    const preview = document.getElementById('previewFotoAtual');
    const img = document.getElementById('imgFotoAtual');
    if (prod.foto_url) {
        img.src = '<?= APP_URL ?>' + prod.foto_url;
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }

    const modal = new bootstrap.Modal(document.getElementById('modalProduto'));
    modal.show();
}
</script>

<?php require __DIR__ . '/../layouts/gestao_footer.php'; ?>
