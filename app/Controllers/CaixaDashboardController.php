<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Mesa;
use App\Models\PedidoModel;
use Config\Database;

class CaixaDashboardController extends Controller
{
    public function index()
    {
        $pdo = Database::getConnection();
        $mesaModel = new Mesa($pdo);
        $empresa_id = $_SESSION['empresa_id'] ?? null;

        $mesas = $mesaModel->buscarTodasPorEmpresa($empresa_id);

        $this->loadView('caixa/mesas', [
            'mesas' => $mesas
        ]);
    }

    public function verConta($params)
    {
        $mesa_id = $params['id'];
        $empresa_id = $_SESSION['empresa_id'] ?? null;

        $pdo = Database::getConnection();
        $mesaModel = new Mesa($pdo);
        $mesa = $mesaModel->buscarPorId($mesa_id);

        $pedidoModel = new PedidoModel($pdo);
        $ultimo_pedido = $pedidoModel->buscarItensDoUltimoPedidoDaMesa($mesa_id, $empresa_id);

        $this->loadView('caixa/mesa', [
            'mesa' => $mesa,
            'pedido' => $ultimo_pedido
        ]);
    }

    public function fecharConta()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('HTTP/1.0 405 Method Not Allowed');
            echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
            exit;
        }

        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        $mesa_id = $data['mesa_id'] ?? null;

        if (!$mesa_id) {
            echo json_encode(['success' => false, 'message' => 'Mesa inválida.']);
            return;
        }

        $pdo = Database::getConnection();
        $mesaModel = new Mesa($pdo);

        $mesaModel->atualizarStatus($mesa_id, 'Livre');

        echo json_encode(['success' => true, 'message' => 'Conta fechada e mesa liberada!']);
    }
}
