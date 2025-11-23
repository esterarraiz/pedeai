<?php
// Ficheiro: public/index.php (Versão Definitiva)

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    $sessionPath = dirname(__DIR__) . '/sessions';
    if (!is_dir($sessionPath)) { mkdir($sessionPath, 0777, true); }
    session_save_path($sessionPath);
    session_start(['cookie_httponly' => true]);
}

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar .env APENAS em ambiente local (quando o arquivo existe)
$envPath = __DIR__ . '/../.env';

if (file_exists($envPath)) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}
// Em produção (Render), as variáveis vêm do painel "Environment"

use App\Core\Router;

$router = new Router();
$router->dispatch();
