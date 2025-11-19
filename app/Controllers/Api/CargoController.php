<?php

namespace App\Controllers\Api;

use App\Core\JsonController;
use App\Models\CargoModel;
use Config\Database;
use Exception;
use PDO; // Importe o PDO se o CargoModel precisar

class CargoController extends JsonController
{
    /**
     * Factory method para obter o CargoModel.
     * Isso facilita o mock durante os testes.
     */
    protected function getModel(): CargoModel
    {
        $pdo = Database::getConnection();
        return new CargoModel($pdo);
    }

    /**
     * Endpoint: GET /api/cargos
     * Lista todos os cargos.
     */
    public function listar()
    {
        try {
            // Usamos o factory method em vez de 'new' direto
            $cargoModel = $this->getModel();
            
            $cargos = $cargoModel->buscarTodos();
            $this->jsonResponse($cargos);
        } catch (Exception $e) {
            $this->jsonResponse(['message' => $e->getMessage()], 500);
        }
    }
}