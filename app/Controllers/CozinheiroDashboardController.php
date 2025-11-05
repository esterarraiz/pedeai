<?php
// Ficheiro: app/Controllers/CozinheiroDashboardController.php (Versão Limpa)

namespace App\Controllers;

use App\Core\Controller;
use App\Models\PedidoModel;
use Config\Database;

class CozinheiroDashboardController extends Controller
{
    /**
     * Carrega a view principal da cozinha com os pedidos pendentes.
     * A lógica da API foi movida para PedidoController.
     */
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

    // O método 'marcarPronto()' foi removido daqui e movido para
    // App\Controllers\Api\PedidoController, que é o lugar correto.
}
