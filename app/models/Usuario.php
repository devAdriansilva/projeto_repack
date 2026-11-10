<?php
declare(strict_types=1);

/**
 * Projeto Repack - Model Usuario
 * Baseado estritamente em docs/FSD.md
 */

require_once __DIR__ . '/../config/database.php';

class Usuario
{
    /**
     * Retorna todos os operadores ativos para seleção no quiosque da Urna Eletrônica
     * @return array
     */
    public static function listarOperadoresAtivos(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT id, matricula, nome, setor 
            FROM usuarios 
            WHERE perfil = 'operador' 
              AND ativo = 1 
              AND deleted_at IS NULL 
            ORDER BY nome ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Busca um usuário pelo ID
     * @param int $id
     * @return array|null
     */
    public static function buscarPorId(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT id, matricula, nome, perfil, setor, ativo 
            FROM usuarios 
            WHERE id = :id 
              AND deleted_at IS NULL 
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Busca um usuário pela matrícula
     * @param string $matricula
     * @return array|null
     */
    public static function buscarPorMatricula(string $matricula): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT id, matricula, nome, senha_hash, perfil, setor, ativo 
            FROM usuarios 
            WHERE matricula = :matricula 
              AND deleted_at IS NULL 
            LIMIT 1
        ");
        $stmt->execute(['matricula' => $matricula]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
