<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
if (is_file(APP_ROOT . '/.env') && is_readable(APP_ROOT . '/.env')) {
    $environment = parse_ini_file(APP_ROOT . '/.env', false, INI_SCANNER_RAW);
    if ($environment === false) {
        throw new RuntimeException('The .env configuration file could not be read.');
    }
    foreach ($environment as $key => $value) {
        if (getenv($key) === false && is_string($value)) {
            putenv($key . '=' . trim($value, "\"'"));
        }
    }
}
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Africa/Nairobi');
define('APP_NAME', getenv('APP_NAME') ?: 'Yucca Fabrics');
$configuredBase = getenv('APP_BASE_URL');
if ($configuredBase === false) {
    $documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $applicationRoot = realpath(APP_ROOT);
    $relativeRoot = ($documentRoot && $applicationRoot) ? str_replace('\\', '/', substr($applicationRoot, strlen($documentRoot))) : '';
    $configuredBase = '/' . trim($relativeRoot, '/');
}
define('BASE_URL', rtrim($configuredBase, '/'));
define('APP_SITE_URL', rtrim(getenv('APP_SITE_URL') ?: '', '/'));
define('CURRENCY', getenv('APP_CURRENCY') ?: 'KES');
define('TAX_RATE', (float) (getenv('APP_TAX_RATE') ?: '0.16'));
define('DELIVERY_FEE', (float) (getenv('APP_DELIVERY_FEE') ?: '350'));

if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $https,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

if (!isset($_SESSION['created_at'])) {
    $_SESSION['created_at'] = time();
} elseif (time() - (int) $_SESSION['created_at'] > 7200) {
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['created_at'] = time();
}
