<?php
declare(strict_types=1);

/**
 * Projeto Repack - Controller Avaria
 * Baseado estritamente em docs/FSD.md (Seções 6, 11, 12.1, 13.1, 23)
 */

require_once __DIR__ . '/../models/ApontamentoBO.php';
require_once __DIR__ . '/../models/Produto.php';

class AvariaController
{
    /**
     * Passo 1: Seleção de Produto e Quantidade
     */
    public function passo1(): void
    {
        // Limpa rascunho apenas se o operador solicitar explicitamente novo=1
        if (!empty($_GET['novo'])) {
            unset($_SESSION['avaria_temp']);
        }
        require_once __DIR__ . '/../views/operador/avaria_passo1.php';
    }

    /**
     * Passo 2: Seleção do Tipo de Avaria
     */
    public function passo2(): void
    {
        require_once __DIR__ . '/../views/operador/avaria_passo2.php';
    }

    /**
     * Passo 3: Conferência Dupla (Corrigir / Confirmar)
     */
    public function resumo(): void
    {
        require_once __DIR__ . '/../views/operador/avaria_resumo.php';
    }

    /**
     * Endpoint API: /api/bo/salvar.php
     */
    public function salvar(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        try {
            $raw = file_get_contents('php://input');
            $dados = json_decode($raw, true) ?? [];

            // Se o usuário não estiver setado no payload, busca da sessão
            if (empty($dados['usuario_id']) && !empty($_SESSION['operador_id'])) {
                $dados['usuario_id'] = (int)$_SESSION['operador_id'];
            }

            $resultado = ApontamentoBO::salvar($dados);
            unset($_SESSION['avaria_temp']);
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            // Fallback de erro em log conforme regra de arquitetura
            $logMsg = "[" . date('Y-m-d H:i:s') . "] [AvariaController::salvar] " . $e->getMessage() . PHP_EOL;
            @file_put_contents(__DIR__ . '/../../logs/app_errors.log', $logMsg, FILE_APPEND | LOCK_EX);

            echo json_encode([
                'status' => 'error',
                'message' => 'Erro interno ao processar apontamento de B.O.',
                'error' => (defined('APP_ENV') && APP_ENV === 'development') ? $e->getMessage() : 'Erro interno.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Endpoint API: /api/bo/sincronizar.php (Sincronização em Lote de B.O. Offline)
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

            $resultado = ApontamentoBO::sincronizarLote($apontamentos);
            echo json_encode([
                'status' => 'success',
                'dados' => $resultado
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            $logMsg = "[" . date('Y-m-d H:i:s') . "] [AvariaController::sincronizar] " . $e->getMessage() . PHP_EOL;
            @file_put_contents(__DIR__ . '/../../logs/app_errors.log', $logMsg, FILE_APPEND | LOCK_EX);

            echo json_encode([
                'status' => 'error',
                'message' => 'Falha na sincronização em lote de B.O.s.',
                'error' => (defined('APP_ENV') && APP_ENV === 'development') ? $e->getMessage() : 'Erro interno.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
