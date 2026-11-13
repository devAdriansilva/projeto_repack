<?php
declare(strict_types=1);

/**
 * Projeto Repack - Controller Operador
 * Baseado estritamente em docs/FSD.md
 */

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Configuracao.php';
require_once __DIR__ . '/../models/EstoqueLojinha.php';
require_once __DIR__ . '/../models/LogSistema.php';

class OperadorController
{
    /**
     * Renderiza a tela principal da Urna Eletrônica
     */
    public function index(): void
    {
        if (!empty($_GET['limpar_bo'])) {
            unset($_SESSION['avaria_temp']);
        }
        if (!empty($_GET['limpar_cronometro'])) {
            unset($_SESSION['cronometro_temp']);
        }
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

    /**
     * Define o operador padrão global no banco de dados
     * Permite que qualquer gestor altere através de outro dispositivo
     */
    public function definirPadrao(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $raw = file_get_contents('php://input');
        $dados = json_decode($raw, true) ?? [];

        $operadorId = (int)($dados['operador_id'] ?? 0);
        $usuario = Usuario::buscarPorId($operadorId);

        if (!$usuario || (int)$usuario['ativo'] !== 1 || $usuario['perfil'] !== 'operador') {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Operador inválido ou inativo.']);
            return;
        }

        // Grava no banco de dados para sincronizar entre todos os dispositivos
        Configuracao::salvar('operador_padrao_id', (string)$operadorId, 'ID do Operador Padrão da bancada de Repack');

        // Atualiza a sessão corrente
        $_SESSION['operador_id'] = $operadorId;
        $_SESSION['operador_nome'] = $usuario['nome'];
        $_SESSION['ultimo_padrao_id'] = $operadorId;

        echo json_encode([
            'status' => 'success',
            'operador_id' => $operadorId,
            'operador_nome' => $usuario['nome'],
            'message' => 'Operador padrão atualizado para ' . $usuario['nome'] . ' com sucesso!'
        ]);
    }

    /**
     * Tela de baixa de itens da lojinha (Operador / Bancada)
     */
    public function baixaLojinha(): void
    {
        require_once __DIR__ . '/../views/operador/baixa_lojinha.php';
    }

    /**
     * Endpoint API para registrar a baixa solicitada pelo operador
     */
    public function apiBaixaLojinha(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $raw = file_get_contents('php://input');
        $dados = json_decode($raw, true) ?? [];

        $produtoId = (int)($dados['produto_id'] ?? 0);
        $quantidadeBaixa = (int)($dados['quantidade_baixa'] ?? 0);
        $motivo = trim((string)($dados['motivo'] ?? 'Descarte por perda na lojinha'));
        $operadorId = (int)($dados['usuario_id'] ?? ($_SESSION['operador_id'] ?? 1));

        if ($produtoId <= 0 || $quantidadeBaixa <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Produto e quantidade válidos são obrigatórios.']);
            return;
        }

        try {
            EstoqueLojinha::registrarBaixaAdministrativa($produtoId, $quantidadeBaixa, $operadorId, $motivo);

            LogSistema::registrar('INFO', "Baixa registrada pelo operador: {$quantidadeBaixa} un. do produto #{$produtoId} ({$motivo})", [
                'produto_id' => $produtoId,
                'quantidade' => $quantidadeBaixa,
                'motivo' => $motivo,
                'operador_id' => $operadorId
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Baixa registrada e estoque da lojinha abatido com sucesso.'
            ]);
        } catch (\Throwable $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
