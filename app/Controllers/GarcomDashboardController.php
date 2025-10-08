<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Mesa; // Importa o Model de Mesa
use Config\Database;

class GarcomDashboardController extends Controller
{
    public function index()
    {
        $pdo = Database::getConnection();

        // Usa o Model de Mesa
        $mesaModel = new Mesa($pdo);

        $empresa_id = $_SESSION['empresa_id'] ?? null;
        if (!$empresa_id) {
            // Se não estiver logado, redireciona para o login
            // (Assumindo que BASE_PATH está definido em algum lugar)
            header('Location: /login');
            exit;
        }
        
        // Busca todas as mesas da empresa
        $mesas = $mesaModel->buscarTodasPorEmpresa($empresa_id);

        // Carrega a nova view, passando a lista de mesas
        $this->loadView('garcom/mesaview', [
            'mesas' => $mesas
        ]);
    }
}