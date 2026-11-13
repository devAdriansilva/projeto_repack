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
            $candidateDbs = array_unique([
                DB_NAME,
                'financas-facil-atelie_repack',
                'financas-facil-atelie_repack_db',
                'financas-facil-atelie_projeto_repack',
                'financas-facil-atelie_db',
                'repack_db'
            ]);

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '-03:00'"
            ];

            $lastException = null;
            foreach ($candidateDbs as $db) {
                try {
                    $dsn = sprintf(
                        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                        DB_HOST,
                        DB_PORT,
                        $db,
                        DB_CHARSET
                    );
                    self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                    // Garante explicitamente o fuso horário de Brasília (UTC-3) na sessão MySQL
                    self::$instance->exec("SET time_zone = '-03:00'");
                    break;
                } catch (PDOException $e) {
                    $lastException = $e;
                    // Se o erro for de base desconhecida (código 1049), tenta a próxima candidata
                    if (strpos($e->getMessage(), 'Unknown database') !== false || $e->getCode() == 1049) {
                        continue;
                    }
                    break;
                }
            }

            if (self::$instance === null && $lastException !== null) {
                self::logFileFallback($lastException->getMessage(), 'DATABASE_CONNECTION_ERROR');
                throw $lastException;
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
