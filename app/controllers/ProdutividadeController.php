<?php
declare(strict_types=1);

/**
 * Projeto Repack - Controller Produtividade
 * Baseado estritamente em docs/FSD.md (Seções 6, 11, 12.1, 13.2, 23)
 */

require_once __DIR__ . '/../models/ApontamentoProdutividade.php';
require_once __DIR__ . '/../models/Produto.php';

class ProdutividadeController
{
    /**
     * Tela do Registro de Produção
     */
    public function cronometro(): void
    {
        if (!empty($_GET['novo'])) {
            unset($_SESSION['producao_ativa'], $_SESSION['cronometro_temp']);
        }
        require_once __DIR__ . '/../views/operador/cronometro.php';
    }

    /**
     * Endpoint API para registrar início da produção na sessão
     */
    public function iniciarSessao(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $raw = file_get_contents('php://input');
        $dados = json_decode($raw, true) ?? [];

        $_SESSION['producao_ativa'] = [
            'produto_id' => (int)($dados['produto_id'] ?? 0),
            'produto_nome' => (string)($dados['produto_nome'] ?? ''),
            'produto_codigo' => (string)($dados['produto_codigo'] ?? ''),
            'produto_embalagem' => (string)($dados['produto_embalagem'] ?? ''),
            'data_hora_inicio' => (string)($dados['data_hora_inicio'] ?? date('Y-m-d H:i:s')),
            'pausado' => false,
            'segundos_pausa' => 0
        ];

        echo json_encode(['status' => 'success', 'producao' => $_SESSION['producao_ativa']]);
    }

    /**
     * Endpoint API para pausar produção em andamento
     */
    public function pausarSessao(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        if (!empty($_SESSION['producao_ativa'])) {
            $raw = file_get_contents('php://input');
            $dados = json_decode($raw, true) ?? [];
            $_SESSION['producao_ativa']['pausado'] = true;
            $_SESSION['producao_ativa']['pausa_inicio'] = (string)($dados['pausa_inicio'] ?? date('Y-m-d H:i:s'));
        }
        echo json_encode(['status' => 'success', 'producao' => $_SESSION['producao_ativa'] ?? null]);
    }

    /**
     * Endpoint API para retomar produção em andamento
     */
    public function retomarSessao(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        if (!empty($_SESSION['producao_ativa'])) {
            $raw = file_get_contents('php://input');
            $dados = json_decode($raw, true) ?? [];
            $_SESSION['producao_ativa']['pausado'] = false;
            $_SESSION['producao_ativa']['segundos_pausa'] = (int)($dados['segundos_pausa'] ?? 0);
            unset($_SESSION['producao_ativa']['pausa_inicio']);
        }
        echo json_encode(['status' => 'success', 'producao' => $_SESSION['producao_ativa'] ?? null]);
    }

    /**
     * Endpoint API para descartar produção em andamento
     */
    public function descartarSessao(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        unset($_SESSION['producao_ativa'], $_SESSION['cronometro_temp']);
        echo json_encode(['status' => 'success']);
    }

    /**
     * Endpoint API de Heartbeat (keep-alive) para manter a sessão viva em processos longos
     */
    public function heartbeat(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $_SESSION['ultimo_heartbeat'] = time();
        echo json_encode(['status' => 'success', 'timestamp' => time()]);
    }

    /**
     * Tela de Conferência de Produtividade
     */
    public function resumo(): void
    {
        require_once __DIR__ . '/../views/operador/cronometro_resumo.php';
    }

    /**
     * Endpoint API: /api/produtividade/salvar.php
     */
    public function salvar(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        try {
            $raw = file_get_contents('php://input');
            $dados = json_decode($raw, true) ?? [];

            if (empty($dados['usuario_id']) && !empty($_SESSION['operador_id'])) {
                $dados['usuario_id'] = (int)$_SESSION['operador_id'];
            }

            $resultado = ApontamentoProdutividade::salvar($dados);
            unset($_SESSION['cronometro_temp'], $_SESSION['producao_ativa']);
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            $logMsg = "[" . date('Y-m-d H:i:s') . "] [ProdutividadeController::salvar] " . $e->getMessage() . PHP_EOL;
            @file_put_contents(__DIR__ . '/../../logs/app_errors.log', $logMsg, FILE_APPEND | LOCK_EX);

            echo json_encode([
                'status' => 'error',
                'message' => 'Erro interno ao processar apontamento de produtividade.',
                'error' => (defined('APP_ENV') && APP_ENV === 'development') ? $e->getMessage() : 'Erro interno.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Endpoint API: /api/produtividade/sincronizar.php
     */
    public function sincronizar(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        try {
            $raw = file_get_contents('php://input');
            $dados = json_decode($raw, true) ?? [];
            $apontamentos = $dados['apontamentos'] ?? [];

            if (!is_array($apontamentos)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Lote de apontamentos inválido.']);
                return;
            }

            $resultado = ApontamentoProdutividade::sincronizarLote($apontamentos);
            echo json_encode([
                'status' => 'success',
                'dados' => $resultado
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            $logMsg = "[" . date('Y-m-d H:i:s') . "] [ProdutividadeController::sincronizar] " . $e->getMessage() . PHP_EOL;
            @file_put_contents(__DIR__ . '/../../logs/app_errors.log', $logMsg, FILE_APPEND | LOCK_EX);

            echo json_encode([
                'status' => 'error',
                'message' => 'Falha na sincronização em lote de produtividade.',
                'error' => (defined('APP_ENV') && APP_ENV === 'development') ? $e->getMessage() : 'Erro interno.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
