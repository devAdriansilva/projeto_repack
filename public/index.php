<?php
declare(strict_types=1);

/**
 * Projeto Repack - Front Controller e Roteador Base
 * Baseado estritamente em docs/FSD.md (Seções 3, 5, 15, 16 e 24) e AGENTS.md
 */

// Carrega as configurações essenciais e conexão com banco de dados
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';

// Inicia sessão segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cabeçalhos fundamentais de segurança HTTP
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// Captura a URI requisitada
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// Remove o prefixo base da URL (ex: /projeto_repack ou /public) se existir
$basePath = APP_URL;
if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

// Normaliza a URI
$route = '/' . trim($requestUri, '/');
if ($route === '//' || $route === '') {
    $route = '/';
}

// Roteador Base da Aplicação
switch ($route) {
    case '/':
    case '/home':
        // Rota principal: redirecionamento inicial para o módulo da Urna Eletrônica (Fase 2)
        // Enquanto a Fase 2 está pendente, exibe tela informativa da Fundação ativa
        header('Content-Type: text/html; charset=UTF-8');
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= sanitize_html(APP_NAME) ?> - Fundação Ativa</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                    background-color: #F3F4F6;
                    color: #111827;
                    margin: 0;
                    padding: 40px 20px;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 80vh;
                }
                .card {
                    background: #FFFFFF;
                    border-radius: 16px;
                    padding: 32px;
                    max-width: 600px;
                    width: 100%;
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                    text-align: center;
                    border: 1px solid #D1D5DB;
                }
                .badge {
                    display: inline-block;
                    background-color: #10B981;
                    color: #FFFFFF;
                    font-weight: 700;
                    padding: 8px 16px;
                    border-radius: 9999px;
                    font-size: 14px;
                    margin-bottom: 20px;
                }
                h1 {
                    font-size: 28px;
                    margin-bottom: 12px;
                    font-weight: 800;
                }
                p {
                    font-size: 16px;
                    color: #6B7280;
                    line-height: 1.6;
                    margin-bottom: 24px;
                }
                .status-box {
                    background: #F9FAFB;
                    border: 1px solid #E5E7EB;
                    border-radius: 8px;
                    padding: 16px;
                    text-align: left;
                    font-size: 14px;
                    font-family: monospace;
                    margin-bottom: 20px;
                }
                .btn {
                    display: inline-block;
                    background-color: #3B82F6;
                    color: #FFFFFF;
                    text-decoration: none;
                    padding: 14px 28px;
                    border-radius: 8px;
                    font-weight: 700;
                    font-size: 16px;
                }
            </style>
        </head>
        <body>
            <div class="card">
                <span class="badge">Fase 1 Concluída</span>
                <h1><?= sanitize_html(APP_NAME) ?></h1>
                <p>A infraestrutura de backend, conexão segura PDO e banco de dados relacional estão plenamente operacionais.</p>
                <div class="status-box">
                    <strong>Status da Fundação:</strong><br>
                    • PHP: <?= sanitize_html(PHP_VERSION) ?><br>
                    • Conexão PDO: Operacional<br>
                    • DSN: <?= sanitize_html(DB_HOST . ':' . DB_PORT . ' / ' . DB_NAME) ?><br>
                    • Timezone: <?= sanitize_html(date_default_timezone_get()) ?><br>
                    • Front Controller: Ativo
                </div>
                <a href="<?= sanitize_html(APP_URL . '/api/health') ?>" class="btn">Testar Endpoint de Saúde (JSON)</a>
            </div>
        </body>
        </html>
        <?php
        break;

    case '/api/health':
        // Endpoint de verificação de integridade da infraestrutura (Fase 1)
        header('Content-Type: application/json; charset=UTF-8');
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->query("SELECT COUNT(*) FROM migrations_log");
            $totalMigrations = (int)$stmt->fetchColumn();

            $stmtTables = $pdo->query("SHOW TABLES");
            $tables = $stmtTables->fetchAll(PDO::FETCH_COLUMN);

            echo json_encode([
                'status' => 'success',
                'app' => APP_NAME,
                'php_version' => PHP_VERSION,
                'database' => [
                    'connected' => true,
                    'name' => DB_NAME,
                    'migrations_applied' => $totalMigrations,
                    'total_tables' => count($tables),
                    'tables' => $tables
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Falha de conexão com o banco de dados.',
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        // Rota não encontrada
        http_response_code(404);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status' => 'error',
            'code' => 404,
            'message' => 'Rota não encontrada.',
            'route' => $route
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;
}
