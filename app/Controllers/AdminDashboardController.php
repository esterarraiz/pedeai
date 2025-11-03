<?php

namespace App\Controllers;

use App\Core\Controller;


class AdminDashboardController extends Controller
{

    public function __construct($route_params = [])
    {
        parent::__construct($route_params); 
        
        $this->requireLogin();
        
        if ($_SESSION['user_cargo'] !== 'administrador') {
            header('Location: /acesso-negado');
            exit;
        }
    }

    public function index()
    {
        $this->loadView('dashboard/admin', ['pageTitle' => 'Dashboard Administrador']);
    }

    public function gerenciarCardapio()
    {

        $this->loadView('admin/cardapio', [
            'pageTitle'  => 'Gerenciar Cardápio',
            // (REMOVIDO) Não passamos mais 'cardapio' ou 'categorias'
            'activePage' => 'cardapio'
        ]);
    }


}