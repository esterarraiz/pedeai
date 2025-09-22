<?php

namespace App\Models;

class PedidoModel
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
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
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

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
            // Inicia uma transação
            $this->pdo->beginTransaction();

            // 1. Insere o registro principal na tabela 'pedidos'
            $sql_pedido = "
                INSERT INTO pedidos (empresa_id, mesa_id, funcionario_id, status, data_abertura)
                VALUES (?, ?, ?, 'em_preparo', NOW());
            ";
            $stmt_pedido = $this->pdo->prepare($sql_pedido);
            $stmt_pedido->execute([$empresa_id, $mesa_id, $funcionario_id]);

            // Pega o ID do pedido que acabamos de criar
            $pedido_id = $this->pdo->lastInsertId();

            // 2. Prepara a query para inserir os itens do pedido
            $sql_itens = "
                INSERT INTO pedido_itens (pedido_id, item_id, quantidade, preco_unitario_momento)
                VALUES (:pedido_id, :item_id, :quantidade, :preco);
            ";
            $stmt_itens = $this->pdo->prepare($sql_itens);

            // 3. Itera sobre os itens e os insere na tabela 'pedido_itens'
            $sql_preco = "SELECT preco FROM cardapio_itens WHERE id = ?";
            $stmt_preco = $this->pdo->prepare($sql_preco);

            // 4. Itera sobre os itens, busca o preço e os insere na tabela 'pedido_itens'
            foreach ($itens as $item_id => $quantidade) {
            // Busca o preço atual do item no cardápio
            $stmt_preco->execute([$item_id]);
            $item_info = $stmt_preco->fetch(\PDO::FETCH_ASSOC);
            $preco_do_item = $item_info ? $item_info['preco'] : 0; // Pega o preço ou usa 0 se não encontrar

            // Executa a inserção com o preço
            $stmt_itens->execute([
                ':pedido_id' => $pedido_id,
                ':item_id' => $item_id,
                ':quantidade' => $quantidade,
                ':preco' => $preco_do_item // <-- Adiciona o preço aqui
            ]);
}

            // Se tudo deu certo, confirma a transação
            $this->pdo->commit();
            return true;

        } catch (\PDOException $e) {
    // Se algo deu errado, desfaz a transação
    $this->pdo->rollBack();

    // --- MODIFICAÇÃO TEMPORÁRIA PARA DEBUG ---
    // A linha abaixo vai parar o script e mostrar o erro exato.
    // Lembre-se de removê-la depois de resolver o problema!
    die("Erro no Banco de Dados: " . $e->getMessage());
    // --- FIM DA MODIFICAÇÃO ---

    // O ideal aqui é logar o erro: error_log($e->getMessage());
    return false;
        }
    }
}
