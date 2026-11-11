<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    exit(1);
}

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/EstoqueLojinha.php';

echo "=== TESTE DE INVENTÁRIO COM SOMA PARCIAL ===\n";

$pdo = Database::getConnection();
$produto = $pdo->query("SELECT id, nome FROM produtos WHERE ativo = 1 LIMIT 1")->fetch();
assert(!empty($produto), "Deve existir ao menos um produto ativo");

$produtoId = (int)$produto['id'];
$usuarioId = 1; // Gestor admin

// 1. Testa inventário por Substituição Direta
echo "1. Realizando inventário com substituição para 35 un...\n";
$ok1 = EstoqueLojinha::realizarInventario($produtoId, 35, 'substituicao', $usuarioId, 'Teste inventario total', '12 + 12 + 11');
assert($ok1, "Falha ao gravar inventario por substituicao");

$saldo1 = EstoqueLojinha::obterPorProdutoId($produtoId);
assert((int)$saldo1['quantidade_total'] === 35, "Saldo deveria ser 35");
echo " -> Saldo atualizado com sucesso: {$saldo1['quantidade_total']} un.\n";

// 2. Testa inventário com Incremento Parcial (+15 un)
echo "2. Realizando incremento parcial de +15 un...\n";
$ok2 = EstoqueLojinha::realizarInventario($produtoId, 15, 'incremento', $usuarioId, 'Teste incremento parcial', '6 + 6 + 3');
assert($ok2, "Falha ao gravar incremento parcial");

$saldo2 = EstoqueLojinha::obterPorProdutoId($produtoId);
assert((int)$saldo2['quantidade_total'] === 50, "Saldo deveria ser 35 + 15 = 50");
echo " -> Saldo incrementado com sucesso: {$saldo2['quantidade_total']} un.\n";

// 3. Testa histórico gravado
$historico = EstoqueLojinha::listarHistoricoInventarios($produtoId, 5);
assert(count($historico) >= 2, "Devem existir ao menos 2 registros no historico");
echo " -> Historico auditado gravado com sucesso! Total de registros: " . count($historico) . "\n";

echo "=== TESTE DE INVENTÁRIO CONCLUÍDO COM 100% DE SUCESSO! ===\n";
