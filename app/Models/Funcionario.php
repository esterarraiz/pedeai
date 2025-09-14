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
}

