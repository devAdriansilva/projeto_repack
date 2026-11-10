<?php
declare(strict_types=1);

/**
 * Projeto Repack - Controller Operador
 * Baseado estritamente em docs/FSD.md
 */

require_once __DIR__ . '/../models/Usuario.php';

class OperadorController
{
    /**
     * Renderiza a tela principal da Urna Eletrônica
     */
    public function index(): void
    {
        require_once __DIR__ . '/../views/operador/home.php';
    }

    /**
     * Atualiza o operador ativo no quiosque da Urna Eletrônica
     */
    public function selecionarOperador(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $raw = file_get_contents('php://input');
        $dados = json_decode($raw, true) ?? [];

        $operadorId = (int)($dados['operador_id'] ?? 0);
        $operadorNome = (string)($dados['operador_nome'] ?? '');

        if ($operadorId <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Operador inválido.']);
            return;
        }

        $_SESSION['operador_id'] = $operadorId;
        $_SESSION['operador_nome'] = $operadorNome;

        echo json_encode([
            'status' => 'success',
            'operador_id' => $operadorId,
            'operador_nome' => $operadorNome
        ]);
    }
}
