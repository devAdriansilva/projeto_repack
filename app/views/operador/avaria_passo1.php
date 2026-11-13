<?php
declare(strict_types=1);

/**
 * Projeto Repack - Urna Eletrônica (Avaria - Passo 1: Produto por Código SKU e Quantidade)
 * Baseado estritamente em docs/DESIGN.md e docs/FSD.md (Seção 12.1)
 */

require_once __DIR__ . '/../../models/Produto.php';

$produtos = Produto::listarAtivos();

// Produto previamente selecionado (caso esteja em andamento na sessão ou voltando do Passo 2)
if (isset($_GET['novo']) && $_GET['novo'] === '1') {
    unset($_SESSION['avaria_temp']);
}

$produtoPreSelecionadoId = (int)($_SESSION['avaria_temp']['produto_id'] ?? 0);
$quantidadeInicial = (int)($_SESSION['avaria_temp']['quantidade'] ?? 1);
if ($quantidadeInicial < 1) {
    $quantidadeInicial = 1;
}

$produtoPreSelecionado = null;
if ($produtoPreSelecionadoId > 0) {
    foreach ($produtos as $p) {
        if ((int)$p['id'] === $produtoPreSelecionadoId) {
            $produtoPreSelecionado = $p;
            break;
        }
    }
}

// Mapeia array seguro para JS em memória
$produtosJson = array_map(function($p) {
    return [
        'id' => (int)$p['id'],
        'codigo' => (string)$p['codigo'],
        'nome' => (string)$p['nome'],
        'embalagem' => (string)($p['pacote_padrao'] ?? ''),
        'foto_url' => !empty($p['foto_url']) ? (APP_URL . $p['foto_url']) : null
    ];
}, $produtos);

$pageTitle = "Registrar B.O. - Digitar Código";
require_once __DIR__ . '/../layouts/header.php';
?>

<div style="text-align: center; margin-bottom: 20px;">
    <h1 class="headline-lg" style="margin: 0; font-size: 48px; font-weight: 900; color: #0F172A; letter-spacing: 0.5px;">
        REGISTRAR B.O.
    </h1>
</div>

