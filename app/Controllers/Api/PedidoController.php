<?php

namespace App\Controllers\Api;

use App\Core\JsonController;
use App\Models\PedidoModel;
use App\Models\Mesa; 
use Config\Database;
use Exception;
use PDO; // Adicionado o import

class PedidoController extends JsonController
{
    // private PedidoModel $pedidoModel; // <-- REMOVIDO
    // private Mesa $mesaModel; // <-- REMOVIDO
    private ?int $empresa_id;
    private ?int $funcionario_id;

    public function __construct()
    {
        // $pdo = Database::getConnection(); // <-- REMOVIDO
        // $this->pedidoModel = new PedidoModel($pdo); // <-- REMOVIDO
        // $this->mesaModel = new Mesa($pdo); // <-- REMOVIDO
        $this->empresa_id = $_SESSION['empresa_id'] ?? null;
        $this->funcionario_id = $_SESSION['user_id'] ?? null;
    }

    // --- MÉTODOS DE INJEÇÃO (para teste) ---

    protected function getPdo(): PDO
    {
        // Retorna uma nova conexão ou reutiliza uma existente
        // Para este controller, vamos garantir que a conexão seja criada
        static $pdo = null;
        if ($pdo === null) {
            $pdo = Database::getConnection();
        }
        return $pdo;
    }

    protected function getPedidoModel(): PedidoModel
    {
        return new PedidoModel($this->getPdo());
    }

    protected function getMesaModel(): Mesa
    {
        return new Mesa($this->getPdo());
    }

    // --- MÉTODOS DA API ---

    public function criarPedido()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        $requestData = $this->getJsonData();
        $mesa_id = filter_var($requestData['mesa_id'] ?? null, FILTER_SANITIZE_NUMBER_INT);
        $itens_pedido = $requestData['itens'] ?? [];
        $itens_validos = array_filter($itens_pedido, fn($qtd) => is_numeric($qtd) && $qtd > 0);

        if (empty($itens_validos) || !$mesa_id) {
            return $this->jsonResponse(['message' => 'Pedido inválido ou sem itens.'], 400);
        }
        if (!$this->empresa_id || !$this->funcionario_id) {
            return $this->jsonResponse(['message' => 'Sessão inválida ou expirada.'], 401);
        }

        $pdo = $this->getPdo(); // <-- MUDANÇA: Usa o método mockável
        try {
            $pdo->beginTransaction();

            // MUDANÇA: Usa os getters
            $pedido_id = $this->getPedidoModel()->criarNovoPedido(
                $this->empresa_id, 
                (int)$mesa_id, 
                $this->funcionario_id, 
                $itens_validos
            );

            // MUDANÇA: Usa os getters
            $this->getMesaModel()->atualizarStatus($mesa_id, 'ocupada'); 

            $pdo->commit();
            $this->jsonResponse(['success' => true, 'message' => "Pedido #{$pedido_id} lançado com sucesso!"]);

        } catch (Exception $e) {
            if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
            error_log("Erro ao criar pedido via API: " . $e->getMessage());
            $this->jsonResponse(['message' => $e->getMessage()], 500);
        }
    }

    public function getPedidosProntos()
    {
        if (!$this->empresa_id) {
            return $this->jsonResponse(['message' => 'Empresa não identificada.'], 401);
        }
        // MUDANÇA: Usa o getter
        $pedidosProntos = $this->getPedidoModel()->buscarPedidosProntosPorEmpresa($this->empresa_id);
        $this->jsonResponse(['pedidos' => $pedidosProntos]);
    }

    public function marcarPedidoEntregue()
    {
        $data = $this->getJsonData();
        $pedido_id = $data['pedido_id'] ?? null;

        if (!$pedido_id || !$this->empresa_id) {
            return $this->jsonResponse(['message' => 'ID do pedido ou empresa inválido.'], 400);
        }

        // MUDANÇA: Usa o getter
        $success = $this->getPedidoModel()->marcarPedidosDaMesaComoEntregues((int)$pedido_id, $this->empresa_id);
        
        if ($success) {
            $this->jsonResponse(['message' => 'Pedido marcado como entregue.']);
        } else {
            $this->jsonResponse(['message' => 'Falha ao marcar pedido como entregue (verifique o status atual).'], 500);
        }
    }

    public function marcarPedidoPronto()
    {
        try {
            $data = $this->getJsonData();
            $pedido_id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
            $empresa_id = $this->empresa_id; 

            if (!$pedido_id || !$empresa_id) {
                throw new Exception("ID do pedido ou ID da empresa inválido.");
            }

            // MUDANÇA: Usa o getter
            $sucesso = $this->getPedidoModel()->marcarComoPronto($pedido_id, $empresa_id);

            if ($sucesso) {
                $this->jsonResponse(['success' => true, 'message' => "Pedido #{$pedido_id} marcado como pronto!"]);
            } else {
                throw new Exception("O Pedido #{$pedido_id} não pôde ser atualizado. Ele pode já ter sido marcado como 'pronto' ou o seu estado não é 'em preparo'.");
            }

        } catch (Exception $e) { 
            error_log("Erro ao marcar pedido como pronto: " . $e->getMessage()); 
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500); 
        }
    }
}