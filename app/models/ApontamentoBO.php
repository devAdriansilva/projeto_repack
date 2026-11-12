<?php
declare(strict_types=1);

/**
 * Projeto Repack - Model ApontamentoBO
 * Baseado estritamente em docs/FSD.md (Seções 6, 11, 12.3, 13.1, 14 e 17)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/EstoqueLojinha.php';

class ApontamentoBO
{
    public const TIPOS_LOJINHA = ['amassada', 'def_rotulo', 'emba_danificada'];
    public const TIPOS_DESCARTE = ['furo', 'micro_furo', 'quebrada', 'validade'];

    /**
     * Determina o destino da avaria com base no tipo padronizado
     * @param string $tipoAvaria
     * @return string 'lojinha' ou 'descarte'
     */
    public static function determinarDestino(string $tipoAvaria): string
    {
        $tipoNormalizado = strtolower(trim($tipoAvaria));
        if (in_array($tipoNormalizado, self::TIPOS_LOJINHA, true)) {
            return 'lojinha';
        }
        if (in_array($tipoNormalizado, self::TIPOS_DESCARTE, true)) {
            return 'descarte';
        }
        throw new InvalidArgumentException("Tipo de avaria inválido: {$tipoAvaria}");
    }

    /**
     * Salva um apontamento de B.O. garantindo integridade transacional e roteamento automático
     * @param array $dados
     * @return array
     */
    public static function salvar(array $dados): array
    {
        $produtoId = (int)($dados['produto_id'] ?? 0);
        $usuarioId = (int)($dados['usuario_id'] ?? 0);
        $tipoAvaria = (string)($dados['tipo_avaria'] ?? '');
        $quantidade = (int)($dados['quantidade'] ?? 0);
        $origem = ($dados['origem_sincronizacao'] ?? 'online') === 'offline' ? 'offline' : 'online';

        if ($produtoId <= 0) {
            throw new InvalidArgumentException("Produto inválido.");
        }
        if ($usuarioId <= 0) {
            throw new InvalidArgumentException("Operador inválido.");
        }
        if ($quantidade <= 0) {
            throw new InvalidArgumentException("Quantidade deve ser maior que zero.");
        }

        $destino = self::determinarDestino($tipoAvaria);

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("
                INSERT INTO apontamentos_bo (
                    produto_id, 
                    usuario_id, 
                    tipo_avaria, 
                    quantidade, 
                    destino, 
                    origem_sincronizacao,
                    created_at
                ) VALUES (
                    :produto_id, 
                    :usuario_id, 
                    :tipo_avaria, 
                    :quantidade, 
                    :destino, 
                    :origem_sincronizacao,
                    :created_at
                )
            ");

            $createdAt = !empty($dados['created_at']) 
                ? date('Y-m-d H:i:s', strtotime($dados['created_at'])) 
                : date('Y-m-d H:i:s');

            $stmt->execute([
                'produto_id' => $produtoId,
                'usuario_id' => $usuarioId,
                'tipo_avaria' => $tipoAvaria,
                'quantidade' => $quantidade,
                'destino' => $destino,
                'origem_sincronizacao' => $origem,
                'created_at' => $createdAt
            ]);

            $boId = (int)$pdo->lastInsertId();

            // Se for destinado à lojinha, incrementa o saldo disponível
            if ($destino === 'lojinha') {
                EstoqueLojinha::incrementarSaldo($produtoId, $quantidade);
            }

            $pdo->commit();

            return [
                'status' => 'success',
                'id' => $boId,
                'destino' => $destino,
                'quantidade' => $quantidade,
                'message' => 'Apontamento de B.O. registrado com sucesso.'
            ];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Processa a sincronização de um lote de B.O.s acumulados offline
     * @param array $apontamentos
     * @return array
     */
    public static function sincronizarLote(array $apontamentos): array
    {
        $resultados = [
            'processados' => 0,
            'sucessos' => 0,
            'falhas' => 0,
            'itens' => []
        ];

        foreach ($apontamentos as $item) {
            $resultados['processados']++;
            try {
                $item['origem_sincronizacao'] = 'offline';
                $resp = self::salvar($item);
                $resultados['sucessos']++;
                $resultados['itens'][] = [
                    'local_id' => $item['local_id'] ?? null,
                    'status' => 'success',
                    'id' => $resp['id']
                ];
            } catch (\Throwable $e) {
                $resultados['falhas']++;
                $resultados['itens'][] = [
                    'local_id' => $item['local_id'] ?? null,
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $resultados;
    }

    /**
     * Busca um B.O. pelo ID com dados complementares de produto, operador e corretor
     * @param int $id
     * @return array|null
     */
    public static function buscarPorId(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT bo.*, p.nome AS produto_nome, p.codigo AS produto_codigo,
                   u.nome AS operador_nome, u.matricula AS operador_matricula,
                   uc.nome AS corretor_nome
            FROM apontamentos_bo bo
            INNER JOIN produtos p ON p.id = bo.produto_id
            INNER JOIN usuarios u ON u.id = bo.usuario_id
            LEFT JOIN usuarios uc ON uc.id = bo.corrigido_por_usuario_id
            WHERE bo.id = :id AND bo.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $bo = $stmt->fetch();
        return $bo ?: null;
    }

    /**
     * Lista apontamentos com filtros para o painel de gestão
     * @param array $filtros
     * @return array
     */
    public static function listarComFiltros(array $filtros = []): array
    {
        $pdo = Database::getConnection();
        $sql = "
            SELECT bo.*, p.nome AS produto_nome, p.codigo AS produto_codigo,
                   u.nome AS operador_nome, u.matricula AS operador_matricula,
                   uc.nome AS corretor_nome
            FROM apontamentos_bo bo
            INNER JOIN produtos p ON p.id = bo.produto_id
            INNER JOIN usuarios u ON u.id = bo.usuario_id
            LEFT JOIN usuarios uc ON uc.id = bo.corrigido_por_usuario_id
            WHERE bo.deleted_at IS NULL
        ";
        $params = [];

        if (!empty($filtros['data_inicio'])) {
            $sql .= " AND bo.created_at >= :data_inicio";
            $params['data_inicio'] = $filtros['data_inicio'] . ' 00:00:00';
        }
        if (!empty($filtros['data_fim'])) {
            $sql .= " AND bo.created_at <= :data_fim";
            $params['data_fim'] = $filtros['data_fim'] . ' 23:59:59';
        }
        if (!empty($filtros['produto_id'])) {
            $sql .= " AND bo.produto_id = :produto_id";
            $params['produto_id'] = (int)$filtros['produto_id'];
        }
        if (!empty($filtros['usuario_id'])) {
            $sql .= " AND bo.usuario_id = :usuario_id";
            $params['usuario_id'] = (int)$filtros['usuario_id'];
        }
        if (!empty($filtros['destino'])) {
            $sql .= " AND bo.destino = :destino";
            $params['destino'] = $filtros['destino'];
        }
        if (!empty($filtros['tipo_avaria'])) {
            $sql .= " AND bo.tipo_avaria = :tipo_avaria";
            $params['tipo_avaria'] = $filtros['tipo_avaria'];
        }

        $sql .= " ORDER BY bo.created_at DESC";

        $limite = (int)($filtros['limite'] ?? 100);
        $sql .= " LIMIT " . max(1, min(500, $limite));

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Retifica um apontamento incorreto feito pelo operador, mantendo integridade com o estoque
     * @param int $id ID do apontamento
     * @param array $novosDados Contendo: quantidade, tipo_avaria
     * @param int $gestorId ID do usuário gestor autenticado
     * @param string $motivo Justificativa formal obrigatória
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function corrigir(int $id, array $novosDados, int $gestorId, string $motivo): bool
    {
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw new InvalidArgumentException("O motivo da retificação é estritamente obrigatório.");
        }

        $novaQtd = (int)($novosDados['quantidade'] ?? 0);
        if ($novaQtd <= 0) {
            throw new InvalidArgumentException("A nova quantidade deve ser maior que zero.");
        }

        $novoTipo = (string)($novosDados['tipo_avaria'] ?? '');
        $novoDestino = self::determinarDestino($novoTipo);

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // Busca o registro original
            $boOriginal = self::buscarPorId($id);
            if (!$boOriginal) {
                throw new InvalidArgumentException("Apontamento de B.O. #{$id} não encontrado.");
            }

            $produtoId = (int)$boOriginal['produto_id'];
            $antigoDestino = (string)$boOriginal['destino'];
            $antigaQtd = (int)$boOriginal['quantidade'];

            // Ajuste reflexo no estoque da lojinha
            if ($antigoDestino === 'lojinha' && $novoDestino === 'lojinha') {
                $dif = $novaQtd - $antigaQtd;
                if ($dif !== 0) {
                    $stmtEstoque = $pdo->prepare("
                        UPDATE estoque_lojinha 
                        SET quantidade_total = GREATEST(0, quantidade_total + :dif),
                            updated_at = NOW()
                        WHERE produto_id = :produto_id
                    ");
                    $stmtEstoque->execute(['dif' => $dif, 'produto_id' => $produtoId]);
                }
            } elseif ($antigoDestino === 'lojinha' && $novoDestino === 'descarte') {
                // Remove o que havia sido creditado na lojinha
                $stmtEstoque = $pdo->prepare("
                    UPDATE estoque_lojinha 
                    SET quantidade_total = GREATEST(0, quantidade_total - :antiga_qtd),
                        updated_at = NOW()
                    WHERE produto_id = :produto_id
                ");
                $stmtEstoque->execute(['antiga_qtd' => $antigaQtd, 'produto_id' => $produtoId]);
            } elseif ($antigoDestino === 'descarte' && $novoDestino === 'lojinha') {
                // Credita o novo valor na lojinha
                EstoqueLojinha::incrementarSaldo($produtoId, $novaQtd);
            }

            // Atualiza o apontamento de B.O. com autoria e justificativa
            $stmtUpdate = $pdo->prepare("
                UPDATE apontamentos_bo 
                SET quantidade = :quantidade,
                    tipo_avaria = :tipo_avaria,
                    destino = :destino,
                    motivo_correcao = :motivo,
                    corrigido_por_usuario_id = :gestor_id,
                    updated_at = NOW()
                WHERE id = :id AND deleted_at IS NULL
            ");
            $stmtUpdate->execute([
                'quantidade' => $novaQtd,
                'tipo_avaria' => $novoTipo,
                'destino' => $novoDestino,
                'motivo' => $motivo,
                'gestor_id' => $gestorId,
                'id' => $id
            ]);

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Relatório Sintético de Movimentação de Repack
     * Conforme docs/FSD.md Seções 6 e 22.2
     * @param string|null $dataInicio
     * @param string|null $dataFim
     * @return array
     */
    public static function obterRelatorioSintetico(?string $dataInicio = null, ?string $dataFim = null): array
    {
        $pdo = Database::getConnection();

        $where = "WHERE deleted_at IS NULL";
        $params = [];

        if (!empty($dataInicio)) {
            $where .= " AND created_at >= :data_inicio";
            $params['data_inicio'] = $dataInicio . ' 00:00:00';
        }
        if (!empty($dataFim)) {
            $where .= " AND created_at <= :data_fim";
            $params['data_fim'] = $dataFim . ' 23:59:59';
        }

        // 1. Totais Gerais
        $stmtTotais = $pdo->prepare("
            SELECT 
                COUNT(*) AS total_registros,
                COALESCE(SUM(quantidade), 0) AS total_unidades,
                COALESCE(SUM(CASE WHEN destino = 'lojinha' THEN quantidade ELSE 0 END), 0) AS unidades_lojinha,
                COALESCE(SUM(CASE WHEN destino = 'descarte' THEN quantidade ELSE 0 END), 0) AS unidades_descarte
            FROM apontamentos_bo
            {$where}
        ");
        $stmtTotais->execute($params);
        $totais = $stmtTotais->fetch();

        $totalUnidades = (int)($totais['total_unidades'] ?? 0);
        $unidadesLojinha = (int)($totais['unidades_lojinha'] ?? 0);
        $unidadesDescarte = (int)($totais['unidades_descarte'] ?? 0);

        $percentualLojinha = $totalUnidades > 0 ? round(($unidadesLojinha / $totalUnidades) * 100, 2) : 0.0;
        $percentualDescarte = $totalUnidades > 0 ? round(($unidadesDescarte / $totalUnidades) * 100, 2) : 0.0;

        // 2. Detalhamento por Tipo de Avaria
        $stmtTipos = $pdo->prepare("
            SELECT 
                tipo_avaria,
                destino,
                COUNT(*) AS total_ocorrencias,
                COALESCE(SUM(quantidade), 0) AS quantidade_total
            FROM apontamentos_bo
            {$where}
            GROUP BY tipo_avaria, destino
            ORDER BY quantidade_total DESC
        ");
        $stmtTipos->execute($params);
        $linhasTipos = $stmtTipos->fetchAll();

        $porTipo = [];
        foreach ($linhasTipos as $tipo) {
            $qtd = (int)$tipo['quantidade_total'];
            $pct = $totalUnidades > 0 ? round(($qtd / $totalUnidades) * 100, 2) : 0.0;
            $porTipo[] = [
                'tipo_avaria' => $tipo['tipo_avaria'],
                'destino' => $tipo['destino'],
                'total_ocorrencias' => (int)$tipo['total_ocorrencias'],
                'quantidade_total' => $qtd,
                'percentual' => $pct
            ];
        }

        // 3. Detalhamento por Produto
        $whereProd = str_replace(['created_at', 'deleted_at'], ['bo.created_at', 'bo.deleted_at'], $where);
        $stmtProdutos = $pdo->prepare("
            SELECT 
                p.id AS produto_id,
                p.codigo AS produto_codigo,
                p.nome AS produto_nome,
                p.pacote_padrao,
                COUNT(bo.id) AS total_ocorrencias,
                COALESCE(SUM(bo.quantidade), 0) AS total_unidades,
                COALESCE(SUM(CASE WHEN bo.destino = 'lojinha' THEN bo.quantidade ELSE 0 END), 0) AS unidades_lojinha,
                COALESCE(SUM(CASE WHEN bo.destino = 'descarte' THEN bo.quantidade ELSE 0 END), 0) AS unidades_descarte
            FROM apontamentos_bo bo
            INNER JOIN produtos p ON p.id = bo.produto_id
            {$whereProd}
            GROUP BY p.id, p.codigo, p.nome, p.pacote_padrao
            ORDER BY total_unidades DESC
        ");
        $stmtProdutos->execute($params);
        $linhasProdutos = $stmtProdutos->fetchAll();

        $porProduto = [];
        foreach ($linhasProdutos as $prod) {
            $tot = (int)$prod['total_unidades'];
            $loj = (int)$prod['unidades_lojinha'];
            $desc = (int)$prod['unidades_descarte'];
            $taxaRecup = $tot > 0 ? round(($loj / $tot) * 100, 2) : 0.0;
            $porProduto[] = [
                'produto_id' => (int)$prod['produto_id'],
                'codigo' => $prod['produto_codigo'],
                'nome' => $prod['produto_nome'],
                'pacote_padrao' => $prod['pacote_padrao'],
                'total_ocorrencias' => (int)$prod['total_ocorrencias'],
                'total_unidades' => $tot,
                'unidades_lojinha' => $loj,
                'unidades_descarte' => $desc,
                'taxa_recuperacao' => $taxaRecup
            ];
        }

        return [
            'total_registros' => (int)($totais['total_registros'] ?? 0),
            'total_unidades' => $totalUnidades,
            'unidades_lojinha' => $unidadesLojinha,
            'unidades_descarte' => $unidadesDescarte,
            'percentual_lojinha' => $percentualLojinha,
            'percentual_descarte' => $percentualDescarte,
            'por_tipo' => $porTipo,
            'por_produto' => $porProduto
        ];
    }
}
