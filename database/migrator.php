<?php
declare(strict_types=1);

/**
 * Projeto Repack - Script CLI de Execução Idempotente de Migrations
 * Baseado estritamente em docs/FSD.md (Seção 11.1 e 26) e AGENTS.md
 */

// Bloqueio rigoroso contra requisições web (apenas CLI permitido)
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Acesso negado. Este script deve ser executado exclusivamente via CLI.\n";
    exit(1);
}

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';

echo "====================================================\n";
echo " PROJETO REPACK - MIGRADOR DE BANCO DE DADOS (CLI) \n";
echo "====================================================\n\n";

try {
    // 1. Conexão inicial para garantia da existência do Database
    echo "[1/4] Verificando integridade da base de dados...\n";
    $dsnServer = sprintf('mysql:host=%s;port=%s;charset=%s', DB_HOST, DB_PORT, DB_CHARSET);
    $pdoServer = new PDO($dsnServer, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdoServer->exec(sprintf(
        "CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET %s COLLATE utf8mb4_unicode_ci",
        DB_NAME,
        DB_CHARSET
    ));
    echo " -> Base de dados '" . DB_NAME . "' garantida com sucesso.\n\n";

    // 2. Conexão direta com a base de dados via Database Singleton
    echo "[2/4] Conectando à base de dados via Singleton PDO...\n";
    $pdo = Database::getConnection();
    echo " -> Conexão estabelecida com sucesso.\n\n";

    // 3. Garantir a tabela de controle de migrations
    echo "[3/4] Verificando tabela de controle 'migrations_log'...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS migrations_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(150) NOT NULL UNIQUE,
            executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo " -> Tabela 'migrations_log' pronta.\n\n";

    // 4. Mapear arquivos .sql no diretório de migrations
    echo "[4/4] Processando arquivos de migration...\n";
    $migrationsDir = __DIR__ . '/migrations';
    $files = glob($migrationsDir . '/*.sql');

    if (empty($files)) {
        echo " -> Nenhum arquivo .sql encontrado em database/migrations/.\n";
        exit(0);
    }

    sort($files); // Garante a ordem de execução (001, 002, ...)

    // Consulta migrations já executadas
    $stmt = $pdo->query("SELECT migration_name FROM migrations_log");
    $executedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $executedCount = 0;
    $skippedCount = 0;

    foreach ($files as $file) {
        $migrationName = basename($file);

        if (in_array($migrationName, $executedMigrations, true)) {
            echo " [PULADA]    " . $migrationName . " (já executada anteriormente)\n";
            $skippedCount++;
            continue;
        }

        echo " [APLICANDO] " . $migrationName . " ... ";

        $sqlContent = file_get_contents($file);
        if ($sqlContent === false || trim($sqlContent) === '') {
            echo "FALHA: Arquivo vazio ou ilegível.\n";
            exit(1);
        }

        // Execução do conteúdo SQL da migration
        try {
            $pdo->exec($sqlContent);

            $stmtLog = $pdo->prepare("INSERT INTO migrations_log (migration_name, executed_at) VALUES (:name, NOW())");
            $stmtLog->execute([':name' => $migrationName]);

            echo "OK (Sucesso)\n";
            $executedCount++;
        } catch (\Throwable $migrationError) {
            echo "ERRO!\n\n";
            throw new \RuntimeException(
                "Falha ao executar a migration '{$migrationName}': " . $migrationError->getMessage(),
                0,
                $migrationError
            );
        }
    }

    echo "\n----------------------------------------------------\n";
    echo "Resumo: {$executedCount} executada(s), {$skippedCount} já aplicada(s).\n";
    echo "Migração concluída com sucesso absoluto!\n";
    echo "----------------------------------------------------\n";
    exit(0);

} catch (\Throwable $e) {
    echo "\n[ERRO CRÍTICO NA MIGRAÇÃO]\n";
    echo $e->getMessage() . "\n";
    Database::logFileFallback("Erro na execução do migrator.php: " . $e->getMessage(), 'MIGRATION_ERROR');
    exit(1);
}
