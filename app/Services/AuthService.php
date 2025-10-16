<?php

namespace App\Services;

use App\Models\Funcionario;
use Config\Database;
use Exception;

class AuthService
{
    private Funcionario $funcionarioModel;

    public function __construct()
    {
        $pdo = Database::getConnection();
        $this->funcionarioModel = new Funcionario($pdo);
    }

    /**
     * Tenta autenticar um utilizador e configurar a sessão.
     * @return array Retorna os dados do utilizador em caso de sucesso.
     * @throws Exception Se a autenticação falhar.
     */
    public function attemptLogin(int $empresa_id, string $email, string $senha): array
    {
        $user = $this->funcionarioModel->validarLogin($empresa_id, $email, $senha);

        if (!$user) {
            throw new Exception("ID da empresa, e-mail ou senha incorretos.");
        }

        // Configura a sessão
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nome'] = $user['nome'];
        $_SESSION['user_cargo'] = strtolower($user['nome_cargo']);
        $_SESSION['empresa_id'] = $user['empresa_id'];
        
        session_regenerate_id(true);

        return $user;
    }

    /**
     * Faz o logout do utilizador, destruindo a sessão.
     */
    public function logout(): void
    {
        session_unset();
        session_destroy();
    }
}
