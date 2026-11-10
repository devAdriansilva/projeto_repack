<?php
declare(strict_types=1);

/**
 * Projeto Repack - Model Produto
 * Baseado estritamente em docs/FSD.md
 */

require_once __DIR__ . '/../config/database.php';

class Produto
{
    /**
     * Retorna todos os produtos ativos para seleção nas telas do Operador e Lojinha
     * @return array
     */
    public static function listarAtivos(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT id, codigo, nome, foto_url, pacote_padrao 
            FROM produtos 
            WHERE ativo = 1 
              AND deleted_at IS NULL 
            ORDER BY nome ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Busca um produto pelo ID
     * @param int $id
     * @return array|null
     */
    public static function buscarPorId(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT id, codigo, nome, foto_url, pacote_padrao, ativo 
            FROM produtos 
            WHERE id = :id 
              AND deleted_at IS NULL 
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
