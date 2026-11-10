<?php
declare(strict_types=1);

/**
 * Projeto Repack - Model Produto
 * Baseado estritamente em docs/FSD.md (Seções 10, 11, 12.3, 18 e 21)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

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
     * Retorna todos os produtos cadastrados para listagem na gestão
     * @param bool $apenasAtivos
     * @return array
     */
    public static function listarTodos(bool $apenasAtivos = false): array
    {
        $pdo = Database::getConnection();
        $sql = "
            SELECT p.id, p.codigo, p.nome, p.foto_url, p.pacote_padrao, p.ativo, p.created_at, p.updated_at,
                   COALESCE(el.quantidade_total, 0) AS quantidade_total,
                   COALESCE(el.quantidade_reservada_gestao, 0) AS quantidade_reservada_gestao
            FROM produtos p
            LEFT JOIN estoque_lojinha el ON el.produto_id = p.id
            WHERE p.deleted_at IS NULL
        ";
        if ($apenasAtivos) {
            $sql .= " AND p.ativo = 1";
        }
        $sql .= " ORDER BY p.nome ASC";

        $stmt = $pdo->prepare($sql);
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
            SELECT id, codigo, nome, foto_url, pacote_padrao, ativo, created_at, updated_at
            FROM produtos 
            WHERE id = :id 
              AND deleted_at IS NULL 
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Busca um produto pelo código único (com opção de ignorar determinado ID em edição)
     * @param string $codigo
     * @param int|null $ignorarId
     * @return array|null
     */
    public static function buscarPorCodigo(string $codigo, ?int $ignorarId = null): ?array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT id, codigo, nome FROM produtos WHERE codigo = :codigo AND deleted_at IS NULL";
        $params = ['codigo' => trim($codigo)];

        if ($ignorarId !== null) {
            $sql .= " AND id != :ignorar_id";
            $params['ignorar_id'] = $ignorarId;
        }
        $sql .= " LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Salva ou atualiza um produto
     * @param array $dados
     * @return int ID do produto
     * @throws InvalidArgumentException
     */
    public static function salvar(array $dados): int
    {
        $id = !empty($dados['id']) ? (int)$dados['id'] : null;
        $codigo = trim((string)($dados['codigo'] ?? ''));
        $nome = trim((string)($dados['nome'] ?? ''));
        $pacotePadrao = trim((string)($dados['pacote_padrao'] ?? 'Unidade'));
        $fotoUrl = !empty($dados['foto_url']) ? trim((string)$dados['foto_url']) : null;
        $ativo = isset($dados['ativo']) ? (int)$dados['ativo'] : 1;

        if ($codigo === '') {
            throw new InvalidArgumentException("O código do produto é obrigatório.");
        }
        if ($nome === '') {
            throw new InvalidArgumentException("O nome do produto é obrigatório.");
        }
        if ($pacotePadrao === '') {
            $pacotePadrao = 'Unidade';
        }

        // Verifica unicidade de código
        $existente = self::buscarPorCodigo($codigo, $id);
        if ($existente) {
            throw new InvalidArgumentException("Já existe um produto ativo cadastrado com o código '{$codigo}'.");
        }

        $pdo = Database::getConnection();

        if ($id !== null && $id > 0) {
            // Edição
            if ($fotoUrl !== null) {
                $stmt = $pdo->prepare("
                    UPDATE produtos 
                    SET codigo = :codigo,
                        nome = :nome,
                        pacote_padrao = :pacote_padrao,
                        foto_url = :foto_url,
                        ativo = :ativo,
                        updated_at = NOW()
                    WHERE id = :id AND deleted_at IS NULL
                ");
                $stmt->execute([
                    'codigo' => $codigo,
                    'nome' => $nome,
                    'pacote_padrao' => $pacotePadrao,
                    'foto_url' => $fotoUrl,
                    'ativo' => $ativo,
                    'id' => $id
                ]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE produtos 
                    SET codigo = :codigo,
                        nome = :nome,
                        pacote_padrao = :pacote_padrao,
                        ativo = :ativo,
                        updated_at = NOW()
                    WHERE id = :id AND deleted_at IS NULL
                ");
                $stmt->execute([
                    'codigo' => $codigo,
                    'nome' => $nome,
                    'pacote_padrao' => $pacotePadrao,
                    'ativo' => $ativo,
                    'id' => $id
                ]);
            }
            return $id;
        }

        // Novo cadastro
        $stmt = $pdo->prepare("
            INSERT INTO produtos (codigo, nome, pacote_padrao, foto_url, ativo, created_at, updated_at)
            VALUES (:codigo, :nome, :pacote_padrao, :foto_url, :ativo, NOW(), NOW())
        ");
        $stmt->execute([
            'codigo' => $codigo,
            'nome' => $nome,
            'pacote_padrao' => $pacotePadrao,
            'foto_url' => $fotoUrl,
            'ativo' => $ativo
        ]);

        $novoId = (int)$pdo->lastInsertId();

        // Garante registro de saldo zerado na tabela estoque_lojinha
        $stmtEstoque = $pdo->prepare("
            INSERT INTO estoque_lojinha (produto_id, quantidade_total, quantidade_reservada_gestao)
            VALUES (:produto_id, 0, 0)
            ON DUPLICATE KEY UPDATE updated_at = NOW()
        ");
        $stmtEstoque->execute(['produto_id' => $novoId]);

        return $novoId;
    }

    /**
     * Alterna o status ativo/inativo do produto
     * @param int $id
     * @return bool
     */
    public static function alternarStatus(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE produtos 
            SET ativo = IF(ativo = 1, 0, 1),
                updated_at = NOW()
            WHERE id = :id AND deleted_at IS NULL
        ");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Realiza soft delete do produto
     * @param int $id
     * @return bool
     */
    public static function excluir(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE produtos 
            SET deleted_at = NOW(),
                ativo = 0
            WHERE id = :id AND deleted_at IS NULL
        ");
        return $stmt->execute(['id' => $id]);
    }
}
