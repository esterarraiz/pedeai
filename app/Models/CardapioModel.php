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
}