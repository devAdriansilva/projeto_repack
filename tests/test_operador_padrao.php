<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    exit(1);
}

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Usuario.php';
require_once __DIR__ . '/../app/models/Configuracao.php';

echo "=== TESTE DE OPERADORES E OPERADOR PADRÃO ===\n";

$operadores = Usuario::listarOperadoresAtivos();
echo "Operadores ativos encontrados: " . count($operadores) . "\n";
foreach ($operadores as $op) {
    echo " - ID: {$op['id']} | Nome: {$op['nome']} | Matrícula: {$op['matricula']}\n";
}

assert(count($operadores) >= 2, "Devem existir ao menos 2 operadores ativos");

$padrao = Configuracao::obterOperadorPadrao();
echo "Operador padrão atual: " . ($padrao ? $padrao['nome'] : 'Nenhum') . "\n";
assert($padrao !== null, "Operador padrão não pode ser nulo");

// Testa alteração de operador padrão
$outroOperador = null;
foreach ($operadores as $op) {
    if ((int)$op['id'] !== (int)$padrao['id']) {
        $outroOperador = $op;
        break;
    }
}

if ($outroOperador) {
    echo "Alterando padrão para {$outroOperador['nome']}...\n";
    $ok = Configuracao::salvar('operador_padrao_id', (string)$outroOperador['id']);
    assert($ok, "Falha ao salvar novo operador padrão");

    $novoPadrao = Configuracao::obterOperadorPadrao();
    assert((int)$novoPadrao['id'] === (int)$outroOperador['id'], "Novo padrão não corresponde ao esperado");
    echo " -> Alterado com sucesso para {$novoPadrao['nome']}!\n";

    // Restaura para o padrão original
    Configuracao::salvar('operador_padrao_id', (string)$padrao['id']);
    echo " -> Restaurado com sucesso para {$padrao['nome']}!\n";
}

echo "=== TODOS OS TESTES DE OPERADOR PADRÃO PASSARAM COM SUCESSO! ===\n";
