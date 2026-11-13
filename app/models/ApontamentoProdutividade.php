<?php
declare(strict_types=1);

/**
 * Projeto Repack - Model ApontamentoProdutividade
 * Baseado estritamente em docs/FSD.md (Seções 6, 11, 13.2 e 14)
 */

require_once __DIR__ . '/../config/database.php';

class ApontamentoProdutividade
{
    /**
     * Salva um registro de produtividade
     * @param array $dados
     * @return array
     */
    public static function salvar(array $dados): array
    {
        $produtoId = (int)($dados['produto_id'] ?? 0);
        $usuarioId = (int)($dados['usuario_id'] ?? 0);
        $pacotesRefeitos = (int)($dados['pacotes_refeitos'] ?? 0);
        $dataInicio = (string)($dados['data_hora_inicio'] ?? '');
        $dataFim = (string)($dados['data_hora_fim'] ?? '');
        $origem = ($dados['origem_sincronizacao'] ?? 'online') === 'offline' ? 'offline' : 'online';

        if ($produtoId <= 0) {
            throw new InvalidArgumentException("Produto inválido.");
        }
        if ($usuarioId <= 0) {
            throw new InvalidArgumentException("Operador inválido.");
        }
        if ($pacotesRefeitos < 0) {
            throw new InvalidArgumentException("Quantidade de pacotes não pode ser negativa.");
        }
        if (empty($dataInicio) || empty($dataFim)) {
            throw new InvalidArgumentException("Timestamps de início e término são obrigatórios.");
        }

        $inicioTs = strtotime($dataInicio);
        $fimTs = strtotime($dataFim);

        if ($inicioTs === false || $fimTs === false || $fimTs < $inicioTs) {
            throw new InvalidArgumentException("Intervalo de tempo inválido para o ciclo de produtividade.");
        }

        // Duração calculada em segundos
        $duracaoSegundos = (int)($fimTs - $inicioTs);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO apontamentos_produtividade (
                produto_id,
                usuario_id,
                data_hora_inicio,
                data_hora_fim,
                pacotes_refeitos,
                duracao_segundos,
                origem_sincronizacao,
                created_at
            ) VALUES (
                :produto_id,
                :usuario_id,
                :data_hora_inicio,
                :data_hora_fim,
                :pacotes_refeitos,
                :duracao_segundos,
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
            'data_hora_inicio' => date('Y-m-d H:i:s', $inicioTs),
            'data_hora_fim' => date('Y-m-d H:i:s', $fimTs),
            'pacotes_refeitos' => $pacotesRefeitos,
            'duracao_segundos' => $duracaoSegundos,
            'origem_sincronizacao' => $origem,
            'created_at' => $createdAt
        ]);

