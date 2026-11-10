<?php
declare(strict_types=1);

/**
 * Projeto Repack - Controller Lojinha Virtual (Colaborador)
 * Baseado estritamente em docs/FSD.md (Seções 6.3, 8, 9, 12.2, 13.3, 14, 15, 16, 23 e 24) e docs/DESIGN.md
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/LoteVenda.php';
require_once __DIR__ . '/../models/EstoqueLojinha.php';
require_once __DIR__ . '/../models/Pedido.php';
require_once __DIR__ . '/../models/LogSistema.php';

class LojinhaController
{
    /**
     * Garante que o colaborador está autenticado e com lote aberto ativo
     * @return array [usuario, lote]
     */
    private function exigirColaborador(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $colaboradorId = $_SESSION['colaborador_id'] ?? null;
        if (!$colaboradorId) {
            header('Location: /lojinha/login');
            exit;
        }

        $usuario = Usuario::buscarPorId((int)$colaboradorId);
        if (!$usuario || (int)$usuario['ativo'] !== 1) {
            $this->logout();
            exit;
        }

        $lote = LoteVenda::buscarLoteAtivoParaSetor($usuario['setor']);
        if (!$lote) {
            $_SESSION['flash_aviso'] = "O lote de compras para o seu setor ({$usuario['setor']}) foi encerrado ou expirou.";
            header('Location: /lojinha/login');
            exit;
        }

        $_SESSION['lojinha_lote_id'] = (int)$lote['id'];

        return [
            'usuario' => $usuario,
            'lote' => $lote
        ];
    }

    /**
     * Tela de Identificação rápida do Colaborador (por Matrícula e validação de Setor)
     * Conforme docs/FSD.md Seções 12.2, 13.3 e 15
     */
    public function login(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $erro = '';
        $aviso = $_SESSION['flash_aviso'] ?? '';
        unset($_SESSION['flash_aviso']);

        // Se já autenticado e com lote ativo, redireciona para a vitrine
        if (!empty($_SESSION['colaborador_id']) && !empty($_SESSION['colaborador_setor'])) {
            $lote = LoteVenda::buscarLoteAtivoParaSetor((string)$_SESSION['colaborador_setor']);
            if ($lote) {
                header('Location: /lojinha');
                exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $matricula = trim((string)($_POST['matricula'] ?? ''));

            if ($matricula === '') {
                $erro = 'Por favor, digite sua matrícula corporativa.';
            } else {
                $usuario = Usuario::buscarPorMatricula($matricula);

                if (!$usuario || (int)$usuario['ativo'] !== 1) {
                    $erro = 'Matrícula não encontrada ou cadastro inativo no sistema.';
                } else {
                    // Valida se há lote de venda aberto no momento para o setor deste colaborador
                    $lote = LoteVenda::buscarLoteAtivoParaSetor($usuario['setor']);

                    if (!$lote) {
                        $proximoLote = LoteVenda::buscarProximoLotePlanejado();
                        $previsao = '';
                        if ($proximoLote) {
                            $dtInicio = date('d/m/Y \à\s H:i', strtotime($proximoLote['data_inicio']));
                            $previsao = " Previsão do próximo lote: {$dtInicio}.";
                        }

                        $erro = "Olá, {$usuario['nome']}! Não há lote de vendas aberto no momento para o seu setor ({$usuario['setor']}).{$previsao} Aguarde os comunicados da Gestão.";
                    } else {
                        // Identificação bem-sucedida: vincula sessão do colaborador
                        $_SESSION['colaborador_id'] = (int)$usuario['id'];
                        $_SESSION['colaborador_nome'] = $usuario['nome'];
                        $_SESSION['colaborador_matricula'] = $usuario['matricula'];
                        $_SESSION['colaborador_setor'] = $usuario['setor'];
                        $_SESSION['lojinha_lote_id'] = (int)$lote['id'];

                        header('Location: /lojinha');
                        exit;
                    }
                }
            }
        }

        require __DIR__ . '/../views/lojinha/login.php';
    }

    /**
     * Encerra a sessão do colaborador na lojinha
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        unset(
            $_SESSION['colaborador_id'],
            $_SESSION['colaborador_nome'],
            $_SESSION['colaborador_matricula'],
            $_SESSION['colaborador_setor'],
            $_SESSION['lojinha_lote_id']
        );

        header('Location: /lojinha/login');
        exit;
    }

    /**
     * Vitrine de Produtos da Lojinha Virtual
     * Conforme docs/FSD.md Seções 6.3, 9, 12.2 e 13.3
     */
    public function vitrine(): void
    {
        $auth = $this->exigirColaborador();
        $usuario = $auth['usuario'];
        $lote = $auth['lote'];

        $flashSucesso = $_SESSION['flash_sucesso'] ?? '';
        $flashErro = $_SESSION['flash_erro'] ?? '';
        unset($_SESSION['flash_sucesso'], $_SESSION['flash_erro']);

        // Carrega produtos com o saldo dinâmico (Fórmula: Total - Reservas - Carrinhos Ativos)
        $produtos = EstoqueLojinha::listarProdutosVitrine((int)$lote['id'], (int)$usuario['id']);

        // Resumo de limites e cotas do colaborador
        $resumo = Pedido::obterResumoComprador((int)$lote['id'], (int)$usuario['id']);

        require __DIR__ . '/../views/lojinha/vitrine.php';
    }

    /**
     * Sacola / Carrinho de Compras com Cronômetro de Trava Temporária (10 minutos)
     * Conforme docs/FSD.md Seções 12.2 e 13.3
     */
    public function carrinho(): void
    {
        $auth = $this->exigirColaborador();
        $usuario = $auth['usuario'];
        $lote = $auth['lote'];

        $flashSucesso = $_SESSION['flash_sucesso'] ?? '';
        $flashErro = $_SESSION['flash_erro'] ?? '';
        unset($_SESSION['flash_sucesso'], $_SESSION['flash_erro']);

        // Lista itens válidos na reserva do colaborador
        $itensCarrinho = Pedido::listarCarrinho((int)$lote['id'], (int)$usuario['id']);

        // Resumo consolidado de cotas
        $resumo = Pedido::obterResumoComprador((int)$lote['id'], (int)$usuario['id']);

        require __DIR__ . '/../views/lojinha/carrinho.php';
    }

    /**
     * Remove um item específico do carrinho
     */
    public function removerItem(): void
    {
        $auth = $this->exigirColaborador();
        $usuario = $auth['usuario'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $carrinhoId = (int)($_POST['carrinho_id'] ?? 0);
            if ($carrinhoId > 0) {
                Pedido::removerDoCarrinho($carrinhoId, (int)$usuario['id']);
                $_SESSION['flash_sucesso'] = "Item removido da sacola com sucesso.";
            }
        }

        header('Location: /lojinha/carrinho');
        exit;
    }

    /**
     * Esvazia todos os itens do carrinho do colaborador
     */
    public function esvaziar(): void
    {
        $auth = $this->exigirColaborador();
        $usuario = $auth['usuario'];
        $lote = $auth['lote'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Pedido::esvaziarCarrinho((int)$lote['id'], (int)$usuario['id']);
            $_SESSION['flash_sucesso'] = "Todos os itens foram liberados da sua sacola.";
        }

        header('Location: /lojinha/carrinho');
        exit;
    }

    /**
     * Finalização Transacional do Pedido (Checkout)
     * Conforme docs/FSD.md Seções 13.3 e 14
     */
    public function checkout(): void
    {
        $auth = $this->exigirColaborador();
        $usuario = $auth['usuario'];
        $lote = $auth['lote'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /lojinha/carrinho');
            exit;
        }

        try {
            $resultado = Pedido::finalizarCheckout(
                (int)$lote['id'],
                (int)$usuario['id'],
                session_id()
            );

            header('Location: /lojinha/sucesso?codigo=' . urlencode($resultado['codigo_pedido']));
            exit;
        } catch (\Throwable $e) {
            $_SESSION['flash_erro'] = $e->getMessage();
            header('Location: /lojinha/carrinho');
            exit;
        }
    }

    /**
     * Tela de Sucesso e Comprovante do Pedido
     * Conforme docs/FSD.md Seções 12.2 e 13.3
     */
    public function sucesso(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $codigo = trim((string)($_GET['codigo'] ?? ''));
        if ($codigo === '') {
            header('Location: /lojinha');
            exit;
        }

        $pedido = Pedido::buscarPorCodigo($codigo);
        if (!$pedido) {
            header('Location: /lojinha');
            exit;
        }

        require __DIR__ . '/../views/lojinha/sucesso.php';
    }

    // -----------------------------------------------------------------
    // Endpoints Assíncronos da Lojinha (JSON)
    // -----------------------------------------------------------------

    /**
     * Adiciona item ao carrinho com reserva de 10 minutos
     * Endpoint: POST /api/carrinho/adicionar
     */
    public function apiAdicionarCarrinho(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $colaboradorId = (int)($_SESSION['colaborador_id'] ?? 0);
        $setor = (string)($_SESSION['colaborador_setor'] ?? '');

        if ($colaboradorId <= 0 || $setor === '') {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Identificação de colaborador necessária. Faça login novamente.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $lote = LoteVenda::buscarLoteAtivoParaSetor($setor);
        if (!$lote) {
            http_response_code(403);
            echo json_encode([
                'status' => 'error',
                'message' => 'Lote de vendas encerrado ou indisponível para o seu setor.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Lê dados via JSON ou POST tradicional
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }

        $produtoId = (int)($input['produto_id'] ?? 0);
        $quantidade = max(1, (int)($input['quantidade'] ?? 1));

        if ($produtoId <= 0) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'ID do produto inválido.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $retorno = Pedido::adicionarAoCarrinho(
                (int)$lote['id'],
                $colaboradorId,
                $produtoId,
                $quantidade,
                session_id()
            );

            // Resumo atualizado após a adição
            $resumo = Pedido::obterResumoComprador((int)$lote['id'], $colaboradorId);

            echo json_encode([
                'status' => 'success',
                'message' => 'Produto adicionado à sacola com reserva garantida por 10 minutos!',
                'dados' => $retorno,
                'resumo' => $resumo
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Throwable $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Remove item do carrinho
     * Endpoint: POST /api/carrinho/remover
     */
    public function apiRemoverCarrinho(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $colaboradorId = (int)($_SESSION['colaborador_id'] ?? 0);
        if ($colaboradorId <= 0) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Não autenticado.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }

        $carrinhoId = (int)($input['carrinho_id'] ?? 0);
        if ($carrinhoId <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID de reserva inválido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $removido = Pedido::removerDoCarrinho($carrinhoId, $colaboradorId);

        echo json_encode([
            'status' => $removido ? 'success' : 'error',
            'message' => $removido ? 'Item removido da reserva.' : 'Falha ao remover item.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Consulta saldo dinâmico atualizado de produto em vitrine
     * Endpoint: GET /api/estoque/saldo
     */
    public function apiSaldo(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        $produtoId = (int)($_GET['produto_id'] ?? 0);
        if ($produtoId <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID do produto inválido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $saldo = EstoqueLojinha::obterSaldoDisponivel($produtoId);

        echo json_encode([
            'status' => 'success',
            'produto_id' => $produtoId,
            'saldo_disponivel' => $saldo
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
