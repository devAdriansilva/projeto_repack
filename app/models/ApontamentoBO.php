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
    public const TIPOS_LOJINHA = ['amassada', 'def_rotulo', 'emba_danificada', 'validade'];
    public const TIPOS_DESCARTE = ['furo', 'micro_furo', 'quebrada'];

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

    /**
     * Dicionário expansível de motivos conhecidos
     */
    public const MAPEAMENTO_MOTIVOS = [
        // Lojinha (Avarias leves, embalagens, validade, descaracterização)
        'amassada' => 'lojinha',
        'def_rotulo' => 'lojinha',
        'def. rótulo' => 'lojinha',
        'def rotulo' => 'lojinha',
        'defeito rotulo' => 'lojinha',
        'rotulo' => 'lojinha',
        'emba_danificada' => 'lojinha',
        'emba. danificada' => 'lojinha',
        'embalagem danificada' => 'lojinha',
        'validade' => 'lojinha',
        'vencimento' => 'lojinha',
        'vencimento proximo' => 'lojinha',
        'troca de embalagem' => 'lojinha',
        'descaracterizacao' => 'lojinha',
        'palete' => 'lojinha',

        // Descarte (Perda crítica irreversível)
        'furo' => 'descarte',
        'furada' => 'descarte',
        'micro_furo' => 'descarte',
        'micro furo' => 'descarte',
        'microfuro' => 'descarte',
        'quebrada' => 'descarte',
        'quebra' => 'descarte',
        'vazamento' => 'descarte',
        'contaminada' => 'descarte'
    ];

    /**
     * Classifica um motivo externo vindo de relatórios CSV corporativos
     * Permite sobreposição manual enviada pelo gestor
     * @param string $motivoTexto
     * @param string|null $destinoForcado
     * @return array [chave, destino, nome_formatado, customizado]
     */
    public static function classificarMotivoExterno(string $motivoTexto, ?string $destinoForcado = null): array
    {
        $raw = trim($motivoTexto);
        if ($destinoForcado === 'lojinha' || $destinoForcado === 'descarte') {
            return [
                'chave' => mb_strtolower($raw, 'UTF-8'),
                'destino' => $destinoForcado,
                'nome_formatado' => $raw !== '' ? $raw : 'Personalizado',
                'customizado' => true
            ];
        }

        $limpo = mb_strtolower($raw, 'UTF-8');
        $semAcento = (string)iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $limpo);
        $semAcento = strtolower(trim(preg_replace('/\s+/', ' ', preg_replace('/[.\-_]+/', ' ', $semAcento))));

        if (isset(self::MAPEAMENTO_MOTIVOS[$limpo])) {
            return [
                'chave' => $limpo,
                'destino' => self::MAPEAMENTO_MOTIVOS[$limpo],
                'nome_formatado' => $raw,
                'customizado' => false
            ];
        }

        if (isset(self::MAPEAMENTO_MOTIVOS[$semAcento])) {
            return [
                'chave' => $semAcento,
                'destino' => self::MAPEAMENTO_MOTIVOS[$semAcento],
                'nome_formatado' => $raw,
                'customizado' => false
            ];
        }

        // Heurística de descarte crítico
        if (
            strpos($semAcento, 'furo') !== false ||
            strpos($semAcento, 'furad') !== false ||
            strpos($semAcento, 'quebr') !== false ||
            strpos($semAcento, 'vazam') !== false ||
            strpos($semAcento, 'contam') !== false
        ) {
            return [
                'chave' => strpos($semAcento, 'micro') !== false ? 'micro_furo' : 'furo',
                'destino' => 'descarte',
                'nome_formatado' => $raw,
                'customizado' => false
            ];
        }

        // Padrão seguro para motivos recuperáveis adicionais
        return [
            'chave' => $limpo !== '' ? $limpo : 'avaria_externa',
            'destino' => 'lojinha',
            'nome_formatado' => $raw !== '' ? $raw : 'Outros',
            'customizado' => false
        ];
    }

    /**
     * Faz o parse das linhas de um CSV de relatório de B.O. corporativo
     * @param string $conteudoCsv
     * @return array
     */
    public static function parseCsvLinhas(string $conteudoCsv): array
    {
        $linhas = preg_split('/\r\n|\r|\n/', trim($conteudoCsv));
        if (empty($linhas)) {
            return [];
        }

        // Detecta o delimitador na primeira linha
        $primeiraLinha = $linhas[0];
        $delimitador = (substr_count($primeiraLinha, ';') > substr_count($primeiraLinha, ',')) ? ';' : ',';

        $cabecalhoRaw = str_getcsv(array_shift($linhas), $delimitador);
        $mapColunas = [
            'codigo' => null,
            'quantidade' => null,
            'descricao' => null,
            'motivo' => null,
            'data' => null,
            'registrado_por' => null
        ];

        foreach ($cabecalhoRaw as $idx => $nomeCol) {
            $norm = strtolower(trim((string)$nomeCol));
            // Remove aspas ou caracteres especiais
            $norm = preg_replace('/[^a-z0-9_]/', '', (string)iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $norm));

            if (in_array($norm, ['codigo', 'cod', 'sku', 'produto_codigo'])) {
                $mapColunas['codigo'] = $idx;
            } elseif (in_array($norm, ['quantidade', 'qtd', 'quant', 'unidades'])) {
                $mapColunas['quantidade'] = $idx;
            } elseif (in_array($norm, ['descricao', 'desc', 'produto', 'nome_produto'])) {
                $mapColunas['descricao'] = $idx;
            } elseif ($norm === 'motivo') {
                $mapColunas['motivo'] = $idx;
            } elseif ($mapColunas['motivo'] === null && in_array($norm, ['motivoquebra', 'avaria', 'tipo_avaria', 'tipo'])) {
                $mapColunas['motivo'] = $idx;
            } elseif (in_array($norm, ['data', 'datahora', 'created_at', 'data_registro'])) {
                $mapColunas['data'] = $idx;
            } elseif (in_array($norm, ['registradopor', 'usuario', 'operador', 'ajudante'])) {
                $mapColunas['registrado_por'] = $idx;
            }
        }

        // Se não identificou 'codigo' ou 'quantidade', aborta
        if ($mapColunas['codigo'] === null || $mapColunas['quantidade'] === null) {
            throw new InvalidArgumentException("O relatório CSV deve conter ao menos as colunas de 'Código' (SKU) e 'Quantidade'.");
        }

        $registros = [];
        foreach ($linhas as $numLinha => $linhaTexto) {
            if (trim($linhaTexto) === '') {
                continue;
            }

            $colunas = str_getcsv($linhaTexto, $delimitador);
            $codigo = trim((string)($colunas[$mapColunas['codigo']] ?? ''));
            $qtd = (int)preg_replace('/[^0-9]/', '', (string)($colunas[$mapColunas['quantidade']] ?? '0'));
            $descricao = $mapColunas['descricao'] !== null ? trim((string)($colunas[$mapColunas['descricao']] ?? '')) : '';
            $motivo = $mapColunas['motivo'] !== null ? trim((string)($colunas[$mapColunas['motivo']] ?? '')) : 'Amassada';
            $data = $mapColunas['data'] !== null ? trim((string)($colunas[$mapColunas['data']] ?? '')) : '';
            $registradoPor = $mapColunas['registrado_por'] !== null ? trim((string)($colunas[$mapColunas['registrado_por']] ?? '')) : '';

            // Se motivo vier com traço ou vazio, busca na coluna de apoio ou usa Amassada
            if ($motivo === '' || $motivo === '-') {
                $motivo = 'Amassada';
            }

            if ($codigo === '' || $qtd <= 0) {
                continue;
            }

            $registros[] = [
                'linha' => $numLinha + 2,
                'codigo' => $codigo,
                'quantidade' => $qtd,
                'descricao' => $descricao,
                'motivo' => $motivo,
                'data' => $data,
                'registrado_por' => $registradoPor
            ];
        }

        return $registros;
    }

    /**
     * Analisa o CSV e gera a prévia consolidada antes de efetivar no banco de dados
     * @param string $conteudoCsv
     * @param array $destinosCustomizados [motivo => 'lojinha'|'descarte']
     * @return array
     */
    public static function analisarPreviaCsv(string $conteudoCsv, array $destinosCustomizados = []): array
    {
        $linhas = self::parseCsvLinhas($conteudoCsv);
        if (empty($linhas)) {
            throw new InvalidArgumentException("Nenhum registro de avaria válido foi encontrado no arquivo.");
        }

        $pdo = Database::getConnection();

        // Mapeia todos os SKUs do arquivo
        $skus = array_unique(array_column($linhas, 'codigo'));
        $produtosMap = [];

        if (!empty($skus)) {
            $placeholders = implode(',', array_fill(0, count($skus), '?'));
            $stmt = $pdo->prepare("
                SELECT p.id, p.codigo, p.nome, p.pacote_padrao,
                       COALESCE(el.quantidade_total, 0) AS estoque_atual
                FROM produtos p
                LEFT JOIN estoque_lojinha el ON el.produto_id = p.id
                WHERE p.codigo IN ($placeholders)
            ");
            $stmt->execute(array_values($skus));
            while ($p = $stmt->fetch()) {
                $produtosMap[(string)$p['codigo']] = $p;
            }
        }

        $motivosResumo = [];
        $lojinhaAgrupado = [];
        $descarteAgrupado = [];
        $totalLojinha = 0;
        $totalDescarte = 0;
        $produtosNovos = [];

        foreach ($linhas as $r) {
            $cod = $r['codigo'];
            $qtd = $r['quantidade'];
            $motivo = $r['motivo'];

            $destinoForcado = $destinosCustomizados[$motivo] ?? null;
            $class = self::classificarMotivoExterno($motivo, $destinoForcado);
            $destino = $class['destino'];

            // Contabiliza motivos
            if (!isset($motivosResumo[$motivo])) {
                $motivosResumo[$motivo] = [
                    'motivo' => $motivo,
                    'destino' => $destino,
                    'total_unidades' => 0,
                    'total_ocorrencias' => 0,
                    'chave_normalizada' => $class['chave']
                ];
            }
            $motivosResumo[$motivo]['total_unidades'] += $qtd;
            $motivosResumo[$motivo]['total_ocorrencias']++;

            $produtoInfo = $produtosMap[$cod] ?? null;
            $nomeProd = $produtoInfo ? $produtoInfo['nome'] : ($r['descricao'] ?: "SKU {$cod}");

            if (!$produtoInfo && !isset($produtosNovos[$cod])) {
                $produtosNovos[$cod] = [
                    'codigo' => $cod,
                    'nome' => $nomeProd,
                    'quantidade_total' => 0
                ];
            }
            if (!$produtoInfo) {
                $produtosNovos[$cod]['quantidade_total'] += $qtd;
            }

            if ($destino === 'lojinha') {
                $totalLojinha += $qtd;
                if (!isset($lojinhaAgrupado[$cod])) {
                    $lojinhaAgrupado[$cod] = [
                        'codigo' => $cod,
                        'nome' => $nomeProd,
                        'quantidade' => 0,
                        'estoque_atual' => $produtoInfo ? (int)$produtoInfo['estoque_atual'] : 0,
                        'novo_estoque' => $produtoInfo ? (int)$produtoInfo['estoque_atual'] : 0
                    ];
                }
                $lojinhaAgrupado[$cod]['quantidade'] += $qtd;
                $lojinhaAgrupado[$cod]['novo_estoque'] += $qtd;
            } else {
                $totalDescarte += $qtd;
                if (!isset($descarteAgrupado[$cod])) {
                    $descarteAgrupado[$cod] = [
                        'codigo' => $cod,
                        'nome' => $nomeProd,
                        'quantidade' => 0
                    ];
                }
                $descarteAgrupado[$cod]['quantidade'] += $qtd;
            }
        }

        // Ordena por maior volume
        uasort($lojinhaAgrupado, fn($a, $b) => $b['quantidade'] <=> $a['quantidade']);
        uasort($descarteAgrupado, fn($a, $b) => $b['quantidade'] <=> $a['quantidade']);

        return [
            'status' => 'success',
            'hash_conteudo' => hash('sha256', $conteudoCsv),
            'total_linhas' => count($linhas),
            'total_unidades' => $totalLojinha + $totalDescarte,
            'unidades_lojinha' => $totalLojinha,
            'produtos_lojinha_distintos' => count($lojinhaAgrupado),
            'unidades_descarte' => $totalDescarte,
            'produtos_descarte_distintos' => count($descarteAgrupado),
            'motivos' => array_values($motivosResumo),
            'produtos_lojinha' => array_values($lojinhaAgrupado),
            'produtos_descarte' => array_values($descarteAgrupado),
            'produtos_novos' => array_values($produtosNovos)
        ];
    }

    /**
     * Processa e efetiva a importação definitiva do CSV no banco de dados
     * @param string $conteudoCsv
     * @param int $usuarioGestorId
     * @param string $nomeArquivo
     * @param array $destinosCustomizados
     * @param bool $forcarReimportacao
     * @return array
     */
    public static function processarImportacaoCsv(
        string $conteudoCsv,
        int $usuarioGestorId,
        string $nomeArquivo = 'relatorio_bo.csv',
        array $destinosCustomizados = [],
        bool $forcarReimportacao = false
    ): array {
        $linhas = self::parseCsvLinhas($conteudoCsv);
        if (empty($linhas)) {
            throw new InvalidArgumentException("O relatório CSV não possui registros válidos.");
        }

        $hash = hash('sha256', $conteudoCsv);
        $pdo = Database::getConnection();

        // Prevenção de duplicidade: verifica se o mesmo arquivo foi importado
        if (!$forcarReimportacao) {
            $stmtCheca = $pdo->prepare("
                SELECT id, nome_arquivo, created_at 
                FROM importacoes_csv_log 
                WHERE hash_conteudo = :hash 
                LIMIT 1
            ");
            $stmtCheca->execute(['hash' => $hash]);
            $jaImportado = $stmtCheca->fetch();
            if ($jaImportado) {
                $dtFormatada = date('d/m/Y \à\s H:i', strtotime($jaImportado['created_at']));
                throw new DomainException("Este arquivo CSV já foi importado anteriormente em {$dtFormatada} ({$jaImportado['nome_arquivo']}). Se desejar importar novamente, marque a opção 'Forçar reimportação'.");
            }
        }

        $pdo->beginTransaction();

        try {
            // Mapeia produtos existentes
            $skus = array_unique(array_column($linhas, 'codigo'));
            $produtosMap = [];

            if (!empty($skus)) {
                $placeholders = implode(',', array_fill(0, count($skus), '?'));
                $stmt = $pdo->prepare("SELECT id, codigo, nome FROM produtos WHERE codigo IN ($placeholders)");
                $stmt->execute(array_values($skus));
                while ($p = $stmt->fetch()) {
                    $produtosMap[(string)$p['codigo']] = $p;
                }
            }

            $totalLojinha = 0;
            $totalDescarte = 0;
            $boIds = [];

            // Prepara inserts
            $stmtInsertProd = $pdo->prepare("
                INSERT INTO produtos (codigo, nome, pacote_padrao, ativo, created_at, updated_at)
                VALUES (:codigo, :nome, 'Unidade', 1, NOW(), NOW())
            ");

            $stmtInsertBO = $pdo->prepare("
                INSERT INTO apontamentos_bo (
                    produto_id, usuario_id, tipo_avaria, motivo_original, quantidade,
                    destino, origem_sincronizacao, motivo_correcao, arquivo_origem, created_at
                ) VALUES (
                    :produto_id, :usuario_id, :tipo_avaria, :motivo_original, :quantidade,
                    :destino, 'online', :motivo_correcao, :arquivo_origem, :created_at
                )
            ");

            foreach ($linhas as $r) {
                $cod = $r['codigo'];
                $qtd = $r['quantidade'];
                $motivo = $r['motivo'];
                $descricao = $r['descricao'] ?: "Produto SKU {$cod}";
                $dataRegistro = !empty($r['data']) ? date('Y-m-d H:i:s', strtotime($r['data'])) : date('Y-m-d H:i:s');

                // Se produto não existir, cadastra sob demanda
                if (!isset($produtosMap[$cod])) {
                    $stmtInsertProd->execute([
                        'codigo' => $cod,
                        'nome' => $descricao
                    ]);
                    $novoId = (int)$pdo->lastInsertId();
                    $produtosMap[$cod] = [
                        'id' => $novoId,
                        'codigo' => $cod,
                        'nome' => $descricao
                    ];

                    // Inicializa estoque lojinha zerado
                    $pdo->prepare("INSERT IGNORE INTO estoque_lojinha (produto_id, quantidade_total, quantidade_reservada_gestao) VALUES (:pid, 0, 0)")
                        ->execute(['pid' => $novoId]);
                }

                $produtoId = (int)$produtosMap[$cod]['id'];

                $destForcado = $destinosCustomizados[$motivo] ?? null;
                $class = self::classificarMotivoExterno($motivo, $destForcado);
                $destino = $class['destino'];

                $stmtInsertBO->execute([
                    'produto_id' => $produtoId,
                    'usuario_id' => $usuarioGestorId,
                    'tipo_avaria' => $class['chave'],
                    'motivo_original' => $motivo,
                    'quantidade' => $qtd,
                    'destino' => $destino,
                    'motivo_correcao' => "Importado via CSV: " . $nomeArquivo . ($r['registrado_por'] ? " (Registrado por: {$r['registrado_por']})" : ''),
                    'arquivo_origem' => $nomeArquivo,
                    'created_at' => $dataRegistro
                ]);
                $boIds[] = (int)$pdo->lastInsertId();

                if ($destino === 'lojinha') {
                    $totalLojinha += $qtd;
                    EstoqueLojinha::incrementarSaldo($produtoId, $qtd);
                } else {
                    $totalDescarte += $qtd;
                }
            }

            // Registra no histórico de importações
            $stmtLog = $pdo->prepare("
                INSERT INTO importacoes_csv_log (
                    nome_arquivo, hash_conteudo, total_linhas, unidades_lojinha,
                    unidades_descarte, usuario_id, detalhes_json, created_at
                ) VALUES (
                    :nome_arquivo, :hash_conteudo, :total_linhas, :unidades_lojinha,
                    :unidades_descarte, :usuario_id, :detalhes_json, NOW()
                )
            ");
            $stmtLog->execute([
                'nome_arquivo' => $nomeArquivo,
                'hash_conteudo' => $hash,
                'total_linhas' => count($linhas),
                'unidades_lojinha' => $totalLojinha,
                'unidades_descarte' => $totalDescarte,
                'usuario_id' => $usuarioGestorId,
                'detalhes_json' => json_encode([
                    'total_bo_criados' => count($boIds),
                    'motivos_customizados' => $destinosCustomizados
                ], JSON_UNESCAPED_UNICODE)
            ]);

            $pdo->commit();

            return [
                'status' => 'success',
                'total_linhas' => count($linhas),
                'unidades_lojinha' => $totalLojinha,
                'unidades_descarte' => $totalDescarte,
                'bo_criados' => count($boIds),
                'message' => "Importação concluída com sucesso! {$totalLojinha} unidades adicionadas ao estoque da Lojinha e {$totalDescarte} unidades computadas para Descarte."
            ];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
