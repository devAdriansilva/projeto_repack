<?php
declare(strict_types=1);

/**
 * Projeto Repack - Model Configuracao
 * Persistência e leitura de configurações e parâmetros globais do sistema
 * Baseado estritamente em docs/FSD.md e AGENTS.md
 */

require_once __DIR__ . '/../config/database.php';

class Configuracao
{
    /**
     * Obtém o valor de uma chave de configuração
     * @param string $chave
     * @param mixed $padrao
     * @return mixed
     */
    public static function obter(string $chave, mixed $padrao = null): mixed
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT valor FROM configuracoes_sistema WHERE chave = :chave LIMIT 1");
            $stmt->execute(['chave' => $chave]);
            $row = $stmt->fetch();
            return $row ? $row['valor'] : $padrao;
        } catch (\Throwable $e) {
            return $padrao;
        }
    }

    /**
     * Salva ou atualiza uma chave de configuração
     * @param string $chave
     * @param string $valor
     * @param string|null $descricao
     * @return bool
     */
    public static function salvar(string $chave, string $valor, ?string $descricao = null): bool
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO configuracoes_sistema (chave, valor, descricao, updated_at)
                VALUES (:chave, :valor, :descricao, NOW())
                ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW()
            ");
            return $stmt->execute([
                'chave' => $chave,
                'valor' => $valor,
                'descricao' => $descricao
            ]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Obtém os dados completos do operador padrão configurado
     * @return array|null
     */
    public static function obterOperadorPadrao(): ?array
    {
        require_once __DIR__ . '/Usuario.php';
        $operadorId = self::obter('operador_padrao_id');
        if ($operadorId) {
            $op = Usuario::buscarPorId((int)$operadorId);
            if ($op && (int)$op['ativo'] === 1 && $op['perfil'] === 'operador') {
                return $op;
            }
        }
        $operadores = Usuario::listarOperadoresAtivos();
        return $operadores[0] ?? null;
    }
}
