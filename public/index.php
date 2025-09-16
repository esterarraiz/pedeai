<?php

// ALTERAÇÃO 1: Adicionado código para exibir todos os erros durante o desenvolvimento.
// Isso transforma telas brancas em mensagens de erro úteis.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ALTERAÇÃO 2: Definição da constante BASE_PATH.
// Esta é a correção para o erro "Fatal error: Undefined constant".
// Ela garante que todos os links, CSS e imagens funcionem no subdiretório "/pedeai".
define('BASE_PATH', '/pedeai');

// ALTERAÇÃO 3: A função session_start() deve ser chamada no início do script,
// antes de qualquer outra coisa, para garantir que a superglobal $_SESSION esteja sempre disponível.
session_start();

// --- SEU CÓDIGO ORIGINAL (ORDEM CORRETA) ---

// 1. Carrega o autoloader do Composer para usar namespaces
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Carrega as variáveis de ambiente (DB_HOST, DB_NAME, etc.) do arquivo .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// 3. Importa a classe do Router
use App\Core\Router;

// 4. Instancia o router. O construtor do Router já define todas as rotas.
$router = new Router();

// 5. O método dispatch() encontra a rota correspondente à URL e executa a ação do controller.
$router->dispatch();