<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\Mesa;
use App\Models\PedidoModel;
use App\Models\CardapioModel;
use Config\Database;

class GarcomApiController extends Controller
{
    private $pdo;
    private $empresa_id;

    public function __construct()
    {
        // Garante que a sessão seja iniciada e verifica o login
        parent::__construct(); 
        $this->requireLogin();

        // Garante que apenas o garçom acesse esses endpoints
        if ($_SESSION['user_cargo'] !== 'garçom') {
            http_response_code(403); // Forbidden
            echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
            exit;
        }

        $this->pdo = Database::getConnection();
        $this->empresa_id = $_SESSION['empresa_id'];
    }

    /**
     * Endpoint para listar todas as mesas.
     * Rota: GET /api/garcom/mesas
     */
    public function listarMesas()
    {
        header('Content-Type: application/json');
        try {
            $mesaModel = new Mesa($this->pdo);
            $mesas = $mesaModel->buscarTodasPorEmpresa($this->empresa_id);

            echo json_encode(['success' => true, 'data' => $mesas]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar mesas: ' . $e->getMessage()]);
        }
    }

    /**
     * Endpoint para buscar os detalhes de uma mesa específica.
     * Rota: GET /api/garcom/mesas/{id}
     */
    public function detalhesMesa($params)
    {
        header('Content-Type: application/json');
        $mesa_id = $params['id'] ?? 0;

        try {
            if (empty($mesa_id)) {
                http_response_code(400); // Bad Request
                echo json_encode(['success' => false, 'message' => 'ID da mesa não fornecido.']);
                exit;
            }

            $mesaModel = new Mesa($this->pdo);
            $mesa = $mesaModel->buscarPorId($mesa_id);

            $pedidoModel = new PedidoModel($this->pdo);
            $ultimo_pedido = $pedidoModel->buscarItensDoUltimoPedidoDaMesa($mesa_id, $this->empresa_id);

            echo json_encode([
                'success' => true,
                'data' => [
                    'mesa' => $mesa,
                    'pedido' => $ultimo_pedido
                ]
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar detalhes da mesa: ' . $e->getMessage()]);
        }
    }

    /**
     * Endpoint para buscar o cardápio.
     * Rota: GET /api/garcom/cardapio
     */
    public function getCardapio()
    {
        header('Content-Type: application/json');
        try {
            $cardapioModel = new CardapioModel($this->pdo);
            $itens = $cardapioModel->buscarItensAgrupados($this->empresa_id);
            echo json_encode(['success' => true, 'data' => $itens]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar cardápio: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Endpoint para criar um novo pedido.
     * Rota: POST /api/garcom/pedidos
     */
    public function lancarPedido()
    {
        header('Content-Type: application/json');
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        // Validação dos dados recebidos
        if (empty($data['mesa_id']) || empty($data['itens'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dados incompletos para o pedido.']);
            exit;
        }

        try {
            $pedidoModel = new PedidoModel($this->pdo);
            // A lógica de criar o pedido já existe no seu PedidoModel, vamos usá-la.
            $pedido_id = $pedidoModel->criarNovoPedido(
                (int)$data['mesa_id'],
                $data['itens'],
                $_SESSION['user_id'],
                $this->empresa_id
            );

            echo json_encode(['success' => true, 'message' => 'Pedido criado com sucesso!', 'pedido_id' => $pedido_id]);

        } catch (\Exception $e) {
            http_response_code(500);
            error_log("Erro em lancarPedido API: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno ao criar o pedido.']);
        }
    }

    /**
     * Endpoint para o garçom buscar pedidos prontos na cozinha.
     * Rota: GET /api/garcom/pedidos/prontos
     */
    public function buscarPedidosProntos()
    {
        header('Content-Type: application/json');
        try {
            $pedidoModel = new PedidoModel($this->pdo);
            $pedidosProntos = $pedidoModel->buscarPedidosProntosPorEmpresa($this->empresa_id);
            
            echo json_encode(['success' => true, 'data' => $pedidosProntos]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Endpoint para o garçom marcar um pedido como entregue.
     * Rota: POST /api/garcom/pedidos/marcar-entregue
     */
    public function marcarComoEntregue()
    {
        header('Content-Type: application/json');
        $json = file_get_contents('php://input');
        $data = json_decode($json);
        $pedido_id = $data->id ?? null;

        if (!$pedido_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID do pedido não fornecido.']);
            exit;
        }
        
        try {
            $pedidoModel = new PedidoModel($this->pdo);
            // Você precisará adicionar o método 'marcarComoEntregue' no seu PedidoModel.php
            $sucesso = $pedidoModel->marcarComoEntregue((int)$pedido_id, $this->empresa_id);

            if ($sucesso) {
                echo json_encode(['success' => true, 'message' => 'Pedido marcado como entregue!']);
            } else {
                throw new \Exception("Não foi possível atualizar o status do pedido.");
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}