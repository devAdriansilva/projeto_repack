<?php
declare(strict_types=1);

/**
 * Teste Automatizado de Concorrência e Saldos Dinâmicos na Vitrine da Lojinha
 * Valida a sincronia e decremento mútuo entre múltiplos compradores concorrentes
 * Projeto Repack - MANUT-081
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Usuario.php';
require_once __DIR__ . '/../app/models/Produto.php';
require_once __DIR__ . '/../app/models/LoteVenda.php';
require_once __DIR__ . '/../app/models/Pedido.php';
require_once __DIR__ . '/../app/models/EstoqueLojinha.php';

echo "=== INICIANDO TESTE DE CONCORRÊNCIA E SALDOS DINÂMICOS NA VITRINE ===" . PHP_EOL;

$pdo = Database::getConnection();

// 1. Localiza ou cria Lote de Vendas aberto
$stmtLote = $pdo->query("SELECT id FROM lotes_venda WHERE status = 'aberto' AND data_inicio <= NOW() AND data_fim >= NOW() LIMIT 1");
$loteId = $stmtLote->fetchColumn();

if (!$loteId) {
    // Cria um lote de teste
    $pdo->exec("
        INSERT INTO lotes_venda (codigo_lote, data_inicio, data_fim, status, limite_itens_por_comprador, tempo_carrinho_minutos, created_at)
        VALUES ('LOTE-TEST-CONC', NOW() - INTERVAL 1 HOUR, NOW() + INTERVAL 2 HOUR, 'aberto', 20, 10, NOW())
    ");
    $loteId = (int)$pdo->lastInsertId();
    echo "Lote de teste criado: ID {$loteId}" . PHP_EOL;
} else {
    $loteId = (int)$loteId;
    echo "Lote aberto utilizado: ID {$loteId}" . PHP_EOL;
}

// 2. Busca 2 usuários colaboradores ativos
$stmtUsers = $pdo->query("SELECT id, matricula, setor FROM usuarios WHERE ativo = 1 AND perfil = 'operador' LIMIT 2");
$usuarios = $stmtUsers->fetchAll();

if (count($usuarios) < 2) {
    // Busca quaisquer 2 usuários ativos
    $stmtUsers = $pdo->query("SELECT id, matricula, setor FROM usuarios WHERE ativo = 1 LIMIT 2");
    $usuarios = $stmtUsers->fetchAll();
}

$comprador1 = $usuarios[0];
$comprador2 = $usuarios[1];
echo "Comprador 1: ID {$comprador1['id']} ({$comprador1['matricula']})" . PHP_EOL;
echo "Comprador 2: ID {$comprador2['id']} ({$comprador2['matricula']})" . PHP_EOL;

// 3. Cria produto de teste com estoque total = 10
$codigoProd = 'TEST-CONC-' . time();
$pdo->prepare("
    INSERT INTO produtos (codigo, nome, pacote_padrao, ativo, created_at)
    VALUES (:codigo, 'Produto Teste Concorrencia 350ml', 'Lata 350ml', 1, NOW())
")->execute(['codigo' => $codigoProd]);
$produtoId = (int)$pdo->lastInsertId();

// Insere estoque lojinha = 10 un
$pdo->prepare("
    INSERT INTO estoque_lojinha (produto_id, quantidade_total, quantidade_reservada_gestao, updated_at)
    VALUES (:produto_id, 10, 0, NOW())
")->execute(['produto_id' => $produtoId]);

echo "Produto de teste criado: ID {$produtoId} ({$codigoProd}) com Estoque = 10 un" . PHP_EOL;

try {
    // 4. Limpa quaisquer carrinhos anteriores deste produto
    $pdo->prepare("DELETE FROM carrinho_temporario WHERE produto_id = :p")->execute(['p' => $produtoId]);

    // 5. Cenário Inicial: Ambos visualizam 10 unidades
    $vitrineC1 = EstoqueLojinha::listarProdutosVitrine($loteId, (int)$comprador1['id']);
    $vitrineC2 = EstoqueLojinha::listarProdutosVitrine($loteId, (int)$comprador2['id']);

    $pC1 = array_values(array_filter($vitrineC1, fn($p) => (int)$p['produto_id'] === $produtoId))[0] ?? null;
    $pC2 = array_values(array_filter($vitrineC2, fn($p) => (int)$p['produto_id'] === $produtoId))[0] ?? null;

    assert($pC1 !== null, "Produto deve existir na vitrine de C1");
    assert($pC2 !== null, "Produto deve existir na vitrine de C2");
    assert((int)$pC1['saldo_disponivel'] === 10, "C1 deve ver saldo 10");
    assert((int)$pC2['saldo_disponivel'] === 10, "C2 deve ver saldo 10");
    echo "✓ [OK] Cenário Inicial: Ambos vêem 10 disponíveis." . PHP_EOL;

    // 6. Comprador 1 adiciona 2 unidades ao carrinho
    $resAdd1 = Pedido::adicionarAoCarrinho($loteId, (int)$comprador1['id'], $produtoId, 2, 'sess_c1');
    assert($resAdd1['status'] === 'success', "C1 deve conseguir adicionar 2 un");
    assert((int)$resAdd1['saldo_disponivel'] === 8, "Retorno de C1 deve indicar saldo restante 8");
    assert((int)$resAdd1['item_quantidade'] === 2, "Retorno de C1 deve indicar 2 un no carrinho dele");

    // 7. Consulta vitrine para Comprador 1 (O bug anterior mantinha 10 aqui!)
    $vitrineC1 = EstoqueLojinha::listarProdutosVitrine($loteId, (int)$comprador1['id']);
    $pC1 = array_values(array_filter($vitrineC1, fn($p) => (int)$p['produto_id'] === $produtoId))[0];

    echo "  > Após C1 adicionar 2 un:";
    echo " C1 vê Saldo Disp: {$pC1['saldo_disponivel']}, No Carrinho: {$pC1['no_meu_carrinho']}, Bloq Outros: {$pC1['bloqueios_outros_carrinhos']}" . PHP_EOL;
    assert((int)$pC1['saldo_disponivel'] === 8, "C1 DEVE ver saldo decrementado para 8 (Bug corrigido!)");
    assert((int)$pC1['no_meu_carrinho'] === 2, "C1 deve ver 2 no seu carrinho");
    assert((int)$pC1['bloqueios_outros_carrinhos'] === 0, "C1 deve ver 0 em bloqueios de outros");
    echo "✓ [OK] Comprador 1 agora vê saldo 8 decrementado em sua própria vitrine." . PHP_EOL;

    // 8. Consulta vitrine para Comprador 2
    $vitrineC2 = EstoqueLojinha::listarProdutosVitrine($loteId, (int)$comprador2['id']);
    $pC2 = array_values(array_filter($vitrineC2, fn($p) => (int)$p['produto_id'] === $produtoId))[0];

    echo "  > Para C2:";
    echo " C2 vê Saldo Disp: {$pC2['saldo_disponivel']}, No Carrinho: {$pC2['no_meu_carrinho']}, Bloq Outros: {$pC2['bloqueios_outros_carrinhos']}" . PHP_EOL;
    assert((int)$pC2['saldo_disponivel'] === 8, "C2 deve ver saldo 8");
    assert((int)$pC2['no_meu_carrinho'] === 0, "C2 tem 0 no carrinho");
    assert((int)$pC2['bloqueios_outros_carrinhos'] === 2, "C2 vê 2 bloqueados por outros");
    echo "✓ [OK] Comprador 2 também vê saldo 8 e 2 bloqueados por outros." . PHP_EOL;

    // 9. Comprador 2 adiciona 3 unidades ao carrinho
    $resAdd2 = Pedido::adicionarAoCarrinho($loteId, (int)$comprador2['id'], $produtoId, 3, 'sess_c2');
    assert($resAdd2['status'] === 'success', "C2 deve adicionar 3 un com sucesso");
    assert((int)$resAdd2['saldo_disponivel'] === 5, "Retorno de C2 deve indicar saldo restante 5");

    // 10. Verifica vitrine de ambos após as duas adições
    $vitrineC1 = EstoqueLojinha::listarProdutosVitrine($loteId, (int)$comprador1['id']);
    $vitrineC2 = EstoqueLojinha::listarProdutosVitrine($loteId, (int)$comprador2['id']);
    $pC1 = array_values(array_filter($vitrineC1, fn($p) => (int)$p['produto_id'] === $produtoId))[0];
    $pC2 = array_values(array_filter($vitrineC2, fn($p) => (int)$p['produto_id'] === $produtoId))[0];

    echo "  > Após C2 adicionar 3 un:";
    echo " C1 vê Saldo Disp: {$pC1['saldo_disponivel']}, Meu: {$pC1['no_meu_carrinho']}, Outros: {$pC1['bloqueios_outros_carrinhos']}" . PHP_EOL;
    echo " C2 vê Saldo Disp: {$pC2['saldo_disponivel']}, Meu: {$pC2['no_meu_carrinho']}, Outros: {$pC2['bloqueios_outros_carrinhos']}" . PHP_EOL;

    assert((int)$pC1['saldo_disponivel'] === 5, "C1 deve ver saldo 5");
    assert((int)$pC1['no_meu_carrinho'] === 2, "C1 deve ver 2 no seu carrinho");
    assert((int)$pC1['bloqueios_outros_carrinhos'] === 3, "C1 deve ver 3 bloqueados por outros");

    assert((int)$pC2['saldo_disponivel'] === 5, "C2 deve ver saldo 5");
    assert((int)$pC2['no_meu_carrinho'] === 3, "C2 deve ver 3 no seu carrinho");
    assert((int)$pC2['bloqueios_outros_carrinhos'] === 2, "C2 deve ver 2 bloqueados por outros");
    echo "✓ [OK] Ambos os compradores têm visão rigorosa e congruente: 5 restantes no estoque." . PHP_EOL;

    // 11. Valida Endpoint Compacto em Tempo Real (Smart Polling)
    $saldosCompactos = EstoqueLojinha::listarSaldosCompactos();
    assert(isset($saldosCompactos[$produtoId]), "Produto deve constar nos saldos compactos");
    assert($saldosCompactos[$produtoId] === 5, "Saldo compacto do produto deve ser exatamente 5");
    echo "✓ [OK] Smart Polling retorna dicionário leve com saldo exato de 5 un." . PHP_EOL;

    // 12. Esgota as 5 unidades restantes (C1 adiciona mais 3 e C2 adiciona mais 2, respeitando as cotas do lote)
    Pedido::adicionarAoCarrinho($loteId, (int)$comprador1['id'], $produtoId, 3, 'sess_c1');
    Pedido::adicionarAoCarrinho($loteId, (int)$comprador2['id'], $produtoId, 2, 'sess_c2');

    $saldosAposEsgotar = EstoqueLojinha::listarSaldosCompactos();
    assert(isset($saldosAposEsgotar[$produtoId]), "Produto deve constar nos saldos compactos");
    assert($saldosAposEsgotar[$produtoId] === 0, "Saldo compacto agora deve ser 0 (Esgotado)");

    $vitrineC2 = EstoqueLojinha::listarProdutosVitrine($loteId, (int)$comprador2['id']);
    $pC2 = array_values(array_filter($vitrineC2, fn($p) => (int)$p['produto_id'] === $produtoId))[0];
    assert((int)$pC2['saldo_disponivel'] === 0, "C2 deve ver saldo 0 (Esgotado)");
    echo "✓ [OK] Produto com saldo 0 é reportado como esgotado para o comprador sem disponibilidade." . PHP_EOL;

} finally {
    // Limpeza
    $pdo->prepare("DELETE FROM carrinho_temporario WHERE produto_id = :p")->execute(['p' => $produtoId]);
    $pdo->prepare("DELETE FROM estoque_lojinha WHERE produto_id = :p")->execute(['p' => $produtoId]);
    $pdo->prepare("DELETE FROM produtos WHERE id = :p")->execute(['p' => $produtoId]);
    echo "Limpeza de registros de teste concluída com sucesso." . PHP_EOL;
}

echo "=== TODOS OS TESTES DE CONCORRÊNCIA E TEMPO REAL PASSARAM COM SUCESSO! ===" . PHP_EOL;
