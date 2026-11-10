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
}
