<?php
// Caminho: app/Models/DetalhesEmpresaModel.php
// (Este é o seu 'NovaEmpresaModel.php', RENOMEADO e CORRIGIDO)

namespace App\Models;

use PDO;
use Exception;
use PDOException;

class DetalhesEmpresasModel // <-- 1. Nome da CLASSE atualizado
{
    private $db;
    
    // 2. Nome da TABELA atualizado para o correto
    protected $table = 'detalhes_empresas'; 

    public function __construct(PDO $pdo_connection)
    {
        $this->db = $pdo_connection;
    }

    /**
     * Cria os dados detalhados da empresa.
     */
    public function create(int $empresa_id, array $dados): bool
    {
        $senha_hash = password_hash($dados['senha'], PASSWORD_DEFAULT);

        $sql = "INSERT INTO {$this->table} 
                  (empresa_id, cnpj, nome_proprietario, email, telefone, endereco, senha) 
                VALUES 
                  (:empresa_id, :cnpj, :nome_proprietario, :email, :telefone, :endereco, :senha)";
        
        try {
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                ':empresa_id'        => $empresa_id,
                ':cnpj'              => $dados['cnpj'],
                ':nome_proprietario' => $dados['nome_proprietario'],
                ':email'             => $dados['email'],
                ':telefone'          => $dados['telefone'],
                ':endereco'          => $dados['endereco'],
                ':senha'             => $senha_hash
            ]);

        } catch (PDOException $e) {
            throw $e; 
        }
    }

    /**
     * Busca um registro pelo email.
     */
    public function findByEmail(string $email)
    {
        $sql = "SELECT id, email, senha FROM {$this->table} WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca um registro pelo CNPJ.
     */
    public function findByCnpj(string $cnpj)
    {
        $sql = "SELECT id, cnpj FROM {$this->table} WHERE cnpj = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cnpj]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}