<?php

namespace App\Controllers;

use App\Core\Controller;

class CaixaDashboardController extends Controller
{
    /**
     * Apenas carrega a view principal do caixa.
     * Os dados serão carregados via JavaScript (API).
     */
    public function index()
    {
        $this->loadView('/caixa/index', [
            'activePage' => 'mesas'
        ]);
    }
}