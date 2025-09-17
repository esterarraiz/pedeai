<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Mesa;
use Config\Database;

class MesaController extends Controller
{

    public function index()
    {

        $pdo = Database::getConnection();


        $mesaModel = new Mesa($pdo);


        $empresa_id = $_SESSION['empresa_id'] ?? null;
        $mesas = $mesaModel->buscarTodasPorEmpresa($empresa_id);


         $this->loadView('mesaview', [
         'mesas' => $mesas
            ]);
    }
}