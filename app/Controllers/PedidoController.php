<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CardapioModel;
use App\Models\PedidoModel;
use Config\Database;

class PedidoController extends Controller
{
    /**
     * Exibe a página para lançar um novo pedido.
     */
    public function showFormNovoPedido($params)
    {
        $mesaId = $params['id'] ?? null;
        if (!$mesaId || !is_numeric($mesaId)) {
            header('Location: /dashboard/garcom?status=mesa_invalida');
            exit;
        }

        try {
            $pdo = Database::getConnection();
            $empresa_id = $_SESSION['empresa_id'] ?? null;

            if (!$empresa_id) {
                throw new \Exception("ID da empresa não encontrado na sessão.");
            }
            
            $cardapioModel = new CardapioModel($pdo);
            $itensCardapio = $cardapioModel->buscarItensAgrupados($empresa_id);

            $this->loadView('pedidos/novo', [
                'mesa_id'  => (int) $mesaId,
                'cardapio' => $itensCardapio
            ]);

        } catch (\Exception $e) {
            error_log("Erro em showFormNovoPedido: " . $e->getMessage());
            $this->loadView('error/500', [
                'message' => 'Não foi possível carregar a página de pedidos.'
            ]);
        }
    }

    // -------------------------------------------------------------------
    // ✨ NOVO: Atualização (Edição) de Pedido via API
    // -------------------------------------------------------------------

    /**
     * Endpoint API para atualizar um pedido existente.
     * Espera um payload JSON com 'itens' e o ID do pedido na rota.
     */
    public function updatePedido($params)
    {
        $pedido_id = $params['id'] ?? null;

        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
            return;
        }

        header('Content-Type: application/json');

        try {
            if (!$pedido_id || !is_numeric($pedido_id)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID do pedido inválido.']);
                return;
            }

            $empresa_id = $_SESSION['empresa_id'] ?? null;
            if (!$empresa_id) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Sessão da empresa não encontrada.']);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $itens = $data['itens'] ?? [];

            if (empty($itens)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Nenhum item enviado para atualização.']);
                return;
            }

            $pdo = Database::getConnection();
            $pedidoModel = new PedidoModel($pdo);

            $sucesso = $pedidoModel->atualizarPedido((int) $pedido_id, (int) $empresa_id, $itens);

            if ($sucesso) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Pedido atualizado com sucesso!']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Falha ao atualizar o pedido.']);
            }

        } catch (\Exception $e) {
            error_log("Erro na API updatePedido: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro interno ao atualizar o pedido.',
                'details' => $e->getMessage()
            ]);
        }
    }
}
