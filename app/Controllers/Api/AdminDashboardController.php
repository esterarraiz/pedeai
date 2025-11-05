<?php
// Ficheiro: app/Controllers/Api/AdminDashboardController.php (NOVO)

namespace App\Controllers\Api;

use App\Core\JsonController;
use App\Models\AdminDashboardModel;
use Config\Database;

class AdminDashboardController extends JsonController
{
    private AdminDashboardModel $dashboardModel;
    private ?int $empresa_id;

    public function __construct($route_params = [])
    {
        parent::__construct($route_params);
        $this->requireLoginApi();

        if ($_SESSION['user_cargo'] !== 'administrador') {
            $this->jsonError('Acesso negado.', 403);
        }

        $pdo = Database::getConnection();
        $this->dashboardModel = new AdminDashboardModel($pdo);
        $this->empresa_id = $_SESSION['empresa_id'];
    }

    /**
     * [GET] /api/admin/dashboard
     * Retorna todos os dados necessários para o dashboard do admin.
     */
    public function getDadosDashboard()
    {
        try {
            $metricas = $this->dashboardModel->getMetricas($this->empresa_id);
            $pedidos_recentes = $this->dashboardModel->getPedidosRecentes($this->empresa_id);

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