<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CardapioModel; // Assume que este Model existe
use App\Models\PedidoModel; // Importar o PedidoModel
use Config\Database;

class PedidoController extends Controller
{
    /**
     * Ação: Apenas exibe a página (view) para lançar um novo pedido.
     */
    public function showFormNovoPedido($params)
    {
        $mesaId = $params['id'] ?? null;
        if (!$mesaId || !is_numeric($mesaId)) {
            // Redireciona se o ID da mesa for inválido
            header('Location: /dashboard/garcom?status=mesa_invalida');
            exit;
        }

        try {
            $pdo = Database::getConnection();
            $empresa_id = $_SESSION['empresa_id'] ?? null;
            if (!$empresa_id) { throw new \Exception("ID da empresa não encontrado na sessão."); }
            
            $cardapioModel = new CardapioModel($pdo); 
            $itensCardapio = $cardapioModel->buscarItensAgrupados($empresa_id); 

            $this->loadView('pedidos/novo', [
                'mesa_id'   => (int)$mesaId,
                'cardapio'  => $itensCardapio
            ]);

        } catch (\Exception $e) {
            error_log("Erro em showFormNovoPedido: " . $e->getMessage());
            $this->loadView('error/500', ['message' => 'Não foi possível carregar a página de pedidos.']);
        }
    }

    // -------------------------------------------------------------------
    // ✨ NOVO MÉTODO: ATUALIZAÇÃO (EDIÇÃO) DE PEDIDO VIA API
    // -------------------------------------------------------------------
    
    /**
     * Endpoint API para atualizar um pedido existente.
     * Espera um payload JSON com 'itens' e o ID do pedido via URL.
     * Rota esperada: PUT /api/pedidos/{id}
     */
    public function updatePedido($params)
    {
        // Garante que o ID do pedido veio da URL
        $pedido_id = $params['id'] ?? null;

        // Garante que o método seja PUT (ou POST se não for possível usar PUT)
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); // Method Not Allowed
            echo json_encode(['success' => false, 'message' => 'Método não permitido. Use PUT/POST.']);
            return;
        }

        header('Content-Type: application/json');
        
        try {
            // Valida o ID do pedido
            if (!$pedido_id || !is_numeric($pedido_id)) {
                http_response_code(400); // Bad Request
                echo json_encode(['success' => false, 'message' => 'ID do pedido inválido.']);
                return;
            }

            // Garante que a empresa_id está na sessão
            $empresa_id = $_SESSION['empresa_id'] ?? null;
            if (!$empresa_id) {
                http_response_code(401); // Unauthorized
                echo json_encode(['success' => false, 'message' => 'Sessão da empresa não encontrada.']);
                return;
            }
            
            // Lê o payload JSON da requisição
            $data = json_decode(file_get_contents('php://input'), true);
            $itens = $data['itens'] ?? []; // Espera um array de itens

            if (empty($itens)) {
                http_response_code(400); 
                echo json_encode(['success' => false, 'message' => 'Nenhum item para atualizar no pedido.']);
                return;
            }

            $pdo = Database::getConnection();
            $pedidoModel = new PedidoModel($pdo);

            // Chama o Model para executar a lógica transacional de atualização
            $sucesso = $pedidoModel->atualizarPedido((int)$pedido_id, (int)$empresa_id, $itens);

            if ($sucesso) {
                http_response_code(200); // OK
                echo json_encode(['success' => true, 'message' => 'Pedido atualizado com sucesso!']);
            } else {
                // Caso o Model retorne false sem lançar exceção (menos provável com a lógica atual)
                http_response_code(500); 
                echo json_encode(['success' => false, 'message' => 'Falha desconhecida ao atualizar o pedido.']);
            }

        } catch (\Exception $e) {
            error_log("Erro na API de atualização de pedido: " . $e->getMessage());
            // Se uma exceção for lançada, retorna 500
            http_response_code(500); 
            echo json_encode(['success' => false, 'message' => 'Erro interno ao processar a atualização.', 'details' => $e->getMessage()]);
        }
    }
}