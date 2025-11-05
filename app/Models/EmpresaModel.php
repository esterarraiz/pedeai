<?php

namespace App\Models;

use PDO;
use PDOException;

class EmpresaModel
{
    private $db;

    public function __construct(PDO $pdo_connection)
    {
        $this->db = $pdo_connection;
    }

    /**
     * Cria uma nova empresa (apenas o nome) e RETORNA O SEU ID.
     *
     * @param string $nome_empresa O nome do estabelecimento.
     * @return int|false Retorna o ID da nova empresa em sucesso, ou false em falha.
     */
    public function create(string $nome_empresa): int|false
    {
        // Usamos a tabela 'empresas' (a da sua imagem)
        $sql = "INSERT INTO empresas (nome_empresa) VALUES (:nome_empresa) RETURNING id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':nome_empresa' => $nome_empresa]);
            return $stmt->fetchColumn(); // Retorna o novo ID

        } catch (PDOException $e) {
            // Lança a exceção para o Controller (para o rollback)
            throw $e; 
        }
    }
}
