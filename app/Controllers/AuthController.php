<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Funcionario;
use Config\Database;

class AuthController extends Controller
{
    /**
     * Ação: Exibe a página de login (View)
     */
    public function showLogin()
    {
        // Exige que o usuário esteja DESLOGADO para ver esta página
        $this->requireLogout(); 
        
        $login_error = null;
        if (isset($_SESSION['login_error'])) {
            $login_error = $_SESSION['login_error'];
            unset($_SESSION['login_error']);
        }
        
        // Carrega a view de login
        $this->loadView('auth/login', ['login_error' => $login_error]);
    }

    /**
     * Ação: Processa a tentativa de login (POST do formulário)
     */
    public function processLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }

        $empresa_id = filter_input(INPUT_POST, 'empresa_id', FILTER_SANITIZE_NUMBER_INT);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $senha = $_POST['senha'];

        $pdo = Database::getConnection();
        $funcionarioModel = new Funcionario($pdo);
        $user = $funcionarioModel->validarLogin($empresa_id, $email, $senha);

        if ($user) {
            // Sucesso! Configura a sessão
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nome'] = $user['nome'];
            $_SESSION['user_cargo_id'] = $user['cargo_id']; // <-- IMPORTANTE: Armazena o ID do cargo
            $_SESSION['user_cargo_nome'] = $user['nome_cargo'];
            $_SESSION['empresa_id'] = $user['empresa_id'];
            
            session_regenerate_id(true); 

            // --- LÓGICA DE REDIRECIONAMENTO POR CARGO ---
            $cargo_id = $user['cargo_id'];
            
            switch ($cargo_id) {
                case 1: // Administrador
                    header('Location: /dashboard/admin');
                    break;
                case 2: // Garçom
                    header('Location: /dashboard/garcom');
                    break;
                case 3: // Caixa
                    header('Location: /dashboard/caixa');
                    break;
                case 4: // Cozinheiro (assumindo ID 4)
                    header('Location: /dashboard/cozinheiro');
                    break;
                default:
                    // Se o cargo não for reconhecido, vai para uma página genérica ou de erro
                    header('Location: /dashboard/generico');
                    break;
            }
            exit;

        } else {
            // Falha
            $_SESSION['login_error'] = 'ID da empresa, e-mail ou senha incorretos.';
            header('Location: /login');
            exit;
        }
    }

    /**
     * Ação: Realiza o logout do usuário
     */
    public function logout()
    {
        session_unset();
        session_destroy();
        header('Location: /login?logout=success');
        exit;
    }

    /**
     * Ação: Helper para redirecionar a rota raiz (/) para o login
     */
    public function redirectToLogin()
    {
        header('Location: /login');
        exit;
    }
}

