<?php
/**
 * Arquivo de configuração do sistema
 */

// ============================================
// AMBIENTE
// ============================================

define('ENVIRONMENT', 'production');

if (ENVIRONMENT === 'production') {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../error.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// ============================================
// SESSÃO SEGURA
// ============================================

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');

if (
    isset($_SERVER['HTTPS']) &&
    ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == '1')
) {
    ini_set('session.cookie_secure', '1');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// BANCO DE DADOS
// ============================================

define('DB_DRIVER', 'sqlite');

// Banco de Dados Local
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'west_news');

// Banco de Dados Produção
// define('DB_HOST', 'localhost');
// define('DB_USER', 'por46724_westfm');
// define('DB_PASS', 'Postgresql2026@');
// define('DB_NAME', 'por46724_noticias');

// ============================================
// SUPABASE
// ============================================

define('SUPABASE_HOST', 'aws-1-us-west-2.pooler.supabase.com');
define('SUPABASE_PORT', '5432');
define('SUPABASE_USER', 'postgres.icahhtaellukrdubmrnt');
define('SUPABASE_PASS', '2pGuf8nz&e$Xq7K');
define('SUPABASE_NAME', 'postgres');

define('FORCE_SUPABASE', false);

// ============================================
// URL BASE AUTOMÁTICA
// FUNCIONA EM QUALQUER DOMÍNIO
// ============================================

$https = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ($_SERVER['SERVER_PORT'] ?? 80) == 443
);

$protocol = $https ? 'https://' : 'http://';

$host = $_SERVER['HTTP_HOST'] ?? '96news.com.br';

// Remove WWW
$host = preg_replace('/^www\./i', '', $host);

define('BASE_URL', $protocol . $host);

// ============================================
// INFORMAÇÕES DO SITE
// ============================================

define('SITE_NAME', '96NEWS');
define('SITE_DESC', 'Portal de Notícias');

// ============================================
// TIMEZONE
// ============================================

date_default_timezone_set('America/Campo_Grande');

// ============================================
// UPLOADS
// ============================================

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);

define('ALLOWED_EXTENSIONS', [
    'jpg',
    'jpeg',
    'png',
    'webp'
]);

// ============================================
// CONFIGURAÇÕES EXTRAS
// ============================================

define('ITEMS_PER_PAGE', 15);
define('CACHE_TIME', 300);
