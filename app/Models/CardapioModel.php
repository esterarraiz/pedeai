<?php

namespace App\Models;

use \PDO;
use \PDOException;

class CardapioModel
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // --- Métodos de Busca (Leitura) ---

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
                ci.imagem_url, -- Suporte a imagem
                c.nome AS categoria_nome,
                ci.categoria_id 
            FROM cardapio_itens ci
            JOIN categorias c ON ci.categoria_id = c.id
            WHERE ci.empresa_id = ? AND ci.disponivel = TRUE
            ORDER BY c.nome, ci.nome;
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$empresa_id]);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Agrupa os itens por categoria
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

    /**
     * Busca um único item pelo ID. Usado no Controller para verificar a URL da imagem antiga antes de deletar.
     */
    public function buscarItemPorId(int $id, int $empresa_id): array|false
    {
        $sql = "
            SELECT id, categoria_id, nome, descricao, preco, imagem_url, disponivel
            FROM cardapio_itens
            WHERE id = ? AND empresa_id = ?;
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id, $empresa_id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca todas as categorias de uma empresa.
     */
    public function buscarTodasCategorias(int $empresa_id): array
    {
        $sql = "SELECT id, nome FROM categorias WHERE empresa_id = ? ORDER BY nome ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$empresa_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // --- Métodos de Modificação de Itens ---

    /**
     * Cria um novo item no cardápio.
     */
    public function criarItem(array $dados): bool
    {
        $sql = "INSERT INTO cardapio_itens (empresa_id, categoria_id, nome, descricao, preco, imagem_url) 
                VALUES (:empresa_id, :categoria_id, :nome, :descricao, :preco, :imagem_url)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':empresa_id'   => $dados['empresa_id'],
            ':categoria_id' => $dados['categoria_id'],
            ':nome'         => $dados['nome'],
            ':descricao'    => $dados['descricao'],
            ':preco'        => $dados['preco'],
            ':imagem_url'   => $dados['imagem_url'] ?? null
        ]);
    }

    /**
     * Atualiza um item existente no cardápio.
     */
    public function atualizarItem(array $dados): bool
    {
        $sql = "UPDATE cardapio_itens SET
                    categoria_id = :categoria_id,
                    nome = :nome,
                    descricao = :descricao,
                    preco = :preco,
                    imagem_url = :imagem_url
                WHERE id = :id AND empresa_id = :empresa_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':categoria_id' => $dados['categoria_id'],
            ':nome'         => $dados['nome'],
            ':descricao'    => $dados['descricao'],
            ':preco'        => $dados['preco'],
            ':imagem_url'   => $dados['imagem_url'] ?? null, 
            ':id'           => $dados['id'],
            ':empresa_id'   => $dados['empresa_id']
        ]);
    }

    /**
     * Remove um item do cardápio.
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

    // ===============================================
    // == MÉTODOS PARA GERENCIAMENTO DE CATEGORIAS ==
    // ===============================================

    /**
     * Cria uma nova categoria.
     */
    public function criarCategoria(string $nome, int $empresa_id): bool
    {
        // NOTA: A Chave Estrangeira e a restrição UNIQUE no BD garantem que a empresa_id seja a correta.
        $sql = "INSERT INTO categorias (nome, empresa_id) VALUES (:nome, :empresa_id)";
        $stmt = $this->pdo->prepare($sql);
        
        try {
            return $stmt->execute([
                ':nome' => $nome,
                ':empresa_id' => $empresa_id
            ]);
        } catch (\PDOException $e) {
            // Re-lança a exceção para que o AdminCardapioController possa tratá-la
            throw $e;
        }
    }

    /**
     * Remove uma categoria.
     */
    public function removerCategoria(int $id, int $empresa_id): bool
    {
        $sql = "DELETE FROM categorias WHERE id = :id AND empresa_id = :empresa_id";
        $stmt = $this->pdo->prepare($sql);
        
        try {
            $stmt->execute([
                ':id' => $id,
                ':empresa_id' => $empresa_id
            ]);
            // Retorna verdadeiro se pelo menos uma linha foi afetada
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            // Re-lança a exceção para que o AdminCardapioController possa tratar o erro 1451 (Chave Estrangeira)
            throw $e;
        }
    }
}