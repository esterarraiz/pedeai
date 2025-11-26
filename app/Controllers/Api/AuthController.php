<?php

namespace App\Controllers\Api;

use App\Core\JsonController; 
use App\Models\Funcionario;
use Config\Database;
use Exception;
use PDO; // Adicionar o import

class AuthController extends JsonController
{
    // --- Métodos de Injeção (para teste) ---

    protected function getPdo(): PDO
    {
        return Database::getConnection();
    }

    protected function getFuncionarioModel(): Funcionario
    {
        // Agora o getFuncionarioModel também usa o getPdo
        return new Funcionario($this->getPdo());
    }

    /**
     * Inicia a sessão do usuário.
     * Extraído para permitir o mock.
     */
    protected function startUserSession(array $user): void
    {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nome'] = $user['nome'];
        $_SESSION['user_cargo'] = strtolower($user['nome_cargo']);
        $_SESSION['empresa_id'] = $user['empresa_id'];
        
        session_regenerate_id(true);
    }

    /**
     * Destrói a sessão do usuário.
     * Extraído para permitir o mock.
     */
    protected function destroyUserSession(): void
    {
        session_unset();
        session_destroy();
    }

    // --- Métodos da API ---

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
            // MUDANÇA: Usa o factory method
            $funcionarioModel = $this->getFuncionarioModel();
            $user = $funcionarioModel->validarLogin($empresa_id, $email, $senha);

            if (!$user) {
                throw new Exception("ID da empresa, e-mail ou senha incorretos.");
            }

            // MUDANÇA: Usa o método de sessão
            $this->startUserSession($user);

            // A lógica de URL usa o $user, pois o $_SESSION só estará 
            // disponível na *próxima* requisição.
            // (Na verdade, $_SESSION é global, então podemos usá-lo, mas é mais limpo usar a variável local $user)
            $cargo = strtolower($user['nome_cargo']);
            $cargoParaUrl = str_replace('ç', 'c', $cargo);
            
            $this->jsonResponse([
                'message' => 'Login bem-sucedido!',
                'redirectTo' => '/dashboard/' . $cargoParaUrl 
            ]);

        } catch (Exception $e) {
            $this->jsonResponse(['message' => $e->getMessage()], 401);
        }
    }

    public function logout()
    {
        // MUDANÇA: Usa o método de sessão
        $this->destroyUserSession();
        $this->jsonResponse(['message' => 'Logout efetuado com sucesso.']);
    }
}