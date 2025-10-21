<?php
// Ficheiro: public/index.php (Versão Final e Corrigida com Gestão de Sessão Centralizada)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- GESTÃO DE SESSÃO CENTRALIZADA ---

// 1. Define um caminho seguro para guardar as sessões dentro do projeto
$sessionPath = dirname(__DIR__) . '/sessions';

// 2. Cria a pasta se ela não existir
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}

// 3. Diz ao PHP para usar esta pasta para guardar as sessões
session_save_path($sessionPath);

// 4. Inicia a sessão com o cookie seguro
// Define o cookie de sessão para ser válido em todo o site ('/'),
// e para ser 'httponly' (mais seguro contra ataques XSS).
session_start([
    'cookie_lifetime' => 86400, // 1 dia
    'cookie_path' => '/',
    'cookie_httponly' => true
]);
// --- FIM DA GESTÃO DE SESSÃO ---

// Carrega o autoloader do Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Carrega as variáveis de ambiente do ficheiro .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Carrega e executa o resto da aplicação
use App\Core\Router;
$router = new Router();
$router->dispatch();