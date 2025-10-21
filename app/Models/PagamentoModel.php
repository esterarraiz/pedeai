<?php
// Ficheiro: app/Models/PagamentoModel.php (Versão para Resolver o Conflito)

namespace App\Models;

use PDO;
use Exception;
use App\Models\PedidoModel;
use App\Models\Mesa;

class PagamentoModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function registrarPagamento(int $mesa_id, float $valorPago, string $metodoPagamento, int $funcionario_id): bool
    {
        try {
            $this->pdo->beginTransaction();

            $pedidoModel = new PedidoModel($this->pdo);
            $empresa_id = $_SESSION['empresa_id'] ?? 0;
            
            if ($empresa_id === 0) {
                throw new Exception("ID da empresa não encontrado na sessão.");
            }
            
            $ultimoPedido = $pedidoModel->buscarItensDoUltimoPedidoDaMesa($mesa_id, $empresa_id);

            if (!$ultimoPedido) {
                throw new Exception("Nenhum pedido ativo encontrado para a mesa {$mesa_id}.");
            }
            $pedido_id = $ultimoPedido['id'];

            // 1. Insere o registo do pagamento
            $sqlPagamento = "INSERT INTO pagamentos (pedido_id, valor, metodo_pagamento, funcionario_id, data_pagamento) VALUES (?, ?, ?, ?, NOW())";
            $stmtPagamento = $this->pdo->prepare($sqlPagamento);
            $stmtPagamento->execute([$pedido_id, $valorPago, $metodoPagamento, $funcionario_id]);

            // 2. Atualiza o status do pedido para 'pago'
            $sqlPedido = "UPDATE pedidos SET status = 'pago', data_fechamento = NOW() WHERE id = ? AND empresa_id = ?";
            $stmtPedido = $this->pdo->prepare($sqlPedido);
            $stmtPedido->execute([$pedido_id, $empresa_id]);

            // 3. Atualiza o status da mesa para 'disponivel'
            $mesaModel = new Mesa($this->pdo);
            $mesaModel->atualizarStatus($mesa_id, 'disponivel');

            // 4. Limpa automaticamente as notificações de 'pronto' para esta mesa
            $pedidoModel->arquivarPedidosProntosDeMesa($mesa_id);

            $this->pdo->commit();

            return true;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Erro ao registrar pagamento: " . $e->getMessage());
            // Lança a exceção para que o controller possa tratá-la e mostrar um erro
            throw $e;
        }
    }
}