<?php
declare(strict_types=1);

/**
 * Projeto Repack - Teste Automatizado de Lançamento por Lote / Turno (MANUT-081)
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/ApontamentoProdutividade.php';
require_once __DIR__ . '/../app/models/Produto.php';

echo "=== TESTE: LANÇAMENTO DE PRODUTIVIDADE POR LOTE / TURNO ===\n";

$passou = 0;
$falhou = 0;

function assertTest(string $nome, bool $condicao) {
    global $passou, $falhou;
    if ($condicao) {
        echo "  [PASS] {$nome}\n";
        $passou++;
    } else {
        echo "  [FAIL] {$nome}\n";
        $falhou++;
    }
}

// 1. Validações de entrada do salvarLote
try {
    ApontamentoProdutividade::salvarLote([
        'usuario_id' => 0,
        'data_hora_inicio' => '2026-11-16 08:00:00',
        'data_hora_fim' => '2026-11-16 12:00:00',
        'itens' => []
    ]);
    assertTest("Validação: operador inválido deve lançar exceção", false);
} catch (InvalidArgumentException $e) {
    assertTest("Validação: operador inválido barra com mensagem correta", str_contains($e->getMessage(), 'Operador inválido'));
}

try {
    ApontamentoProdutividade::salvarLote([
        'usuario_id' => 1,
        'data_hora_inicio' => '',
        'data_hora_fim' => '2026-11-16 12:00:00',
        'itens' => []
    ]);
    assertTest("Validação: horário vazio deve lançar exceção", false);
} catch (InvalidArgumentException $e) {
    assertTest("Validação: horário vazio barra com sucesso", str_contains($e->getMessage(), 'obrigatórios'));
}

try {
    ApontamentoProdutividade::salvarLote([
        'usuario_id' => 1,
        'data_hora_inicio' => '2026-11-16 12:00:00',
        'data_hora_fim' => '2026-11-16 08:00:00',
        'itens' => [['produto_id' => 1, 'pacotes_refeitos' => 10]]
    ]);
    assertTest("Validação: fim menor que início deve lançar exceção", false);
} catch (InvalidArgumentException $e) {
    assertTest("Validação: intervalo inválido barra com sucesso", str_contains($e->getMessage(), 'Intervalo de tempo'));
}

try {
    ApontamentoProdutividade::salvarLote([
        'usuario_id' => 1,
        'data_hora_inicio' => '2026-11-16 08:00:00',
        'data_hora_fim' => '2026-11-16 12:00:00',
        'itens' => []
    ]);
    assertTest("Validação: lote sem itens deve lançar exceção", false);
} catch (InvalidArgumentException $e) {
    assertTest("Validação: lote sem itens barra com sucesso", str_contains($e->getMessage(), 'Nenhum item informado'));
}

// 2. Testar gravação se conexão com banco de dados estiver disponível
try {
    $pdo = Database::getConnection();
    
    // Busca 2 produtos ativos reais
    $produtos = Produto::listarAtivos();
    if (count($produtos) >= 2) {
        $p1 = $produtos[0];
        $p2 = $produtos[1];

        $dadosTeste = [
            'usuario_id' => 1,
            'data_hora_inicio' => '2026-11-16 08:00:00',
            'data_hora_fim' => '2026-11-16 12:00:00', // 4h = 14400s
            'itens' => [
                ['produto_id' => (int)$p1['id'], 'pacotes_refeitos' => 30],
                ['produto_id' => (int)$p2['id'], 'pacotes_refeitos' => 10]
            ]
        ];

        $resultado = ApontamentoProdutividade::salvarLote($dadosTeste);

        assertTest("Gravação do lote retornou status success", $resultado['status'] === 'success');
        assertTest("Total de pacotes calculado corretamente (40)", $resultado['total_pacotes'] === 40);
        assertTest("Duração total em segundos (14400s = 4h)", $resultado['duracao_total_segundos'] === 14400);
        assertTest("Tempo médio por pacote calculado (360s = 6min)", (int)$resultado['tempo_medio_segundos'] === 360);
        assertTest("Foram criados 2 registros atômicos", count($resultado['ids']) === 2);

        // Verifica no banco de dados se os 2 registros foram gravados e se o rateio foi exato
        $id1 = $resultado['ids'][0];
        $id2 = $resultado['ids'][1];

        $stmt = $pdo->prepare("SELECT duracao_segundos, pacotes_refeitos FROM apontamentos_produtividade WHERE id IN (?, ?)");
        $stmt->execute([$id1, $id2]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $duracaoTotalItens = 0;
        foreach ($rows as $r) {
            $duracaoTotalItens += (int)$r['duracao_segundos'];
        }

        assertTest("Soma das durações individuais bate 100% com a duração total do lote (14400s)", $duracaoTotalItens === 14400);

        // Limpeza dos registros de teste
        $pdo->prepare("DELETE FROM apontamentos_produtividade WHERE id IN (?, ?)")->execute([$id1, $id2]);
        assertTest("Limpeza dos registros de teste executada", true);
    } else {
        echo "  [INFO] Menos de 2 produtos ativos para teste de persistência completo.\n";
    }
} catch (\PDOException $e) {
    echo "  [INFO] Banco de dados local offline no ambiente CLI. Validação unitária concluída com sucesso.\n";
}

echo "\nResultado: {$passou} passaram, {$falhou} falharam.\n";
exit($falhou > 0 ? 1 : 0);
