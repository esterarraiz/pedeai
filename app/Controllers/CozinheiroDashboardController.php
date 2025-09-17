<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\PedidoModel;
use Config\Database;

class CozinheiroDashboardController extends Controller
{

    public function index()
    {

        $pdo = Database::getConnection();
        

        $pedidoModel = new PedidoModel($pdo);


        $empresa_id = $_SESSION['empresa_id'] ?? 0;
        $pedidos = $pedidoModel->buscarPedidosParaCozinha($empresa_id);

        $this->loadView('cozinha/cozinhaview', [
            'pedidos' => $pedidos
        ]);
    }
}