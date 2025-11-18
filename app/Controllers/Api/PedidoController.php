<?php

namespace App\Controllers\Api;

use App\Core\JsonController;
use App\Models\PedidoModel;
use App\Models\Mesa; // Necessário para atualizar o status da mesa
use Config\Database;
use Exception; // Usaremos Exception para lidar com erros

class PedidoController extends JsonController
{
    private PedidoModel $pedidoModel;
    private Mesa $mesaModel; // Adicionado para interagir com mesas
    private ?int $empresa_id;
    private ?int $funcionario_id;

    public function __construct()
    {
        $pdo = Database::getConnection();
        $this->pedidoModel = new PedidoModel($pdo);
        $this->mesaModel = new Mesa($pdo); // Instancia o Mesa Model
        $this->empresa_id = $_SESSION['empresa_id'] ?? null;
        $this->funcionario_id = $_SESSION['user_id'] ?? null;
    }
    public function getDetalhesPedidoAdmin($params)
    {
        $pedido_id = $params['id'] ?? null;

        if (!$pedido_id || !$this->empresa_id) {
            return $this->jsonResponse(['message' => 'ID do pedido ou empresa inválido.'], 400);
        }

        try {
            // Usa o novo método do Model
            $detalhes = $this->pedidoModel->buscarDetalhesPorPedidoId((int)$pedido_id, $this->empresa_id);
            
            if ($detalhes) {
                $this->jsonResponse(['success' => true, 'data' => $detalhes]);
            } else {
                $this->jsonResponse(['message' => 'Pedido não encontrado.'], 404);
            }
        } catch (Exception $e) {
            error_log("Erro ao buscar detalhes do pedido (Admin): " . $e->getMessage());
            $this->jsonResponse(['message' => 'Erro interno ao buscar detalhes.'], 500);
        }
    }

    /**
     * Endpoint: POST /api/pedidos
     * Cria um novo pedido.
     */
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

        $pdo = Database::getConnection(); // Pega a conexão novamente para a transação
        try {
            $pdo->beginTransaction();

            $pedido_id = $this->pedidoModel->criarNovoPedido(
                $this->empresa_id, 
                (int)$mesa_id, 
                $this->funcionario_id, 
                $itens_validos
            );

            // Atualiza o status da mesa para 'ocupada'
            $this->mesaModel->atualizarStatus($mesa_id, 'ocupada'); // Usando o método correto

            $pdo->commit();
            $this->jsonResponse(['success' => true, 'message' => "Pedido #{$pedido_id} lançado com sucesso!"]);

        } catch (Exception $e) {
            if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
            error_log("Erro ao criar pedido via API: " . $e->getMessage());
            $this->jsonResponse(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint: GET /api/pedidos/prontos
     * Busca pedidos prontos para notificar o garçom.
     */
    public function getPedidosProntos()
    {
        if (!$this->empresa_id) {
            return $this->jsonResponse(['message' => 'Empresa não identificada.'], 401);
        }
        $pedidosProntos = $this->pedidoModel->buscarPedidosProntosPorEmpresa($this->empresa_id);
        $this->jsonResponse(['pedidos' => $pedidosProntos]);
    }

    /**
     * Endpoint: POST /api/pedidos/marcar-entregue
     * Marca um pedido como entregue.
     */
    public function marcarPedidoEntregue()
    {
        $data = $this->getJsonData();
        $pedido_id = $data['pedido_id'] ?? null;

        if (!$pedido_id || !$this->empresa_id) {
            return $this->jsonResponse(['message' => 'ID do pedido ou empresa inválido.'], 400);
        }

        $success = $this->pedidoModel->marcarComoEntregue((int)$pedido_id, $this->empresa_id);
        
        if ($success) {
            $this->jsonResponse(['message' => 'Pedido marcado como entregue.']);
        } else {
            $this->jsonResponse(['message' => 'Falha ao marcar pedido como entregue (verifique o status atual).'], 500);
        }
    }
    public function marcarPedidoPronto()
    {
        try {
            // Pega o corpo da requisição POST (que o JavaScript envia como JSON)
            $data = $this->getJsonData();

            // Valida os dados recebidos
            $pedido_id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
            $empresa_id = $this->empresa_id; // Pego do construtor

            if (!$pedido_id || !$empresa_id) {
                // Usa 'Exception' (baseado no 'use' do topo)
                throw new Exception("ID do pedido ou ID da empresa inválido.");
            }

            // Executa a lógica de negócio no Model
            $sucesso = $this->pedidoModel->marcarComoPronto($pedido_id, $empresa_id);

            if ($sucesso) {
                // Se o model retornar true, a atualização foi bem-sucedida.
                $this->jsonResponse(['success' => true, 'message' => "Pedido #{$pedido_id} marcado como pronto!"]);
            } else {
                // Se o model retornar false, a atualização falhou (0 linhas afetadas).
                // Usa 'Exception' (baseado no 'use' do topo)
                throw new Exception("O Pedido #{$pedido_id} não pôde ser atualizado. Ele pode já ter sido marcado como 'pronto' ou o seu estado não é 'em preparo'.");
            }

        } catch (Exception $e) { // CORRIGIDO: Padrão igual ao 'criarPedido'
            // Em caso de qualquer erro, retorna uma resposta JSON com a mensagem de erro.
            error_log("Erro ao marcar pedido como pronto: " . $e->getMessage()); // CORRIGIDO: Adiciona log
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500); // CORRIGIDO: Muda para 500
        }
    }
}
