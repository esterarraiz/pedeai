<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Mesa;
use App\Models\PedidoModel;
use Config\Database;

class MesaController extends Controller
{


    public function index()
    {

        $pdo = Database::getConnection();


        $mesaModel = new Mesa($pdo);


        $empresa_id = $_SESSION['empresa_id'] ?? null;
        $mesas = $mesaModel->buscarTodasPorEmpresa($empresa_id);



         $this->loadView('mesaview', [
         'mesas' => $mesas
            ]);
    }
    /**
 * Recebe uma requisição POST para liberar uma mesa (mudar status para 'Livre').
 */
  public function liberarMesa()
    {
        // Garante que a requisição seja POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('HTTP/1.0 405 Method Not Allowed');
            echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
            exit;
        }

        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        $mesaId = $data['mesa_id'] ?? null;

        if (!$mesaId || !is_numeric($mesaId)) {
            echo json_encode(['success' => false, 'message' => 'ID da mesa inválido.']);
            return;
        }

        $mesaModel = new Mesa();
        $sucesso = $mesaModel->atualizarStatus((int)$mesaId, 'Livre');

        if ($sucesso) {
            echo json_encode(['success' => true, 'message' => 'Mesa liberada com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Falha ao atualizar o status da mesa.']);
        }
    }
    public function showDetalhesMesa($params)
    {
        // ...
        $mesa_id = $params['id'];
        $empresa_id = $_SESSION['empresa_id'];

        $pdo = \Config\Database::getConnection();
        
        $mesaModel = new \App\Models\Mesa($pdo);
        $mesa = $mesaModel->buscarPorId($mesa_id);

        $pedidoModel = new \App\Models\PedidoModel($pdo);
        // CHAMANDO O NOVO MÉTODO
        $ultimo_pedido = $pedidoModel->buscarItensDoUltimoPedidoDaMesa($mesa_id, $empresa_id);

        // ENVIANDO PARA A VIEW com a chave 'pedido' (singular)
        $this->loadView('mesas/detalhes', [
            'mesa' => $mesa,
            'pedido' => $ultimo_pedido
        ]);
    }
}