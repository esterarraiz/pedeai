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

    /**
     * Busca os itens do último pedido 'em_preparo' de cada mesa para o dashboard da cozinha.
     */
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
            WHERE p.id IN (
                SELECT MAX(p_sub.id)
                FROM pedidos p_sub
                WHERE p_sub.empresa_id = :empresa_id
                  AND p_sub.status = 'em_preparo'
                GROUP BY p_sub.mesa_id
            )
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

    /**
     * Marca um pedido como pronto.
     */
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
     * Busca mesas com pedidos prontos para entrega.
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
              AND m.status = 'ocupada'
            GROUP BY m.id, m.numero
            ORDER BY MIN(p.data_pronto) ASC;
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
     * Busca os dados completos de um pedido por ID.
     * (NOVO)
     */
    public function buscarDetalhesPorPedidoId(int $pedido_id, int $empresa_id): ?array
    {
        $sql = "
            SELECT p.id AS pedido_id, p.status, p.data_abertura, pi.quantidade,
                   pi.preco_unitario_momento, ci.nome AS item_nome, m.numero as mesa_numero
            FROM pedidos p
            JOIN pedido_itens pi ON p.id = pi.pedido_id
            JOIN cardapio_itens ci ON pi.item_id = ci.id
            JOIN mesas m ON p.mesa_id = m.id
            WHERE p.id = :pedido_id AND p.empresa_id = :empresa_id
            ORDER BY ci.nome ASC;
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':pedido_id' => $pedido_id, ':empresa_id' => $empresa_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($results)) return null;

        $pedido = [
            'id' => $results[0]['pedido_id'],
            'mesa_numero' => $results[0]['mesa_numero'],
            'hora' => date('H:i', strtotime($results[0]['data_abertura'])),
            'status' => $results[0]['status'],
            'itens' => [],
            'total' => 0
        ];

        foreach ($results as $row) {
            $subtotal = $row['quantidade'] * $row['preco_unitario_momento'];
            $pedido['itens'][] = [
                'nome' => $row['item_nome'],
                'quantidade' => $row['quantidade'],
                'preco_unitario' => $row['preco_unitario_momento']
            ];
            $pedido['total'] += $subtotal;
        }

        return $pedido;
    }

    /**
     * Cria um novo pedido.
     */
    public function criarNovoPedido(int $empresa_id, int $mesa_id, int $funcionario_id, array $itens): int
    {
        try {
            $valorTotal = 0.0;
            $itensParaInserir = [];
            $sql_preco = "SELECT preco, nome FROM cardapio_itens WHERE id = :item_id AND empresa_id = :empresa_id";
            $stmt_preco = $this->pdo->prepare($sql_preco);
            
            foreach ($itens as $item_id => $quantidade) {
                if ($quantidade <= 0) continue;
                
                $stmt_preco->execute([':item_id' => $item_id, ':empresa_id' => $empresa_id]);
                $item_info = $stmt_preco->fetch(PDO::FETCH_ASSOC);
                
                if (!$item_info) throw new Exception("O item com ID {$item_id} não foi encontrado.");

                if (!is_numeric($item_info['preco'])) {
                    throw new Exception("O item '{$item_info['nome']}' está com um preço inválido.");
                }
                
                $preco_unitario = (float) $item_info['preco'];
                $valorTotal += $preco_unitario * $quantidade;

                $itensParaInserir[] = [
                    'item_id' => $item_id,
                    'quantidade' => $quantidade,
                    'preco' => $preco_unitario
                ];
            }
            
            $sql_pedido = "
                INSERT INTO pedidos (empresa_id, mesa_id, funcionario_id, status, data_abertura, valor_total)
                VALUES (?, ?, ?, 'em_preparo', NOW(), ?)
            ";
            $stmt_pedido = $this->pdo->prepare($sql_pedido);
            $stmt_pedido->execute([$empresa_id, $mesa_id, $funcionario_id, $valorTotal]);

            $pedido_id = $this->pdo->lastInsertId();
            if (!$pedido_id) throw new Exception("Falha ao obter o ID do novo pedido.");

            $sql_itens = "
                INSERT INTO pedido_itens (pedido_id, item_id, quantidade, preco_unitario_momento)
                VALUES (:pedido_id, :item_id, :quantidade, :preco)
            ";
            $stmt_itens = $this->pdo->prepare($sql_itens);

            foreach ($itensParaInserir as $item) {
                $stmt_itens->execute([
                    ':pedido_id' => $pedido_id,
                    ':item_id' => $item['item_id'],
                    ':quantidade' => $item['quantidade'],
                    ':preco' => $item['preco']
                ]);
            }
            
            return (int)$pedido_id;

        } catch (Exception $e) {
            error_log("Erro ao criar pedido: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Atualiza um pedido existente.
     * (NOVO & COMPLEXO)
     */
    public function atualizarPedido(int $pedido_id, int $empresa_id, array $novos_itens): bool
    {
        try {
            $this->pdo->beginTransaction();

            $valorTotal = 0.0;
            $itensParaInserir = [];

            $sql_preco = "
                SELECT preco, nome FROM cardapio_itens
                WHERE id = :item_id AND empresa_id = :empresa_id
            ";
            $stmt_preco = $this->pdo->prepare($sql_preco);

            foreach ($novos_itens as $item_id => $quantidade) {
                if ($quantidade <= 0) continue;

                $stmt_preco->execute([':item_id' => $item_id, ':empresa_id' => $empresa_id]);
                $item = $stmt_preco->fetch(PDO::FETCH_ASSOC);

                if (!$item) throw new Exception("Item ID {$item_id} inválido.");

                $preco_unit = (float)$item['preco'];
                $valorTotal += $preco_unit * $quantidade;

                $itensParaInserir[] = [
                    'item_id' => $item_id,
                    'quantidade' => $quantidade,
                    'preco' => $preco_unit
                ];
            }

            $this->pdo->prepare("DELETE FROM pedido_itens WHERE pedido_id = :pedido_id")
                      ->execute([':pedido_id' => $pedido_id]);

            if (!empty($itensParaInserir)) {
                $stmt_itens = $this->pdo->prepare("
                    INSERT INTO pedido_itens (pedido_id, item_id, quantidade, preco_unitario_momento)
                    VALUES (:pedido_id, :item_id, :quantidade, :preco)
                ");

                foreach ($itensParaInserir as $item) {
                    $stmt_itens->execute([
                        ':pedido_id' => $pedido_id,
                        ':item_id' => $item['item_id'],
                        ':quantidade' => $item['quantidade'],
                        ':preco' => $item['preco']
                    ]);
                }
            }

            $stmt_update = $this->pdo->prepare("
                UPDATE pedidos
                SET valor_total = ?, status = 'em_preparo'
                WHERE id = ? AND empresa_id = ?
            ");
            $stmt_update->execute([$valorTotal, $pedido_id, $empresa_id]);

            if ($stmt_update->rowCount() === 0) {
                throw new Exception("Pedido não encontrado ou não pode ser atualizado.");
            }

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log("Erro ao atualizar pedido: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Busca itens do último pedido ativo da mesa (com item_id para edição).
     */
    public function buscarItensDoUltimoPedidoDaMesa(int $mesa_id, int $empresa_id): ?array
    {
        $sql = "
            SELECT 
                p.id AS pedido_id, p.status, p.data_abertura, pi.quantidade,
                pi.preco_unitario_momento,
                ci.nome AS item_nome,
                ci.id AS item_id_cardapio_fk
            FROM pedidos p
            JOIN pedido_itens pi ON p.id = pi.pedido_id
            JOIN cardapio_itens ci ON pi.item_id = ci.id
            WHERE p.id = (
                SELECT MAX(p_sub.id)
                FROM pedidos p_sub
                WHERE p_sub.mesa_id = :mesa_id
                  AND p_sub.empresa_id = :empresa_id
                  AND p_sub.status IN ('em_preparo', 'entregue', 'pronto')
            )
            ORDER BY ci.nome ASC;
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':mesa_id' => $mesa_id, ':empresa_id' => $empresa_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($results)) return null;

        $pedido = [
            'id' => $results[0]['pedido_id'],
            'hora' => date('H:i', strtotime($results[0]['data_abertura'])),
            'status' => $results[0]['status'],
            'itens' => [],
            'total' => 0
        ];

        foreach ($results as $row) {
            $subtotal = $row['quantidade'] * $row['preco_unitario_momento'];
            $pedido['itens'][] = [
                'nome' => $row['item_nome'],
                'quantidade' => $row['quantidade'],
                'preco_unitario' => $row['preco_unitario_momento'],
                'item_id' => $row['item_id_cardapio_fk']
            ];
            $pedido['total'] += $subtotal;
        }

        return $pedido;
    }

    /**
     * Busca todos os pedidos ativos de uma mesa.
     */
    public function buscarPedidosPorMesa(int $mesa_id, int $empresa_id): array
    {
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
            WHERE p.mesa_id = :mesa_id 
              AND p.empresa_id = :empresa_id
              AND p.status IN ('em_preparo', 'pronto', 'entregue') 
            ORDER BY p.data_abertura ASC, ci.nome ASC;
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':mesa_id' => $mesa_id, ':empresa_id' => $empresa_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pedidos = [];
        foreach ($results as $row) {
            $pedido_id = $row['pedido_id'];

            if (!isset($pedidos[$pedido_id])) {
                $pedidos[$pedido_id] = [
                    'id' => $pedido_id,
                    'hora' => date('H:i', strtotime($row['data_abertura'])),
                    'status' => $row['status'],
                    'itens' => [],
                    'subtotal' => 0
                ];
            }

            $subtotal = $row['quantidade'] * $row['preco_unitario_momento'];

            $pedidos[$pedido_id]['itens'][] = [
                'nome' => $row['item_nome'],
                'quantidade' => $row['quantidade'],
                'preco_unitario' => $row['preco_unitario_momento'],
                'subtotal' => $subtotal
            ];

            $pedidos[$pedido_id]['subtotal'] += $subtotal;
        }

        return array_values($pedidos);
    }

    /**
     * Marca pedidos prontos como entregues.
     */
    public function marcarPedidosDaMesaComoEntregues(int $mesa_id, int $empresa_id): bool
    {
        $sql = "
            UPDATE pedidos
            SET status = 'entregue'
            WHERE mesa_id = :mesa_id
              AND empresa_id = :empresa_id
              AND status = 'pronto'
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':mesa_id' => $mesa_id, ':empresa_id' => $empresa_id]);
            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log("Erro ao marcar como entregue: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marca pedidos ativos como pagos.
     */
    public function marcarPedidosDaMesaComoPagos(int $mesa_id, int $empresa_id): bool
    {
        $sql = "
            UPDATE pedidos
            SET status = 'pago'
            WHERE mesa_id = :mesa_id
              AND empresa_id = :empresa_id
              AND status IN ('em_preparo', 'pronto', 'entregue')
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':mesa_id' => $mesa_id, ':empresa_id' => $empresa_id]);
            return true;

        } catch (PDOException $e) {
            error_log("Erro ao marcar como pago: " . $e->getMessage());
            return false;
        }
    }
}
