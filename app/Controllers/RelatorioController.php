<?php


namespace App\Controllers;

use App\Core\Controller;

class RelatorioController extends Controller
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

    /**
     * Carrega a view principal de Relatórios de Vendas.
     * Os dados serão carregados via API (JavaScript).
     */
    public function index()
    {
        $this->loadView('relatorios/vendas', [
            'pageTitle'  => 'Relatórios de Vendas',
            'activePage' => 'relatorios' // Para marcar o item da sidebar como ativo
        ]);
    }
}