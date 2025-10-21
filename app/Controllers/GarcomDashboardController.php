<?php
// Ficheiro: app/Controllers/GarcomDashboardController.php (Versão Definitiva)

namespace App\Controllers;

use App\Core\Controller;

class GarcomDashboardController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        // ESTA LINHA É CRUCIAL:
        // Garante que apenas utilizadores logados podem aceder a este dashboard.
        $this->requireLogin();
    }

    public function index()
    {
        $this->loadView('garcom/dashboard_api', [
            'pageTitle' => 'Dashboard do Garçom'
        ]);
    }
}