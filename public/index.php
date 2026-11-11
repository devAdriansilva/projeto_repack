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
if ($basePath !== '' && strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

// Remove o prefixo /public caso a requisição venha diretamente por ele
if (strpos($requestUri, '/public') === 0) {
    $requestUri = substr($requestUri, 7);
}

// Normaliza a rota
$route = '/' . trim($requestUri, '/');
if ($route === '//' || $route === '') {
    $route = '/';
}

// Controllers
require_once __DIR__ . '/../app/controllers/OperadorController.php';
require_once __DIR__ . '/../app/controllers/AvariaController.php';
require_once __DIR__ . '/../app/controllers/ProdutividadeController.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/GestaoController.php';
require_once __DIR__ . '/../app/controllers/LojinhaController.php';
require_once __DIR__ . '/../app/controllers/RelatorioController.php';
require_once __DIR__ . '/../app/models/Usuario.php';
require_once __DIR__ . '/../app/models/Produto.php';
require_once __DIR__ . '/../app/models/Pedido.php';

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

    case '/operador/definir-padrao':
    case '/api/operador/definir-padrao':
        (new OperadorController())->definirPadrao();
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

    case '/operador/baixa':
    case '/operador/baixa_lojinha':
        (new OperadorController())->baixaLojinha();
        break;

    case '/api/estoque/baixa-operador':
        (new OperadorController())->apiBaixaLojinha();
        break;

    // -------------------------------------------------------------
    // Rotas de Autenticação da Gestão
    // -------------------------------------------------------------
    case '/gestao/login':
    case '/api/gestao/login':
        (new AuthController())->login();
        break;

    case '/gestao/logout':
        (new AuthController())->logout();
        break;

    // -------------------------------------------------------------
    // Rotas do Painel Administrativo da Gestão
    // -------------------------------------------------------------
    case '/gestao':
    case '/gestao/dashboard':
        (new GestaoController())->dashboard();
        break;

    // Produtos
    case '/gestao/produtos':
        (new GestaoController())->produtos();
        break;

    case '/gestao/produtos/salvar':
        (new GestaoController())->salvarProduto();
        break;

    case '/gestao/produtos/toggle':
        (new GestaoController())->toggleStatusProduto();
        break;

    // Lotes de Venda
    case '/gestao/lotes':
        (new GestaoController())->lotes();
        break;

    case '/gestao/lotes/salvar':
        (new GestaoController())->salvarLote();
        break;

    case '/gestao/lotes/status':
        (new GestaoController())->alterarStatusLote();
        break;

    case '/gestao/lotes/excluir':
        (new GestaoController())->excluirLote();
        break;

    // Estoque & Reservas
    case '/gestao/estoque':
        (new GestaoController())->estoque();
        break;

    case '/gestao/estoque/reserva':
        (new GestaoController())->salvarReserva();
        break;

    case '/gestao/estoque/baixa':
        (new GestaoController())->salvarBaixa();
        break;

    // Correção de B.O.
    case '/gestao/bo':
    case '/gestao/correcao_bo':
        (new GestaoController())->correcaoBo();
        break;

    case '/gestao/bo/corrigir':
        (new GestaoController())->salvarCorrecaoBo();
        break;

    // Lista de Separação Física no Galpão (docs/FSD.md Seções 16 e 22.1)
    case '/gestao/separacao':
        (new RelatorioController())->separacao();
        break;

    case '/gestao/separacao/status':
        (new RelatorioController())->atualizarStatusSeparacao();
        break;

    case '/gestao/separacao/exportar':
        (new RelatorioController())->exportarSeparacaoCsv();
        break;

    // Relatórios e Extratos Analíticos (docs/FSD.md Seções 22.2 e 22.3)
    case '/gestao/relatorios':
        (new RelatorioController())->relatorios();
        break;

    case '/gestao/relatorios/exportar':
        (new RelatorioController())->exportarRelatoriosCsv();
        break;

    // -------------------------------------------------------------
    // Rotas da Vitrine e Carrinho da Lojinha (Colaborador)
    // -------------------------------------------------------------
    case '/lojinha':
    case '/lojinha/vitrine':
    case '/vitrine':
        (new LojinhaController())->vitrine();
        break;

    case '/lojinha/login':
    case '/login':
        (new LojinhaController())->login();
        break;

    case '/lojinha/logout':
        (new LojinhaController())->logout();
        break;

    case '/lojinha/carrinho':
    case '/lojinha/sacola':
    case '/carrinho':
    case '/sacola':
        (new LojinhaController())->carrinho();
        break;

    case '/lojinha/carrinho/remover':
        (new LojinhaController())->removerItem();
        break;

    case '/lojinha/carrinho/esvaziar':
        (new LojinhaController())->esvaziar();
        break;

    case '/lojinha/checkout':
        (new LojinhaController())->checkout();
        break;

    case '/lojinha/sucesso':
        (new LojinhaController())->sucesso();
        break;

    // -------------------------------------------------------------
    // Endpoints de API da Lojinha (Carrinho e Saldo)
    // -------------------------------------------------------------
    case '/api/carrinho/adicionar':
    case '/api/carrinho/adicionar.php':
        (new LojinhaController())->apiAdicionarCarrinho();
        break;

    case '/api/carrinho/remover':
    case '/api/carrinho/remover.php':
        (new LojinhaController())->apiRemoverCarrinho();
        break;

    case '/api/estoque/saldo':
    case '/api/estoque/saldo.php':
        (new LojinhaController())->apiSaldo();
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

    case '/api/produtividade/iniciar':
        (new ProdutividadeController())->iniciarSessao();
        break;

    case '/api/produtividade/pausar':
        (new ProdutividadeController())->pausarSessao();
        break;

    case '/api/produtividade/retomar':
        (new ProdutividadeController())->retomarSessao();
        break;

    case '/api/produtividade/descartar':
        (new ProdutividadeController())->descartarSessao();
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
            $totalLotes = (int)$pdo->query("SELECT COUNT(*) FROM lotes_venda WHERE deleted_at IS NULL")->fetchColumn();
            $totalPedidos = (int)$pdo->query("SELECT COUNT(*) FROM pedidos WHERE deleted_at IS NULL")->fetchColumn();
            $totalCarrinho = (int)$pdo->query("SELECT COUNT(*) FROM carrinho_temporario WHERE expiracao_em > NOW()")->fetchColumn();

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
                    'total_produtividade' => $totalProd,
                    'total_lotes' => $totalLotes,
                    'total_pedidos' => $totalPedidos,
                    'total_carrinho_ativo' => $totalCarrinho
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Falha de conexão com o banco de dados.',
                'error' => (defined('APP_ENV') && APP_ENV === 'development') ? $e->getMessage() : 'Erro interno do servidor.'
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
