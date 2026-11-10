<?php
declare(strict_types=1);

/**
 * Projeto Repack - Singleton de Conexão com Banco de Dados PDO
 * Baseado estritamente em docs/FSD.md (Seção 5, 19 e 24) e AGENTS.md
 */

require_once __DIR__ . '/config.php';

class Database
{
    private static ?PDO $instance = null;

    /**
     * Construtor privado para impedir instanciação direta (Padrão Singleton)
     */
    private function __construct()
    {
    }

    /**
     * Prevenção de clonagem da instância
     */
    private function __clone()
    {
    }

    /**
     * Prevenção de desserialização da instância
     */
    public function __wakeup()
    {
        throw new \Exception("Não é permitido desserializar a classe Database.");
    }

    /**
     * Retorna a instância única de conexão PDO
     *
     * @return PDO
     * @throws PDOException
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                self::logFileFallback($e->getMessage(), 'DATABASE_CONNECTION_ERROR');
                throw $e;
            }
        }

        return self::$instance;
    }

    /**
     * Gravação em arquivo de log de fallback caso o banco falhe
     * Conforme docs/FSD.md Seção 19.2 e AGENTS.md
     */
    public static function logFileFallback(string $errorMessage, string $level = 'ERROR'): void
    {
        $logFile = LOGS_PATH . '/app_errors.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI/LOCAL';
        $formattedMessage = sprintf("[%s] [%s] [IP: %s] %s%s", $timestamp, $level, $ip, $errorMessage, PHP_EOL);

        @file_put_contents($logFile, $formattedMessage, FILE_APPEND | LOCK_EX);
    }
}
