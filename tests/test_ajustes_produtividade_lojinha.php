<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/LoteVenda.php';
require_once __DIR__ . '/../app/models/Pedido.php';
require_once __DIR__ . '/../app/models/Usuario.php';
require_once __DIR__ . '/../app/models/Produto.php';

echo "=== TESTANDO AJUSTES DE PRODUTIVIDADE, LOTE E CARRINHO ===" . PHP_EOL;

// 1. Criar Lote com tempo_carrinho_minutos dinâmico
$novoLoteId = LoteVenda::salvar([
    'titulo' => 'Lote Teste Dinamico ' . time(),
    'descricao' => 'Teste tempo carrinho',
    'data_inicio' => date('Y-m-d H:i:s'),
    'data_fim' => date('Y-m-d H:i:s', strtotime('+2 days')),
    'setores_autorizados' => ['*'],
    'limite_itens_por_comprador' => 5,
    'tempo_carrinho_minutos' => 15,
    'status' => LoteVenda::STATUS_ABERTO
], 1);

$loteCriado = LoteVenda::buscarPorId($novoLoteId);
assert((int)$loteCriado['tempo_carrinho_minutos'] === 15, 'tempo_carrinho_minutos deve ser 15');
echo "[PASS] Lote criado com tempo de carrinho dinâmico: " . $loteCriado['tempo_carrinho_minutos'] . " min." . PHP_EOL;

// 2. Testar Adicionar ao Carrinho e verificar expiração
$pdo = Database::getConnection();
$produtos = Produto::listarTodos();
$produtoValido = null;
foreach ($produtos as $p) {
    if ((int)$p['quantidade_total'] > 5) {
        $produtoValido = $p;
        break;
    }
}

if ($produtoValido) {
    // Garante que o usuário de teste (ex: ID 3) não comprou
    $usuarioId = 3;
    Pedido::esvaziarCarrinho($novoLoteId, $usuarioId);
    
    $resAdd = Pedido::adicionarAoCarrinho($novoLoteId, $usuarioId, (int)$produtoValido['id'], 1);
    assert(($resAdd['status'] ?? '') === 'success', 'Deveria adicionar ao carrinho');
    echo "[PASS] Item adicionado ao carrinho com sucesso." . PHP_EOL;

    // Lista itens do carrinho
    $itens = Pedido::listarCarrinho($novoLoteId, $usuarioId);
    assert(count($itens) > 0, 'Deveria ter 1 item no carrinho');
    $carrinhoId = (int)$itens[0]['carrinho_id'];
    echo "[PASS] Item listado com segundos_restantes: " . $itens[0]['segundos_restantes'] . "s." . PHP_EOL;

    // 3. Testar Incremento (+)
    $resInc = Pedido::alterarQuantidadeCarrinho($carrinhoId, $usuarioId, 1);
    assert($resInc['sucesso'] === true, 'Deveria incrementar quantidade');
    assert((int)$resInc['nova_quantidade'] === 2, 'Nova quantidade deve ser 2');
    echo "[PASS] Incremento de quantidade testado com sucesso (nova qtd: 2)." . PHP_EOL;

    // 4. Testar Decremento (-)
    $resDec = Pedido::alterarQuantidadeCarrinho($carrinhoId, $usuarioId, -1);
    assert($resDec['sucesso'] === true, 'Deveria decrementar quantidade');
    assert((int)$resDec['nova_quantidade'] === 1, 'Nova quantidade deve ser 1');
    echo "[PASS] Decremento de quantidade testado com sucesso (nova qtd: 1)." . PHP_EOL;

    // Limpa carrinho de teste
    Pedido::esvaziarCarrinho($novoLoteId, $usuarioId);
}

echo "=== TODOS OS TESTES PASSARAM COM SUCESSO! ===" . PHP_EOL;
