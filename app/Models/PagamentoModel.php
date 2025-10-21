<?php

namespace App\Models;

use PDO;
use Exception;

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

            // Encontra o último pedido ativo da mesa
            $pedidoModel = new PedidoModel($this->pdo);
            $empresa_id = $_SESSION['empresa_id'] ?? 0;
            $ultimoPedido = $pedidoModel->buscarItensDoUltimoPedidoDaMesa($mesa_id, $empresa_id);

            if (!$ultimoPedido) {
                throw new Exception("Nenhum pedido ativo encontrado para a mesa {$mesa_id}.");
            }
            $pedido_id = $ultimoPedido['id'];

            // Registra o pagamento
            $sqlPagamento = "INSERT INTO pagamentos (pedido_id, valor, metodo_pagamento, funcionario_id, data_pagamento) VALUES (?, ?, ?, ?, NOW())";
            $stmtPagamento = $this->pdo->prepare($sqlPagamento);
            $stmtPagamento->execute([$pedido_id, $valorPago, $metodoPagamento, $funcionario_id]);

            // Atualiza o status do pedido para 'pago'
            $sqlPedido = "UPDATE pedidos SET status = 'pago', data_fechamento = NOW() WHERE id = ?";
            $stmtPedido = $this->pdo->prepare($sqlPedido);
            $stmtPedido->execute([$pedido_id]);

            // Libera a mesa, mudando seu status para 'disponivel'
            $mesaModel = new Mesa($this->pdo);
            $mesaModel->atualizarStatus($mesa_id, 'disponivel');

            // Se tudo deu certo, confirma a transação
            $this->pdo->commit();

            return true;

        } catch (Exception $e) {
            // Se qualquer passo falhar, desfaz tudo
            if ($this->pdo->inTransaction()) {
                // CORREÇÃO APLICADA AQUI: O correto é rollBack com 'B' maiúsculo
                $this->pdo->rollBack();
            }
            // Loga o erro para o desenvolvedor e lança a exceção para a API
            error_log("Erro ao registrar pagamento: " . $e->getMessage());
            throw $e;
        }
    }
}