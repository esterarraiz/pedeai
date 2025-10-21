<?php
// Arquivo: app/Controllers/GarcomDashboardController.php

namespace App\Controllers;

use App\Core\Controller;

class GarcomDashboardController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        
        if ($_SESSION['user_cargo'] !== 'garçom') {
            header('Location: /acesso-negado');
            exit;
        }
    }

    /**
     * Carrega o novo dashboard interativo do garçom, que consome a API.
     */
    public function index()
    {
        // AGORA ELE CHAMA O NOVO ARQUIVO QUE VOCÊ CRIOU
        $this->loadView('garcom/dashboard_api', [
            'pageTitle' => 'Dashboard do Garçom'
        ]);
    }
}