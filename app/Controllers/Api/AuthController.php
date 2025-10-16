<?php

namespace App\Controllers\Api;

use App\Core\JsonController; 
use App\Models\Funcionario;
use Config\Database;
use Exception;

class AuthController extends JsonController
{
    public function login()
    {
        $data = $this->getJsonData();

        $empresa_id = filter_var($data['empresa_id'] ?? null, FILTER_SANITIZE_NUMBER_INT);
        $email = filter_var($data['email'] ?? null, FILTER_SANITIZE_EMAIL);
        $senha = $data['senha'] ?? null;

        if (!$empresa_id || !$email || !$senha) {
            return $this->jsonResponse(['message' => 'Todos os campos são obrigatórios.'], 400);
        }

        try {
            $pdo = Database::getConnection();
            $funcionarioModel = new Funcionario($pdo);
            $user = $funcionarioModel->validarLogin($empresa_id, $email, $senha);

            if (!$user) {
                throw new Exception("ID da empresa, e-mail ou senha incorretos.");
            }

            // Sucesso! Configura a sessão
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nome'] = $user['nome'];
            $_SESSION['user_cargo'] = strtolower($user['nome_cargo']);
            $_SESSION['empresa_id'] = $user['empresa_id'];
            
            session_regenerate_id(true);

            $cargoParaUrl = str_replace('ç', 'c', $_SESSION['user_cargo']);
            
            $this->jsonResponse([
                'message' => 'Login bem-sucedido!',
                'redirectTo' => '/dashboard/' . $cargoParaUrl // Usa a versão corrigida
            ]);

        } catch (Exception $e) {
            $this->jsonResponse(['message' => $e->getMessage()], 401);
        }
    }
    public function logout()
    {
        session_unset();
        session_destroy();
        $this->jsonResponse(['message' => 'Logout efetuado com sucesso.']);
    }
}

