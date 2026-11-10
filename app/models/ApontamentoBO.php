<?php
declare(strict_types=1);

/**
 * Projeto Repack - Model ApontamentoBO
 * Baseado estritamente em docs/FSD.md (Seções 6, 11, 13.1 e 14)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/EstoqueLojinha.php';

class ApontamentoBO
{
    public const TIPOS_LOJINHA = ['amassada', 'def_rotulo', 'emba_danificada'];
    public const TIPOS_DESCARTE = ['furo', 'micro_furo'];

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
}
