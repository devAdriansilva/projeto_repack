<?php
declare(strict_types=1);

/**
 * Teste Unitário e de Integração: Substituição de Itens em Pedidos
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Pedido.php';
require_once __DIR__ . '/../app/models/EstoqueLojinha.php';
require_once __DIR__ . '/../app/models/Produto.php';

echo "=== TESTE DE SUBSTITUIÇÃO DE ITENS EM PEDIDOS ===" . PHP_EOL;

$pdo = Database::getConnection();

// 1. Obtém dois produtos ativos
$produtos = Produto::listarAtivos();
if (count($produtos) < 2) {
    echo "[FAIL] É necessário ter ao menos 2 produtos cadastrados." . PHP_EOL;
    exit(1);
}

$prodA = $produtos[0];
$prodB = $produtos[1];

// Garante saldo no produto B
EstoqueLojinha::incrementarSaldo((int)$prodB['id'], 10);

$saldoA_antes = EstoqueLojinha::obterSaldoDisponivel((int)$prodA['id']);
$saldoB_antes = EstoqueLojinha::obterSaldoDisponivel((int)$prodB['id']);

// 2. Cria um pedido com o Produto A
$codigoPedido = 'TEST-SUB-' . uniqid();
$stmtPed = $pdo->prepare("
    INSERT INTO pedidos (codigo_pedido, lote_id, usuario_id, quantidade_total, status, created_at)
    VALUES (:cod, 1, 1, 1, 'confirmado', NOW())
");
$stmtPed->execute(['cod' => $codigoPedido]);
$pedidoId = (int)$pdo->lastInsertId();

$stmtItem = $pdo->prepare("
    INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, created_at)
    VALUES (:pid, :prid, 1, NOW())
");
$stmtItem->execute(['pid' => $pedidoId, 'prid' => $prodA['id']]);
$itemId = (int)$pdo->lastInsertId();

// 3. Testa substituição simples (devolve A para o estoque e abate B)
$res = Pedido::substituirItem($pedidoId, $itemId, (int)$prodB['id'], 'Teste de divergência de sabor', false, 1);

assert($res['status'] === 'success', "Substituição falhou: " . ($res['message'] ?? ''));
echo "[PASS] Item do pedido substituído com sucesso de A para B." . PHP_EOL;

// Verifica se o item do pedido agora aponta para B
$stmtCheck = $pdo->prepare("SELECT produto_id FROM pedido_itens WHERE id = :id");
$stmtCheck->execute(['id' => $itemId]);
$itemNovo = $stmtCheck->fetch();
assert((int)$itemNovo['produto_id'] === (int)$prodB['id'], "Item do pedido não foi atualizado no banco.");
echo "[PASS] pedido_itens atualizado para o novo produto B." . PHP_EOL;

// Verifica saldo de B (deve ter decrementado em 1)
$saldoB_depois = EstoqueLojinha::obterSaldoDisponivel((int)$prodB['id']);
assert($saldoB_depois === $saldoB_antes - 1, "Saldo de B não foi abatido corretamente.");
echo "[PASS] Saldo de B abatido corretamente no estoque da lojinha." . PHP_EOL;

// Verifica saldo de A (deve ter estornado +1)
$saldoA_depois = EstoqueLojinha::obterSaldoDisponivel((int)$prodA['id']);
assert($saldoA_depois === $saldoA_antes + 1, "Saldo de A não foi estornado corretamente.");
echo "[PASS] Saldo de A estornado corretamente ao estoque da lojinha." . PHP_EOL;

// 4. Limpeza do pedido de teste
$pdo->exec("DELETE FROM pedido_itens WHERE id = {$itemId}");
$pdo->exec("DELETE FROM pedidos WHERE id = {$pedidoId}");

echo "=== TODOS OS TESTES DE SUBSTITUIÇÃO PASSARAM COM SUCESSO! ===" . PHP_EOL;
