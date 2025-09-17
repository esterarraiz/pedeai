<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Mesa;
use Config\Database;

class GarcomDashboardController extends Controller
{

    public function index()
    {

        $pdo = Database::getConnection();


        $mesaModel = new Mesa($pdo);


        $empresa_id = $_SESSION['empresa_id'] ?? null;
        if (!$empresa_id) {


            header('Location: ' . BASE_PATH . '/login');
            exit;
        }
        $mesas = $mesaModel->buscarTodasPorEmpresa($empresa_id);


        $this->loadView('mesaview', [
            'mesas' => $mesas
        ]);
    }
}