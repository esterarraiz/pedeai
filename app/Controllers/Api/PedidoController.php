<?php

namespace App\Controllers\Api;

use App\Core\JsonController;
use App\Models\PedidoModel;
use App\Models\Mesa; 
use Config\Database;
use Exception;
use PDO; 

class PedidoController extends JsonController
{
    private ?int $empresa_id;
    private ?int $funcionario_id;

    public function __construct()
    {
        $this->empresa_id = $_SESSION['empresa_id'] ?? null;
        $this->funcionario_id = $_SESSION['user_id'] ?? null;
    }

    // --- MÉTODOS DE INJEÇÃO (para teste) ---

    protected function getPdo(): PDO
    {
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

        $pdo = $this->getPdo(); 
        try {
            $pdo->beginTransaction();

            $pedido_id = $this->getPedidoModel()->criarNovoPedido(
                $this->empresa_id, 
                (int)$mesa_id, 
                $this->funcionario_id, 
                $itens_validos
            );

            $this->getMesaModel()->atualizarStatus($mesa_id, 'ocupada'); 

            $pdo->commit();
            $this->jsonResponse(['success' => true, 'message' => "Pedido #{$pedido_id} lançado com sucesso!"]);

        } catch (Exception $e) {
            if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
            error_log("Erro ao criar pedido via API: " . $e->getMessage());
            $statusCode = (strpos($e->getMessage(), 'encontrado') !== false || strpos($e->getMessage(), 'inválido') !== false) ? 400 : 500;
            $this->jsonResponse(['message' => $e->getMessage()], $statusCode);
        }
    }

    /**
     * Endpoint para PUT /api/pedidos/{id} (Atualização/Edição)
     */
    public function updatePedido($params)
    {
        $pedido_id = filter_var($params['id'] ?? null, FILTER_VALIDATE_INT);
        $requestData = $this->getJsonData();
        $itens = $requestData['itens'] ?? []; 
        
        if (!$pedido_id) {
            return $this->jsonResponse(['message' => 'ID do pedido não fornecido ou inválido.'], 400);
        }
        if (!$this->empresa_id) {
            return $this->jsonResponse(['message' => 'Sessão da empresa não encontrada.'], 401);
        }

        $itens_validos = array_filter($itens, fn($qtd) => is_numeric($qtd) && $qtd > 0);
        $pedido_vazio = empty($itens_validos);
        
        try {
            $pdo = $this->getPdo();
            $pedidoModel = $this->getPedidoModel();
            $mesaModel = $this->getMesaModel();

            // 1. Atualiza o pedido (seja com novos itens ou zerando o valor)
            $sucesso = $pedidoModel->atualizarPedido(
                $pedido_id, 
                $this->empresa_id, 
                $itens
            );

            $mesa_id = null;
            $status_message = "Pedido #{$pedido_id} editado com sucesso!";

            // 2. Lógica de Liberação da Mesa
            if ($sucesso && $pedido_vazio) {
                // A. Buscar o ID da Mesa associada ao Pedido
                $sql_get_mesa = "SELECT mesa_id FROM pedidos WHERE id = :pedido_id AND empresa_id = :empresa_id";
                $stmt = $pdo->prepare($sql_get_mesa);
                $stmt->execute([':pedido_id' => $pedido_id, ':empresa_id' => $this->empresa_id]);
                $mesa_id = $stmt->fetchColumn();

                if ($mesa_id) {
                    // B. Atualizar o status da mesa para 'disponivel' (ou 'Livre')
                    $mesaModel->atualizarStatus((int)$mesa_id, 'disponivel'); 
                    $status_message = "Pedido #{$pedido_id} zerado e mesa liberada com sucesso!";
                }
            }
            // 3. Retorno
            $this->jsonResponse(['success' => true, 'message' => $status_message]);
            
        } catch (\PDOException $e) {
            error_log("Erro PDO na API de atualização: " . $e->getMessage());
            $this->jsonResponse(['message' => 'Erro interno no banco de dados durante a edição.'], 500);
        } catch (Exception $e) {
            error_log("Erro na API de atualização de pedido: " . $e->getMessage());
            $this->jsonResponse(['message' => $e->getMessage()], 400); 
        }
    }
    
    // --- Outros Métodos (inalterados) ---

    public function getPedidosProntos()
    {
        if (!$this->empresa_id) {
            return $this->jsonResponse(['message' => 'Empresa não identificada.'], 401);
        }
        $pedidosProntos = $this->getPedidoModel()->buscarPedidosProntosPorEmpresa($this->empresa_id);
        $this->jsonResponse(['pedidos' => $pedidosProntos]);
    }

    public function marcarPedidoEntregue()
    {
        $data = $this->getJsonData();
        $mesa_id = $data['mesa_id'] ?? null; 
        
        if (!$mesa_id || !$this->empresa_id) {
            return $this->jsonResponse(['message' => 'ID da mesa ou empresa inválido.'], 400);
        }

        $success = $this->getPedidoModel()->marcarPedidosDaMesaComoEntregues((int)$mesa_id, $this->empresa_id);
        
        if ($success) {
            $this->jsonResponse(['success' => true, 'message' => 'Pedidos marcados como entregues.']);
        } else {
            $this->jsonResponse(['message' => 'Falha ao marcar pedidos como entregues (verifique o status atual).'], 500);
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