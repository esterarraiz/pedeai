<?php

namespace App\Controllers;

use App\Core\Controller;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            $cargo = $_SESSION['user_cargo'] ?? 'generico';
            header('Location: /dashboard/' . $cargo);
            exit;
        }
        $this->loadView('auth/login');
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        header('Location: /login?logout=success');
        exit;
    }
    
}

