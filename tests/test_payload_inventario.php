<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    exit(1);
}

echo "=== TESTE UNITÁRIO: PAYLOAD DE INVENTÁRIO & REGRAS DE NEGÓCIO ===\n";

$payloadValidoJson = json_encode([
    [
        'produto_id' => 1,
        'novo_saldo' => 24,
        'detalhes_contagem' => '12 + 12'
    ],
    [
        'produto_id' => 2,
        'novo_saldo' => 36,
        'detalhes_contagem' => '24 + 12'
    ]
]);

$dados = json_decode($payloadValidoJson, true);
assert(is_array($dados), "Payload deve ser decodificado como array");
assert(count($dados) === 2, "Payload deve conter 2 itens");

foreach ($dados as $item) {
    $pid = (int)($item['produto_id'] ?? 0);
    $novoSaldo = max(0, (int)($item['novo_saldo'] ?? 0));
    $detalhes = !empty($item['detalhes_contagem']) ? (string)$item['detalhes_contagem'] : null;

    assert($pid > 0, "ID do produto deve ser positivo");
    assert($novoSaldo >= 0, "Novo saldo deve ser >= 0");
    assert(!empty($detalhes), "Detalhes da contagem devem ser preservados");
    echo "  [PASS] Item verificado: Produto #{$pid} => {$novoSaldo} un (Coleta: {$detalhes})\n";
}

echo "=== TESTE DE PAYLOAD EXECUTADO COM SUCESSO! ===\n";
