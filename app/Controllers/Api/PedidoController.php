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

    // -------------------------------
    // MÉTODOS DE INJEÇÃO (Mockáveis)
    // -------------------------------
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

    // --------------------------------------------------
    // NOVO: Buscar detalhes completos do Pedido (Admin)
    // --------------------------------------------------
    public function getDetalhesPedidoAdmin($params)
    {
        $pedido_id = $params['id'] ?? null;

        if (!$pedido_id || !$this->empresa_id) {
            return $this->jsonResponse(['message' => 'ID do pedido ou empresa inválido.'], 400);
        }

        try {
            $detalhes = $this->getPedidoModel()->buscarDetalhesPorPedidoId((int)$pedido_id, $this->empresa_id);
            
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

    // ----------------------------
    // Criar um novo pedido (API)
    // ----------------------------
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
            return $this->jsonResponse([
                'success' => true,
                'message' => "Pedido #{$pedido_id} lançado com sucesso!"
            ]);

        } catch (Exception $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log("Erro ao criar pedido via API: " . $e->getMessage());
            
            $status = (str_contains($e->getMessage(), 'inválido') || str_contains($e->getMessage(), 'encontrado')) 
                      ? 400 : 500;

            return $this->jsonResponse(['message' => $e->getMessage()], $status);
        }
    }

    // -------------------------------------------------------------
    // NOVO: PUT /api/pedidos/{id} — Atualização/Edição de Pedido
    // -------------------------------------------------------------
    public function updatePedido($params)
    {
        $pedido_id = filter_var($params['id'] ?? null, FILTER_VALIDATE_INT);
        $data = $this->getJsonData();
        $itens = $data['itens'] ?? [];

        if (!$pedido_id) {
            return $this->jsonResponse(['message' => 'ID do pedido inválido.'], 400);
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

            $sucesso = $pedidoModel->atualizarPedido($pedido_id, $this->empresa_id, $itens);

            $status_message = "Pedido #{$pedido_id} editado com sucesso!";

            // Se o pedido ficou sem itens → libera mesa
            if ($sucesso && $pedido_vazio) {
                $sql = "SELECT mesa_id FROM pedidos WHERE id = :pedido_id AND empresa_id = :empresa_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':pedido_id' => $pedido_id, ':empresa_id' => $this->empresa_id]);
                $mesa_id = $stmt->fetchColumn();

                if ($mesa_id) {
                    $mesaModel->atualizarStatus((int)$mesa_id, 'disponivel');
                    $status_message = "Pedido #{$pedido_id} zerado e mesa liberada!";
                }
            }

            return $this->jsonResponse(['success' => true, 'message' => $status_message]);

        } catch (\PDOException $e) {
            error_log("Erro PDO na atualização: " . $e->getMessage());
            return $this->jsonResponse(['message' => 'Erro no banco de dados.'], 500);

        } catch (Exception $e) {
            error_log("Erro na atualização do pedido: " . $e->getMessage());
            return $this->jsonResponse(['message' => $e->getMessage()], 400);
        }
    }

    // --------------------------------------------------
    // Pedidos prontos
    // --------------------------------------------------
    public function getPedidosProntos()
    {
        if (!$this->empresa_id) {
            return $this->jsonResponse(['message' => 'Empresa não identificada.'], 401);
        }

        $pedidosProntos = $this->getPedidoModel()->buscarPedidosProntosPorEmpresa($this->empresa_id);
        return $this->jsonResponse(['pedidos' => $pedidosProntos]);
    }

    // --------------------------------------------------
    // Marcar pedidos da mesa como entregues
    // --------------------------------------------------
    public function marcarPedidoEntregue()
    {
        $data = $this->getJsonData();
        $mesa_id = $data['mesa_id'] ?? null;

        if (!$mesa_id || !$this->empresa_id) {
            return $this->jsonResponse(['message' => 'ID da mesa ou empresa inválido.'], 400);
        }

        $success = $this->getPedidoModel()->marcarPedidosDaMesaComoEntregues((int)$mesa_id, $this->empresa_id);

        if ($success) {
            return $this->jsonResponse(['success' => true, 'message' => 'Pedidos marcados como entregues.']);
        }

        return $this->jsonResponse(['message' => 'Falha ao marcar pedidos como entregues.'], 500);
    }

    // --------------------------------------------------
    // Marcar 1 pedido como pronto
    // --------------------------------------------------
    public function marcarPedidoPronto()
    {
        try {
            $data = $this->getJsonData();
            $pedido_id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

            if (!$pedido_id || !$this->empresa_id) {
                throw new Exception("ID do pedido ou empresa inválido.");
            }

            $sucesso = $this->getPedidoModel()->marcarComoPronto($pedido_id, $this->empresa_id);

            if ($sucesso) {
                return $this->jsonResponse(['success' => true, 'message' => "Pedido #{$pedido_id} marcado como pronto!"]);
            }

            throw new Exception("O Pedido #{$pedido_id} não pôde ser atualizado.");

        } catch (Exception $e) {
            error_log("Erro ao marcar pedido como pronto: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