<form action="<?= APP_URL ?>/operador/avaria/passo2" method="POST" id="formAvariaPasso1" autocomplete="off" onsubmit="return validarEnvio(event)">
    <input type="hidden" name="produto_id" id="inputProdutoId" value="<?= $produtoPreSelecionado['id'] ?? '' ?>">
    <input type="hidden" name="quantidade" id="inputQuantidade" value="<?= $quantidadeInicial ?>">

    <div class="sku-entry-container">
        
        <!-- Campo de Código Gigante com Teclado Numérico Nativo -->
        <label for="inputCodigoSku" style="display: block; font-size: 42px; font-weight: 900; color: #0F172A; text-transform: uppercase; margin-bottom: 16px; text-align: center; letter-spacing: 0.5px;">
            CÓDIGO:
        </label>
        
        <div class="sku-input-wrapper">
            <input type="text" 
                   id="inputCodigoSku" 
                   class="input-sku-gigante <?= $produtoPreSelecionado ? 'sucesso-encontrado' : '' ?>" 
                   inputmode="numeric" 
                   pattern="[0-9]*"
                   placeholder="9068" 
                   value="<?= sanitize_html($produtoPreSelecionado['codigo'] ?? '') ?>"
                   autocomplete="off"
                   autofocus
                   oninput="processarEntradaSku(this.value)">
            <button type="button" 
                    id="btnLimparSku" 
                    class="btn-limpar-sku" 
                    style="<?= $produtoPreSelecionado ? 'display: flex;' : 'display: none;' ?>" 
                    onclick="limparSku()" 
                    aria-label="Limpar código">✕</button>
        </div>

        <!-- Card de Confirmação Imediata (Verde de Alto Contraste) -->
        <div class="card-confirmacao-sku" id="cardConfirmacaoSku" style="<?= $produtoPreSelecionado ? 'display: block;' : 'display: none;' ?>">
            <div class="badge-status-ok">
                <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
                <span>PRODUTO IDENTIFICADO</span>
            </div>

            <!-- Foto Destaque do Produto (Monumental para Acessibilidade) -->
            <div id="wrapperFotoProduto" style="text-align: center; margin: 18px 0 16px 0;">
                <img id="imgFotoProduto" 
                     src="<?= !empty($produtoPreSelecionado['foto_url']) ? (APP_URL . sanitize_html($produtoPreSelecionado['foto_url'])) : '' ?>" 
                     alt="Foto" 
                     style="width: 100%; max-width: 400px; height: 380px; object-fit: contain; border-radius: 28px; background: #FFFFFF; border: 5px solid #059669; box-shadow: 0 10px 28px rgba(5,150,105,0.25); padding: 8px; <?= empty($produtoPreSelecionado['foto_url']) ? 'display: none;' : 'display: inline-block;' ?>">
            </div>

            <div class="nome-produto-destaque" id="displayNomeProduto">
                <?= sanitize_html($produtoPreSelecionado['nome'] ?? '') ?>
            </div>
            <div class="detalhes-produto">
                <span class="badge-sku" id="displaySkuBadge">SKU <?= sanitize_html($produtoPreSelecionado['codigo'] ?? '') ?></span>
                <span class="badge-embalagem" id="displayEmbalagem">
                    <?= sanitize_html($produtoPreSelecionado['pacote_padrao'] ?? '') ?>
                </span>
            </div>
        </div>

        <!-- Alerta de Código Não Encontrado -->
        <div class="card-nao-encontrado" id="cardNaoEncontrado" style="display: none;">
            ⚠️ Nenhum produto cadastrado com o código <strong id="codigoNaoEncontrado"></strong>.<br>
            <span style="font-size: 18px; font-weight: 600;">Confira os dígitos na etiqueta da embalagem.</span>
        </div>

        <!-- Lista Rápida de Sugestões (Quando o operador digita texto como "Skol" ou "Brahma") -->
        <div id="wrapperSugestoes" style="display: none;">
            <div style="font-size: 16px; font-weight: 800; color: #0F172A; text-transform: uppercase; margin: 12px 0 6px 0;">
                Selecione o produto correspondente:
            </div>
            <div class="lista-sugestoes-sku" id="listaSugestoes"></div>
        </div>

    </div>

    <!-- Seletor Numérico Gigante -->
    <div class="urna-card" style="text-align: center; width: 100%; margin: 0 0 24px 0; padding: 30px 24px; border-radius: 32px; border: 7px solid #0F172A; background: #FFFFFF; box-shadow: 0 8px 24px rgba(0,0,0,0.12);">
        <label class="body-lg" style="font-weight: 900; display: block; margin-bottom: 22px; font-size: 42px; color: #0F172A; text-transform: uppercase; letter-spacing: 0.5px;">
            QUANTIDADE:
        </label>
        <div class="seletor-numerico-wrapper" style="justify-content: center; gap: 26px; margin: 0;">
            <button type="button" class="btn-counter" onclick="alterarQuantidade(-1)" aria-label="Diminuir quantidade">−</button>
            <input type="number" 
                   id="displayQtd" 
                   class="counter-display" 
                   inputmode="numeric" 
                   pattern="[0-9]*" 
                   min="1" 
                   autocomplete="off" 
                   value="<?= $quantidadeInicial ?>" 
                   onfocus="this.select()" 
                   oninput="atualizarQuantidadeDigitada(this.value)" 
                   onblur="validarQuantidadeBlur()" 
                   aria-label="Quantidade avariada">
            <button type="button" class="btn-counter" onclick="alterarQuantidade(1)" aria-label="Aumentar quantidade">+</button>
        </div>
    </div>

    <!-- Alerta Flutuante se tentar avançar sem produto -->
    <div id="alertaSemProduto" style="display: none; width: 100%; margin: 0 0 20px 0; background: #FEF2F2; color: #991B1B; border: 4.5px solid #EF4444; border-radius: 22px; padding: 22px; font-weight: 900; text-align: center; font-size: 24px;">
        ⚠️ DIGITE UM CÓDIGO VÁLIDO ANTES DE AVANÇAR!
    </div>

    <!-- Botões de Ação do Rodapé (Grandes Alvos 140px de Alto Contraste) -->
    <div class="dupla-confirmacao-rodape" style="width: 100%; margin: 26px 0 0 0; gap: 20px;">
        <a href="<?= APP_URL ?>/operador?limpar_bo=1" onclick="cancelarRascunhoBO()" class="btn-urna btn-urna-warning" style="min-height: 140px; font-size: 46px; font-weight: 900; border-radius: 36px; text-decoration: none; display: flex; align-items: center; justify-content: center;">
            CANCELAR
        </a>
        <button type="submit" class="btn-urna btn-urna-primary" id="btnAvancar" style="min-height: 140px; font-size: 46px; font-weight: 900; border-radius: 36px;">
            PRÓXIMO ➔
        </button>
    </div>
</form>

<script>
const PRODUTOS = <?= json_encode($produtosJson, JSON_UNESCAPED_UNICODE) ?>;
let produtoAtivo = <?= $produtoPreSelecionado ? json_encode([
    'id' => (int)$produtoPreSelecionado['id'],
    'codigo' => (string)$produtoPreSelecionado['codigo'],
    'nome' => (string)$produtoPreSelecionado['nome'],
    'embalagem' => (string)($produtoPreSelecionado['pacote_padrao'] ?? '')
], JSON_UNESCAPED_UNICODE) : 'null' ?>;

