<?php

namespace App\Models;

use PDO;
use PDOException;

class Funcionario
{
    private $db;

    public function __construct($pdo_connection)
    {
        $this->db = $pdo_connection;
    }

    /**
     * Valida o login do funcionário usando hash de senha.
     */
    public function validarLogin($empresa_id, $email, $senha)
    {
        try {
            // Preparamos a query para buscar o funcionário
            $sql = "
                SELECT f.*, c.nome_cargo 
                FROM funcionarios f
                JOIN cargos c ON f.cargo_id = c.id
                WHERE f.empresa_id = :empresa_id AND f.email = :email AND f.ativo = true
            "; // <-- AQUI ESTÁ A CORREÇÃO: f.ativo = true
            
            $stmt = $this->db->prepare($sql);
            
            // Usamos o método execute() com array, que lidou bem com o tipo int8
            $stmt->execute([
                'empresa_id' => $empresa_id,
                'email' => $email
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 1. Verifica se o usuário foi encontrado
            if ($user) {
                // 2. Verifica se a senha fornecida bate com o hash salvo no banco
                if (password_verify($senha, $user['senha'])) {
                    // Login bem-sucedido!
                    unset($user['senha']);
                    return $user; 
                }
            }

            // Se o usuário não foi encontrado ou a senha está incorreta
            return false;

        } catch (PDOException $e) {
            // Logar o erro para análise futura, sem expor ao usuário
            error_log('Erro no login: ' . $e->getMessage());
            return false;
        }
    }
    public function buscarTodosPorEmpresa(int $empresa_id): array
    {
        $sql = "
            SELECT f.id, f.nome, f.email, f.ativo, c.nome_cargo
            FROM funcionarios f
            JOIN cargos c ON f.cargo_id = c.id
            WHERE f.empresa_id = ?
            ORDER BY f.nome ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$empresa_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function buscarPorId(int $id)
    {
        $sql = "SELECT id, nome, email, cargo_id FROM funcionarios WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function criar(int $empresa_id, int $cargo_id, string $nome, string $email, string $senha): bool
    {
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $sql = "INSERT INTO funcionarios (empresa_id, cargo_id, nome, email, senha, ativo) VALUES (?, ?, ?, ?, ?, TRUE)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$empresa_id, $cargo_id, $nome, $email, $senha_hash]);
    }

    public function atualizar(int $id, int $cargo_id, string $nome, string $email, ?string $senha = null): bool
    {
        if (!empty($senha)) {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $sql = "UPDATE funcionarios SET cargo_id = ?, nome = ?, email = ?, senha = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$cargo_id, $nome, $email, $senha_hash, $id]);
        } else {
            $sql = "UPDATE funcionarios SET cargo_id = ?, nome = ?, email = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$cargo_id, $nome, $email, $id]);
        }
    }

    public function atualizarStatus(int $id, bool $novo_status): bool
    {
        $sql = "UPDATE funcionarios SET ativo = :status WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        // Vincula os parâmetros especificando seus tipos
        $stmt->bindParam(':status', $novo_status, PDO::PARAM_BOOL);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    public function redefinirSenha(int $id, string $nova_senha): bool
    {
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $sql = "UPDATE funcionarios SET senha = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$senha_hash, $id]);
    }
}

