<?php
// Ficheiro: app/Models/PagamentoModel.php (Versão Corrigida)

namespace App\Models;

use PDO;
use Exception;
use App\Models\PedidoModel; // Assume que PedidoModel está no mesmo namespace
use App\Models\Mesa;       // Assume que Mesa está no mesmo namespace

class PagamentoModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Registra um pagamento, fecha o último pedido e libera a mesa, tudo em uma transação.
     */
    public function registrarPagamento(int $mesa_id, float $valorPago, string $metodoPagamento, int $funcionario_id): bool
    {
        try {
            $this->pdo->beginTransaction();

            $pedidoModel = new PedidoModel($this->pdo);
            $mesaModel = new Mesa($this->pdo); // Instancia o MesaModel aqui
            
            $empresa_id = $_SESSION['empresa_id'] ?? 0;
            
            if ($empresa_id === 0) {
                throw new Exception("ID da empresa não encontrado na sessão.");
            }
            
            $ultimoPedido = $pedidoModel->buscarItensDoUltimoPedidoDaMesa($mesa_id, $empresa_id);

            if (!$ultimoPedido) {
                // Se não há pedido ativo, apenas tenta liberar a mesa
                if (!$mesaModel->liberarMesa($mesa_id, $empresa_id)) {
                    throw new Exception("Mesa {$mesa_id} não encontrada ou não pôde ser liberada (sem pedido ativo).");
                }
                $this->pdo->commit();
                // Retorna true, pois a mesa foi liberada
                return true; 
            }
            
            $pedido_id = $ultimoPedido['id'];

            // 1. Insere o registo do pagamento
            $sqlPagamento = "INSERT INTO pagamentos (pedido_id, valor, metodo_pagamento, funcionario_id, data_pagamento) VALUES (?, ?, ?, ?, NOW())";
            $stmtPagamento = $this->pdo->prepare($sqlPagamento);
            $stmtPagamento->execute([$pedido_id, $valorPago, $metodoPagamento, $funcionario_id]);

            // 2. Atualiza o status do ÚLTIMO pedido para 'pago'
            $sqlPedido = "UPDATE pedidos SET status = 'pago', data_fechamento = NOW() WHERE id = ? AND empresa_id = ?";
            $stmtPedido = $this->pdo->prepare($sqlPedido);
            $stmtPedido->execute([$pedido_id, $empresa_id]);

            // 3. Atualiza o status da mesa para 'disponivel'
            // --- CORREÇÃO IMPORTANTE: ADICIONADA A VERIFICAÇÃO DE FALHA ---
            if (!$mesaModel->liberarMesa($mesa_id, $empresa_id)) {
                // Se liberarMesa() retornou false (rowCount 0), algo está errado.
                // Lançamos uma exceção para reverter a transação (rollback).
                throw new Exception("Pagamento registrado, mas a mesa {$mesa_id} (Empresa: {$empresa_id}) não pôde ser liberada. Verifique se os IDs estão corretos.");
            }

            // 4. Linha do erro fatal removida

            $this->pdo->commit();

            return true;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Erro ao registrar pagamento: " . $e->getMessage());
            // Lança a exceção para que o controller possa tratá-la
            throw $e;
        }
    }
}

