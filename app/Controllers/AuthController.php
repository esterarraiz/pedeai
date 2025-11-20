<?php
// Ficheiro: app/Controllers/AuthController.php (Versão Final para API)

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Funcionario;
use Config\Database;

class AuthController extends Controller
{
    /**
     * Mostra a página de login.
     */
    public function showLogin()
    {
        $this->loadView('auth/login');
    }

    /**
     * Processa a tentativa de login (chamado via JavaScript).
     */
    public function processLogin()
    {
        header('Content-Type: application/json');

        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            $empresa_id = filter_var($data['empresa_id'] ?? null, FILTER_SANITIZE_NUMBER_INT);
            $email = filter_var($data['email'] ?? null, FILTER_SANITIZE_EMAIL);
            $senha = $data['senha'] ?? null;

            if (empty($empresa_id) || empty($email) || empty($senha)) {
                throw new \Exception("Todos os campos são obrigatórios.");
            }

            $pdo = Database::getConnection();
            $funcionarioModel = new Funcionario($pdo);
            $user = $funcionarioModel->validarLogin($empresa_id, $email, $senha);

            if ($user) {
                // Login bem-sucedido: cria a sessão
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nome'] = $user['nome'];
                $_SESSION['empresa_id'] = $user['empresa_id'];
                $_SESSION['user_cargo'] = strtolower($user['nome_cargo']);
                session_regenerate_id(true);

                // Define para qual URL o JavaScript deve redirecionar
                $redirectTo = '/';
                switch ($_SESSION['user_cargo']) {
                    case 'administrador': $redirectTo = '/dashboard/admin'; break;
                    case 'garçom': $redirectTo = '/dashboard/garcom'; break;
                    case 'caixa': $redirectTo = '/dashboard/caixa'; break;
                    case 'cozinheiro': $redirectTo = '/dashboard/cozinheiro'; break;
                }
                
                // Envia a resposta de sucesso em JSON
                echo json_encode(['success' => true, 'redirectTo' => $redirectTo]);
                exit;

            } else {
                // Login falhou: lança um erro
                throw new \Exception("ID da empresa, e-mail ou senha incorretos.");
            }

        } catch (\Exception $e) {
            // Em caso de qualquer erro, envia uma resposta de erro em JSON
            http_response_code(401); // 401 Unauthorized é mais apropriado para falha de login
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * Efetua o logout do utilizador.
     */
    public function logout()
    {
        session_unset();
        session_destroy();
        header('Location: /login?logout=success');
        exit;
    }
}