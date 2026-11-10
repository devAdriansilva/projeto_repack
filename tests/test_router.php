<?php
declare(strict_types=1);

// Teste das rotas públicas e da API
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/api/health';
ob_start();
require __DIR__ . '/../public/index.php';
$output = ob_get_clean();

$data = json_decode($output, true);
if (isset($data['status']) && $data['status'] === 'success') {
    echo "Endpoint /api/health OK! Database conectado, migrations: " . $data['database']['migrations_applied'] . "\n";
    exit(0);
} else {
    echo "Erro em /api/health: " . $output . "\n";
    exit(1);
}
