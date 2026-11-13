<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['usuario_id'] = 1;
$_SESSION['perfil'] = 'gestor';
$_SESSION['nome'] = 'Coordenadora Teste';
$_SESSION['matricula'] = 'GEST-001';
$_SESSION['ultimo_acesso'] = time();

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/ApontamentoProdutividade.php';
require_once __DIR__ . '/../app/models/ApontamentoBO.php';

// 1. Testar ApontamentoProdutividade::listarComFiltros
$prodGeral = ApontamentoProdutividade::listarComFiltros(['limite' => 10]);
assert(count($prodGeral) > 0, 'Deveria retornar registros de produtividade');

// 2. Testar com filtro de período
$prodHoje = ApontamentoProdutividade::listarComFiltros([
    'data_inicio' => date('Y-m-d'),
    'data_fim' => date('Y-m-d'),
    'limite' => 10
]);
assert(is_array($prodHoje), 'Deveria retornar array para filtro de hoje');

// 3. Testar ApontamentoBO::listarComFiltros com tipo_avaria e destino
$boAmassada = ApontamentoBO::listarComFiltros([
    'tipo_avaria' => 'amassada',
    'limite' => 5
]);
assert(is_array($boAmassada), 'Deveria retornar array para amassada');

$boLojinha = ApontamentoBO::listarComFiltros([
    'destino' => 'lojinha',
    'limite' => 5
]);
assert(is_array($boLojinha), 'Deveria retornar array para lojinha');

echo "=== TESTES UNITÁRIOS DE TELEMETRIA PASSARAM COM SUCESSO! ===" . PHP_EOL;
