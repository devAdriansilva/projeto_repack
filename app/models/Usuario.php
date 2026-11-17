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
     * Retorna todos os colaboradores ativos
     * @return array
     */
    public static function listarTodos(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT id, matricula, nome, perfil, setor, tipo_acesso_lojinha, ativo, created_at
            FROM usuarios 
            WHERE deleted_at IS NULL 
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
            SELECT id, matricula, nome, senha_hash, pin_hash, perfil, setor, tipo_acesso_lojinha, ativo 
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
            SELECT id, matricula, nome, senha_hash, pin_hash, perfil, setor, tipo_acesso_lojinha, ativo 
            FROM usuarios 
            WHERE matricula = :matricula 
              AND deleted_at IS NULL 
            LIMIT 1
        ");
        $stmt->execute(['matricula' => $matricula]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Define ou atualiza o PIN de 4 dígitos do colaborador comprador
     * @param int $id
     * @param string $pin
     * @return bool
     */
    public static function definirPin(int $id, string $pin): bool
    {
        if (!preg_match('/^\d{4}$/', $pin)) {
            return false;
        }

        $hash = password_hash($pin, PASSWORD_DEFAULT);
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET pin_hash = :hash, updated_at = NOW() 
            WHERE id = :id AND deleted_at IS NULL
        ");
        return $stmt->execute(['hash' => $hash, 'id' => $id]);
    }

    /**
     * Verifica se o PIN informado confere com o PIN gravado do colaborador
     * @param int $id
     * @param string $pin
     * @return bool
     */
    public static function verificarPin(int $id, string $pin): bool
    {
        if (!preg_match('/^\d{4}$/', $pin)) {
            return false;
        }

        $usuario = self::buscarPorId($id);
        if (!$usuario || empty($usuario['pin_hash'])) {
            return false;
        }

        return password_verify($pin, $usuario['pin_hash']);
    }

    /**
     * Reseta o PIN de um colaborador (torna nulo para exigir autocadastro no próximo acesso)
     * @param int $id
     * @return bool
     */
    public static function resetarPin(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET pin_hash = NULL, updated_at = NOW() 
            WHERE id = :id AND deleted_at IS NULL
        ");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Lista todos os colaboradores para gestão de acesso e controle de PIN
     * @return array
     */
    public static function listarColaboradoresParaGestao(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT id, matricula, nome, perfil, setor, tipo_acesso_lojinha, ativo,
                   CASE WHEN pin_hash IS NOT NULL AND pin_hash != '' THEN 1 ELSE 0 END AS tem_pin,
                   updated_at
            FROM usuarios 
            WHERE deleted_at IS NULL 
            ORDER BY nome ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Setores autorizados enxutos para parametrização de lotes de venda da lojinha
     */
    public const SETORES_LOTES_DISPONIVEIS = [
        'Administrativo',
        'Armazém',
        'Distribuição',
        'Puxada',
        'Vendas'
    ];

    /**
     * Retorna lista oficial e enxuta de setores autorizados para parametrização de lotes de venda
     * @return array
     */
    public static function listarSetores(): array
    {
        return self::SETORES_LOTES_DISPONIVEIS;
    }
}
