<?php
declare(strict_types=1);

/**
 * Projeto Repack - Model LoteVenda
 * Baseado estritamente em docs/FSD.md (Seções 6, 10, 11, 12.3 e 14)
 */

require_once __DIR__ . '/../config/database.php';

class LoteVenda
{
    public const STATUS_PLANEJADO = 'planejado';
    public const STATUS_ABERTO = 'aberto';
    public const STATUS_ENCERRADO = 'encerrado';

    /**
     * Retorna todos os lotes não excluídos
     * @return array
     */
    public static function listarTodos(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT lv.*, u.nome AS criado_por_nome,
                   (SELECT COUNT(*) FROM pedidos p WHERE p.lote_id = lv.id AND p.status != 'cancelado') AS total_pedidos,
                   (SELECT COALESCE(SUM(p.quantidade_total), 0) FROM pedidos p WHERE p.lote_id = lv.id AND p.status != 'cancelado') AS total_itens_vendidos
            FROM lotes_venda lv
            INNER JOIN usuarios u ON u.id = lv.criado_por_usuario_id
            WHERE lv.deleted_at IS NULL
            ORDER BY lv.created_at DESC
        ");
        $stmt->execute();
        $lotes = $stmt->fetchAll();

        // Converte setores_autorizados para array legível caso seja JSON
        foreach ($lotes as &$lote) {
            $setoresRaw = $lote['setores_autorizados'] ?? '';
            $decoded = json_decode($setoresRaw, true);
            $lote['setores_lista'] = is_array($decoded) ? $decoded : array_map('trim', explode(',', $setoresRaw));
        }

        return $lotes;
    }

    /**
     * Busca um lote por ID
     * @param int $id
     * @return array|null
     */
    public static function buscarPorId(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT lv.*, u.nome AS criado_por_nome
            FROM lotes_venda lv
            INNER JOIN usuarios u ON u.id = lv.criado_por_usuario_id
            WHERE lv.id = :id AND lv.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $lote = $stmt->fetch();
        if (!$lote) {
            return null;
        }

        $setoresRaw = $lote['setores_autorizados'] ?? '';
        $decoded = json_decode($setoresRaw, true);
        $lote['setores_lista'] = is_array($decoded) ? $decoded : array_map('trim', explode(',', $setoresRaw));
        return $lote;
    }

