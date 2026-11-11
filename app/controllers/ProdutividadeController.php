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
     * Tela do Cronômetro
     */
    public function cronometro(): void
    {
        if (empty($_GET['origem']) || $_GET['origem'] !== 'voltar') {
            unset($_SESSION['cronometro_temp']);
        }
        require_once __DIR__ . '/../views/operador/cronometro.php';
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
            unset($_SESSION['cronometro_temp']);
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