let quantidadeAtual = <?= $quantidadeInicial ?>;

function persistirBoLocalStorage() {
    if (produtoAtivo && produtoAtivo.id) {
        localStorage.setItem('repack_bo_ativo', JSON.stringify({
            produto_id: produtoAtivo.id,
            produto_codigo: produtoAtivo.codigo,
            produto_nome: produtoAtivo.nome,
            produto_embalagem: produtoAtivo.embalagem || '',
            quantidade: quantidadeAtual
        }));
    }
}

function cancelarRascunhoBO() {
    try {
        localStorage.removeItem('repack_bo_ativo');
    } catch(e) {}
}

// Restauração de rascunho persistido
window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('novo') === '1') {
        cancelarRascunhoBO();
        return;
    }

    if (!produtoAtivo) {
        const salvoStr = localStorage.getItem('repack_bo_ativo');
        if (salvoStr) {
            try {
                const salvo = JSON.parse(salvoStr);
                if (salvo && salvo.produto_id) {
                    const match = PRODUTOS.find(p => Number(p.id) === Number(salvo.produto_id));
                    if (match) {
                        selecionarProdutoConfirmado(match);
                        if (salvo.quantidade && Number(salvo.quantidade) >= 1) {
                            quantidadeAtual = Number(salvo.quantidade);
                            document.getElementById('displayQtd').value = quantidadeAtual;
                            document.getElementById('inputQuantidade').value = quantidadeAtual;
                        }
                    }
                }
            } catch(e) {}
        }
    } else {
        persistirBoLocalStorage();
    }
});

function processarEntradaSku(valor) {
    const termo = valor.trim().toLowerCase();
    const btnLimpar = document.getElementById('btnLimparSku');
    const input = document.getElementById('inputCodigoSku');
    const cardOk = document.getElementById('cardConfirmacaoSku');
    const cardErro = document.getElementById('cardNaoEncontrado');
    const wrapperSugestoes = document.getElementById('wrapperSugestoes');
    const listaSugestoes = document.getElementById('listaSugestoes');
    const alerta = document.getElementById('alertaSemProduto');

    if (alerta) alerta.style.display = 'none';
    btnLimpar.style.display = termo ? 'flex' : 'none';

    if (!termo) {
        desmarcarProduto();
        cardOk.style.display = 'none';
        cardErro.style.display = 'none';
        wrapperSugestoes.style.display = 'none';
        input.classList.remove('sucesso-encontrado');
        return;
    }

    // 1. Busca exata por código SKU
    const matchExato = PRODUTOS.find(p => p.codigo.toLowerCase() === termo);
    if (matchExato) {
        selecionarProdutoConfirmado(matchExato);
        cardErro.style.display = 'none';
        wrapperSugestoes.style.display = 'none';
        return;
    }

    // Se é puramente numérico e tem 3+ dígitos, tenta prefixo de SKU
    const apenasNumeros = /^\d+$/.test(termo);
    if (apenasNumeros) {
        const matchesSku = PRODUTOS.filter(p => p.codigo.startsWith(termo));
        if (matchesSku.length === 1) {
            selecionarProdutoConfirmado(matchesSku[0]);
            cardErro.style.display = 'none';
            wrapperSugestoes.style.display = 'none';
            return;
        } else if (matchesSku.length > 1 && matchesSku.length <= 5) {
            desmarcarProduto();
            cardOk.style.display = 'none';
            cardErro.style.display = 'none';
            exibirSugestoes(matchesSku);
            return;
        } else if (termo.length >= 4) {
            // Digitou 4+ números e não bateu com nenhum SKU
            desmarcarProduto();
            cardOk.style.display = 'none';
            wrapperSugestoes.style.display = 'none';
            document.getElementById('codigoNaoEncontrado').innerText = valor;
            cardErro.style.display = 'block';
            input.classList.remove('sucesso-encontrado');
            return;
        }
    }

    // 2. Busca por texto (Nome do produto)
    if (!apenasNumeros && termo.length >= 2) {
        const matchesNome = PRODUTOS.filter(p => p.nome.toLowerCase().includes(termo)).slice(0, 6);
        if (matchesNome.length > 0) {
            desmarcarProduto();
            cardOk.style.display = 'none';
            cardErro.style.display = 'none';
            exibirSugestoes(matchesNome);
            return;
        } else {
            desmarcarProduto();
            cardOk.style.display = 'none';
            wrapperSugestoes.style.display = 'none';
            document.getElementById('codigoNaoEncontrado').innerText = valor;
            cardErro.style.display = 'block';
            input.classList.remove('sucesso-encontrado');
            return;
        }
    }

    // Estado neutro digitando
    desmarcarProduto();
    cardOk.style.display = 'none';
    cardErro.style.display = 'none';
    wrapperSugestoes.style.display = 'none';
    input.classList.remove('sucesso-encontrado');
}

