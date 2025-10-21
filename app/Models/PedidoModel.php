<?php

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
     * ATUALIZAÇÃO FINAL: Busca pedidos prontos para notificar o garçom,
     * GARANTINDO que a mesa associada ainda está com o status 'ocupada'.
     */
    public function buscarPedidosProntosPorEmpresa(int $empresa_id): array
    {
        $sql = "
            SELECT
                p.id AS pedido_id,
                m.numero AS mesa_numero
            FROM pedidos p
            JOIN mesas m ON p.mesa_id = m.id
            WHERE p.empresa_id = :empresa_id 
              AND p.status = 'pronto'
              AND m.status = 'ocupada' -- <-- ESTA É A GARANTIA DE SEGURANÇA
            ORDER BY p.data_pronto ASC;
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

    public function criarNovoPedido(int $mesa_id, array $itens, int $funcionario_id, int $empresa_id): int
    {
        try {
            $this->pdo->beginTransaction();

            $valorTotal = 0.0;
            $itensParaInserir = []; 
            
            $sql_preco = "SELECT preco FROM cardapio_itens WHERE id = :item_id AND empresa_id = :empresa_id";
            $stmt_preco = $this->pdo->prepare($sql_preco);

            foreach ($itens as $item) {
                $stmt_preco->execute([':item_id' => $item['id'], ':empresa_id' => $empresa_id]);
                $item_info = $stmt_preco->fetch(PDO::FETCH_ASSOC);
                
                if (!$item_info) { throw new Exception("O item com ID {$item['id']} não foi encontrado no cardápio."); }
                
                $preco_unitario = (float) $item_info['preco'];
                $quantidade = (int) $item['quantidade'];
                
                $valorTotal += $preco_unitario * $quantidade;
                $itensParaInserir[] = ['item_id' => $item['id'], 'quantidade' => $quantidade, 'preco' => $preco_unitario];
            }
            
            $sql_pedido = "INSERT INTO pedidos (empresa_id, mesa_id, funcionario_id, status, data_abertura, valor_total) VALUES (?, ?, ?, 'em_preparo', NOW(), ?)";
            $stmt_pedido = $this->pdo->prepare($sql_pedido);
            $stmt_pedido->execute([$empresa_id, $mesa_id, $funcionario_id, $valorTotal]);
            $pedido_id = $this->pdo->lastInsertId();
            
            if (!$pedido_id) { throw new Exception("Falha ao obter o ID do novo pedido criado."); }
            
            $sql_itens = "INSERT INTO pedido_itens (pedido_id, item_id, quantidade, preco_unitario_momento) VALUES (:pedido_id, :item_id, :quantidade, :preco);";
            $stmt_itens = $this->pdo->prepare($sql_itens);
            foreach ($itensParaInserir as $item) {
                $stmt_itens->execute([':pedido_id' => $pedido_id, ':item_id' => $item['item_id'], ':quantidade'=> $item['quantidade'], ':preco' => $item['preco']]);
            }
            
            $mesaModel = new Mesa($this->pdo);
            $mesaModel->atualizarStatus($mesa_id, 'ocupada');
            
            $this->pdo->commit();
            
            return (int)$pedido_id;

        } catch (Exception $e) { 
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Erro no Model ao criar pedido: " . $e->getMessage()); 
            throw $e; 
        }
    }

    public function buscarItensDoUltimoPedidoDaMesa(int $mesa_id, int $empresa_id): ?array
    {
        $sql_pedido = "
            SELECT p.id, p.status, p.data_abertura
            FROM pedidos p
            WHERE p.mesa_id = :mesa_id 
              AND p.empresa_id = :empresa_id
              AND p.status IN ('em_preparo', 'pronto', 'entregue')
            ORDER BY p.id DESC
            LIMIT 1;
        ";
        $stmt_pedido = $this->pdo->prepare($sql_pedido);
        $stmt_pedido->execute([':mesa_id' => $mesa_id, ':empresa_id' => $empresa_id]);
        $pedido = $stmt_pedido->fetch(PDO::FETCH_ASSOC);

        if (!$pedido) { return null; }

        $sql_itens = "
            SELECT pi.quantidade, pi.preco_unitario_momento, ci.nome AS item_nome
            FROM pedido_itens pi
            JOIN cardapio_itens ci ON pi.item_id = ci.id
            WHERE pi.pedido_id = :pedido_id
            ORDER BY ci.nome ASC;
        ";
        $stmt_itens = $this->pdo->prepare($sql_itens);
        $stmt_itens->execute([':pedido_id' => $pedido['id']]);
        $itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);
        
        $total = 0;
        foreach ($itens as $item) {
            $total += $item['quantidade'] * $item['preco_unitario_momento'];
        }

        return [ 
            'id' => $pedido['id'], 
            'hora' => date('H:i', strtotime($pedido['data_abertura'])), 
            'status' => $pedido['status'], 
            'itens' => $itens, 
            'total' => $total 
        ];
    }

    public function marcarComoEntregue(int $pedido_id, int $empresa_id): bool
    {
        $sql = "UPDATE pedidos 
                SET status = 'entregue' 
                WHERE id = :pedido_id 
                  AND empresa_id = :empresa_id 
                  AND status = 'pronto'";
                  
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':pedido_id' => $pedido_id, ':empresa_id' => $empresa_id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Altera o estado de todos os pedidos 'pronto' de uma mesa para 'arquivado'.
     * Isto é útil para limpar notificações pendentes quando uma mesa é paga e liberada.
     */
    public function arquivarPedidosProntosDeMesa(int $mesa_id): bool
    {
        // O código de diagnóstico "die(...)" foi removido daqui.
        try {
            $sql = "UPDATE pedidos 
                    SET status = 'arquivado' 
                    WHERE mesa_id = :mesa_id 
                      AND status = 'pronto'";
                      
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':mesa_id' => $mesa_id]);
            return true;
        } catch (PDOException $e) {
            error_log("Erro ao arquivar pedidos prontos: " . $e->getMessage());
            return false;
        }
    }
}