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
        // ... (código existente, sem alterações)
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
        // ... (código existente, sem alterações)
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
                m.id AS mesa_id,      -- ID da mesa
                m.numero AS mesa_numero -- Número da mesa
            FROM pedidos p
            JOIN mesas m ON p.mesa_id = m.id
            WHERE p.empresa_id = :empresa_id 
              AND p.status = 'pronto'
              -- Opcional: Garante que só notifica se a mesa ainda estiver ocupada
              AND m.status = 'ocupada' 
            GROUP BY m.id, m.numero -- <-- Agrupa para ter uma linha por mesa
            ORDER BY MIN(p.data_pronto) ASC; -- Ordena pela notificação mais antiga primeiro
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

    public function criarNovoPedido(int $empresa_id, int $mesa_id, int $funcionario_id, array $itens): int
    {
        // ... (código existente, sem alterações)
        try {
            $valorTotal = 0.0; $itensParaInserir = [];
            $sql_preco = "SELECT preco, nome FROM cardapio_itens WHERE id = :item_id AND empresa_id = :empresa_id";
            $stmt_preco = $this->pdo->prepare($sql_preco);
            foreach ($itens as $item_id => $quantidade) {
                 if ($quantidade <= 0) continue;
                $stmt_preco->execute([':item_id' => $item_id, ':empresa_id' => $empresa_id]);
                $item_info = $stmt_preco->fetch(PDO::FETCH_ASSOC);
                if (!$item_info) { throw new Exception("O item com ID {$item_id} não foi encontrado."); }
                if (!isset($item_info['preco']) || !is_numeric($item_info['preco'])) { throw new Exception("O item '{$item_info['nome']}' está com um preço inválido.");}
                $preco_unitario = (float) $item_info['preco'];
                $valorTotal += $preco_unitario * $quantidade;
                $itensParaInserir[] = ['item_id' => $item_id, 'quantidade' => $quantidade, 'preco' => $preco_unitario ];
            }
            $sql_pedido = "INSERT INTO pedidos (empresa_id, mesa_id, funcionario_id, status, data_abertura, valor_total) VALUES (?, ?, ?, 'em_preparo', NOW(), ?) RETURNING id;";
            $stmt_pedido = $this->pdo->prepare($sql_pedido);
            $stmt_pedido->execute([$empresa_id, $mesa_id, $funcionario_id, $valorTotal]);
            $pedido_id = $stmt_pedido->fetchColumn();
            if (!$pedido_id) { throw new Exception("Falha ao obter o ID do novo pedido."); }
            $sql_itens = "INSERT INTO pedido_itens (pedido_id, item_id, quantidade, preco_unitario_momento) VALUES (:pedido_id, :item_id, :quantidade, :preco);";
            $stmt_itens = $this->pdo->prepare($sql_itens);
            foreach ($itensParaInserir as $item) {
                $stmt_itens->execute([':pedido_id' => $pedido_id, ':item_id' => $item['item_id'], ':quantidade'=> $item['quantidade'], ':preco' => $item['preco']]);
            }
            return (int)$pedido_id;
        } catch (PDOException $e) { error_log("Erro PDO ao criar pedido: " . $e->getMessage()); throw $e;
        } catch (Exception $e) { error_log("Erro Geral ao criar pedido: " . $e->getMessage()); throw $e; }
    }

    public function buscarItensDoUltimoPedidoDaMesa(int $mesa_id, int $empresa_id): ?array
    {
        // ... (código existente, sem alterações)
         $sql = "
            SELECT p.id AS pedido_id, p.status, p.data_abertura, pi.quantidade, pi.preco_unitario_momento, ci.nome AS item_nome
            FROM pedidos p
            JOIN pedido_itens pi ON p.id = pi.pedido_id
            JOIN cardapio_itens ci ON pi.item_id = ci.id
            WHERE p.id = ( SELECT MAX(p_sub.id) FROM pedidos p_sub WHERE p_sub.mesa_id = :mesa_id AND p_sub.empresa_id = :empresa_id AND p_sub.status IN ('em_preparo', 'entregue', 'pronto') )
            ORDER BY ci.nome ASC;
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':mesa_id' => $mesa_id, ':empresa_id' => $empresa_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($results)) { return null; }
        $ultimo_pedido = [ 'id' => $results[0]['pedido_id'], 'hora' => date('H:i', strtotime($results[0]['data_abertura'])), 'status' => $results[0]['status'], 'itens' => [], 'total' => 0 ];
        foreach ($results as $row) {
            $subtotal = $row['quantidade'] * $row['preco_unitario_momento'];
            $ultimo_pedido['itens'][] = [ 'nome' => $row['item_nome'], 'quantidade' => $row['quantidade'], 'preco_unitario' => $row['preco_unitario_momento'] ];
            $ultimo_pedido['total'] += $subtotal;
        }
        return $ultimo_pedido;
    }

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
            // Retorna true se pelo menos uma linha foi afetada (indica que havia pedidos prontos)
            return $stmt->rowCount() > 0; 
        } catch (PDOException $e) {
            error_log("Erro ao marcar pedidos da mesa como entregues: " . $e->getMessage());
            return false;
        }
    }
}

