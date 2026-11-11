<?php
declare(strict_types=1);

/**
 * Projeto Repack - Model EstoqueLojinha
 * Baseado estritamente em docs/FSD.md (Seções 6, 9, 10, 11, 12.3 e 17)
 */

require_once __DIR__ . '/../config/database.php';

class EstoqueLojinha
{
    /**
     * Incrementa a quantidade total do produto na lojinha (proveniente de B.O. leve)
     * @param int $produtoId
     * @param int $quantidade
     * @return bool
     */
    public static function incrementarSaldo(int $produtoId, int $quantidade): bool
    {
        if ($quantidade <= 0) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO estoque_lojinha (produto_id, quantidade_total, quantidade_reservada_gestao) 
            VALUES (:produto_id, :quantidade, 0)
            ON DUPLICATE KEY UPDATE 
                quantidade_total = quantidade_total + VALUES(quantidade_total),
                updated_at = NOW()
        ");

        return $stmt->execute([
            'produto_id' => $produtoId,
            'quantidade' => $quantidade
        ]);
    }

    /**
     * Obtém o saldo do estoque de um produto
     * @param int $produtoId
     * @return array|null
     */
    public static function obterPorProdutoId(int $produtoId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT id, produto_id, quantidade_total, quantidade_reservada_gestao, updated_at 
            FROM estoque_lojinha 
            WHERE produto_id = :produto_id 
            LIMIT 1
        ");
        $stmt->execute(['produto_id' => $produtoId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Retorna a visão consolidada de estoque de todos os produtos ativos
     * Fórmula: Disponível = Total - Reserva Oculta
     * @return array
     */
    public static function listarVisaoGeral(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT p.id AS produto_id, p.codigo, p.nome, p.foto_url, p.pacote_padrao, p.ativo,
                   COALESCE(el.quantidade_total, 0) AS quantidade_total,
                   COALESCE(el.quantidade_reservada_gestao, 0) AS quantidade_reservada_gestao,
                   GREATEST(0, COALESCE(el.quantidade_total, 0) - COALESCE(el.quantidade_reservada_gestao, 0)) AS saldo_disponivel
            FROM produtos p
            LEFT JOIN estoque_lojinha el ON el.produto_id = p.id
            WHERE p.deleted_at IS NULL
              AND COALESCE(el.quantidade_total, 0) > 0
            ORDER BY p.nome ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Ajusta a quantidade da Reserva Oculta da Gestão com histórico formal
     * @param int $produtoId
     * @param int $novaQuantidadeReserva
     * @param int $usuarioId
     * @param string $motivo
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function ajustarReservaOculta(int $produtoId, int $novaQuantidadeReserva, int $usuarioId, string $motivo): bool
    {
        if ($novaQuantidadeReserva < 0) {
            throw new InvalidArgumentException("A reserva não pode ser um valor negativo.");
        }
        $motivo = trim($motivo);
        if ($motivo === '') {
            $motivo = 'Ajuste manual de reserva';
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // Verifica o saldo total atual do produto
            $estoqueAtual = self::obterPorProdutoId($produtoId);
            $totalFisico = (int)($estoqueAtual['quantidade_total'] ?? 0);

            if ($novaQuantidadeReserva > $totalFisico) {
                throw new InvalidArgumentException("A reserva ({$novaQuantidadeReserva}) não pode ser superior ao saldo total físico em estoque ({$totalFisico}).");
            }

            // 1. Registra no histórico de reservas da gestão
            $stmtHist = $pdo->prepare("
                INSERT INTO reservas_gestao_historico (produto_id, usuario_id, quantidade, motivo, created_at)
                VALUES (:produto_id, :usuario_id, :quantidade, :motivo, NOW())
            ");
            $stmtHist->execute([
                'produto_id' => $produtoId,
                'usuario_id' => $usuarioId,
                'quantidade' => $novaQuantidadeReserva,
                'motivo' => $motivo
            ]);

            // 2. Atualiza a tabela estoque_lojinha
            $stmtEstoque = $pdo->prepare("
                INSERT INTO estoque_lojinha (produto_id, quantidade_total, quantidade_reservada_gestao)
                VALUES (:produto_id, 0, :reserva)
                ON DUPLICATE KEY UPDATE 
                    quantidade_reservada_gestao = :reserva_update,
                    updated_at = NOW()
            ");
            $stmtEstoque->execute([
                'produto_id' => $produtoId,
                'reserva' => $novaQuantidadeReserva,
                'reserva_update' => $novaQuantidadeReserva
            ]);

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Registra Baixa Administrativa (perdas na sala de espera) com abatimento direto de estoque total
     * @param int $produtoId
     * @param int $quantidadeBaixa
     * @param int $usuarioId
     * @param string $motivo
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function registrarBaixaAdministrativa(int $produtoId, int $quantidadeBaixa, int $usuarioId, string $motivo): bool
    {
        if ($quantidadeBaixa <= 0) {
            throw new InvalidArgumentException("A quantidade da baixa deve ser maior que zero.");
        }
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw new InvalidArgumentException("O motivo da baixa administrativa é obrigatório.");
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $estoqueAtual = self::obterPorProdutoId($produtoId);
            $totalFisico = (int)($estoqueAtual['quantidade_total'] ?? 0);
            $reservaAtual = (int)($estoqueAtual['quantidade_reservada_gestao'] ?? 0);

            if ($quantidadeBaixa > $totalFisico) {
                throw new InvalidArgumentException("A quantidade da baixa ({$quantidadeBaixa}) excede o saldo físico total em estoque ({$totalFisico}).");
            }

            // 1. Registra na tabela de baixas administrativas
            $stmtBaixa = $pdo->prepare("
                INSERT INTO baixas_administrativas (produto_id, usuario_id, quantidade, motivo, created_at)
                VALUES (:produto_id, :usuario_id, :quantidade, :motivo, NOW())
            ");
            $stmtBaixa->execute([
                'produto_id' => $produtoId,
                'usuario_id' => $usuarioId,
                'quantidade' => $quantidadeBaixa,
                'motivo' => $motivo
            ]);

            // 2. Abate do saldo total na lojinha
            $novoTotal = $totalFisico - $quantidadeBaixa;
            $novaReserva = min($reservaAtual, $novoTotal); // ajusta reserva se novo total ficou menor

            $stmtEstoque = $pdo->prepare("
                UPDATE estoque_lojinha 
                SET quantidade_total = :novo_total,
                    quantidade_reservada_gestao = :nova_reserva,
                    updated_at = NOW()
                WHERE produto_id = :produto_id
            ");
            $stmtEstoque->execute([
                'novo_total' => $novoTotal,
                'nova_reserva' => $novaReserva,
                'produto_id' => $produtoId
            ]);

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Retorna histórico de ajustes de reservas
     * @param int|null $produtoId
     * @param int $limite
     * @return array
     */
    public static function listarHistoricoReservas(?int $produtoId = null, int $limite = 50): array
    {
        $pdo = Database::getConnection();
        $sql = "
            SELECT r.*, p.nome AS produto_nome, p.codigo AS produto_codigo, u.nome AS usuario_nome
            FROM reservas_gestao_historico r
            INNER JOIN produtos p ON p.id = r.produto_id
            INNER JOIN usuarios u ON u.id = r.usuario_id
        ";
        $params = [];
        if ($produtoId !== null) {
            $sql .= " WHERE r.produto_id = :produto_id";
            $params['produto_id'] = $produtoId;
        }
        $sql .= " ORDER BY r.created_at DESC LIMIT " . (int)$limite;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Retorna histórico de baixas administrativas
     * @param int|null $produtoId
     * @param int $limite
     * @return array
     */
    public static function listarHistoricoBaixas(?int $produtoId = null, int $limite = 50): array
    {
        $pdo = Database::getConnection();
        $sql = "
            SELECT b.*, p.nome AS produto_nome, p.codigo AS produto_codigo, u.nome AS usuario_nome
            FROM baixas_administrativas b
            INNER JOIN produtos p ON p.id = b.produto_id
            INNER JOIN usuarios u ON u.id = b.usuario_id
        ";
        $params = [];
        if ($produtoId !== null) {
            $sql .= " WHERE b.produto_id = :produto_id";
            $params['produto_id'] = $produtoId;
        }
        $sql .= " ORDER BY b.created_at DESC LIMIT " . (int)$limite;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Realiza o Inventário / Balanço de Estoque com suporte a substituição total ou incremento parcial
     * @param int $produtoId
     * @param int $quantidadeContada
     * @param string $tipoOperacao 'substituicao' ou 'incremento'
     * @param int $usuarioId
     * @param string $motivo
     * @param string|null $detalhesContagem
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function realizarInventario(
        int $produtoId,
        int $quantidadeContada,
        string $tipoOperacao,
        int $usuarioId,
        string $motivo,
        ?string $detalhesContagem = null
    ): bool {
        if ($quantidadeContada < 0) {
            throw new InvalidArgumentException("A quantidade contada não pode ser negativa.");
        }
        $motivo = trim($motivo);
        if ($motivo === '') {
            $motivo = 'Inventário periódico de estoque';
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $estoqueAtual = self::obterPorProdutoId($produtoId);
            $saldoAnterior = (int)($estoqueAtual['quantidade_total'] ?? 0);
            $reservaAtual = (int)($estoqueAtual['quantidade_reservada_gestao'] ?? 0);

            if ($tipoOperacao === 'incremento') {
                $novoSaldo = $saldoAnterior + $quantidadeContada;
                $diferenca = $quantidadeContada;
            } else {
                $novoSaldo = $quantidadeContada;
                $diferenca = $novoSaldo - $saldoAnterior;
            }

            if ($novoSaldo < 0) {
                throw new InvalidArgumentException("O saldo resultante não pode ser negativo.");
            }

            // Ajusta a reserva se o novo saldo total físico for menor que a reserva oculta
            $novaReserva = min($reservaAtual, $novoSaldo);

            // 1. Atualiza ou insere o estoque_lojinha
            $stmtEstoque = $pdo->prepare("
                INSERT INTO estoque_lojinha (produto_id, quantidade_total, quantidade_reservada_gestao, updated_at)
                VALUES (:produto_id, :novo_saldo, :nova_reserva, NOW())
                ON DUPLICATE KEY UPDATE
                    quantidade_total = :novo_saldo_up,
                    quantidade_reservada_gestao = :nova_reserva_up,
                    updated_at = NOW()
            ");
            $stmtEstoque->execute([
                'produto_id' => $produtoId,
                'novo_saldo' => $novoSaldo,
                'nova_reserva' => $novaReserva,
                'novo_saldo_up' => $novoSaldo,
                'nova_reserva_up' => $novaReserva
            ]);

            // 2. Grava histórico auditado de inventário
            $stmtHist = $pdo->prepare("
                INSERT INTO inventarios_estoque_historico (
                    produto_id, usuario_id, saldo_anterior, novo_saldo, diferenca,
                    tipo_operacao, detalhes_contagem, motivo, created_at
                ) VALUES (
                    :produto_id, :usuario_id, :saldo_anterior, :novo_saldo, :diferenca,
                    :tipo_operacao, :detalhes_contagem, :motivo, NOW()
                )
            ");
            $stmtHist->execute([
                'produto_id' => $produtoId,
                'usuario_id' => $usuarioId,
                'saldo_anterior' => $saldoAnterior,
                'novo_saldo' => $novoSaldo,
                'diferenca' => $diferenca,
                'tipo_operacao' => $tipoOperacao === 'incremento' ? 'incremento' : 'substituicao',
                'detalhes_contagem' => $detalhesContagem,
                'motivo' => $motivo
            ]);

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Retorna o histórico de inventários e balanços
     * @param int|null $produtoId
     * @param int $limite
     * @return array
     */
    public static function listarHistoricoInventarios(?int $produtoId = null, int $limite = 50): array
    {
        $pdo = Database::getConnection();
        $sql = "
            SELECT i.*, p.nome AS produto_nome, p.codigo AS produto_codigo, u.nome AS usuario_nome
            FROM inventarios_estoque_historico i
            INNER JOIN produtos p ON p.id = i.produto_id
            INNER JOIN usuarios u ON u.id = i.usuario_id
        ";
        $params = [];
        if ($produtoId !== null) {
            $sql .= " WHERE i.produto_id = :produto_id";
            $params['produto_id'] = $produtoId;
        }
        $sql .= " ORDER BY i.created_at DESC LIMIT " . (int)$limite;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Retorna a lista de produtos formatada para a Vitrine da Lojinha
     * Fórmula obrigatória: Disponível = Total - Reserva Oculta - Bloqueios em Carrinho Ativo
     * Conforme docs/FSD.md Seções 6.3, 9, 12.2, 13.3 e 26
     * @param int $loteId
     * @param int $usuarioId
     * @return array
     */
    public static function listarProdutosVitrine(int $loteId, int $usuarioId): array
    {
        $pdo = Database::getConnection();

        // 1. Limpa carrinhos expirados
        $pdo->exec("DELETE FROM carrinho_temporario WHERE expiracao_em < NOW()");

        // 2. Consulta os produtos ativos com saldos dinâmicos
        $stmt = $pdo->prepare("
            SELECT * FROM (
                SELECT p.id AS produto_id, p.codigo, p.nome, p.foto_url, p.pacote_padrao,
                       COALESCE(el.quantidade_total, 0) AS quantidade_total,
                       COALESCE(el.quantidade_reservada_gestao, 0) AS quantidade_reservada_gestao,
                       COALESCE((
                           SELECT SUM(ct.quantidade)
                           FROM carrinho_temporario ct
                           WHERE ct.produto_id = p.id 
                             AND ct.expiracao_em > NOW()
                             AND ct.usuario_id != :uid_outros_1
                       ), 0) AS bloqueios_outros_carrinhos,
                       COALESCE((
                           SELECT SUM(ct.quantidade)
                           FROM carrinho_temporario ct
                           WHERE ct.produto_id = p.id 
                             AND ct.usuario_id = :uid_meu 
                             AND ct.lote_id = :lote_id 
                             AND ct.expiracao_em > NOW()
                       ), 0) AS no_meu_carrinho,
                       GREATEST(0, COALESCE(el.quantidade_total, 0) - COALESCE(el.quantidade_reservada_gestao, 0) - COALESCE((
                           SELECT SUM(ct.quantidade)
                           FROM carrinho_temporario ct
                           WHERE ct.produto_id = p.id 
                             AND ct.expiracao_em > NOW()
                             AND ct.usuario_id != :uid_outros_2
                       ), 0)) AS saldo_disponivel
                FROM produtos p
                LEFT JOIN estoque_lojinha el ON el.produto_id = p.id
                WHERE p.ativo = 1 
                  AND p.deleted_at IS NULL
                  AND COALESCE(el.quantidade_total, 0) > 0
            ) AS vitrine
            WHERE saldo_disponivel > 0 OR no_meu_carrinho > 0
            ORDER BY saldo_disponivel DESC, nome ASC
        ");

        $stmt->execute([
            'uid_outros_1' => $usuarioId,
            'uid_meu' => $usuarioId,
            'lote_id' => $loteId,
            'uid_outros_2' => $usuarioId
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Retorna o saldo disponível atualizado de um produto específico
     * @param int $produtoId
     * @return int
     */
    public static function obterSaldoDisponivel(int $produtoId): int
    {
        $pdo = Database::getConnection();
        $pdo->exec("DELETE FROM carrinho_temporario WHERE expiracao_em < NOW()");

        $stmt = $pdo->prepare("
            SELECT GREATEST(0, COALESCE(el.quantidade_total, 0) - COALESCE(el.quantidade_reservada_gestao, 0) - COALESCE((
                SELECT SUM(ct.quantidade)
                FROM carrinho_temporario ct
                WHERE ct.produto_id = :produto_id_ct 
                  AND ct.expiracao_em > NOW()
            ), 0)) AS saldo_disponivel
            FROM produtos p
            LEFT JOIN estoque_lojinha el ON el.produto_id = p.id
            WHERE p.id = :produto_id_p AND p.ativo = 1 AND p.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([
            'produto_id_ct' => $produtoId,
            'produto_id_p' => $produtoId
        ]);
        $val = $stmt->fetchColumn();
        return $val !== false ? max(0, (int)$val) : 0;
    }
}
