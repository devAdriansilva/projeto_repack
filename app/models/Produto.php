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
            SELECT id, codigo, nome, foto_url, pacote_padrao, produto_pai_id 
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
            SELECT p.id, p.codigo, p.nome, p.foto_url, p.pacote_padrao, p.ativo, p.produto_pai_id, p.created_at, p.updated_at,
                   p_pai.codigo AS produto_pai_codigo, p_pai.nome AS produto_pai_nome,
                   (SELECT COUNT(*) FROM produtos p_filhos WHERE p_filhos.produto_pai_id = p.id AND p_filhos.deleted_at IS NULL) AS total_filhos_acumulados,
                   COALESCE(el.quantidade_total, 0) AS quantidade_total,
                   COALESCE(el.quantidade_reservada_gestao, 0) AS quantidade_reservada_gestao
            FROM produtos p
            LEFT JOIN produtos p_pai ON p_pai.id = p.produto_pai_id
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
            SELECT id, codigo, nome, foto_url, pacote_padrao, ativo, produto_pai_id, created_at, updated_at
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
        $sql = "SELECT id, codigo, nome, produto_pai_id FROM produtos WHERE codigo = :codigo AND deleted_at IS NULL";
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
     * Retorna o ID do produto acumulador de estoque na Lojinha
     * Se o produto for uma variação/multipack com produto_pai_id, retorna o ID do produto pai
     * Caso contrário, retorna o próprio ID do produto
     * @param int $produtoId
     * @return int
     */
    public static function obterIdAcumulador(int $produtoId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, produto_pai_id FROM produtos WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $produtoId]);
        $row = $stmt->fetch();
        if ($row && !empty($row['produto_pai_id'])) {
            return (int)$row['produto_pai_id'];
        }
        return $produtoId;
    }

    /**
     * Retorna a lista de SKUs/produtos que acumulam neste produto pai
     * @param int $produtoPaiId
     * @return array
     */
    public static function listarFilhosAcumulados(int $produtoPaiId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT id, codigo, nome, pacote_padrao
            FROM produtos
            WHERE produto_pai_id = :pai_id AND deleted_at IS NULL
            ORDER BY nome ASC
        ");
        $stmt->execute(['pai_id' => $produtoPaiId]);
        return $stmt->fetchAll();
    }

    /**
     * Vincula ou desvincula um produto pai/acumulador
     * @param int $produtoFilhoId
     * @param int|null $produtoPaiId
     * @return bool
     */
    public static function vincularProdutoPai(int $produtoFilhoId, ?int $produtoPaiId): bool
    {
        if ($produtoPaiId !== null && $produtoFilhoId === $produtoPaiId) {
            throw new InvalidArgumentException("Um produto não pode ser acumulador dele mesmo.");
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE produtos 
            SET produto_pai_id = :pai_id,
                updated_at = NOW()
            WHERE id = :filho_id AND deleted_at IS NULL
        ");
        $ok = $stmt->execute([
            'pai_id' => ($produtoPaiId !== null && $produtoPaiId > 0) ? $produtoPaiId : null,
            'filho_id' => $produtoFilhoId
        ]);

        if ($ok && $produtoPaiId !== null && $produtoPaiId > 0) {
            // Migra qualquer saldo que esteja na lojinha do filho para o pai
            $stmtMigra = $pdo->prepare("
                UPDATE estoque_lojinha el_pai
                JOIN estoque_lojinha el_filho ON el_filho.produto_id = :filho_id
                SET el_pai.quantidade_total = el_pai.quantidade_total + el_filho.quantidade_total,
                    el_filho.quantidade_total = 0
                WHERE el_pai.produto_id = :pai_id AND el_filho.quantidade_total > 0
            ");
            $stmtMigra->execute([
                'filho_id' => $produtoFilhoId,
                'pai_id' => $produtoPaiId
            ]);
        }

        return $ok;
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
        $produtoPaiId = !empty($dados['produto_pai_id']) ? (int)$dados['produto_pai_id'] : null;

        if ($id !== null && $produtoPaiId === $id) {
            $produtoPaiId = null; // Impede auto-referência
        }

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
                        produto_pai_id = :produto_pai_id,
                        foto_url = :foto_url,
                        ativo = :ativo,
                        updated_at = NOW()
                    WHERE id = :id AND deleted_at IS NULL
                ");
                $stmt->execute([
                    'codigo' => $codigo,
                    'nome' => $nome,
                    'pacote_padrao' => $pacotePadrao,
                    'produto_pai_id' => $produtoPaiId,
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
                        produto_pai_id = :produto_pai_id,
                        ativo = :ativo,
                        updated_at = NOW()
                    WHERE id = :id AND deleted_at IS NULL
                ");
                $stmt->execute([
                    'codigo' => $codigo,
                    'nome' => $nome,
                    'pacote_padrao' => $pacotePadrao,
                    'produto_pai_id' => $produtoPaiId,
                    'ativo' => $ativo,
                    'id' => $id
                ]);
            }
            return $id;
        }

        // Novo cadastro
        $stmt = $pdo->prepare("
            INSERT INTO produtos (codigo, nome, pacote_padrao, produto_pai_id, foto_url, ativo, created_at, updated_at)
            VALUES (:codigo, :nome, :pacote_padrao, :produto_pai_id, :foto_url, :ativo, NOW(), NOW())
        ");
        $stmt->execute([
            'codigo' => $codigo,
            'nome' => $nome,
            'pacote_padrao' => $pacotePadrao,
            'produto_pai_id' => $produtoPaiId,
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
