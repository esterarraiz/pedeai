<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Mesa;
use App\Models\PedidoModel;
use App\Models\CardapioModel;
use Config\Database;

class PedidoController extends Controller
{
    public function showFormNovoPedido($params)
    {
        // ... (seu código existente, sem alterações)
        $mesaId = $params['id'] ?? null;
        if (!$mesaId || !is_numeric($mesaId)) {
            header('Location: /dashboard/garcom?status=mesa_invalida');
            exit;
        }
        try {
            $pdo = \Config\Database::getConnection();
            $empresa_id = $_SESSION['empresa_id'] ?? null;
            if (!$empresa_id) { throw new \Exception("ID da empresa não encontrado na sessão."); }
            $cardapioModel = new \App\Models\CardapioModel($pdo); 
            $itensCardapio = $cardapioModel->buscarItensAgrupados($empresa_id); 
            $this->loadView('pedidos/novo', [
                'pageTitle' => 'Lançar Pedido para a Mesa ' . htmlspecialchars($mesaId),
                'mesa_id'   => (int)$mesaId,
                'cardapio'  => $itensCardapio
            ]);
        } catch (\Exception $e) {
            error_log("Erro em showFormNovoPedido: " . $e->getMessage());
            $this->loadView('error', ['message' => 'Não foi possível carregar a página de pedidos.']);
        }
    }
    
    public function processarPedidoAjax()
    {
        // ... (seu código existente, sem alterações)
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
            exit;
        }
        $jsonPayload = file_get_contents('php://input');
        $requestData = json_decode($jsonPayload, true);
        $mesa_id = filter_var($requestData['mesa_id'] ?? null, FILTER_SANITIZE_NUMBER_INT);
        $itens_pedido = $requestData['itens'] ?? [];
        $itens_validos = array_filter($itens_pedido, fn($qtd) => is_numeric($qtd) && $qtd > 0);
        if (empty($itens_validos) || !$mesa_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Pedido inválido ou sem itens.']);
            exit;
        }
        $pdo = null;
        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();
            $pedidoModel = new PedidoModel($pdo);
            $mesaModel = new Mesa($pdo);
            $empresa_id = $_SESSION['empresa_id'] ?? null;
            $funcionario_id = $_SESSION['user_id'] ?? null;
            if (!$empresa_id || !$funcionario_id) {
                throw new \Exception("Sessão do usuário inválida ou expirada. Verifique se está logado.");
            }
            $pedido_id = $pedidoModel->criarNovoPedido($empresa_id, (int)$mesa_id, (int)$funcionario_id, $itens_validos);
            $mesaSucesso = $mesaModel->atualizarStatus($mesa_id, 'Ocupada');
            if (!$mesaSucesso) {
                throw new \Exception("Falha ao atualizar o status da mesa. Verifique o MesaModel.");
            }
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => "Pedido #{$pedido_id} lançado com sucesso!"]);
            exit;
        } catch (\Exception $e) {
            if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
            error_log("Erro ao processar pedido AJAX: " . $e->getMessage() . " no arquivo " . $e->getFile() . " na linha " . $e->getLine());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * NOVO MÉTODO: Endpoint da API para o JavaScript do garçom buscar pedidos prontos.
     */
    public function buscarPedidosProntos()
    {
        header('Content-Type: application/json');
        
        try {
            $empresa_id = $_SESSION['empresa_id'] ?? null;
            if (!$empresa_id) {
                throw new \Exception("Usuário não autenticado ou empresa não encontrada.");
            }

            $pdo = Database::getConnection();
            $pedidoModel = new PedidoModel($pdo);

            $pedidosProntos = $pedidoModel->buscarPedidosProntosPorEmpresa($empresa_id);

            echo json_encode([
                'success' => true,
                'pedidos' => $pedidosProntos
            ]);

        } catch (\Exception $e) {
            http_response_code(401); // Unauthorized ou outro código de erro apropriado
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}