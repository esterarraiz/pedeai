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
            WHERE p.empresa_id = ? AND p.status = 'em_preparo'
            ORDER BY p.data_abertura ASC, p.id, ci.nome;
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$empresa_id]);
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

    public function criarNovoPedido(int $empresa_id, int $mesa_id, int $funcionario_id, array $itens): bool
    {
        try {
            if (empty($itens)) {
                throw new Exception("Lista de itens do pedido está vazia.");
            }

            // 1. Insere o registro principal na tabela 'pedidos'
            $sql_pedido = "
                INSERT INTO pedidos (empresa_id, mesa_id, funcionario_id, status, data_abertura)
                VALUES (?, ?, ?, 'em_preparo', NOW());
            ";
            $stmt_pedido = $this->pdo->prepare($sql_pedido);
            if (!$stmt_pedido->execute([$empresa_id, $mesa_id, $funcionario_id])) {
                throw new Exception("Falha ao inserir o pedido principal: " . implode(" | ", $stmt_pedido->errorInfo()));
            }

            $pedido_id = $this->pdo->lastInsertId();

            // 2. Prepara a query para inserir os itens do pedido
            $sql_itens = "
                INSERT INTO pedido_itens (pedido_id, item_id, quantidade, preco_unitario_momento)
                VALUES (:pedido_id, :item_id, :quantidade, :preco);
            ";
            $stmt_itens = $this->pdo->prepare($sql_itens);

            $sql_preco = "SELECT preco FROM cardapio_itens WHERE id = ?";
            $stmt_preco = $this->pdo->prepare($sql_preco);

            foreach ($itens as $item_id => $quantidade) {
                $stmt_preco->execute([$item_id]);
                $item_info = $stmt_preco->fetch(PDO::FETCH_ASSOC);

                if (!$item_info) {
                    throw new Exception("Item de ID {$item_id} não encontrado no cardápio.");
                }

                $preco_do_item = $item_info['preco'];

                if (!$stmt_itens->execute([
                    ':pedido_id' => $pedido_id,
                    ':item_id' => $item_id,
                    ':quantidade' => $quantidade,
                    ':preco' => $preco_do_item
                ])) {
                    throw new Exception("Falha ao inserir item {$item_id}: " . implode(" | ", $stmt_itens->errorInfo()));
                }
            }

            return true;

        } catch (PDOException $e) {
            throw new Exception("Erro PDO no PedidoModel: " . $e->getMessage());
        }
    }
}
