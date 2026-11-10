<?php
declare(strict_types=1);

/**
 * Projeto Repack - Model LogSistema
 * Baseado estritamente em docs/FSD.md (Seção 19) e AGENTS.md
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class LogSistema
{
    /**
     * Registra um evento no log do sistema (MySQL com fallback em arquivo)
     * @param string $nivel INFO, WARNING, ERROR ou SECURITY
     * @param string $mensagem
     * @param array|null $contexto
     * @return bool
     */
    public static function registrar(string $nivel, string $mensagem, ?array $contexto = null): bool
    {
        $nivel = strtoupper(trim($nivel));
        if (!in_array($nivel, ['INFO', 'WARNING', 'ERROR', 'SECURITY'], true)) {
            $nivel = 'INFO';
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $contextoJson = $contexto ? json_encode($contexto, JSON_UNESCAPED_UNICODE) : null;

        // Se for evento de segurança, registra imediatamente no arquivo security.log
        if ($nivel === 'SECURITY') {
            self::gravarArquivoFallback('SECURITY', $mensagem, $contexto, null);
        }

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO logs_sistema (nivel, mensagem, contexto_json, ip, created_at)
                VALUES (:nivel, :mensagem, :contexto_json, :ip, NOW())
            ");
            return $stmt->execute([
                'nivel' => $nivel,
                'mensagem' => $mensagem,
                'contexto_json' => $contextoJson,
                'ip' => $ip
            ]);
        } catch (\Throwable $e) {
            // Fallback imediato em arquivo em caso de falha no banco
            self::gravarArquivoFallback($nivel, $mensagem, $contexto, $e->getMessage());
            return false;
        }
    }

    public static function info(string $mensagem, ?array $contexto = null): bool
    {
        return self::registrar('INFO', $mensagem, $contexto);
    }

    public static function warning(string $mensagem, ?array $contexto = null): bool
    {
        return self::registrar('WARNING', $mensagem, $contexto);
    }

    public static function error(string $mensagem, ?array $contexto = null): bool
    {
        return self::registrar('ERROR', $mensagem, $contexto);
    }

    public static function security(string $mensagem, ?array $contexto = null): bool
    {
        return self::registrar('SECURITY', $mensagem, $contexto);
    }

    /**
     * Grava log em arquivo de contingência
     * @param string $nivel
     * @param string $mensagem
     * @param array|null $contexto
     * @param string|null $erroBanco
     */
    private static function gravarArquivoFallback(string $nivel, string $mensagem, ?array $contexto, ?string $erroBanco): void
    {
        $arquivo = ($nivel === 'SECURITY')
            ? LOGS_PATH . '/security.log'
            : LOGS_PATH . '/app_errors.log';

        $dataHora = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ctxStr = $contexto ? ' | Contexto: ' . json_encode($contexto, JSON_UNESCAPED_UNICODE) : '';
        $dbErrStr = $erroBanco ? ' | Falha DB: ' . $erroBanco : '';

        $linha = "[{$dataHora}] [{$nivel}] [IP: {$ip}] {$mensagem}{$ctxStr}{$dbErrStr}" . PHP_EOL;

        @file_put_contents($arquivo, $linha, FILE_APPEND | LOCK_EX);
    }
}
