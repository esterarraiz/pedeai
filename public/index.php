<?php

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
//    O Controller, por sua vez, cuidará de iniciar a sessão.
$router->dispatch();
