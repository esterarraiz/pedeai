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

    public function criarNovoPedido(int $empresa_id, int $mesa_id, int $funcionario_id, array $itens): int
    {
        try {
            // --- ETAPA 1: Calcular o valor total e validar todos os itens PRIMEIRO ---
            $valorTotal = 0.0;
            $itensParaInserir = []; // Armazena os detalhes dos itens para a inserção posterior

            $sql_preco = "SELECT preco, nome FROM cardapio_itens WHERE id = ?";
            $stmt_preco = $this->pdo->prepare($sql_preco);

            foreach ($itens as $item_id => $quantidade) {
                $stmt_preco->execute([$item_id]);
                $item_info = $stmt_preco->fetch(PDO::FETCH_ASSOC);

                if (!$item_info) {
                    throw new Exception("O item com ID {$item_id} não foi encontrado no cardápio.");
                }

                if (!isset($item_info['preco']) || !is_numeric($item_info['preco'])) {
                    throw new Exception("O item '{$item_info['nome']}' (ID: {$item_id}) está com um preço inválido ou não definido no cardápio.");
                }
                
                $preco_unitario = (float) $item_info['preco'];
                $valorTotal += $preco_unitario * $quantidade;

                // Adiciona os detalhes do item ao array para a segunda fase
                $itensParaInserir[] = [
                    'item_id' => $item_id,
                    'quantidade' => $quantidade,
                    'preco' => $preco_unitario
                ];
            }

            // --- ETAPA 2: Inserir o registro principal na tabela 'pedidos' com o valor_total ---
            $sql_pedido = "
                INSERT INTO pedidos (empresa_id, mesa_id, funcionario_id, status, data_abertura, valor_total)
                VALUES (?, ?, ?, 'em_preparo', NOW(), ?)
                RETURNING id;
            ";
            $stmt_pedido = $this->pdo->prepare($sql_pedido);
            $stmt_pedido->execute([$empresa_id, $mesa_id, $funcionario_id, $valorTotal]);
            $pedido_id = $stmt_pedido->fetchColumn();

            if (!$pedido_id) {
                throw new Exception("Falha ao obter o ID do novo pedido criado.");
            }

            // --- ETAPA 3: Inserir os itens na tabela 'pedido_itens' ---
            $sql_itens = "
                INSERT INTO pedido_itens (pedido_id, item_id, quantidade, preco_unitario_momento)
                VALUES (:pedido_id, :item_id, :quantidade, :preco);
            ";
            $stmt_itens = $this->pdo->prepare($sql_itens);
            
            foreach ($itensParaInserir as $item) {
                $stmt_itens->execute([
                    ':pedido_id' => $pedido_id,
                    ':item_id'   => $item['item_id'],
                    ':quantidade'=> $item['quantidade'],
                    ':preco'     => $item['preco']
                ]);
            }

            return (int)$pedido_id;

        } catch (PDOException $e) {
            error_log("Erro no Model (PDO) ao criar pedido: " . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            error_log("Erro no Model (Geral) ao criar pedido: " . $e->getMessage());
            throw $e;
        }
    }
    public function buscarItensDoUltimoPedidoDaMesa(int $mesa_id, int $empresa_id): ?array
    {
        // Esta query usa uma sub-query para encontrar o ID (MAX(id)) do último pedido
        // ativo para a mesa especificada.
        $sql = "
            SELECT
                p.id AS pedido_id,
                p.status,
                p.data_abertura,
                pi.quantidade,
                pi.preco_unitario_momento,
                ci.nome AS item_nome
            FROM pedidos p
            JOIN pedido_itens pi ON p.id = pi.pedido_id
            JOIN cardapio_itens ci ON pi.item_id = ci.id
            WHERE p.id = (
                SELECT MAX(p_sub.id)
                FROM pedidos p_sub
                WHERE p_sub.mesa_id = :mesa_id 
                  AND p_sub.empresa_id = :empresa_id
                  AND p_sub.status IN ('em_preparo', 'entregue')
            )
            ORDER BY ci.nome ASC;
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':mesa_id' => $mesa_id, ':empresa_id' => $empresa_id]);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Se a query não retornar resultados, significa que não há pedido ativo.
        if (empty($results)) {
            return null;
        }

        // Como todos os itens são do mesmo pedido, podemos montar o resultado diretamente
        $ultimo_pedido = [
            'id' => $results[0]['pedido_id'],
            'hora' => date('H:i', strtotime($results[0]['data_abertura'])),
            'status' => $results[0]['status'],
            'itens' => [],
            'total' => 0
        ];

        foreach ($results as $row) {
            $subtotal_item = $row['quantidade'] * $row['preco_unitario_momento'];
            $ultimo_pedido['itens'][] = [
                'nome' => $row['item_nome'],
                'quantidade' => $row['quantidade'],
                'preco_unitario' => $row['preco_unitario_momento'],
            ];
            $ultimo_pedido['total'] += $subtotal_item;
        }

        return $ultimo_pedido;
    }
}