    /**
     * Cadastra ou atualiza um lote de venda
     * @param array $dados
     * @param int $usuarioGestorId
     * @return int
     * @throws InvalidArgumentException
     */
    public static function salvar(array $dados, int $usuarioGestorId): int
    {
        $id = !empty($dados['id']) ? (int)$dados['id'] : null;
        $titulo = trim((string)($dados['titulo'] ?? ''));
        $descricao = trim((string)($dados['descricao'] ?? ''));
        $dataInicio = trim((string)($dados['data_inicio'] ?? ''));
        $dataFim = trim((string)($dados['data_fim'] ?? ''));
        $limiteItens = max(1, (int)($dados['limite_itens_por_comprador'] ?? 5));
        $tempoCarrinho = max(1, (int)($dados['tempo_carrinho_minutos'] ?? 5));
        $status = strtolower(trim((string)($dados['status'] ?? self::STATUS_PLANEJADO)));

        if (!in_array($status, [self::STATUS_PLANEJADO, self::STATUS_ABERTO, self::STATUS_ENCERRADO], true)) {
            $status = self::STATUS_PLANEJADO;
        }

        if ($titulo === '') {
            throw new InvalidArgumentException("O título do lote é obrigatório.");
        }
        if ($dataInicio === '' || $dataFim === '') {
            throw new InvalidArgumentException("As datas e horários de início e término são obrigatórias.");
        }

        $dtInicio = strtotime($dataInicio);
        $dtFim = strtotime($dataFim);
        if ($dtInicio === false || $dtFim === false || $dtFim <= $dtInicio) {
            throw new InvalidArgumentException("A data/hora de término deve ser posterior à data/hora de início.");
        }

        // Setores autorizados: converte array em JSON formatado
        $setores = $dados['setores_autorizados'] ?? [];
        if (is_string($setores)) {
            $setores = array_map('trim', explode(',', $setores));
        }
        if (!is_array($setores) || empty($setores)) {
            throw new InvalidArgumentException("Selecione ao menos um setor autorizado para o lote.");
        }
        $setoresJson = json_encode(array_values(array_filter($setores)), JSON_UNESCAPED_UNICODE);

        $pdo = Database::getConnection();

        if ($id !== null && $id > 0) {
            $stmt = $pdo->prepare("
                UPDATE lotes_venda 
                SET titulo = :titulo,
                    descricao = :descricao,
                    data_inicio = :data_inicio,
                    data_fim = :data_fim,
                    setores_autorizados = :setores_autorizados,
                    limite_itens_por_comprador = :limite,
                    tempo_carrinho_minutos = :tempo_carrinho,
                    status = :status,
                    updated_at = NOW()
                WHERE id = :id AND deleted_at IS NULL
            ");
            $stmt->execute([
                'titulo' => $titulo,
                'descricao' => $descricao,
                'data_inicio' => date('Y-m-d H:i:s', $dtInicio),
                'data_fim' => date('Y-m-d H:i:s', $dtFim),
                'setores_autorizados' => $setoresJson,
                'limite' => $limiteItens,
                'tempo_carrinho' => $tempoCarrinho,
                'status' => $status,
                'id' => $id
            ]);
            return $id;
        }

        $stmt = $pdo->prepare("
            INSERT INTO lotes_venda (
                titulo, descricao, data_inicio, data_fim, setores_autorizados,
                limite_itens_por_comprador, tempo_carrinho_minutos, status, criado_por_usuario_id, created_at, updated_at
            ) VALUES (
                :titulo, :descricao, :data_inicio, :data_fim, :setores_autorizados,
                :limite, :tempo_carrinho, :status, :criado_por, NOW(), NOW()
            )
        ");
        $stmt->execute([
            'titulo' => $titulo,
            'descricao' => $descricao,
            'data_inicio' => date('Y-m-d H:i:s', $dtInicio),
            'data_fim' => date('Y-m-d H:i:s', $dtFim),
            'setores_autorizados' => $setoresJson,
            'limite' => $limiteItens,
            'tempo_carrinho' => $tempoCarrinho,
            'status' => $status,
            'criado_por' => $usuarioGestorId
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Altera o status do lote
     * @param int $id
     * @param string $status
     * @return bool
     */
    public static function alterarStatus(int $id, string $status): bool
    {
        $status = strtolower(trim($status));
        if (!in_array($status, [self::STATUS_PLANEJADO, self::STATUS_ABERTO, self::STATUS_ENCERRADO], true)) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE lotes_venda 
            SET status = :status,
                updated_at = NOW()
            WHERE id = :id AND deleted_at IS NULL
        ");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /**
     * Soft delete do lote
     * @param int $id
     * @return bool
     */
    public static function excluir(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE lotes_venda 
            SET deleted_at = NOW()
            WHERE id = :id AND deleted_at IS NULL
        ");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Busca o lote aberto ativo compatível com o setor informado
     * Conforme docs/FSD.md Seções 6, 8, 12.2, 13.3 e 16
     * @param string $setor
     * @return array|null
     */
    public static function buscarLoteAtivoParaSetor(string $setor): ?array
    {
        $setorAlvo = mb_strtolower(trim($setor));
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT lv.*, u.nome AS criado_por_nome
            FROM lotes_venda lv
            INNER JOIN usuarios u ON u.id = lv.criado_por_usuario_id
            WHERE lv.status = 'aberto'
              AND lv.data_inicio <= NOW()
              AND lv.data_fim >= NOW()
              AND lv.deleted_at IS NULL
            ORDER BY lv.data_inicio DESC
        ");
        $stmt->execute();
        $lotes = $stmt->fetchAll();

        foreach ($lotes as $lote) {
            $setoresRaw = $lote['setores_autorizados'] ?? '';
            $decoded = json_decode($setoresRaw, true);
            $setoresLista = is_array($decoded) ? $decoded : array_map('trim', explode(',', $setoresRaw));

            foreach ($setoresLista as $s) {
                $sFormat = mb_strtolower(trim((string)$s));
                if ($sFormat === '*' || $sFormat === 'todos' || $sFormat === $setorAlvo) {
                    $lote['setores_lista'] = $setoresLista;
                    return $lote;
                }
            }
        }

        return null;
    }

    /**
     * Busca o próximo lote com status planejado para informar ao colaborador
     * @return array|null
     */
    public static function buscarProximoLotePlanejado(): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT *
            FROM lotes_venda
            WHERE status = 'planejado'
              AND data_inicio > NOW()
              AND deleted_at IS NULL
            ORDER BY data_inicio ASC
            LIMIT 1
        ");
        $stmt->execute();
        $lote = $stmt->fetch();
        if (!$lote) {
            return null;
        }

        $setoresRaw = $lote['setores_autorizados'] ?? '';
        $decoded = json_decode($setoresRaw, true);
        $lote['setores_lista'] = is_array($decoded) ? $decoded : array_map('trim', explode(',', $setoresRaw));
        return $lote;
    }

    /**
     * Retorna o lote corporativo permanente utilizado por contas setoriais e gestão master
     * @return array|null
     */
    public static function obterLoteCorporativo(): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT lv.*, u.nome AS criado_por_nome
            FROM lotes_venda lv
            LEFT JOIN usuarios u ON u.id = lv.criado_por_usuario_id
            WHERE lv.titulo = 'Lote Corporativo e Setorial' 
              AND lv.deleted_at IS NULL
            ORDER BY lv.id DESC
            LIMIT 1
        ");
        $stmt->execute();
        $lote = $stmt->fetch();
        if ($lote) {
            $setoresRaw = $lote['setores_autorizados'] ?? '';
            $decoded = json_decode($setoresRaw, true);
            $lote['setores_lista'] = is_array($decoded) ? $decoded : ['*'];
            return $lote;
        }

        // Fallback: se por algum motivo ainda não existir, cria sob demanda
        $stmtGestor = $pdo->query("SELECT id FROM usuarios WHERE perfil = 'gestao' LIMIT 1");
        $gestorId = (int)$stmtGestor->fetchColumn() ?: 1;

        $novoId = self::salvar([
            'titulo' => 'Lote Corporativo e Setorial',
            'descricao' => 'Lote institucional contínuo para requisições de setores internos e gestão master.',
            'data_inicio' => '2026-01-01 00:00:00',
            'data_fim' => '2099-12-31 23:59:59',
            'setores_autorizados' => ['*'],
            'limite_itens_por_comprador' => 9999,
            'status' => self::STATUS_ABERTO
        ], $gestorId);

        return self::buscarPorId($novoId);
    }
}
