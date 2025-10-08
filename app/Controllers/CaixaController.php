<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Mesa;
use App\Models\PedidoModel;
use Config\Database;

class CaixaController extends Controller
{
    public function index()
    {
        // Aqui você pode listar todas as mesas se quiser
        $pdo = Database::getConnection();
        $mesaModel = new Mesa($pdo);
        $empresa_id = $_SESSION['empresa_id'] ?? null;
        $mesas = $mesaModel->buscarTodasPorEmpresa($empresa_id);

        $this->loadView('caixa/index', ['mesas' => $mesas]);
    }

    public function detalhesMesa($params)
    {
        $mesa_id = $params['id'];
        $empresa_id = $_SESSION['empresa_id'];

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
}
