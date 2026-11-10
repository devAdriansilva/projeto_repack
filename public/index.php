<?php
declare(strict_types=1);

/**
 * Projeto Repack - Front Controller e Roteador Central
 * Baseado estritamente em docs/FSD.md (Seções 3, 5, 12, 15, 16, 23 e 24) e AGENTS.md
 */

// Carrega configurações globais e singleton PDO
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

// Normaliza a rota
$route = '/' . trim($requestUri, '/');
if ($route === '//' || $route === '') {
    $route = '/';
}

// Controllers da Fase 2
require_once __DIR__ . '/../app/controllers/OperadorController.php';
require_once __DIR__ . '/../app/controllers/AvariaController.php';
require_once __DIR__ . '/../app/controllers/ProdutividadeController.php';
require_once __DIR__ . '/../app/models/Usuario.php';
require_once __DIR__ . '/../app/models/Produto.php';

// Roteador
switch ($route) {
    // -------------------------------------------------------------
    // Rotas do Operador (Urna Eletrônica)
    // -------------------------------------------------------------
    case '/':
    case '/operador':
        (new OperadorController())->index();
        break;

    case '/operador/selecionar-operador':
        (new OperadorController())->selecionarOperador();
        break;

    case '/operador/avaria/passo1':
        (new AvariaController())->passo1();
        break;

    case '/operador/avaria/passo2':
        (new AvariaController())->passo2();
        break;

    case '/operador/avaria/resumo':
        (new AvariaController())->resumo();
        break;

    case '/operador/cronometro':
        (new ProdutividadeController())->cronometro();
        break;

    case '/operador/cronometro/resumo':
        (new ProdutividadeController())->resumo();
        break;

    // -------------------------------------------------------------
    // Endpoints de API para Operador e PWA Offline
    // -------------------------------------------------------------
    case '/api/bo/salvar':
    case '/api/bo/salvar.php':
        (new AvariaController())->salvar();
        break;

    case '/api/bo/sincronizar':
    case '/api/bo/sincronizar.php':
        (new AvariaController())->sincronizar();
        break;

    case '/api/produtividade/salvar':
    case '/api/produtividade/salvar.php':
        (new ProdutividadeController())->salvar();
        break;

    case '/api/produtividade/sincronizar':
    case '/api/produtividade/sincronizar.php':
        (new ProdutividadeController())->sincronizar();
        break;

    case '/api/operadores':
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status' => 'success',
            'operadores' => Usuario::listarOperadoresAtivos()
        ], JSON_UNESCAPED_UNICODE);
        break;

    case '/api/produtos':
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status' => 'success',
            'produtos' => Produto::listarAtivos()
        ], JSON_UNESCAPED_UNICODE);
        break;

    case '/api/gestao/login':
        header('Content-Type: application/json; charset=UTF-8');
        $raw = file_get_contents('php://input');
        $dados = json_decode($raw, true) ?? [];
        $matricula = trim($dados['matricula'] ?? '');
        $senha = (string)($dados['senha'] ?? '');

        $usuario = Usuario::buscarPorMatricula($matricula);
        if ($usuario && $usuario['perfil'] === 'gestao' && !empty($usuario['senha_hash']) && password_verify($senha, $usuario['senha_hash'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['perfil'] = 'gestao';
            $_SESSION['nome'] = $usuario['nome'];
            echo json_encode(['status' => 'success', 'message' => 'Login realizado com sucesso.']);
        } else {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Matrícula ou senha incorretos.']);
        }
        break;

    case '/api/health':
        header('Content-Type: application/json; charset=UTF-8');
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->query("SELECT COUNT(*) FROM migrations_log");
            $totalMigrations = (int)$stmt->fetchColumn();

            $stmtTables = $pdo->query("SHOW TABLES");
            $tables = $stmtTables->fetchAll(PDO::FETCH_COLUMN);

            $totalBO = (int)$pdo->query("SELECT COUNT(*) FROM apontamentos_bo")->fetchColumn();
            $totalProd = (int)$pdo->query("SELECT COUNT(*) FROM apontamentos_produtividade")->fetchColumn();

            echo json_encode([
                'status' => 'success',
                'app' => APP_NAME,
                'php_version' => PHP_VERSION,
                'database' => [
                    'connected' => true,
                    'name' => DB_NAME,
                    'migrations_applied' => $totalMigrations,
                    'total_tables' => count($tables),
                    'total_bo' => $totalBO,
                    'total_produtividade' => $totalProd
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Falha de conexão com o banco de dados.',
                'error' => $e->getMessage()
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
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
