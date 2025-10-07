<?php

namespace App\Controllers;

use App\Core\Controller;
use Config\Database;

class AdminDashboardController extends Controller
{
    /**
     * Exibe a página principal do dashboard do administrador.
     */
    public function index()
    {
        $dadosParaView = [
        ];

        $this->loadView('dashboard/admin', $dadosParaView);
    }
}