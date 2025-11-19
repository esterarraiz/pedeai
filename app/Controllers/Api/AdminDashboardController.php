<?php
// Ficheiro: app/Controllers/Api/AdminDashboardController.php

namespace App\Controllers\Api;

use App\Core\JsonController;
use App\Models\AdminDashboardModel;
use Config\Database;
use PDO; // Importar PDO

class AdminDashboardController extends JsonController
{
    // private AdminDashboardModel $dashboardModel; // <-- REMOVIDO
    private ?int $empresa_id;

    public function __construct($route_params = [])
    {
        parent::__construct($route_params);
        $this->requireLoginApi();

        if ($_SESSION['user_cargo'] !== 'administrador') {
            $this->jsonError('Acesso negado.', 403);
        }

        // $pdo = Database::getConnection(); // <-- REMOVIDO
        // $this->dashboardModel = new AdminDashboardModel($pdo); // <-- REMOVIDO
        $this->empresa_id = $_SESSION['empresa_id'];
    }

    /**
     * Factory method para o AdminDashboardModel.
     * Permite que os testes injetem um mock.
     */
    protected function getDashboardModel(): AdminDashboardModel
    {
        // NOTA: Se você já tem um getPdo() em seu JsonController,
        // é melhor usá-lo. Caso contrário, isso funciona.
        $pdo = Database::getConnection(); 
        return new AdminDashboardModel($pdo);
    }

    /**
     * [GET] /api/admin/dashboard
     * Retorna todos os dados necessários para o dashboard do admin.
     */
    public function getDadosDashboard()
    {
        try {
            // MUDANÇA: Usa o factory method
            $model = $this->getDashboardModel();
            
            $metricas = $model->getMetricas($this->empresa_id);
            $pedidos_recentes = $model->getPedidosRecentes($this->empresa_id);

            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'metricas' => $metricas,
                    'pedidos_recentes' => $pedidos_recentes
                ]
            ]);
        } catch (\Exception $e) {
            $this->jsonError('Erro ao buscar dados do dashboard: ' . $e->getMessage(), 500);
        }
    }
    
}