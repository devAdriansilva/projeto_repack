<?php
declare(strict_types=1);

/**
 * Projeto Repack - Model Pedido & Carrinho Temporário
 * Baseado estritamente em docs/FSD.md (Seções 6, 9, 10, 11, 13.3, 14, 18 e 24)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/LoteVenda.php';
require_once __DIR__ . '/../models/LogSistema.php';

class Pedido
{
    public const STATUS_CONFIRMADO = 'confirmado';
    public const STATUS_SEPARADO = 'separado';
    public const STATUS_ENTREGUE = 'entregue';
    public const STATUS_CANCELADO = 'cancelado';

    /**
     * Remove reservas temporárias expiradas do carrinho
     * Conforme docs/FSD.md Seção 14: DELETE FROM carrinho_temporario WHERE expiracao_em < NOW();
     * @return int Quantidade de registros removidos
     */
    public static function limparCarrinhosExpirados(): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM carrinho_temporario WHERE expiracao_em < NOW()");
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Verifica se o colaborador já possui pedido confirmado ou em processamento no lote especificado
     * Regra 1B: Limite estrito de 1 único pedido por lote para colaboradores comuns
     * @param int $loteId
     * @param int $usuarioId
     * @return bool
     */
    public static function usuarioJaComprouNoLote(int $loteId, int $usuarioId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM pedidos 
            WHERE lote_id = :lote_id 
              AND usuario_id = :usuario_id 
              AND status != 'cancelado' 
              AND deleted_at IS NULL
        ");
        $stmt->execute([
            'lote_id' => $loteId,
            'usuario_id' => $usuarioId
        ]);
        return ((int)$stmt->fetchColumn()) > 0;
    }

    /**
     * Retorna o último pedido confirmado do usuário em determinado lote
     * @param int $loteId
     * @param int $usuarioId
     * @return array|null
     */
    public static function buscarUltimoPedidoDoUsuarioNoLote(int $loteId, int $usuarioId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT id, codigo_pedido, quantidade_total, status, created_at
            FROM pedidos
            WHERE lote_id = :lote_id
              AND usuario_id = :usuario_id
              AND status != 'cancelado'
              AND deleted_at IS NULL
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute(['lote_id' => $loteId, 'usuario_id' => $usuarioId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Adiciona ou atualiza uma reserva temporária no carrinho do colaborador
     * Conforme docs/FSD.md Seções 9, 13.3 e 14
     * @param int $loteId
     * @param int $usuarioId
     * @param int $produtoId
     * @param int $quantidade
     * @param string $sessionToken
     * @return array
     * @throws InvalidArgumentException|DomainException|RuntimeException
     */
    public static function adicionarAoCarrinho(int $loteId, int $usuarioId, int $produtoId, int $quantidade = 1, string $sessionToken = ''): array
    {
        if ($quantidade <= 0) {
            throw new InvalidArgumentException("A quantidade adicionada deve ser de no mínimo 1 unidade.");
        }

        // 1. Limpa carrinhos expirados previamente
        self::limparCarrinhosExpirados();

        $pdo = Database::getConnection();

        // Identifica perfil de acesso do usuário (comum, setorial ou master)
        $usuario = Usuario::buscarPorId($usuarioId);
        $tipoAcesso = $usuario['tipo_acesso_lojinha'] ?? 'comum';

        // 2. Valida o lote de venda
        $lote = LoteVenda::buscarPorId($loteId);
        if (!$lote || ($lote['status'] !== LoteVenda::STATUS_ABERTO && $tipoAcesso === 'comum')) {
            throw new DomainException("Este lote de vendas não está aberto para compras no momento.");
        }

        $agora = time();
        $inicio = strtotime($lote['data_inicio']);
        $fim = strtotime($lote['data_fim']);
        if ($tipoAcesso === 'comum' && ($agora < $inicio || $agora > $fim)) {
            throw new DomainException("Este lote de vendas expirou ou ainda não iniciou sua vigência.");
        }

        // 3. Valida teto máximo e regra de 1 pedido por lote
        if ($tipoAcesso === 'comum') {
            // Regra 1B: Colaborador comum só pode fazer 1 pedido por lote
            if (self::usuarioJaComprouNoLote($loteId, $usuarioId)) {
                throw new DomainException("Você já realizou seu pedido neste lote. Cada colaborador tem direito a 1 pedido por ciclo.");
            }

            // Limite = total em pedidos válidos + total já no carrinho ativo
            $stmtCompras = $pdo->prepare("
                SELECT COALESCE(SUM(quantidade_total), 0)
                FROM pedidos
                WHERE lote_id = :lote_id 
                  AND usuario_id = :usuario_id 
                  AND status != 'cancelado' 
                  AND deleted_at IS NULL
            ");
            $stmtCompras->execute(['lote_id' => $loteId, 'usuario_id' => $usuarioId]);
            $totalComprado = (int)$stmtCompras->fetchColumn();

            $stmtCarrinhoAtual = $pdo->prepare("
                SELECT COALESCE(SUM(quantidade), 0)
                FROM carrinho_temporario
                WHERE lote_id = :lote_id 
                  AND usuario_id = :usuario_id 
                  AND expiracao_em > NOW()
            ");
            $stmtCarrinhoAtual->execute(['lote_id' => $loteId, 'usuario_id' => $usuarioId]);
            $totalCarrinho = (int)$stmtCarrinhoAtual->fetchColumn();

            $limiteLote = (int)$lote['limite_itens_por_comprador'];
            if (($totalComprado + $totalCarrinho + $quantidade) > $limiteLote) {
                $restante = max(0, $limiteLote - ($totalComprado + $totalCarrinho));
                throw new DomainException("Limite individual do lote excedido. Você pode adicionar no máximo {$restante} item(ns).");
            }
        } else {
            // Contas setoriais e Master: sem trava de 1 pedido e sem limite individual
            $totalComprado = 0;
            $stmtCarrinhoAtual = $pdo->prepare("
                SELECT COALESCE(SUM(quantidade), 0)
                FROM carrinho_temporario
                WHERE lote_id = :lote_id 
                  AND usuario_id = :usuario_id 
                  AND expiracao_em > NOW()
            ");
            $stmtCarrinhoAtual->execute(['lote_id' => $loteId, 'usuario_id' => $usuarioId]);
            $totalCarrinho = (int)$stmtCarrinhoAtual->fetchColumn();
            $limiteLote = 9999;
        }

        // 4. Valida saldo disponível em estoque
        // Fórmula: Disponível = Total - Reserva Oculta - Bloqueios em Carrinho Ativo
        // NOTA: Usuário MASTER tem acesso ao estoque total físico (incluindo a reserva da gestão)
        $stmtEstoque = $pdo->prepare("
            SELECT quantidade_total, quantidade_reservada_gestao
            FROM estoque_lojinha
            WHERE produto_id = :produto_id
            LIMIT 1
        ");
        $stmtEstoque->execute(['produto_id' => $produtoId]);
        $estoque = $stmtEstoque->fetch();
        if (!$estoque) {
            throw new DomainException("Produto não cadastrado no estoque da lojinha.");
        }

        $totalFisico = (int)$estoque['quantidade_total'];
        $reservaGestao = ($tipoAcesso === 'master') ? 0 : (int)$estoque['quantidade_reservada_gestao'];

        // Carrinhos ativos de outros colaboradores para este produto
        $stmtOutrosCarrinhos = $pdo->prepare("
            SELECT COALESCE(SUM(quantidade), 0)
            FROM carrinho_temporario
            WHERE produto_id = :produto_id 
              AND expiracao_em > NOW()
              AND usuario_id != :usuario_id
        ");
        $stmtOutrosCarrinhos->execute([
            'produto_id' => $produtoId,
            'usuario_id' => $usuarioId
        ]);
        $bloqueiosOutros = (int)$stmtOutrosCarrinhos->fetchColumn();

        // Quantidade que o próprio usuário já possui desse produto no carrinho
        $stmtMeuCarrinho = $pdo->prepare("
            SELECT id, quantidade
            FROM carrinho_temporario
            WHERE lote_id = :lote_id 
              AND usuario_id = :usuario_id 
              AND produto_id = :produto_id 
              AND expiracao_em > NOW()
            LIMIT 1
        ");
        $stmtMeuCarrinho->execute([
            'lote_id' => $loteId,
            'usuario_id' => $usuarioId,
            'produto_id' => $produtoId
        ]);
        $itemMeuCarrinho = $stmtMeuCarrinho->fetch();
        $minhaQtdAtual = $itemMeuCarrinho ? (int)$itemMeuCarrinho['quantidade'] : 0;

        // Saldo disponível para este usuário
        $disponivelParaMim = max(0, $totalFisico - $reservaGestao - $bloqueiosOutros - $minhaQtdAtual);

        if ($quantidade > $disponivelParaMim) {
            throw new DomainException("Quantidade solicitada ({$quantidade}) indisponível. Restam {$disponivelParaMim} unidade(s) disponíveis no momento.");
        }

        // 5. Grava ou atualiza a reserva conforme o tempo definido dinamicamente para o lote (padrão 5 min)
        $minutosTimeout = !empty($lote['tempo_carrinho_minutos']) 
            ? (int)$lote['tempo_carrinho_minutos'] 
            : (defined('CARRINHO_TIMEOUT_MINUTOS') ? (int)CARRINHO_TIMEOUT_MINUTOS : 5);
        $sessionTokenFinal = $sessionToken !== '' ? $sessionToken : session_id();

        if ($itemMeuCarrinho) {
            $stmtUpdate = $pdo->prepare("
                UPDATE carrinho_temporario
                SET quantidade = quantidade + :qtd,
                    expiracao_em = DATE_ADD(NOW(), INTERVAL :timeout MINUTE),
                    session_token = :token
                WHERE id = :id
            ");
            $stmtUpdate->execute([
                'qtd' => $quantidade,
                'timeout' => $minutosTimeout,
                'token' => $sessionTokenFinal,
                'id' => (int)$itemMeuCarrinho['id']
            ]);
            $carrinhoId = (int)$itemMeuCarrinho['id'];
        } else {
            $stmtInsert = $pdo->prepare("
                INSERT INTO carrinho_temporario (
                    lote_id, usuario_id, produto_id, quantidade, expiracao_em, session_token, created_at
                ) VALUES (
                    :lote_id, :usuario_id, :produto_id, :quantidade, DATE_ADD(NOW(), INTERVAL :timeout MINUTE), :token, NOW()
                )
            ");
            $stmtInsert->execute([
                'lote_id' => $loteId,
                'usuario_id' => $usuarioId,
                'produto_id' => $produtoId,
                'quantidade' => $quantidade,
                'timeout' => $minutosTimeout,
                'token' => $sessionTokenFinal
            ]);
            $carrinhoId = (int)$pdo->lastInsertId();
        }

        return [
            'status' => 'success',
            'carrinho_id' => $carrinhoId,
            'total_carrinho' => $totalCarrinho + $quantidade,
            'limite_restante' => max(0, $limiteLote - ($totalComprado + $totalCarrinho + $quantidade)),
            'timeout_minutos' => $minutosTimeout
        ];
    }

    /**
     * Remove um item específico do carrinho temporário do colaborador
     * @param int $carrinhoId
     * @param int $usuarioId
     * @return bool
     */
    public static function removerDoCarrinho(int $carrinhoId, int $usuarioId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            DELETE FROM carrinho_temporario 
            WHERE id = :id AND usuario_id = :usuario_id
        ");
        return $stmt->execute([
            'id' => $carrinhoId,
            'usuario_id' => $usuarioId
        ]);
    }

    /**
     * Incrementa ou decrementa a quantidade de um item no carrinho
     * @param int $carrinhoId
     * @param int $usuarioId
     * @param int $delta (+1 ou -1)
     * @return array ['sucesso' => bool, 'mensagem' => string, 'nova_quantidade' => int]
     */
    public static function alterarQuantidadeCarrinho(int $carrinhoId, int $usuarioId, int $delta): array
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT ct.*, p.nome AS produto_nome, l.limite_itens_por_comprador, l.tempo_carrinho_minutos
            FROM carrinho_temporario ct
            INNER JOIN produtos p ON p.id = ct.produto_id
            INNER JOIN lotes_venda l ON l.id = ct.lote_id
            WHERE ct.id = :id AND ct.usuario_id = :usuario_id AND ct.expiracao_em > NOW()
            LIMIT 1
        ");
        $stmt->execute(['id' => $carrinhoId, 'usuario_id' => $usuarioId]);
        $item = $stmt->fetch();

        if (!$item) {
            return ['sucesso' => false, 'mensagem' => 'Item não encontrado no carrinho ou reserva expirada.', 'nova_quantidade' => 0];
        }

        $loteId = (int)$item['lote_id'];
        $produtoId = (int)$item['produto_id'];
        $qtdAtual = (int)$item['quantidade'];
        $novaQtd = $qtdAtual + $delta;

        if ($novaQtd <= 0) {
            self::removerDoCarrinho($carrinhoId, $usuarioId);
            return ['sucesso' => true, 'mensagem' => 'Item removido do carrinho.', 'nova_quantidade' => 0];
        }

        if ($delta > 0) {
            try {
                self::adicionarAoCarrinho($loteId, $usuarioId, $produtoId, $delta, (string)$item['session_token']);
                return ['sucesso' => true, 'mensagem' => 'Quantidade incrementada com sucesso.', 'nova_quantidade' => $novaQtd];
            } catch (\Throwable $e) {
                return ['sucesso' => false, 'mensagem' => $e->getMessage(), 'nova_quantidade' => $qtdAtual];
            }
        } else {
            $minutosTimeout = !empty($item['tempo_carrinho_minutos']) 
                ? (int)$item['tempo_carrinho_minutos'] 
                : (defined('CARRINHO_TIMEOUT_MINUTOS') ? (int)CARRINHO_TIMEOUT_MINUTOS : 5);
            $stmtUp = $pdo->prepare("
                UPDATE carrinho_temporario
                SET quantidade = :qtd,
                    expiracao_em = DATE_ADD(NOW(), INTERVAL :timeout MINUTE)
                WHERE id = :id AND usuario_id = :usuario_id
            ");
            $stmtUp->execute([
                'qtd' => $novaQtd,
                'timeout' => $minutosTimeout,
                'id' => $carrinhoId,
                'usuario_id' => $usuarioId
            ]);
            return ['sucesso' => true, 'mensagem' => 'Quantidade decrementada com sucesso.', 'nova_quantidade' => $novaQtd];
        }
    }

    /**
     * Esvazia todos os itens do carrinho do colaborador no lote
     * @param int $loteId
     * @param int $usuarioId
     * @return bool
     */
    public static function esvaziarCarrinho(int $loteId, int $usuarioId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            DELETE FROM carrinho_temporario 
            WHERE lote_id = :lote_id AND usuario_id = :usuario_id
        ");
        return $stmt->execute([
            'lote_id' => $loteId,
            'usuario_id' => $usuarioId
        ]);
    }

    /**
     * Lista todos os itens válidos no carrinho do colaborador
     * @param int $loteId
     * @param int $usuarioId
     * @return array
     */
    public static function listarCarrinho(int $loteId, int $usuarioId): array
    {
        self::limparCarrinhosExpirados();

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT ct.id AS carrinho_id, ct.lote_id, ct.usuario_id, ct.produto_id, ct.quantidade,
                   ct.expiracao_em, ct.session_token,
                   TIMESTAMPDIFF(SECOND, NOW(), ct.expiracao_em) AS segundos_restantes,
                   p.codigo AS produto_codigo, p.nome AS produto_nome, p.foto_url, p.pacote_padrao
            FROM carrinho_temporario ct
            INNER JOIN produtos p ON p.id = ct.produto_id
            WHERE ct.lote_id = :lote_id 
              AND ct.usuario_id = :usuario_id 
              AND ct.expiracao_em > NOW()
            ORDER BY ct.created_at ASC
        ");
        $stmt->execute([
            'lote_id' => $loteId,
            'usuario_id' => $usuarioId
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Retorna o resumo consolidado de cotas e limites do comprador no lote
     * @param int $loteId
     * @param int $usuarioId
     * @return array
     */
    public static function obterResumoComprador(int $loteId, int $usuarioId): array
    {
        self::limparCarrinhosExpirados();

        $pdo = Database::getConnection();

        $usuario = Usuario::buscarPorId($usuarioId);
        $tipoAcesso = $usuario['tipo_acesso_lojinha'] ?? 'comum';
        $jaComprou = ($tipoAcesso === 'comum') && self::usuarioJaComprouNoLote($loteId, $usuarioId);

        // 1. Limite do lote
        $stmtLote = $pdo->prepare("SELECT limite_itens_por_comprador FROM lotes_venda WHERE id = :id AND deleted_at IS NULL");
        $stmtLote->execute(['id' => $loteId]);
        $limiteLote = (int)$stmtLote->fetchColumn();
        if ($limiteLote <= 0) {
            $limiteLote = 5;
        }

        // 2. Compras já confirmadas
        $stmtCompras = $pdo->prepare("
            SELECT COALESCE(SUM(quantidade_total), 0)
            FROM pedidos
            WHERE lote_id = :lote_id 
              AND usuario_id = :usuario_id 
              AND status != 'cancelado' 
              AND deleted_at IS NULL
        ");
        $stmtCompras->execute(['lote_id' => $loteId, 'usuario_id' => $usuarioId]);
        $totalComprado = (int)$stmtCompras->fetchColumn();

        // 3. Itens no carrinho ativo
        $stmtCarrinho = $pdo->prepare("
            SELECT COALESCE(SUM(quantidade), 0) AS total_itens,
                   MIN(TIMESTAMPDIFF(SECOND, NOW(), expiracao_em)) AS menor_tempo_segundos
            FROM carrinho_temporario
            WHERE lote_id = :lote_id 
              AND usuario_id = :usuario_id 
              AND expiracao_em > NOW()
        ");
        $stmtCarrinho->execute(['lote_id' => $loteId, 'usuario_id' => $usuarioId]);
        $row = $stmtCarrinho->fetch();

        $totalCarrinho = (int)($row['total_itens'] ?? 0);
        $tempoRestanteSegundos = max(0, (int)($row['menor_tempo_segundos'] ?? 0));
        $limiteRestante = ($tipoAcesso === 'comum')
            ? max(0, $limiteLote - ($totalComprado + $totalCarrinho))
            : 9999;

        return [
            'tipo_acesso' => $tipoAcesso,
            'ja_comprou_no_lote' => $jaComprou,
            'limite_lote' => ($tipoAcesso === 'comum' ? $limiteLote : 9999),
            'itens_comprados' => $totalComprado,
            'itens_carrinho' => $totalCarrinho,
            'limite_restante' => $limiteRestante,
            'segundos_restantes' => $tempoRestanteSegundos
        ];
    }

    /**
     * Finaliza o checkout de forma atômica e transacional
     * Conforme docs/FSD.md Seções 9, 13.3, 14 e 24
     * @param int $loteId
     * @param int $usuarioId
     * @param string $sessionToken
     * @return array [pedido_id, codigo_pedido, total_itens]
     * @throws DomainException|RuntimeException
     */
    public static function finalizarCheckout(int $loteId, int $usuarioId, string $sessionToken = ''): array
    {
        // 1. Limpa reservas expiradas
        self::limparCarrinhosExpirados();

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $usuario = Usuario::buscarPorId($usuarioId);
            $tipoAcesso = $usuario['tipo_acesso_lojinha'] ?? 'comum';

            // 2. Busca itens do carrinho com trava de leitura (FOR UPDATE)
            $stmtItens = $pdo->prepare("
                SELECT ct.id, ct.produto_id, ct.quantidade, ct.expiracao_em,
                       p.nome AS produto_nome, p.codigo AS produto_codigo
                FROM carrinho_temporario ct
                INNER JOIN produtos p ON p.id = ct.produto_id
                WHERE ct.lote_id = :lote_id 
                  AND ct.usuario_id = :usuario_id 
                  AND ct.expiracao_em > NOW()
                FOR UPDATE
            ");
            $stmtItens->execute([
                'lote_id' => $loteId,
                'usuario_id' => $usuarioId
            ]);
            $itensCarrinho = $stmtItens->fetchAll();

            if (empty($itensCarrinho)) {
                throw new DomainException("Sua reserva temporária expirou ou o carrinho está vazio. Por favor, adicione os produtos novamente.");
            }

            // 3. Valida o lote ativo (FOR UPDATE)
            $stmtLote = $pdo->prepare("
                SELECT id, status, data_inicio, data_fim, limite_itens_por_comprador 
                FROM lotes_venda 
                WHERE id = :id AND deleted_at IS NULL 
                FOR UPDATE
            ");
            $stmtLote->execute(['id' => $loteId]);
            $lote = $stmtLote->fetch();

            if (!$lote || ($lote['status'] !== LoteVenda::STATUS_ABERTO && $tipoAcesso === 'comum')) {
                throw new DomainException("O lote de vendas não está mais aberto para pedidos.");
            }

            $agora = time();
            if ($tipoAcesso === 'comum' && ($agora < strtotime($lote['data_inicio']) || $agora > strtotime($lote['data_fim']))) {
                throw new DomainException("O lote de vendas encerrou seu período de vigência.");
            }

            // 4. Valida teto máximo do lote e regra de 1 pedido por lote
            $totalItensPedido = 0;
            foreach ($itensCarrinho as $item) {
                $totalItensPedido += (int)$item['quantidade'];
            }

            if ($tipoAcesso === 'comum') {
                if (self::usuarioJaComprouNoLote($loteId, $usuarioId)) {
                    throw new DomainException("Você já concluiu um pedido neste lote. Cada colaborador tem direito a 1 pedido por ciclo.");
                }

                $stmtCompras = $pdo->prepare("
                    SELECT COALESCE(SUM(quantidade_total), 0)
                    FROM pedidos
                    WHERE lote_id = :lote_id 
                      AND usuario_id = :usuario_id 
                      AND status != 'cancelado' 
                      AND deleted_at IS NULL
                ");
                $stmtCompras->execute(['lote_id' => $loteId, 'usuario_id' => $usuarioId]);
                $totalComprado = (int)$stmtCompras->fetchColumn();

                $limiteMaximo = (int)$lote['limite_itens_por_comprador'];
                if (($totalComprado + $totalItensPedido) > $limiteMaximo) {
                    throw new DomainException("Este pedido excede o limite máximo permitido de {$limiteMaximo} itens por colaborador.");
                }
            }

            // 5. Para cada item: verifica e abate estoque em estoque_lojinha (FOR UPDATE)
            foreach ($itensCarrinho as $item) {
                $produtoId = (int)$item['produto_id'];
                $qtdItem = (int)$item['quantidade'];

                $stmtEstoque = $pdo->prepare("
                    SELECT quantidade_total, quantidade_reservada_gestao
                    FROM estoque_lojinha
                    WHERE produto_id = :produto_id
                    FOR UPDATE
                ");
                $stmtEstoque->execute(['produto_id' => $produtoId]);
                $estoqueRow = $stmtEstoque->fetch();

                if (!$estoqueRow || (int)$estoqueRow['quantidade_total'] < $qtdItem) {
                    throw new RuntimeException("Estoque insuficiente para o produto: {$item['produto_nome']}.");
                }

                // Abate definitivo do estoque físico da lojinha
                $novoTotal = max(0, (int)$estoqueRow['quantidade_total'] - $qtdItem);
                $novaReserva = min((int)$estoqueRow['quantidade_reservada_gestao'], $novoTotal);
                $stmtAbater = $pdo->prepare("
                    UPDATE estoque_lojinha
                    SET quantidade_total = :novo_total,
                        quantidade_reservada_gestao = :nova_reserva,
                        updated_at = NOW()
                    WHERE produto_id = :produto_id
                ");
                $stmtAbater->execute([
                    'novo_total' => $novoTotal,
                    'nova_reserva' => $novaReserva,
                    'produto_id' => $produtoId
                ]);
            }

            // 6. Gera código único para o pedido (ex: PED-20261110-8F3A2B)
            $sufixoUnico = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $codigoPedido = 'PED-' . date('Ymd') . '-' . $sufixoUnico;

            // 7. Cria o registro na tabela pedidos
            $stmtPedido = $pdo->prepare("
                INSERT INTO pedidos (
                    codigo_pedido, lote_id, usuario_id, quantidade_total, status, created_at, updated_at
                ) VALUES (
                    :codigo_pedido, :lote_id, :usuario_id, :quantidade_total, :status, NOW(), NOW()
                )
            ");
            $stmtPedido->execute([
                'codigo_pedido' => $codigoPedido,
                'lote_id' => $loteId,
                'usuario_id' => $usuarioId,
                'quantidade_total' => $totalItensPedido,
                'status' => self::STATUS_CONFIRMADO
            ]);
            $pedidoId = (int)$pdo->lastInsertId();

            // 8. Cria os registros na tabela pedido_itens
            $stmtItemPedido = $pdo->prepare("
                INSERT INTO pedido_itens (
                    pedido_id, produto_id, quantidade, created_at
                ) VALUES (
                    :pedido_id, :produto_id, :quantidade, NOW()
                )
            ");
            foreach ($itensCarrinho as $item) {
                $stmtItemPedido->execute([
                    'pedido_id' => $pedidoId,
                    'produto_id' => (int)$item['produto_id'],
                    'quantidade' => (int)$item['quantidade']
                ]);
            }

            // 9. Remove todos os itens reservados da tabela carrinho_temporario
            $stmtLimparMeu = $pdo->prepare("
                DELETE FROM carrinho_temporario
                WHERE lote_id = :lote_id AND usuario_id = :usuario_id
            ");
            $stmtLimparMeu->execute([
                'lote_id' => $loteId,
                'usuario_id' => $usuarioId
            ]);

            // 10. Registra auditoria no sistema
            LogSistema::info(
                "Pedido {$codigoPedido} finalizado com sucesso.",
                [
                    'pedido_id' => $pedidoId,
                    'codigo_pedido' => $codigoPedido,
                    'lote_id' => $loteId,
                    'usuario_id' => $usuarioId,
                    'quantidade_total' => $totalItensPedido
                ]
            );

            // 11. Confirmação atômica
            $pdo->commit();

            return [
                'pedido_id' => $pedidoId,
                'codigo_pedido' => $codigoPedido,
                'quantidade_total' => $totalItensPedido
            ];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Busca um pedido completo por código
     * @param string $codigoPedido
     * @return array|null
     */
    public static function buscarPorCodigo(string $codigoPedido): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT p.*, u.nome AS usuario_nome, u.matricula AS usuario_matricula, u.setor AS usuario_setor,
                   lv.titulo AS lote_titulo
            FROM pedidos p
            INNER JOIN usuarios u ON u.id = p.usuario_id
            INNER JOIN lotes_venda lv ON lv.id = p.lote_id
            WHERE p.codigo_pedido = :codigo_pedido 
              AND p.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute(['codigo_pedido' => $codigoPedido]);
        $pedido = $stmt->fetch();
        if (!$pedido) {
            return null;
        }

        // Carrega itens do pedido
        $stmtItens = $pdo->prepare("
            SELECT pi.*, pr.codigo AS produto_codigo, pr.nome AS produto_nome, pr.foto_url, pr.pacote_padrao
            FROM pedido_itens pi
            INNER JOIN produtos pr ON pr.id = pi.produto_id
            WHERE pi.pedido_id = :pedido_id
            ORDER BY pi.id ASC
        ");
        $stmtItens->execute(['pedido_id' => (int)$pedido['id']]);
        $pedido['itens'] = $stmtItens->fetchAll();

        return $pedido;
    }

    /**
     * Busca um pedido completo por ID
     * @param int $id
     * @return array|null
     */
    public static function buscarPorId(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT p.*, u.nome AS usuario_nome, u.matricula AS usuario_matricula, u.setor AS usuario_setor,
                   lv.titulo AS lote_titulo
            FROM pedidos p
            INNER JOIN usuarios u ON u.id = p.usuario_id
            INNER JOIN lotes_venda lv ON lv.id = p.lote_id
            WHERE p.id = :id 
              AND p.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $pedido = $stmt->fetch();
        if (!$pedido) {
            return null;
        }

        $stmtItens = $pdo->prepare("
            SELECT pi.*, pr.codigo AS produto_codigo, pr.nome AS produto_nome, pr.foto_url, pr.pacote_padrao
            FROM pedido_itens pi
            INNER JOIN produtos pr ON pr.id = pi.produto_id
            WHERE pi.pedido_id = :pedido_id
            ORDER BY pi.id ASC
        ");
        $stmtItens->execute(['pedido_id' => $id]);
        $pedido['itens'] = $stmtItens->fetchAll();

        return $pedido;
    }

    /**
     * Lista todos os pedidos de um usuário em um lote
     * @param int $loteId
     * @param int $usuarioId
     * @return array
     */
    public static function listarPedidosUsuario(int $loteId, int $usuarioId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT p.*,
                   (SELECT COUNT(*) FROM pedido_itens pi WHERE pi.pedido_id = p.id) AS total_linhas_itens
            FROM pedidos p
            WHERE p.lote_id = :lote_id 
              AND p.usuario_id = :usuario_id 
              AND p.deleted_at IS NULL
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([
            'lote_id' => $loteId,
            'usuario_id' => $usuarioId
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Lista consolidada de pedidos para separação física no galpão agrupada por colaborador
     * Conforme docs/FSD.md Seções 6, 16 (Tela 6) e 22.1
     * @param int $loteId ID do lote
     * @param string|null $statusFiltro Filtro de status opcional
     * @return array
     */
    public static function listarParaSeparacao(int $loteId, ?string $statusFiltro = null): array
    {
        $pdo = Database::getConnection();
        $sql = "
            SELECT 
                p.id AS pedido_id,
                p.codigo_pedido,
                p.status AS pedido_status,
                p.quantidade_total,
                p.created_at AS pedido_data,
                u.id AS usuario_id,
                u.matricula AS usuario_matricula,
                u.nome AS usuario_nome,
                u.setor AS usuario_setor,
                pi.id AS item_id,
                pi.quantidade AS item_quantidade,
                pr.id AS produto_id,
                pr.codigo AS produto_codigo,
                pr.nome AS produto_nome,
                pr.pacote_padrao,
                pr.foto_url
            FROM pedidos p
            INNER JOIN usuarios u ON u.id = p.usuario_id
            INNER JOIN pedido_itens pi ON pi.pedido_id = p.id
            INNER JOIN produtos pr ON pr.id = pi.produto_id
            WHERE p.lote_id = :lote_id
              AND p.deleted_at IS NULL
        ";

        $params = ['lote_id' => $loteId];
        if (!empty($statusFiltro)) {
            $sql .= " AND p.status = :status";
            $params['status'] = $statusFiltro;
        } else {
            $sql .= " AND p.status != 'cancelado'";
        }

        $sql .= " ORDER BY u.nome ASC, p.id ASC, pr.nome ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $linhas = $stmt->fetchAll();

        $agrupado = [];
        foreach ($linhas as $linha) {
            $uId = (int)$linha['usuario_id'];
            if (!isset($agrupado[$uId])) {
                $agrupado[$uId] = [
                    'usuario_id' => $uId,
                    'matricula' => $linha['usuario_matricula'],
                    'nome' => $linha['usuario_nome'],
                    'setor' => $linha['usuario_setor'],
                    'total_geral_itens' => 0,
                    'pedidos' => []
                ];
            }

            $pId = (int)$linha['pedido_id'];
            if (!isset($agrupado[$uId]['pedidos'][$pId])) {
                $agrupado[$uId]['pedidos'][$pId] = [
                    'pedido_id' => $pId,
                    'codigo_pedido' => $linha['codigo_pedido'],
                    'status' => $linha['pedido_status'],
                    'quantidade_total' => (int)$linha['quantidade_total'],
                    'created_at' => $linha['pedido_data'],
                    'itens' => []
                ];
                $agrupado[$uId]['total_geral_itens'] += (int)$linha['quantidade_total'];
            }

            $agrupado[$uId]['pedidos'][$pId]['itens'][] = [
                'item_id' => (int)$linha['item_id'],
                'produto_id' => (int)$linha['produto_id'],
                'codigo' => $linha['produto_codigo'],
                'nome' => $linha['produto_nome'],
                'pacote_padrao' => $linha['pacote_padrao'],
                'foto_url' => $linha['foto_url'],
                'quantidade' => (int)$linha['item_quantidade']
            ];
        }

        // Converte os pedidos associativos para listas indexadas
        foreach ($agrupado as $uId => &$dadosUsuario) {
            $dadosUsuario['pedidos'] = array_values($dadosUsuario['pedidos']);
        }
        unset($dadosUsuario);

        return array_values($agrupado);
    }

    /**
     * Atualiza o status do pedido (ex: confirmado -> separado -> entregue -> cancelado)
     * @param int $pedidoId
     * @param string $novoStatus
     * @param int|null $gestorId
     * @return bool
     */
    public static function atualizarStatus(int $pedidoId, string $novoStatus, ?int $gestorId = null): bool
    {
        $statusValidos = [
            self::STATUS_CONFIRMADO,
            self::STATUS_SEPARADO,
            self::STATUS_ENTREGUE,
            self::STATUS_CANCELADO
        ];

        if (!in_array($novoStatus, $statusValidos, true)) {
            throw new InvalidArgumentException("Status '{$novoStatus}' inválido para pedido.");
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE pedidos
            SET status = :status,
                updated_at = NOW()
            WHERE id = :id AND deleted_at IS NULL
        ");
        $stmt->execute([
            'status' => $novoStatus,
            'id' => $pedidoId
        ]);

        if ($stmt->rowCount() > 0) {
            LogSistema::registrar('INFO', "Status do pedido #{$pedidoId} alterado para {$novoStatus}", [
                'pedido_id' => $pedidoId,
                'status' => $novoStatus,
                'gestor_id' => $gestorId
            ]);
            return true;
        }

        return false;
    }

    /**
     * Retorna estatísticas de separação física para um lote específico
     * @param int $loteId
     * @return array
     */
    public static function obterEstatisticasSeparacao(int $loteId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) AS total_pedidos,
                COALESCE(SUM(quantidade_total), 0) AS total_itens,
                COUNT(DISTINCT usuario_id) AS total_colaboradores,
                COUNT(CASE WHEN status = 'confirmado' THEN 1 END) AS total_confirmados,
                COUNT(CASE WHEN status = 'separado' THEN 1 END) AS total_separados,
                COUNT(CASE WHEN status = 'entregue' THEN 1 END) AS total_entregues,
                COUNT(CASE WHEN status = 'cancelado' THEN 1 END) AS total_cancelados
            FROM pedidos
            WHERE lote_id = :lote_id 
              AND deleted_at IS NULL
        ");
        $stmt->execute(['lote_id' => $loteId]);
        $stats = $stmt->fetch();

        return [
            'total_pedidos' => (int)($stats['total_pedidos'] ?? 0),
            'total_itens' => (int)($stats['total_itens'] ?? 0),
            'total_colaboradores' => (int)($stats['total_colaboradores'] ?? 0),
            'total_confirmados' => (int)($stats['total_confirmados'] ?? 0),
            'total_separados' => (int)($stats['total_separados'] ?? 0),
            'total_entregues' => (int)($stats['total_entregues'] ?? 0),
            'total_cancelados' => (int)($stats['total_cancelados'] ?? 0)
        ];
    }
}
