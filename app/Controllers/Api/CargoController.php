<?php

namespace App\Controllers\Api;

use App\Core\JsonController;
use App\Models\CargoModel; // Assume que o seu model se chama CargoModel
use Config\Database;
use Exception;

class CargoController extends JsonController
{
    /**
     * Endpoint: GET /api/cargos
     * Lista todos os cargos.
     */
    public function listar()
    {
        try {
            $pdo = Database::getConnection();
            $cargoModel = new CargoModel($pdo); // Verifique o nome do seu Model
            $cargos = $cargoModel->buscarTodos();
            $this->jsonResponse($cargos);
        } catch (Exception $e) {
            $this->jsonResponse(['message' => $e->getMessage()], 500);
        }
    }
}

