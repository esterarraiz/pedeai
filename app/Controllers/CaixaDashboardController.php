<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Mesa;
use Config\Database;

class CaixaDashboardController extends Controller
{
    /**
     * Exibe o dashboard do caixa com as mesas que têm contas abertas.
     */
    public function index()
    {
        $pdo = Database::getConnection();
        $mesaModel = new Mesa($pdo);
        $empresa_id = $_SESSION['empresa_id'];
        
        // CORRIGIDO: Chama o método com o nome correto.
        $mesas = $mesaModel->buscarMesasComContaAberta($empresa_id);

        $this->loadView('/caixa/index', [
            'mesas' => $mesas,
            'activePage' => 'mesas'
        ]);
    }
}

