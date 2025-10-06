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

    /**
     * NOVO MÉTODO PARA MARCAR O PEDIDO COMO PRONTO (CHAMADO VIA AJAX)
     */
    public function marcarPronto()
    {
        // Define o cabeçalho da resposta como JSON para o JavaScript entender
        header('Content-Type: application/json');

        try {
            // Pega o corpo da requisição POST (que nosso JavaScript enviou como JSON)
            $json = file_get_contents('php://input');
            $data = json_decode($json);

            $pedido_id = $data->id ?? null;

            if (!$pedido_id) {
                throw new \Exception("ID do pedido não fornecido.");
            }

            $pdo = Database::getConnection();
            $pedidoModel = new PedidoModel($pdo);
            $empresa_id = $_SESSION['empresa_id'] ?? 0;

            // Chama o método no model que vamos criar no Passo 4
            $sucesso = $pedidoModel->marcarComoPronto((int)$pedido_id, $empresa_id);

            if ($sucesso) {
                // Se o model retornar sucesso, enviamos uma resposta positiva
                echo json_encode(['success' => true, 'message' => 'Pedido marcado como pronto!']);
            } else {
                // Se o model falhar (ex: pedido não existe), lançamos um erro
                throw new \Exception("Não foi possível atualizar o pedido. Verifique se ele já foi atualizado ou pertence a outra empresa.");
            }

        } catch (\Exception $e) {
            // Em caso de qualquer erro, retorna uma resposta com a mensagem de erro
            http_response_code(400); // Código de erro para "Bad Request"
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}