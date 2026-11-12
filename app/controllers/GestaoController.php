<?php
declare(strict_types=1);

/**
 * Projeto Repack - Controller GestaoController
 * Baseado estritamente em docs/FSD.md (Seções 6, 8, 12.3, 14, 16, 17, 21 e 24) e AGENTS.md
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Produto.php';
require_once __DIR__ . '/../models/LoteVenda.php';
require_once __DIR__ . '/../models/EstoqueLojinha.php';
require_once __DIR__ . '/../models/ApontamentoBO.php';
require_once __DIR__ . '/../models/LogSistema.php';
require_once __DIR__ . '/../models/Configuracao.php';

class GestaoController
{
    public function __construct()
    {
        AuthController::exigirGestao();
    }

    /**
     * Tela 2: Painel Consolidado de Gestão
     */
    public function dashboard(): void
    {
        $pdo = Database::getConnection();

        // 1. Métricas de Avarias (B.O.)
        $stmtBO = $pdo->query("
            SELECT 
                COUNT(*) AS total_bo,
                COALESCE(SUM(CASE WHEN destino = 'lojinha' THEN quantidade ELSE 0 END), 0) AS total_recuperado_lojinha,
                COALESCE(SUM(CASE WHEN destino = 'descarte' THEN quantidade ELSE 0 END), 0) AS total_descartado,
                COALESCE(SUM(quantidade), 0) AS total_unidades_avariadas
            FROM apontamentos_bo
            WHERE deleted_at IS NULL
        ");
        $metricasBO = $stmtBO->fetch();

        $totalAvarias = (int)($metricasBO['total_unidades_avariadas'] ?? 0);
        $totalRecuperado = (int)($metricasBO['total_recuperado_lojinha'] ?? 0);
        $totalDescarte = (int)($metricasBO['total_descartado'] ?? 0);
        $taxaRecuperacao = $totalAvarias > 0 ? round(($totalRecuperado / $totalAvarias) * 100, 1) : 0.0;

        // 2. Métricas de Estoque da Lojinha
        $stmtEstoque = $pdo->query("
            SELECT 
                COALESCE(SUM(quantidade_total), 0) AS estoque_total,
                COALESCE(SUM(quantidade_reservada_gestao), 0) AS total_reservado
            FROM estoque_lojinha
        ");
        $metricasEstoque = $stmtEstoque->fetch();
        $estoqueTotal = (int)($metricasEstoque['estoque_total'] ?? 0);
        $estoqueReservado = (int)($metricasEstoque['total_reservado'] ?? 0);
        $estoqueDisponivel = max(0, $estoqueTotal - $estoqueReservado);

        // 3. Métricas de Produtividade Operacional
        $stmtProd = $pdo->query("
            SELECT 
                COUNT(*) AS total_ciclos,
                COALESCE(SUM(pacotes_refeitos), 0) AS total_pacotes,
                COALESCE(SUM(duracao_segundos), 0) AS total_segundos
            FROM apontamentos_produtividade
            WHERE deleted_at IS NULL
        ");
        $metricasProd = $stmtProd->fetch();
        $totalPacotes = (int)($metricasProd['total_pacotes'] ?? 0);
        $totalSegundos = (int)($metricasProd['total_segundos'] ?? 0);
        $tempoMedioPorPacote = $totalPacotes > 0 ? round($totalSegundos / $totalPacotes, 0) : 0;

        // 4. Lotes Ativos
        $stmtLotes = $pdo->query("
            SELECT COUNT(*) 
            FROM lotes_venda 
            WHERE status = 'aberto' AND deleted_at IS NULL
        ");
        $lotesAtivos = (int)$stmtLotes->fetchColumn();

        // 5. Últimos 10 B.O.s registrados
        $ultimosBOs = ApontamentoBO::listarComFiltros(['limite' => 10]);

        // 6. Dados dos 5 produtos com maior estoque na lojinha
        $stmtTopEstoque = $pdo->query("
            SELECT p.nome, p.codigo, el.quantidade_total, el.quantidade_reservada_gestao,
                   (el.quantidade_total - el.quantidade_reservada_gestao) AS disponivel
            FROM estoque_lojinha el
            INNER JOIN produtos p ON p.id = el.produto_id
            WHERE p.deleted_at IS NULL AND el.quantidade_total > 0
            ORDER BY el.quantidade_total DESC
            LIMIT 5
        ");
        $topEstoque = $stmtTopEstoque->fetchAll();

        // 7. Operadores ativos e Operador Padrão global
        $operadoresAtivos = Usuario::listarOperadoresAtivos();
        $operadorPadrao = Configuracao::obterOperadorPadrao();

        $pageTitle = "Painel da Gestão - " . APP_NAME;
        require __DIR__ . '/../views/gestao/dashboard.php';
    }

    /**
     * Tela 7: Catálogo de Produtos e Fotos
     */
    public function produtos(): void
    {
        $produtos = Produto::listarTodos();
        $pageTitle = "Gestão de Produtos - " . APP_NAME;
        require __DIR__ . '/../views/gestao/produtos.php';
    }

    /**
     * Salva ou atualiza um produto com upload de foto
     */
    public function salvarProduto(): void
    {
        $this->validarCsrfOuAbortar();

        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $codigo = trim((string)($_POST['codigo'] ?? ''));
        $nome = trim((string)($_POST['nome'] ?? ''));
        $pacotePadrao = trim((string)($_POST['pacote_padrao'] ?? 'Unidade'));
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        // Tratamento de upload de foto (docs/FSD.md Seção 21)
        $fotoUrl = null;
        if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fotoUrl = $this->processarUploadFoto($_FILES['foto']);
        }

        try {
            $dados = [
                'id' => $id,
                'codigo' => $codigo,
                'nome' => $nome,
                'pacote_padrao' => $pacotePadrao,
                'ativo' => $ativo
            ];
            if ($fotoUrl !== null) {
                $dados['foto_url'] = $fotoUrl;
            }

            $produtoId = Produto::salvar($dados);

            LogSistema::registrar('INFO', "Produto {$codigo} - {$nome} salvo com sucesso", [
                'produto_id' => $produtoId,
                'gestor_id' => $_SESSION['usuario_id']
            ]);

            $_SESSION['flash_sucesso'] = "Produto '{$nome}' salvo com sucesso!";
        } catch (\Throwable $e) {
            $_SESSION['flash_erro'] = "Erro ao salvar produto: " . $e->getMessage();
        }

        header('Location: ' . APP_URL . '/gestao/produtos');
        exit;
    }

    /**
     * Alterna o status ativo/inativo de um produto
     */
    public function toggleStatusProduto(): void
    {
        $this->validarCsrfOuAbortar();
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            Produto::alternarStatus($id);
            LogSistema::registrar('INFO', "Status do produto #{$id} alterado", ['gestor_id' => $_SESSION['usuario_id']]);
            $_SESSION['flash_sucesso'] = "Status do produto atualizado.";
        }

        header('Location: ' . APP_URL . '/gestao/produtos');
        exit;
    }

    /**
     * Tela 4: Controle de Lotes de Venda
     */
    public function lotes(): void
    {
        $lotes = LoteVenda::listarTodos();
        $setoresDisponiveis = Usuario::listarSetores();
        $pageTitle = "Lotes de Venda - " . APP_NAME;
        require __DIR__ . '/../views/gestao/lotes.php';
    }

    /**
     * Salva ou atualiza um lote de venda
     */
    public function salvarLote(): void
    {
        $this->validarCsrfOuAbortar();

        try {
            $gestorId = (int)$_SESSION['usuario_id'];
            $loteId = LoteVenda::salvar($_POST, $gestorId);

            LogSistema::registrar('INFO', "Lote de venda #{$loteId} salvo com sucesso", [
                'lote_id' => $loteId,
                'gestor_id' => $gestorId
            ]);

            $_SESSION['flash_sucesso'] = "Lote de venda salvo com sucesso!";
        } catch (\Throwable $e) {
            $_SESSION['flash_erro'] = "Erro ao salvar lote: " . $e->getMessage();
        }

        header('Location: ' . APP_URL . '/gestao/lotes');
        exit;
    }

    /**
     * Altera o status de um lote (planejado, aberto, encerrado)
     */
    public function alterarStatusLote(): void
    {
        $this->validarCsrfOuAbortar();

        $id = (int)($_POST['id'] ?? 0);
        $novoStatus = (string)($_POST['status'] ?? '');

        if ($id > 0 && LoteVenda::alterarStatus($id, $novoStatus)) {
            LogSistema::registrar('INFO', "Status do lote #{$id} alterado para {$novoStatus}", [
                'lote_id' => $id,
                'gestor_id' => $_SESSION['usuario_id']
            ]);
            $_SESSION['flash_sucesso'] = "Status do lote alterado para " . ucfirst($novoStatus) . ".";
        } else {
            $_SESSION['flash_erro'] = "Não foi possível alterar o status do lote.";
        }

        header('Location: ' . APP_URL . '/gestao/lotes');
        exit;
    }

    /**
     * Exclui logicamente um lote
     */
    public function excluirLote(): void
    {
        $this->validarCsrfOuAbortar();
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            LoteVenda::excluir($id);
            LogSistema::registrar('INFO', "Lote #{$id} excluído", ['gestor_id' => $_SESSION['usuario_id']]);
            $_SESSION['flash_sucesso'] = "Lote excluído com sucesso.";
        }

        header('Location: ' . APP_URL . '/gestao/lotes');
        exit;
    }

    /**
     * Tela 5: Gestão de Estoque, Reservas Ocultas e Baixas Administrativas
     */
    public function estoque(): void
    {
        $estoqueGeral = EstoqueLojinha::listarVisaoGeral();
        $todosProdutos = Produto::listarAtivos();
        $historicoReservas = EstoqueLojinha::listarHistoricoReservas(null, 30);
        $historicoBaixas = EstoqueLojinha::listarHistoricoBaixas(null, 30);
        $historicoInventarios = EstoqueLojinha::listarHistoricoInventarios(null, 30);
        $pageTitle = "Gestão de Estoque da Lojinha - " . APP_NAME;
        require __DIR__ . '/../views/gestao/estoque.php';
    }

    /**
     * Salva ajuste manual de Reserva Oculta
     */
    public function salvarReserva(): void
    {
        $this->validarCsrfOuAbortar();

        $produtoId = (int)($_POST['produto_id'] ?? 0);
        $quantidade = (int)($_POST['quantidade_reserva'] ?? 0);
        $motivo = trim((string)($_POST['motivo'] ?? ''));
        $gestorId = (int)$_SESSION['usuario_id'];

        try {
            EstoqueLojinha::ajustarReservaOculta($produtoId, $quantidade, $gestorId, $motivo);

            LogSistema::registrar('INFO', "Reserva oculta ajustada para o produto #{$produtoId}: {$quantidade} un.", [
                'produto_id' => $produtoId,
                'reserva' => $quantidade,
                'motivo' => $motivo,
                'gestor_id' => $gestorId
            ]);

            $_SESSION['flash_sucesso'] = "Reserva oculta atualizada com sucesso!";
        } catch (\Throwable $e) {
            $_SESSION['flash_erro'] = "Falha ao ajustar reserva: " . $e->getMessage();
        }

        header('Location: ' . APP_URL . '/gestao/estoque');
        exit;
    }

    /**
     * Registra Baixa Administrativa de perdas ocorridas na sala de espera
     */
    public function salvarBaixa(): void
    {
        $this->validarCsrfOuAbortar();

        $produtoId = (int)($_POST['produto_id'] ?? 0);
        $quantidade = (int)($_POST['quantidade_baixa'] ?? 0);
        $motivo = trim((string)($_POST['motivo'] ?? ''));
        $gestorId = (int)$_SESSION['usuario_id'];

        try {
            EstoqueLojinha::registrarBaixaAdministrativa($produtoId, $quantidade, $gestorId, $motivo);

            LogSistema::registrar('INFO', "Baixa administrativa de {$quantidade} un. registrada para o produto #{$produtoId}", [
                'produto_id' => $produtoId,
                'quantidade' => $quantidade,
                'motivo' => $motivo,
                'gestor_id' => $gestorId
            ]);

            $_SESSION['flash_sucesso'] = "Baixa administrativa registrada e estoque abatido!";
        } catch (\Throwable $e) {
            $_SESSION['flash_erro'] = "Falha ao registrar baixa: " . $e->getMessage();
        }

        header('Location: ' . APP_URL . '/gestao/estoque');
        exit;
    }

    /**
     * Registra Inventário / Balanço de Estoque (por lote consolidado ou unitário)
     */
    public function salvarInventario(): void
    {
        $this->validarCsrfOuAbortar();

        $gestorId = (int)$_SESSION['usuario_id'];
        $motivoPadrao = trim((string)($_POST['motivo'] ?? ''));
        if ($motivoPadrao === '') {
            $motivoPadrao = 'Inventário periódico de rotina';
        }

        $payloadJson = trim((string)($_POST['payload_inventario_json'] ?? ''));

        // Se veio payload em lote (nova metodologia de conciliação por confronto)
        if ($payloadJson !== '') {
            $itens = json_decode($payloadJson, true);
            if (!is_array($itens) || empty($itens)) {
                $_SESSION['flash_erro'] = "Payload de inventário inválido ou vazio.";
                header('Location: ' . APP_URL . '/gestao/estoque');
                exit;
            }

            $sucessos = 0;
            $erros = [];

            foreach ($itens as $item) {
                $pid = (int)($item['produto_id'] ?? 0);
                $novoSaldo = max(0, (int)($item['novo_saldo'] ?? 0));
                $detalhes = !empty($item['detalhes_contagem']) ? (string)$item['detalhes_contagem'] : null;

                if ($pid <= 0) {
                    continue;
                }

                try {
                    EstoqueLojinha::realizarInventario(
                        $pid,
                        $novoSaldo,
                        'substituicao',
                        $gestorId,
                        $motivoPadrao,
                        $detalhes
                    );
                    $sucessos++;
                    LogSistema::registrar('INFO', "Inventário em lote realizado para o produto #{$pid}: {$novoSaldo} un.", [
                        'produto_id' => $pid,
                        'novo_saldo' => $novoSaldo,
                        'detalhes' => $detalhes,
                        'gestor_id' => $gestorId
                    ]);
                } catch (\Throwable $e) {
                    $erros[] = "Produto #{$pid}: " . $e->getMessage();
                }
            }

            if ($sucessos > 0) {
                $_SESSION['flash_sucesso'] = "Inventário concluído com sucesso! {$sucessos} produto(s) conciliado(s).";
            }
            if (!empty($erros)) {
                $_SESSION['flash_erro'] = "Ocorreram erros parciais: " . implode('; ', $erros);
            }

            header('Location: ' . APP_URL . '/gestao/estoque');
            exit;
        }

        // Modo unitário / legado
        $produtoId = (int)($_POST['produto_id'] ?? 0);
        $quantidadeContada = (int)($_POST['quantidade_contada'] ?? 0);
        $tipoOperacao = trim((string)($_POST['tipo_operacao'] ?? 'substituicao'));
        $detalhesContagem = trim((string)($_POST['detalhes_contagem'] ?? ''));

        try {
            EstoqueLojinha::realizarInventario(
                $produtoId,
                $quantidadeContada,
                $tipoOperacao,
                $gestorId,
                $motivoPadrao,
                $detalhesContagem !== '' ? $detalhesContagem : null
            );

            LogSistema::registrar('INFO', "Inventário realizado para o produto #{$produtoId}: {$quantidadeContada} un. ({$tipoOperacao})", [
                'produto_id' => $produtoId,
                'quantidade' => $quantidadeContada,
                'tipo_operacao' => $tipoOperacao,
                'detalhes' => $detalhesContagem,
                'motivo' => $motivoPadrao,
                'gestor_id' => $gestorId
            ]);

            $_SESSION['flash_sucesso'] = "Inventário / Balanço de estoque registrado com sucesso!";
        } catch (\Throwable $e) {
            $_SESSION['flash_erro'] = "Falha ao registrar inventário: " . $e->getMessage();
        }

        header('Location: ' . APP_URL . '/gestao/estoque');
        exit;
    }

    /**
     * Tela 3: Auditoria e Correção de B.O.
     */
    public function correcaoBo(): void
    {
        $filtros = [
            'data_inicio' => trim((string)($_GET['data_inicio'] ?? '')),
            'data_fim' => trim((string)($_GET['data_fim'] ?? '')),
            'produto_id' => !empty($_GET['produto_id']) ? (int)$_GET['produto_id'] : null,
            'usuario_id' => !empty($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : null,
            'destino' => trim((string)($_GET['destino'] ?? '')),
            'tipo_avaria' => trim((string)($_GET['tipo_avaria'] ?? '')),
            'limite' => 150
        ];

        $apontamentos = ApontamentoBO::listarComFiltros($filtros);
        $produtos = Produto::listarTodos();
        $operadores = Usuario::listarOperadoresAtivos();

        $pageTitle = "Correção de B.O. - " . APP_NAME;
        require __DIR__ . '/../views/gestao/correcao_bo.php';
    }

    /**
     * Aplica a retificação em um apontamento de B.O.
     */
    public function salvarCorrecaoBo(): void
    {
        $this->validarCsrfOuAbortar();

        $boId = (int)($_POST['bo_id'] ?? 0);
        $quantidade = (int)($_POST['quantidade'] ?? 0);
        $tipoAvaria = (string)($_POST['tipo_avaria'] ?? '');
        $motivo = trim((string)($_POST['motivo_correcao'] ?? ''));
        $gestorId = (int)$_SESSION['usuario_id'];

        try {
            ApontamentoBO::corrigir($boId, [
                'quantidade' => $quantidade,
                'tipo_avaria' => $tipoAvaria
            ], $gestorId, $motivo);

            LogSistema::registrar('INFO', "B.O. #{$boId} retificado pela Gestão", [
                'bo_id' => $boId,
                'nova_quantidade' => $quantidade,
                'novo_tipo' => $tipoAvaria,
                'motivo' => $motivo,
                'gestor_id' => $gestorId
            ]);

            $_SESSION['flash_sucesso'] = "Apontamento #{$boId} retificado com sucesso!";
        } catch (\Throwable $e) {
            $_SESSION['flash_erro'] = "Erro ao retificar B.O.: " . $e->getMessage();
        }

        header('Location: ' . APP_URL . '/gestao/bo');
        exit;
    }

    /**
     * Valida o token CSRF obrigatório para a Gestão
     */
    private function validarCsrfOuAbortar(): void
    {
        $token = $_POST['csrf_token'] ?? null;
        if (!AuthController::validarCsrfToken($token)) {
            LogSistema::registrar('SECURITY', "Falha de validação CSRF em formulário da Gestão", [
                'post' => $_POST,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
            $_SESSION['flash_erro'] = "Sessão inválida ou formulário expirado. Tente novamente.";
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? (APP_URL . '/gestao/dashboard')));
            exit;
        }
    }

    /**
     * Processa e valida o upload seguro de imagem de produto
     * @param array $file
     * @return string Caminho relativo salvo (ex: /uploads/produtos/prod_xyz.webp)
     * @throws RuntimeException
     */
    private function processarUploadFoto(array $file): string
    {
        if ($file['size'] > UPLOAD_MAX_SIZE_BYTES) {
            throw new RuntimeException("A imagem excede o tamanho máximo de 2 MB permitido.");
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, UPLOAD_ALLOWED_MIME_TYPES, true)) {
            throw new RuntimeException("Formato inválido ({$mimeType}). Apenas JPEG, PNG ou WEBP são permitidos.");
        }

        $extensao = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'img'
        };

        // Garante que o diretório de destino existe
        if (!is_dir(PATH_UPLOADS)) {
            @mkdir(PATH_UPLOADS, 0755, true);
        }

        $novoNome = 'prod_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $extensao;
        $destinoFisico = PATH_UPLOADS . '/' . $novoNome;

        if (!move_uploaded_file($file['tmp_name'], $destinoFisico)) {
            throw new RuntimeException("Não foi possível salvar o arquivo no servidor.");
        }

        return '/uploads/produtos/' . $novoNome;
    }
}
