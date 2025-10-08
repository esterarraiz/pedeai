<?php

namespace App\Models;

class CardapioModel
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Busca todos os itens do cardápio de uma empresa, agrupados por categoria.
     */
    public function buscarItensAgrupados(int $empresa_id): array
    {
        $sql = "
            SELECT
                ci.id,
                ci.nome,
                ci.descricao,
                ci.preco,
                c.nome AS categoria_nome
            FROM cardapio_itens ci
            JOIN categorias c ON ci.categoria_id = c.id
            WHERE ci.empresa_id = ? AND ci.disponivel = TRUE
            ORDER BY c.nome, ci.nome;
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$empresa_id]);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Agrupa os itens por categoria para facilitar a exibição na View
        $cardapio_agrupado = [];
        foreach ($results as $item) {
            $categoria = $item['categoria_nome'];
            if (!isset($cardapio_agrupado[$categoria])) {
                $cardapio_agrupado[$categoria] = [];
            }
            $cardapio_agrupado[$categoria][] = $item;
        }

        return $cardapio_agrupado;
    }
 public function buscarTodasCategorias(int $empresa_id): array
    {
        $sql = "SELECT id, nome FROM categorias WHERE empresa_id = ? ORDER BY nome ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$empresa_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * [NOVO] Cria um novo item no cardápio.
     */
    public function criarItem(array $dados): bool
    {
        $sql = "INSERT INTO cardapio_itens (empresa_id, categoria_id, nome, descricao, preco) 
                VALUES (:empresa_id, :categoria_id, :nome, :descricao, :preco)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':empresa_id'   => $dados['empresa_id'],
            ':categoria_id' => $dados['categoria_id'],
            ':nome'         => $dados['nome'],
            ':descricao'    => $dados['descricao'],
            ':preco'        => $dados['preco']
        ]);
    }

    /**
     * [NOVO] Atualiza um item existente no cardápio.
     */
    public function atualizarItem(array $dados): bool
    {
        $sql = "UPDATE cardapio_itens SET
                    categoria_id = :categoria_id,
                    nome = :nome,
                    descricao = :descricao,
                    preco = :preco
                WHERE id = :id AND empresa_id = :empresa_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':categoria_id' => $dados['categoria_id'],
            ':nome'         => $dados['nome'],
            ':descricao'    => $dados['descricao'],
            ':preco'        => $dados['preco'],
            ':id'           => $dados['id'],
            ':empresa_id'   => $dados['empresa_id']
        ]);
    }

    /**
     * [NOVO] Remove um item do cardápio.
     */
    public function removerItem(int $id, int $empresa_id): bool
    {
        $sql = "DELETE FROM cardapio_itens WHERE id = :id AND empresa_id = :empresa_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id'         => $id,
            ':empresa_id' => $empresa_id
        ]);
    }
}