<?php

namespace App\Models;

use PDO;
use PDOException;

class NovaEmpresaModel
{
    private $db;

    public function __construct(PDO $pdo_connection)
    {
        $this->db = $pdo_connection;
    }

    /**
     * Cria uma nova empresa e RETORNA O SEU ID.
     *
     * @param array $dados Os dados vindo do formulário.
     * @return int|false Retorna o ID da nova empresa em sucesso, ou false em falha.
     */
    public function create(array $dados): int|false
    {
        $senha_hash = password_hash($dados['senha'], PASSWORD_DEFAULT);

        // --- MUDANÇA AQUI: Adicionado "RETURNING id" ---
        $sql = "INSERT INTO novas_empresas 
                    (cnpj, nome_proprietario, email, telefone, endereco, senha) 
                VALUES 
                    (:cnpj, :nome_proprietario, :email, :telefone, :endereco, :senha)
                RETURNING id"; 
        
        try {
            $stmt = $this->db->prepare($sql);
            
            $stmt->execute([
                ':cnpj'             => $dados['cnpj'],
                ':nome_proprietario' => $dados['nome_proprietario'],
                ':email'            => $dados['email'],
                ':telefone'         => $dados['telefone'],
                ':endereco'         => $dados['endereco'],
                ':senha'            => $senha_hash
            ]);

            // --- MUDANÇA AQUI: Retorna o ID que o "RETURNING id" nos deu ---
            return $stmt->fetchColumn();

        } catch (PDOException $e) {
            // Lança a exceção para que o Controller possa pegá-la (para o rollback)
            throw $e; 
        }
    }

    // ... (os métodos findByEmail e findByCnpj continuam iguais) ...
    
    public function findByEmail(string $email)
    {
        $sql = "SELECT id, email, senha FROM novas_empresas WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByCnpj(string $cnpj)
    {
        $sql = "SELECT id, cnpj FROM novas_empresas WHERE cnpj = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cnpj]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}