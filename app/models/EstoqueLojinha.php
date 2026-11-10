<?php
declare(strict_types=1);

/**
 * Projeto Repack - Model EstoqueLojinha
 * Baseado estritamente em docs/FSD.md
 */

require_once __DIR__ . '/../config/database.php';

class EstoqueLojinha
{
    /**
     * Incrementa a quantidade total do produto na lojinha
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
                updated_at = CURRENT_TIMESTAMP
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
}
