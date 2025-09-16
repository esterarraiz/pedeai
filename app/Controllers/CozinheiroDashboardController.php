<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\PedidoModel;
use Config\Database;

class CozinheiroDashboardController extends Controller
{
    /**
     * Carrega a view do painel da cozinha com os dados dos pedidos.
     */
    public function index()
    {
        // 1. Obter a conexão com o banco
        $pdo = Database::getConnection();
        
        // 2. Instanciar o Model
        $pedidoModel = new PedidoModel($pdo);

        // 3. Buscar os pedidos para a cozinha
        $empresa_id = $_SESSION['empresa_id'] ?? 0;
        $pedidos = $pedidoModel->buscarPedidosParaCozinha($empresa_id);

        // 4. Carregar a View e passar os dados dos pedidos diretamente para ela
        // Vamos chamar a view de 'cozinha/index' para manter a organização
        $this->loadView('cozinha/cozinhaview', [
            'pedidos' => $pedidos
        ]);
    }
}