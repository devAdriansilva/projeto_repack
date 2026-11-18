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

<div style="text-align: center; margin-bottom: 12px;">
    <h1 style="margin: 0; font-size: 22px; line-height: 1.2; font-weight: 900; color: #0F172A; letter-spacing: 0.5px;">
        REGISTRAR B.O.
    </h1>
    <span style="display: inline-block; font-size: 13px; font-weight: 800; color: #DC2626; background: #FEF2F2; border: 2px solid #EF4444; padding: 3px 12px; border-radius: 9999px; margin-top: 4px;">
        ⚠️ LANÇAMENTO DE AVARIA
    </span>
</div>

<form action="<?= APP_URL ?>/operador/avaria/passo2" method="POST" id="formAvariaPasso1" autocomplete="off" onsubmit="return validarEnvio(event)">
    <input type="hidden" name="produto_id" id="inputProdutoId" value="<?= $produtoPreSelecionado['id'] ?? '' ?>">
    <input type="hidden" name="quantidade" id="inputQuantidade" value="<?= $quantidadeInicial ?>">

    <div class="sku-entry-container" style="margin-bottom: 12px;">
        
        <!-- Campo de Código Gigante com Teclado Numérico Nativo -->
        <label for="inputCodigoSku" style="display: block; font-size: 16px; font-weight: 900; color: #0F172A; text-transform: uppercase; margin-bottom: 8px; text-align: center; letter-spacing: 0.5px;">
            CÓDIGO (SKU):
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
                   style="height: 60px; min-height: 60px; font-size: 32px; font-weight: 900; border-radius: 14px; border: 2.5px solid #0F172A; padding: 4px 48px 4px 12px; letter-spacing: 2px; text-align: center;"
                   oninput="processarEntradaSku(this.value)">
            <button type="button" 
                    id="btnLimparSku" 
                    class="btn-limpar-sku" 
                    style="<?= $produtoPreSelecionado ? 'display: flex;' : 'display: none;' ?> width: 36px; height: 36px; font-size: 18px; right: 8px;" 
                    onclick="limparSku()" 
                    aria-label="Limpar código">✕</button>
        </div>

        <!-- Card de Confirmação Imediata (Verde de Alto Contraste) -->
        <div class="card-confirmacao-sku" id="cardConfirmacaoSku" style="<?= $produtoPreSelecionado ? 'display: block;' : 'display: none;' ?> border: 2.5px solid #10B981; border-radius: 14px; padding: 10px; text-align: center; background: #F0FDF4; margin: 12px 0;">
            <div class="badge-status-ok" style="font-size: 15px; font-weight: 900; color: #065F46; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 6px; padding: 4px 12px; border-radius: 8px; background: transparent;">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
                <span>PRODUTO IDENTIFICADO</span>
            </div>

            <!-- Foto Destaque do Produto (Harmonizada para Mobile) -->
            <div id="wrapperFotoProduto" style="text-align: center; margin: 6px 0 8px 0;">
                <img id="imgFotoProduto" 
                     src="<?= !empty($produtoPreSelecionado['foto_url']) ? (APP_URL . sanitize_html($produtoPreSelecionado['foto_url'])) : '' ?>" 
                     alt="Foto" 
                     style="width: 100%; max-width: 160px; height: 120px; object-fit: contain; border-radius: 12px; background: #FFFFFF; border: 2px solid #059669; box-shadow: 0 3px 10px rgba(5,150,105,0.18); padding: 3px; <?= empty($produtoPreSelecionado['foto_url']) ? 'display: none;' : 'display: inline-block;' ?>">
            </div>

            <div class="nome-produto-destaque" id="displayNomeProduto" style="font-size: 18px; font-weight: 900; color: #0F172A; line-height: 1.2; word-break: break-word; margin: 6px 0;">
                <?= sanitize_html($produtoPreSelecionado['nome'] ?? '') ?>
            </div>
            <div class="detalhes-produto" style="margin-top: 6px; display: flex; justify-content: center; gap: 6px; flex-wrap: wrap;">
                <span class="badge-sku" id="displaySkuBadge" style="font-size: 13px; font-weight: 900; background: #0F172A; color: #FFFFFF; padding: 2px 8px; border-radius: 6px;">SKU <?= sanitize_html($produtoPreSelecionado['codigo'] ?? '') ?></span>
                <span class="badge-embalagem" id="displayEmbalagem" style="font-size: 13px; font-weight: 800; background: #E2E8F0; color: #334155; padding: 2px 8px; border-radius: 6px;">
                    <?= sanitize_html($produtoPreSelecionado['pacote_padrao'] ?? '') ?>
                </span>
            </div>
        </div>

        <!-- Alerta de Código Não Encontrado -->
        <div class="card-nao-encontrado" id="cardNaoEncontrado" style="display: none; background: #FEF2F2; border: 2.5px solid #EF4444; border-radius: 14px; padding: 12px; margin-top: 10px; text-align: center;">
            <div style="font-size: 15px; font-weight: 900; color: #991B1B;">
                ⚠️ Código <strong id="codigoNaoEncontrado"></strong> não cadastrado!
            </div>
            <div style="font-size: 13px; font-weight: 700; color: #64748B; margin-top: 2px;">
                Confira os dígitos na etiqueta da embalagem.
            </div>
        </div>

        <!-- Lista Rápida de Sugestões (Quando o operador digita texto como "Skol" ou "Brahma") -->
        <div id="wrapperSugestoes" style="display: none; margin-top: 10px; width: 100%;">
            <div style="font-size: 14px; font-weight: 900; color: #475569; text-transform: uppercase; margin-bottom: 4px;">
                Selecione o produto correspondente:
            </div>
            <div class="lista-sugestoes-sku" id="listaSugestoes"></div>
        </div>

    </div>

    <!-- Seletor Numérico Harmonizado -->
    <div class="urna-card" style="text-align: center; width: 100%; margin: 0 0 14px 0; padding: 14px 12px; border-radius: 16px; border: 2.5px solid #0F172A; background: #FFFFFF; box-shadow: 0 3px 10px rgba(0,0,0,0.06);">
        <label style="font-weight: 900; display: block; margin-bottom: 8px; font-size: 16px; color: #0F172A; text-transform: uppercase; letter-spacing: 0.5px;">
            QUANTIDADE:
        </label>
        <div style="display: flex; flex-direction: row !important; flex-wrap: nowrap !important; align-items: center; justify-content: center; gap: 8px; width: 100%; margin: 0 auto; max-width: 380px; box-sizing: border-box;">
            <button type="button" onclick="alterarQuantidade(-1)" style="flex: 0 0 auto; width: 58px; height: 58px; border-radius: 14px; font-size: 32px; font-weight: 900; display: flex; align-items: center; justify-content: center; border: 2.5px solid #0F172A; background-color: #FFFFFF; color: #0F172A; cursor: pointer; touch-action: manipulation; box-shadow: 0 3px 8px rgba(0,0,0,0.08); padding: 0;" aria-label="Diminuir quantidade">−</button>
            <input type="number" 
                   id="displayQtd" 
                   inputmode="numeric" 
                   pattern="[0-9]*" 
                   min="1" 
                   autocomplete="off" 
                   value="<?= $quantidadeInicial ?>" 
                   onfocus="this.select()" 
                   oninput="atualizarQuantidadeDigitada(this.value)" 
                   onblur="validarQuantidadeBlur()" 
                   style="flex: 1 1 auto; min-width: 65px; max-width: 110px; height: 58px; background-color: #FFFFFF; border: 2.5px solid #0F172A; border-radius: 14px; font-size: 32px; font-weight: 900; color: #0F172A; text-align: center; box-sizing: border-box; padding: 0 6px; outline: none; touch-action: manipulation; box-shadow: 0 3px 8px rgba(0,0,0,0.08);"
                   aria-label="Quantidade avariada">
            <button type="button" onclick="alterarQuantidade(1)" style="flex: 0 0 auto; width: 58px; height: 58px; border-radius: 14px; font-size: 32px; font-weight: 900; display: flex; align-items: center; justify-content: center; border: 2.5px solid #0F172A; background-color: #FFFFFF; color: #0F172A; cursor: pointer; touch-action: manipulation; box-shadow: 0 3px 8px rgba(0,0,0,0.08); padding: 0;" aria-label="Aumentar quantidade">+</button>
        </div>
    </div>

    <!-- Alerta Flutuante se tentar avançar sem produto -->
    <div id="alertaSemProduto" style="display: none; width: 100%; margin: 0 0 14px 0; background: #FEF2F2; color: #991B1B; border: 2.5px solid #EF4444; border-radius: 14px; padding: 12px; font-weight: 900; text-align: center; font-size: 15px;">
        ⚠️ DIGITE UM CÓDIGO VÁLIDO ANTES DE AVANÇAR!
    </div>

    <!-- Botões de Ação do Rodapé (Harmonizado 66px) -->
    <div class="dupla-confirmacao-rodape" style="width: 100%; margin: 14px 0 0 0; gap: 10px;">
        <a href="<?= APP_URL ?>/operador?limpar_bo=1" onclick="cancelarRascunhoBO()" class="btn-urna btn-urna-warning" style="min-height: 66px; font-size: 20px; font-weight: 900; border-radius: 16px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink: 0;">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            <span>CANCELAR</span>
        </a>
        <button type="submit" class="btn-urna btn-urna-primary" id="btnAvancar" style="min-height: 66px; font-size: 20px; font-weight: 900; border-radius: 16px; display: flex; align-items: center; justify-content: center; gap: 8px;">
            <span>PRÓXIMO</span>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink: 0;">
                <line x1="5" y1="12" x2="19" y2="12"></line>
                <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
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

    // 1. Busca exata por código SKU (só auto-seleciona se o código for 100% completo e idêntico)
    const matchExato = PRODUTOS.find(p => p.codigo.toLowerCase() === termo);
    if (matchExato) {
        selecionarProdutoConfirmado(matchExato, false);
        cardErro.style.display = 'none';
        wrapperSugestoes.style.display = 'none';
        return;
    }

    // 2. Busca parcial por prefixo de SKU (JAMAIS auto-completa o input para não sequestrar a digitação)
    const apenasNumeros = /^\d+$/.test(termo);
    if (apenasNumeros) {
        desmarcarProduto();
        cardOk.style.display = 'none';
        input.classList.remove('sucesso-encontrado');

        const matchesSku = PRODUTOS.filter(p => p.codigo.startsWith(termo));
        if (matchesSku.length > 0 && matchesSku.length <= 6) {
            cardErro.style.display = 'none';
            exibirSugestoes(matchesSku);
            return;
        } else if (matchesSku.length === 0 && termo.length >= 4) {
            // Digitou 4+ números e não bate com nenhum SKU nem prefixo
            wrapperSugestoes.style.display = 'none';
            document.getElementById('codigoNaoEncontrado').innerText = valor;
            cardErro.style.display = 'block';
            return;
        } else {
            wrapperSugestoes.style.display = 'none';
            cardErro.style.display = 'none';
            return;
        }
    }

    // 3. Busca por texto (Nome do produto)
    if (!apenasNumeros && termo.length >= 2) {
        desmarcarProduto();
        cardOk.style.display = 'none';
        input.classList.remove('sucesso-encontrado');

        const matchesNome = PRODUTOS.filter(p => p.nome.toLowerCase().includes(termo)).slice(0, 6);
        if (matchesNome.length > 0) {
            cardErro.style.display = 'none';
            exibirSugestoes(matchesNome);
            return;
        } else {
            wrapperSugestoes.style.display = 'none';
            document.getElementById('codigoNaoEncontrado').innerText = valor;
            cardErro.style.display = 'block';
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

function selecionarProdutoConfirmado(p, atualizarInput = true) {
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
    if (atualizarInput) {
        input.value = p.codigo;
    }
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
            selecionarProdutoConfirmado(p, true);
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
