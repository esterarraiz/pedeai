<?php
// Ficheiro: app/Controllers/Api/GarcomApiController.php (Versão Definitiva e Corrigida)

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
        parent::__construct(); 
        $this->requireLogin();

        if ($_SESSION['user_cargo'] !== 'garçom') {
            $this->jsonResponse(['success' => false, 'message' => 'Acesso negado.'], 403);
        }

        $this->pdo = Database::getConnection();
        $this->empresa_id = $_SESSION['empresa_id'];
    }

    /**
     * Endpoint para listar todas as mesas. (GET /api/garcom/mesas)
     */
    public function listarMesas()
    {
        try {
            $mesaModel = new Mesa($this->pdo);
            $mesas = $mesaModel->buscarTodasPorEmpresa($this->empresa_id);
            $this->jsonResponse(['success' => true, 'data' => $mesas]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Erro ao buscar mesas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint para buscar os detalhes de uma mesa específica. (GET /api/garcom/mesas/{id})
     */
    public function detalhesMesa($params)
    {
        try {
            $mesa_id = filter_var($params['id'] ?? 0, FILTER_VALIDATE_INT);
            if (!$mesa_id) {
                $this->jsonResponse(['success' => false, 'message' => 'ID da mesa inválido ou não fornecido.'], 400);
            }

            $mesaModel = new Mesa($this->pdo);
            $mesa = $mesaModel->buscarPorId($mesa_id);

            $pedidoModel = new PedidoModel($this->pdo);
            $ultimo_pedido = $pedidoModel->buscarItensDoUltimoPedidoDaMesa($mesa_id, $this->empresa_id);

            $this->jsonResponse(['success' => true, 'data' => ['mesa' => $mesa, 'pedido' => $ultimo_pedido]]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Erro ao buscar detalhes da mesa: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint para buscar o cardápio. (GET /api/garcom/cardapio)
     */
    public function getCardapio()
    {
        try {
            $cardapioModel = new CardapioModel($this->pdo);
            $itens = $cardapioModel->buscarItensAgrupados($this->empresa_id);
            $this->jsonResponse(['success' => true, 'data' => $itens]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Erro ao buscar cardápio: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Endpoint para criar um novo pedido. (POST /api/garcom/pedidos)
     */
    public function lancarPedido()
    {
        // Este controller usa 'file_get_contents'
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $mesa_id = filter_var($data['mesa_id'] ?? null, FILTER_SANITIZE_NUMBER_INT);
        $itens_pedido = $data['itens'] ?? [];
        $itens_validos = array_filter($itens_pedido, fn($qtd) => is_numeric($qtd) && $qtd > 0);
        
        $funcionario_id = $_SESSION['user_id'] ?? null;

        if (empty($itens_validos) || !$mesa_id) {
            return $this->jsonResponse(['message' => 'Pedido inválido ou sem itens.'], 400);
        }
        if (!$this->empresa_id || !$funcionario_id) {
            return $this->jsonResponse(['message' => 'Sessão inválida ou expirada.'], 401);
        }

        try {
            // A transação deve ser movida para dentro do Model (como no PagamentoModel)
            // ou controlada aqui, como no PedidoController.
            // Por agora, vamos focar em corrigir a ordem dos argumentos.
            
            $pedidoModel = new PedidoModel($this->pdo);

            // --- CORREÇÃO DA ORDEM DOS ARGUMENTOS ---
            // O Model espera: (empresa_id, mesa_id, funcionario_id, itens)
            $pedido_id = $pedidoModel->criarNovoPedido(
                $this->empresa_id,      // 1. empresa_id
                (int)$mesa_id,          // 2. mesa_id
                (int)$funcionario_id,   // 3. funcionario_id
                $itens_validos          // 4. itens
            );
            // --- FIM DA CORREÇÃO ---

            // Se o 'criarNovoPedido' não atualiza a mesa, precisamos fazer aqui:
            $mesaModel = new Mesa($this->pdo);
            $mesaModel->atualizarStatus($mesa_id, 'ocupada');
            
            $this->jsonResponse(['success' => true, 'message' => 'Pedido criado com sucesso!', 'pedido_id' => $pedido_id]);

        } catch (Exception $e) {
            error_log("Erro em lancarPedido API: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Erro interno ao criar o pedido: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint para o garçom buscar pedidos prontos na cozinha. (GET /api/garcom/pedidos/prontos)
     */
    public function buscarPedidosProntos()
    {
        try {
            if (!$this->empresa_id) { throw new Exception("Sessão inválida."); }

            $pedidoModel = new PedidoModel($this->pdo);
            
            // ELA CHAMA O MÉTODO 'buscarPedidosProntosPorEmpresa'
            $pedidosProntos = $pedidoModel->buscarPedidosProntosPorEmpresa($this->empresa_id);
            
            // Se o PedidoModel filtrar por 'pronto', $pedidosProntos deve ser um array VAZIO.
            $this->jsonResponse(['success' => true, 'data' => $pedidosProntos]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * ATUALIZAÇÃO FINAL: Endpoint para o garçom marcar TODOS os pedidos prontos de uma MESA como entregues.
     */
    public function marcarComoEntregue()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json);
        
        // Agora esperamos um 'mesa_id' vindo do JavaScript
        $mesa_id = filter_var($data->mesa_id ?? null, FILTER_VALIDATE_INT);

        if (!$mesa_id) {
            $this->jsonResponse(['success' => false, 'message' => 'ID da mesa não fornecido.'], 400);
        }
        
        try {
            $pedidoModel = new PedidoModel($this->pdo);
            // Chama o novo método do Model que atualiza todos os pedidos da mesa
            $sucesso = $pedidoModel->marcarPedidosDaMesaComoEntregues($mesa_id, $this->empresa_id);

            if ($sucesso) {
                $this->jsonResponse(['success' => true, 'message' => 'Pedidos da mesa marcados como entregues!']);
            } else {
                throw new \Exception("Nenhum pedido 'pronto' encontrado para esta mesa para ser atualizado.");
            }
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Método auxiliar para padronizar as respostas JSON.
     */
    private function jsonResponse($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}