        return [
            'status' => 'success',
            'id' => (int)$pdo->lastInsertId(),
            'duracao_segundos' => $duracaoSegundos,
            'pacotes_refeitos' => $pacotesRefeitos,
            'message' => 'Produtividade registrada com sucesso.'
        ];
    }

    /**
     * Sincronização em lote de produtividade offline
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
     * Extrato Analítico de Produtividade da Equipe de Repack
     * Conforme docs/FSD.md Seções 6 e 22.3
     * @param string|null $dataInicio
     * @param string|null $dataFim
     * @return array
     */
    public static function obterExtratoAnalitico(?string $dataInicio = null, ?string $dataFim = null): array
    {
        $pdo = Database::getConnection();

        $where = "WHERE ap.deleted_at IS NULL";
        $params = [];

        if (!empty($dataInicio)) {
            $where .= " AND ap.created_at >= :data_inicio";
            $params['data_inicio'] = $dataInicio . ' 00:00:00';
        }
        if (!empty($dataFim)) {
            $where .= " AND ap.created_at <= :data_fim";
            $params['data_fim'] = $dataFim . ' 23:59:59';
        }

        // 1. Totais Gerais
        $stmtTotais = $pdo->prepare("
            SELECT 
                COUNT(*) AS total_ciclos,
                COALESCE(SUM(ap.pacotes_refeitos), 0) AS total_pacotes,
                COALESCE(SUM(ap.duracao_segundos), 0) AS total_segundos
            FROM apontamentos_produtividade ap
            {$where}
        ");
        $stmtTotais->execute($params);
        $totais = $stmtTotais->fetch();

        $totalCiclos = (int)($totais['total_ciclos'] ?? 0);
        $totalPacotes = (int)($totais['total_pacotes'] ?? 0);
        $totalSegundos = (int)($totais['total_segundos'] ?? 0);

        $totalHoras = $totalSegundos > 0 ? round($totalSegundos / 3600, 2) : 0.0;
        $mediaGeralPacotesPorHora = $totalHoras > 0 ? round($totalPacotes / $totalHoras, 1) : 0.0;
        $tempoMedioGeralPorPacote = $totalPacotes > 0 ? round($totalSegundos / $totalPacotes, 1) : 0.0;

        // 2. Produtividade por Operador
        $stmtOperadores = $pdo->prepare("
            SELECT 
                u.id AS usuario_id,
                u.matricula AS usuario_matricula,
                u.nome AS usuario_nome,
                COUNT(ap.id) AS total_ciclos,
                COALESCE(SUM(ap.pacotes_refeitos), 0) AS total_pacotes,
                COALESCE(SUM(ap.duracao_segundos), 0) AS total_segundos
            FROM apontamentos_produtividade ap
            INNER JOIN usuarios u ON u.id = ap.usuario_id
            {$where}
            GROUP BY u.id, u.matricula, u.nome
            ORDER BY total_pacotes DESC
        ");
        $stmtOperadores->execute($params);
        $linhasOperadores = $stmtOperadores->fetchAll();

        $porOperador = [];
        foreach ($linhasOperadores as $op) {
            $segundos = (int)$op['total_segundos'];
            $pacotes = (int)$op['total_pacotes'];
            $ciclos = (int)$op['total_ciclos'];
            $horas = $segundos > 0 ? ($segundos / 3600) : 0;
            $pacotesHora = $horas > 0 ? round($pacotes / $horas, 1) : 0.0;
            $tempoMedioCiclo = $ciclos > 0 ? round($segundos / $ciclos, 0) : 0;
            $tempoMedioPacote = $pacotes > 0 ? round($segundos / $pacotes, 1) : 0.0;

            $porOperador[] = [
                'usuario_id' => (int)$op['usuario_id'],
                'matricula' => $op['usuario_matricula'],
                'nome' => $op['usuario_nome'],
                'total_ciclos' => $ciclos,
                'total_pacotes' => $pacotes,
                'total_segundos' => $segundos,
                'total_horas' => round($horas, 2),
                'pacotes_por_hora' => $pacotesHora,
                'tempo_medio_ciclo' => (int)$tempoMedioCiclo,
                'tempo_medio_pacote' => $tempoMedioPacote
            ];
        }

        // 3. Tempo Médio de Ciclo por Produto
        $stmtProdutos = $pdo->prepare("
            SELECT 
                pr.id AS produto_id,
                pr.codigo AS produto_codigo,
                pr.nome AS produto_nome,
                pr.pacote_padrao,
                COUNT(ap.id) AS total_ciclos,
                COALESCE(SUM(ap.pacotes_refeitos), 0) AS total_pacotes,
                COALESCE(SUM(ap.duracao_segundos), 0) AS total_segundos
            FROM apontamentos_produtividade ap
            INNER JOIN produtos pr ON pr.id = ap.produto_id
            {$where}
            GROUP BY pr.id, pr.codigo, pr.nome, pr.pacote_padrao
            ORDER BY total_pacotes DESC
        ");
        $stmtProdutos->execute($params);
        $linhasProdutos = $stmtProdutos->fetchAll();

        $porProduto = [];
        foreach ($linhasProdutos as $p) {
            $segundos = (int)$p['total_segundos'];
            $pacotes = (int)$p['total_pacotes'];
            $ciclos = (int)$p['total_ciclos'];
            $horas = $segundos > 0 ? ($segundos / 3600) : 0;
            $tempoMedioPacote = $pacotes > 0 ? round($segundos / $pacotes, 1) : 0.0;
            $pacotesHora = $horas > 0 ? round($pacotes / $horas, 1) : 0.0;

            $porProduto[] = [
                'produto_id' => (int)$p['produto_id'],
                'codigo' => $p['produto_codigo'],
                'nome' => $p['produto_nome'],
                'pacote_padrao' => $p['pacote_padrao'],
                'total_ciclos' => $ciclos,
                'total_pacotes' => $pacotes,
                'total_segundos' => $segundos,
                'tempo_medio_pacote_segundos' => $tempoMedioPacote,
                'pacotes_por_hora' => $pacotesHora
            ];
        }

        return [
            'total_ciclos' => $totalCiclos,
            'total_pacotes' => $totalPacotes,
            'total_segundos' => $totalSegundos,
            'total_horas' => $totalHoras,
            'media_geral_pacotes_por_hora' => $mediaGeralPacotesPorHora,
            'tempo_medio_geral_por_pacote' => $tempoMedioGeralPorPacote,
            'por_operador' => $porOperador,
            'por_produto' => $porProduto
        ];
    }

    /**
     * Lista apontamentos de produtividade com filtros dinâmicos de período, operador e produto
     * Utiliza Prepared Statements nativos e índices para alta performance em tempo real
     * @param array $filtros ['data_inicio', 'data_fim', 'usuario_id', 'produto_id', 'limite']
     * @return array
     */
    public static function listarComFiltros(array $filtros = []): array
    {
        $pdo = Database::getConnection();
        $sql = "
            SELECT ap.*, 
                   p.nome AS produto_nome, p.codigo AS produto_codigo, p.pacote_padrao,
                   u.nome AS operador_nome, u.matricula AS operador_matricula
            FROM apontamentos_produtividade ap
            INNER JOIN produtos p ON p.id = ap.produto_id
            INNER JOIN usuarios u ON u.id = ap.usuario_id
            WHERE ap.deleted_at IS NULL
        ";
        $params = [];

        if (!empty($filtros['data_inicio'])) {
            $sql .= " AND ap.created_at >= :data_inicio";
            $params['data_inicio'] = $filtros['data_inicio'] . ' 00:00:00';
        }
        if (!empty($filtros['data_fim'])) {
            $sql .= " AND ap.created_at <= :data_fim";
            $params['data_fim'] = $filtros['data_fim'] . ' 23:59:59';
        }
        if (!empty($filtros['produto_id'])) {
            $sql .= " AND ap.produto_id = :produto_id";
            $params['produto_id'] = (int)$filtros['produto_id'];
        }
        if (!empty($filtros['usuario_id'])) {
            $sql .= " AND ap.usuario_id = :usuario_id";
            $params['usuario_id'] = (int)$filtros['usuario_id'];
        }

        $sql .= " ORDER BY ap.created_at DESC, ap.id DESC";

        $limite = (int)($filtros['limite'] ?? 50);
        $sql .= " LIMIT " . max(1, min(500, $limite));

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