function selecionarProdutoConfirmado(p) {
    produtoAtivo = p;
    document.getElementById('inputProdutoId').value = p.id;
    document.getElementById('displayNomeProduto').innerText = p.nome;
    document.getElementById('displaySkuBadge').innerText = 'SKU ' + p.codigo;
    document.getElementById('displayEmbalagem').innerText = p.embalagem || 'Padrão';
    
    const imgFoto = document.getElementById('imgFotoProduto');
    if (imgFoto) {
        if (p.foto_url) {
            imgFoto.src = p.foto_url;
            imgFoto.style.display = 'inline-block';
        } else {
            imgFoto.src = '';
            imgFoto.style.display = 'none';
        }
    }

    const cardOk = document.getElementById('cardConfirmacaoSku');
    cardOk.style.display = 'block';

    const input = document.getElementById('inputCodigoSku');
    input.classList.add('sucesso-encontrado');
    input.value = p.codigo;
    document.getElementById('btnLimparSku').style.display = 'flex';
    persistirBoLocalStorage();
}

function desmarcarProduto() {
    produtoAtivo = null;
    document.getElementById('inputProdutoId').value = '';
    const imgFoto = document.getElementById('imgFotoProduto');
    if (imgFoto) {
        imgFoto.src = '';
        imgFoto.style.display = 'none';
    }
    try { localStorage.removeItem('repack_bo_ativo'); } catch(e) {}
}

function exibirSugestoes(lista) {
    const wrapper = document.getElementById('wrapperSugestoes');
    const container = document.getElementById('listaSugestoes');
    container.innerHTML = '';

    lista.forEach(p => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'item-sugestao-sku';
        btn.onclick = () => {
            selecionarProdutoConfirmado(p);
            wrapper.style.display = 'none';
        };

        const fotoHtml = p.foto_url 
            ? `<img src="${p.foto_url}" style="width: 44px; height: 44px; object-fit: contain; border-radius: 8px; border: 1.5px solid #CBD5E1; background: #fff; margin-right: 12px; flex-shrink: 0;">` 
            : `<div style="width: 44px; height: 44px; border-radius: 8px; background: #E2E8F0; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-right: 12px; flex-shrink: 0;">🏷️</div>`;

        btn.innerHTML = `
            <div style="display: flex; align-items: center; text-align: left;">
                ${fotoHtml}
                <div>
                    <span class="sku-badge">SKU ${escapeHtml(p.codigo)}</span>
                    <div style="font-weight: 800; font-size: 16px; color: var(--on-surface); line-height: 1.2;">
                        ${escapeHtml(p.nome)}
                    </div>
                    <div style="font-size: 13px; color: var(--on-surface-muted);">
                        ${escapeHtml(p.embalagem || '')}
                    </div>
                </div>
            </div>
            <div style="color: #2563EB; font-weight: 900; font-size: 20px;">➔</div>
        `;
        container.appendChild(btn);
    });

    wrapper.style.display = 'block';
}

function limparSku() {
    const input = document.getElementById('inputCodigoSku');
    input.value = '';
    processarEntradaSku('');
    input.focus();
}

function alterarQuantidade(delta) {
    let atual = parseInt(document.getElementById('displayQtd').value, 10) || 1;
    quantidadeAtual = Math.max(1, atual + delta);
    document.getElementById('displayQtd').value = quantidadeAtual;
    document.getElementById('inputQuantidade').value = quantidadeAtual;
    persistirBoLocalStorage();
}

function atualizarQuantidadeDigitada(valor) {
    let num = parseInt(valor, 10);
    if (!isNaN(num) && num > 0) {
        quantidadeAtual = num;
        document.getElementById('inputQuantidade').value = quantidadeAtual;
        persistirBoLocalStorage();
    }
}

function validarQuantidadeBlur() {
    const input = document.getElementById('displayQtd');
    let num = parseInt(input.value, 10);
    if (isNaN(num) || num < 1) {
        num = 1;
    }
    quantidadeAtual = num;
    input.value = quantidadeAtual;
    document.getElementById('inputQuantidade').value = quantidadeAtual;
    persistirBoLocalStorage();
}

function validarEnvio(e) {
    const prodId = document.getElementById('inputProdutoId').value;
    if (!prodId || !produtoAtivo) {
        e.preventDefault();
        const alerta = document.getElementById('alertaSemProduto');
        if (alerta) alerta.style.display = 'block';
        const input = document.getElementById('inputCodigoSku');
        input.focus();
        if (navigator.vibrate) navigator.vibrate(150);
        return false;
    }
    persistirBoLocalStorage();
    return true;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
