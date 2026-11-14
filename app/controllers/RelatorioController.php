<?php
declare(strict_types=1);

/**
 * Projeto Repack - Controller RelatorioController
 * Baseado estritamente em docs/FSD.md (Seções 6, 8, 16, 22 e 24) e AGENTS.md
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Produto.php';
require_once __DIR__ . '/../models/LoteVenda.php';
require_once __DIR__ . '/../models/Pedido.php';
require_once __DIR__ . '/../models/ApontamentoBO.php';
require_once __DIR__ . '/../models/ApontamentoProdutividade.php';
require_once __DIR__ . '/../models/LogSistema.php';

class RelatorioController
{
    public function __construct()
    {
        AuthController::exigirGestao();
    }

    /**
     * Tela 6: Lista de Separação de Pedidos (docs/FSD.md Seções 16 e 22.1)
     */
    public function separacao(): void
    {
        $lotes = LoteVenda::listarTodos();

        // Determina o lote selecionado: parâmetro GET ou o primeiro lote disponível (preferencialmente fechado/aberto)
        $loteId = !empty($_GET['lote_id']) ? (int)$_GET['lote_id'] : 0;
        if ($loteId <= 0 && !empty($lotes)) {
            // Busca o primeiro lote encerrado ou aberto
            foreach ($lotes as $l) {
                if ($l['status'] === LoteVenda::STATUS_ENCERRADO || $l['status'] === LoteVenda::STATUS_ABERTO) {
                    $loteId = (int)$l['id'];
                    break;
                }
            }
            if ($loteId <= 0) {
                $loteId = (int)$lotes[0]['id'];
            }
        }

        $statusFiltro = !empty($_GET['status']) ? trim((string)$_GET['status']) : null;
        if ($statusFiltro === 'todos') {
            $statusFiltro = null;
        }

        $loteSelecionado = null;
        $separacaoAgrupada = [];
        $estatisticas = [
            'total_pedidos' => 0,
            'total_itens' => 0,
            'total_colaboradores' => 0,
            'total_confirmados' => 0,
            'total_separados' => 0,
            'total_entregues' => 0,
            'total_cancelados' => 0
        ];

        if ($loteId > 0) {
            $loteSelecionado = LoteVenda::buscarPorId($loteId);
            $separacaoAgrupada = Pedido::listarParaSeparacao($loteId, $statusFiltro);
            $estatisticas = Pedido::obterEstatisticasSeparacao($loteId);
        }

        $pageTitle = "Lista de Separação Física - " . APP_NAME;
        require __DIR__ . '/../views/gestao/separacao.php';
    }

    /**
     * Atualiza o status de conferência/separação de um pedido
     */
    public function atualizarStatusSeparacao(): void
    {
        $token = $_POST['csrf_token'] ?? null;
        if (!AuthController::validarCsrfToken($token)) {
            $this->responderErroOuRedirecionar("Sessão expirada. Tente novamente.");
            return;
        }

        $pedidoId = (int)($_POST['pedido_id'] ?? 0);
        $novoStatus = trim((string)($_POST['status'] ?? ''));
        $gestorId = (int)$_SESSION['usuario_id'];
        $loteId = (int)($_POST['lote_id'] ?? 0);

        try {
            $sucesso = Pedido::atualizarStatus($pedidoId, $novoStatus, $gestorId);

            if ($this->isAjax()) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'status' => $sucesso ? 'success' : 'error',
                    'message' => $sucesso ? "Status do pedido #{$pedidoId} atualizado para '{$novoStatus}'." : "Falha ao atualizar status."
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($sucesso) {
                $_SESSION['flash_sucesso'] = "Status do pedido atualizado para '" . ucfirst($novoStatus) . "'.";
            } else {
                $_SESSION['flash_erro'] = "Não foi possível alterar o status do pedido.";
            }
        } catch (\Throwable $e) {
            if ($this->isAjax()) {
                header('Content-Type: application/json; charset=UTF-8');
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $_SESSION['flash_erro'] = "Erro: " . $e->getMessage();
        }

        $redirectUrl = APP_URL . '/gestao/separacao' . ($loteId > 0 ? '?lote_id=' . $loteId : '');
        header('Location: ' . $redirectUrl);
        exit;
    }

    /**
     * Substitui um produto de um pedido por outro item em estoque (Avaria/Divergência na Separação)
     */
    public function substituirItem(): void
    {
        AuthController::exigirGestao();
        $gestorId = (int)($_SESSION['usuario_id'] ?? 1);

        $token = $_POST['csrf_token'] ?? '';
        if (!AuthController::validarCsrfToken($token)) {
            if ($this->isAjax()) {
                header('Content-Type: application/json; charset=UTF-8');
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido ou expirado.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $_SESSION['flash_erro'] = "Token CSRF inválido.";
            header('Location: ' . APP_URL . '/gestao/separacao');
            exit;
        }

        $pedidoId = (int)($_POST['pedido_id'] ?? 0);
        $itemId = (int)($_POST['item_id'] ?? 0);
        $novoProdutoId = (int)($_POST['novo_produto_id'] ?? 0);
        $motivo = trim((string)($_POST['motivo'] ?? ''));
        $darBaixaAvaria = !empty($_POST['baixa_avaria']);
        $loteId = (int)($_POST['lote_id'] ?? 0);

        try {
            $resultado = Pedido::substituirItem(
                $pedidoId, 
                $itemId, 
                $novoProdutoId, 
                $motivo, 
                $darBaixaAvaria, 
                $gestorId
            );

            if ($this->isAjax()) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
                exit;
            }

            $_SESSION['flash_sucesso'] = $resultado['message'];
        } catch (\Throwable $e) {
            if ($this->isAjax()) {
                header('Content-Type: application/json; charset=UTF-8');
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $_SESSION['flash_erro'] = "Erro ao substituir item: " . $e->getMessage();
        }

        $redirectUrl = APP_URL . '/gestao/separacao' . ($loteId > 0 ? '?lote_id=' . $loteId : '');
        header('Location: ' . $redirectUrl);
        exit;
    }

    /**
     * Exporta a lista de separação física em formato CSV (docs/FSD.md Seção 27.1)
     */
    public function exportarSeparacaoCsv(): void
    {
        $loteId = (int)($_GET['lote_id'] ?? 0);
        if ($loteId <= 0) {
            $_SESSION['flash_erro'] = "Lote inválido para exportação.";
            header('Location: ' . APP_URL . '/gestao/separacao');
            exit;
        }

        $lote = LoteVenda::buscarPorId($loteId);
        $itensAgrupados = Pedido::listarParaSeparacao($loteId);

        $nomeArquivo = 'separacao_lote_' . $loteId . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // BOM UTF-8 para correta leitura no Microsoft Excel
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'Lote',
            'Matrícula',
            'Colaborador',
            'Setor',
            'Código Pedido',
            'Status Pedido',
            'Código Produto',
            'Produto',
            'Embalagem Padrão',
            'Quantidade',
            'Data Pedido'
        ], ';');

        foreach ($itensAgrupados as $colab) {
            foreach ($colab['pedidos'] as $ped) {
                foreach ($ped['itens'] as $item) {
                    fputcsv($output, [
                        $lote['titulo'] ?? "Lote #{$loteId}",
                        $colab['matricula'],
                        $colab['nome'],
                        $colab['setor'],
                        $ped['codigo_pedido'],
                        ucfirst($ped['status']),
                        $item['codigo'],
                        $item['nome'],
                        $item['pacote_padrao'],
                        $item['quantidade'],
                        date('d/m/Y H:i', strtotime($ped['created_at']))
                    ], ';');
                }
            }
        }

        fclose($output);
        exit;
    }

    /**
     * Tela de Relatórios Consolidados (docs/FSD.md Seções 22.2 e 22.3)
     * - Relatório Sintético de Movimentação de Repack
     * - Extrato Analítico de Produtividade da Equipe
     */
    public function relatorios(): void
    {
        $dataInicio = trim((string)($_GET['data_inicio'] ?? ''));
        $dataFim = trim((string)($_GET['data_fim'] ?? ''));

        // Se ambos estiverem vazios, define padrão para os últimos 30 dias para visão inicial rica
        if (empty($dataInicio) && empty($dataFim) && !isset($_GET['filtrar'])) {
            $dataInicio = date('Y-m-d', strtotime('-30 days'));
            $dataFim = date('Y-m-d');
        }

        $relatorioBO = ApontamentoBO::obterRelatorioSintetico(
            !empty($dataInicio) ? $dataInicio : null,
            !empty($dataFim) ? $dataFim : null
        );

        $relatorioProd = ApontamentoProdutividade::obterExtratoAnalitico(
            !empty($dataInicio) ? $dataInicio : null,
            !empty($dataFim) ? $dataFim : null
        );

        $pageTitle = "Relatórios e Extratos Analíticos - " . APP_NAME;
        require __DIR__ . '/../views/gestao/relatorios.php';
    }

    /**
     * Exporta relatórios sintéticos e de produtividade em CSV
     */
    public function exportarRelatoriosCsv(): void
    {
        $tipo = trim((string)($_GET['tipo'] ?? 'bo'));
        $dataInicio = trim((string)($_GET['data_inicio'] ?? ''));
        $dataFim = trim((string)($_GET['data_fim'] ?? ''));

        if ($tipo === 'produtividade') {
            $dados = ApontamentoProdutividade::obterExtratoAnalitico(
                !empty($dataInicio) ? $dataInicio : null,
                !empty($dataFim) ? $dataFim : null
            );

            $nomeArquivo = 'relatorio_produtividade_' . date('Ymd_His') . '.csv';
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
            echo "\xEF\xBB\xBF";
            $output = fopen('php://output', 'w');

            fputcsv($output, ['--- PRODUTIVIDADE POR OPERADOR ---'], ';');
            fputcsv($output, ['Matrícula', 'Operador', 'Ciclos', 'Pacotes Montados', 'Tempo Total (Horas)', 'Pacotes/Hora', 'Tempo Médio Ciclo (s)'], ';');
            foreach ($dados['por_operador'] as $op) {
                fputcsv($output, [
                    $op['matricula'],
                    $op['nome'],
                    $op['total_ciclos'],
                    $op['total_pacotes'],
                    $op['total_horas'],
                    $op['pacotes_por_hora'],
                    $op['tempo_medio_ciclo']
                ], ';');
            }

            fputcsv($output, [], ';');
            fputcsv($output, ['--- TEMPO MÉDIO POR PRODUTO ---'], ';');
            fputcsv($output, ['Código', 'Produto', 'Embalagem', 'Ciclos', 'Pacotes Montados', 'Tempo Médio/Pacote (s)', 'Pacotes/Hora'], ';');
            foreach ($dados['por_produto'] as $p) {
                fputcsv($output, [
                    $p['codigo'],
                    $p['nome'],
                    $p['pacote_padrao'],
                    $p['total_ciclos'],
                    $p['total_pacotes'],
                    $p['tempo_medio_pacote_segundos'],
                    $p['pacotes_por_hora']
                ], ';');
            }

            fclose($output);
            exit;
        }

        // Exportação padrão: Relatório Sintético de Movimentação de Repack (B.O.)
        $dados = ApontamentoBO::obterRelatorioSintetico(
            !empty($dataInicio) ? $dataInicio : null,
            !empty($dataFim) ? $dataFim : null
        );

        $nomeArquivo = 'relatorio_repack_bo_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        echo "\xEF\xBB\xBF";
        $output = fopen('php://output', 'w');

        fputcsv($output, ['--- RELATÓRIO SINTÉTICO DE MOVIMENTAÇÃO DE REPACK ---'], ';');
        fputcsv($output, ['Métrica', 'Valor', 'Percentual'], ';');
        fputcsv($output, ['Total de Unidades Recebidas/Avariadas', $dados['total_unidades'], '100%'], ';');
        fputcsv($output, ['Direcionadas para Lojinha (Recuperadas)', $dados['unidades_lojinha'], $dados['percentual_lojinha'] . '%'], ';');
        fputcsv($output, ['Direcionadas para Descarte (Perdas)', $dados['unidades_descarte'], $dados['percentual_descarte'] . '%'], ';');

        fputcsv($output, [], ';');
        fputcsv($output, ['--- DISTRIBUIÇÃO POR TIPO DE AVARIA ---'], ';');
        fputcsv($output, ['Tipo de Avaria', 'Destino', 'Ocorrências', 'Quantidade', 'Percentual'], ';');
        foreach ($dados['por_tipo'] as $t) {
            fputcsv($output, [
                ucwords(str_replace('_', ' ', $t['tipo_avaria'])),
                ucfirst($t['destino']),
                $t['total_ocorrencias'],
                $t['quantidade_total'],
                $t['percentual'] . '%'
            ], ';');
        }

        fputcsv($output, [], ';');
        fputcsv($output, ['--- MOVIMENTAÇÃO POR PRODUTO ---'], ';');
        fputcsv($output, ['Código', 'Produto', 'Embalagem', 'Total Unidades', 'Lojinha', 'Descarte', 'Taxa Recuperação'], ';');
        foreach ($dados['por_produto'] as $pr) {
            fputcsv($output, [
                $pr['codigo'],
                $pr['nome'],
                $pr['pacote_padrao'],
                $pr['total_unidades'],
                $pr['unidades_lojinha'],
                $pr['unidades_descarte'],
                $pr['taxa_recuperacao'] . '%'
            ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Detecta se a requisição é assíncrona (AJAX / Fetch)
     */
    private function isAjax(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);
    }

    /**
     * Responde erro em JSON ou redireciona com mensagem flash
     */
    private function responderErroOuRedirecionar(string $mensagem): void
    {
        if ($this->isAjax()) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => $mensagem], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $_SESSION['flash_erro'] = $mensagem;
        header('Location: ' . APP_URL . '/gestao/separacao');
        exit;
    }
}
