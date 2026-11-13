<?php
declare(strict_types=1);

/**
 * Projeto Repack - Configurações Globais do Sistema
 * Baseado estritamente em docs/FSD.md (Seção 20) e AGENTS.md
 */

// Fuso horário padrão da operação
date_default_timezone_set('America/Sao_Paulo');

// Definição dos caminhos absolutos do sistema
define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('LOGS_PATH', ROOT_PATH . '/logs');
define('PATH_UPLOADS', PUBLIC_PATH . '/uploads/produtos');

// Configurações de Conexão com o Banco de Dados MySQL (suporte a Alwaysdata e desenvolvimento local)
if (strpos(__DIR__, 'financas-facil-atelie') !== false) {
    define('DB_HOST', getenv('DB_HOST') ?: 'mysql-financas-facil-atelie.alwaysdata.net');
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
    define('DB_NAME', getenv('DB_NAME') ?: 'financas-facil-atelie_repack');
    define('DB_USER', getenv('DB_USER') ?: 'financas-facil-atelie');
    define('DB_PASS', getenv('DB_PASS') ?: '9891601018');
} else {
    define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
    define('DB_NAME', getenv('DB_NAME') ?: 'repack_db');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
}
define('DB_CHARSET', 'utf8mb4');

// Configurações da Aplicação e Regras de Negócio
define('APP_NAME', 'Projeto Repack');
define('APP_VERSION', 'v1.60');
define('APP_ENV', getenv('APP_ENV') ?: 'development'); // 'development' ou 'production'

// Auto-detecção inteligente de APP_URL (suporta raiz '/', Alwaysdata, localhost e subpastas)
if (!defined('APP_URL')) {
    $envUrl = getenv('APP_URL');
    if ($envUrl !== false && $envUrl !== '') {
        define('APP_URL', rtrim($envUrl, '/'));
    } elseif (php_sapi_name() === 'cli') {
        define('APP_URL', '');
    } else {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir = dirname($scriptName);
        $baseDir = preg_replace('#/public$#', '', $scriptDir);
        $baseDir = ($baseDir === '/' || $baseDir === '\\' || $baseDir === '.') ? '' : rtrim($baseDir, '/');
        define('APP_URL', $baseDir);
    }
}
define('CARRINHO_TIMEOUT_MINUTOS', 5);
define('GESTAO_SESSION_TIMEOUT_MINUTOS', 30);

// Limites e Parâmetros de Upload (docs/FSD.md Seção 21)
define('UPLOAD_MAX_SIZE_BYTES', 2 * 1024 * 1024); // 2 MB
define('UPLOAD_ALLOWED_MIME_TYPES', [
    'image/jpeg',
    'image/png',
    'image/webp'
]);

// Configurações de Segurança de Sessão PHP (docs/FSD.md Seção 15)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Strict');
    
    // Configura flag secure caso a conexão seja HTTPS
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }

    // Configura tempo limite de vida da sessão
    ini_set('session.gc_maxlifetime', (string)(GESTAO_SESSION_TIMEOUT_MINUTOS * 60));
}

/**
 * Função utilitária global para sanitização de saída HTML (XSS Protection)
 * Conforme docs/FSD.md Seção 24 e AGENTS.md
 */
function sanitize_html(?string $data): string
{
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}
