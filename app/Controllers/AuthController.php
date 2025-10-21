<?php
// Ficheiro: app/Controllers/AuthController.php (VERSÃO FINAL COM A CORREÇÃO DE TIPO)

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Funcionario;
use Config\Database;

class AuthController extends Controller
{
    public function showLogin()
    {
        $login_error = null;
        if (isset($_SESSION['login_error'])) {
            $login_error = $_SESSION['login_error'];
            unset($_SESSION['login_error']);
        }
        $this->loadView('auth/login', ['login_error' => $login_error]);
    }

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
            // --- CORREÇÃO FINAL APLICADA AQUI ---
            // Guardamos um booleano 'true' em vez do número 1.
            $_SESSION['logged_in'] = true; 
            // ------------------------------------
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nome'] = $user['nome'];
            $_SESSION['empresa_id'] = $user['empresa_id'];
            $_SESSION['user_cargo'] = strtolower($user['nome_cargo']); 
            session_regenerate_id(); 

            switch ($_SESSION['user_cargo']) {
                case 'administrador': header('Location: /dashboard/admin'); break;
                case 'garçom': header('Location: /dashboard/garcom'); break;
                case 'caixa': header('Location: /dashboard/caixa'); break;
                case 'cozinheiro': header('Location: /dashboard/cozinheiro'); break;
                default: header('Location: /dashboard/generico'); break;
            }
            exit;
        } else {
            $_SESSION['login_error'] = 'ID da empresa, e-mail ou senha incorretos.';
            header('Location: /login');
            exit;
        }
    }
    
    public function logout()
    {
        session_unset();
        session_destroy();
        header('Location: /login?logout=success');
        exit;
    }
}