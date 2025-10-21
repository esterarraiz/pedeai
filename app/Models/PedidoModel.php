<?php
// Ficheiro: app/Models/PedidoModel.php (Versão Definitiva e Corrigida)

namespace App\Models;

use PDO;
use PDOException;
use Exception;

class PedidoModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function buscarPedidosParaCozinha(int $empresa_id): array
    {
        $sql = "
            SELECT
                p.id AS pedido_id, p.data_abertura, m.numero AS mesa_numero,
                pi.quantidade, ci.nome AS item_nome
            FROM pedidos p
            JOIN mesas m ON p.mesa_id = m.id
            JOIN pedido_itens pi ON p.id = pi.pedido_id
            JOIN cardapio_itens ci ON pi.item_id = ci.id
            WHERE p.empresa_id = :empresa_id AND p.status = 'em_preparo'
            ORDER BY p.data_abertura ASC, p.id, ci.nome;
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':empresa_id' => $empresa_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pedidos = [];
        foreach ($results as $row) {
            $pedido_id = $row['pedido_id'];
            if (!isset($pedidos[$pedido_id])) {
                $pedidos[$pedido_id] = [
                    'id' => $pedido_id,
                    'mesa' => 'Mesa ' . str_pad($row['mesa_numero'], 2, '0', STR_PAD_LEFT),
                    'hora' => date('H:i', strtotime($row['data_abertura'])),
                    'itens' => []
                ];
            }
            $pedidos[$pedido_id]['itens'][] = [
                'nome' => $row['item_nome'],
                'quantidade' => $row['quantidade']
            ];
        }
        return array_values($pedidos);
    }

    public function marcarComoPronto(int $pedido_id, int $empresa_id): bool
    {
        $sql = "
            UPDATE pedidos
            SET status = 'pronto', data_pronto = NOW()
            WHERE id = :pedido_id AND empresa_id = :empresa_id AND status = 'em_preparo';
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':pedido_id' => $pedido_id, ':empresa_id' => $empresa_id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Erro no Model (PDO) ao marcar pedido como pronto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ATUALIZAÇÃO DEFINITIVA: Busca as MESAS que têm pedidos prontos.
     * GARANTE que a mesa ainda está 'ocupada' e que apenas UMA notificação por mesa é mostrada.
     */
    public function buscarPedidosProntosPorEmpresa(int $empresa_id): array
    {
        $sql = "
            SELECT
                m.id AS mesa_id,
                m.numero AS mesa_numero
            FROM pedidos p
            JOIN mesas m ON p.mesa_id = m.id
            WHERE p.empresa_id = :empresa_id 
              AND p.status = 'pronto'
              AND m.status = 'ocupada' -- <-- A GARANTIA DE SEGURANÇA
            GROUP BY m.id, m.numero -- <-- A SOLUÇÃO PARA CONSOLIDAR AS NOTIFICAÇÕES
            ORDER BY MIN(p.data_pronto) ASC; -- Ordena pela notificação mais antiga
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':empresa_id' => $empresa_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro no Model (PDO) ao buscar pedidos prontos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * CORREÇÃO: Este método estava com vários bugs e foi completamente reescrito.
     */
    public function criarNovoPedido(int $mesa_id, array $itens, int $funcionario_id, int $empresa_id): int
    {
        try {
            $this->pdo->beginTransaction();

            $valorTotal = 0.0;
            $itensParaInserir = []; 
            
            $sql_preco = "SELECT preco, nome FROM cardapio_itens WHERE id = :item_id AND empresa_id = :empresa_id";
            $stmt_preco = $this->pdo->prepare($sql_preco);

            foreach ($itens as $item) {
                $stmt_preco->execute([':item_id' => $item['id'], ':empresa_id' => $empresa_id]);
                $item_info = $stmt_preco->fetch(PDO::FETCH_ASSOC);
                
                if (!$item_info) { throw new Exception("O item com ID {$item['id']} não foi encontrado."); }
                
                $preco_unitario = (float) $item_info['preco'];
                $quantidade = (int) $item['quantidade'];
                
                $valorTotal += $preco_unitario * $quantidade;
                $itensParaInserir[] = ['item_id' => $item['id'], 'quantidade' => $quantidade, 'preco' => $preco_unitario];
            }
            
            $sql_pedido = "INSERT INTO pedidos (empresa_id, mesa_id, funcionario_id, status, data_abertura, valor_total) VALUES (?, ?, ?, 'em_preparo', NOW(), ?)";
            $stmt_pedido = $this->pdo->prepare($sql_pedido);
            $stmt_pedido->execute([$empresa_id, $mesa_id, $funcionario_id, $valorTotal]);
            $pedido_id = $this->pdo->lastInsertId();
            
            if (!$pedido_id) { throw new Exception("Falha ao criar o novo pedido."); }
            
            $sql_itens = "INSERT INTO pedido_itens (pedido_id, item_id, quantidade, preco_unitario_momento) VALUES (:pedido_id, :item_id, :quantidade, :preco);";
            $stmt_itens = $this->pdo->prepare($sql_itens);
            foreach ($itensParaInserir as $item) {
                $stmt_itens->execute($item + [':pedido_id' => $pedido_id]);
            }
            
            $mesaModel = new Mesa($this->pdo);
            $mesaModel->atualizarStatus($mesa_id, 'ocupada');
            
            $this->pdo->commit();
            return (int)$pedido_id;

        } catch (Exception $e) { 
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            error_log("Erro no Model ao criar pedido: " . $e->getMessage()); 
            throw $e; 
        }
    }

    /**
     * CORREÇÃO: Este método estava com um bug crítico (nome de variável errado) e foi otimizado.
     */
    public function buscarItensDoUltimoPedidoDaMesa(int $mesa_id, int $empresa_id): ?array
    {
        $sql = "
            SELECT 
                p.id, p.status, p.data_abertura,
                pi.quantidade, pi.preco_unitario_momento, 
                ci.nome AS item_nome
            FROM pedidos p
            JOIN pedido_itens pi ON p.id = pi.pedido_id
            JOIN cardapio_itens ci ON pi.item_id = ci.id
            WHERE p.id = (
                SELECT MAX(p_sub.id) FROM pedidos p_sub 
                WHERE p_sub.mesa_id = :mesa_id 
                  AND p_sub.empresa_id = :empresa_id 
                  AND p_sub.status IN ('em_preparo', 'pronto', 'entregue')
            )
            ORDER BY ci.nome ASC;
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':mesa_id' => $mesa_id, ':empresa_id' => $empresa_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($results)) { return null; }

        $pedidoCompleto = [
            'id' => $results[0]['id'],
            'hora' => date('H:i', strtotime($results[0]['data_abertura'])),
            'status' => $results[0]['status'],
            'itens' => [],
            'total' => 0
        ];
        
        foreach ($results as $row) {
            $pedidoCompleto['itens'][] = [
                'nome' => $row['item_nome'],
                'quantidade' => $row['quantidade'],
                'preco_unitario' => $row['preco_unitario_momento']
            ];
            $pedidoCompleto['total'] += $row['quantidade'] * $row['preco_unitario_momento'];
        }

        return $pedidoCompleto;
    }

    /**
     * CORREÇÃO: O método antigo 'marcarComoEntregue' estava completamente errado.
     * Ele foi dividido em dois novos métodos para clareza e funcionalidade correta.
     */

    /**
     * NOVO MÉTODO (A SOLUÇÃO): Marca TODOS os pedidos 'pronto' de uma MESA como 'entregue'.
     */
    public function marcarPedidosDaMesaComoEntregues(int $mesa_id, int $empresa_id): bool
    {
        $sql = "UPDATE pedidos
                SET status = 'entregue'
                WHERE mesa_id = :mesa_id
                  AND empresa_id = :empresa_id
                  AND status = 'pronto'";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':mesa_id' => $mesa_id, ':empresa_id' => $empresa_id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Erro ao marcar pedidos da mesa como entregues: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Altera o estado de todos os pedidos 'pronto' de uma mesa para 'arquivado'.
     */
    public function arquivarPedidosProntosDeMesa(int $mesa_id): bool
    {
        try {
            $sql = "UPDATE pedidos SET status = 'arquivado' WHERE mesa_id = :mesa_id AND status = 'pronto'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':mesa_id' => $mesa_id]);
            return true;
        } catch (PDOException $e) {
            error_log("Erro ao arquivar pedidos prontos: " . $e->getMessage());
            return false;
        }
    }
}