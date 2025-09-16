<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Mesa;
use Config\Database;

class GarcomDashboardController extends Controller
{
    /**
     * Ação principal (index) do dashboard do garçom.
     * Esta função irá exibir a página de gerenciamento de mesas.
     */
    public function index()
    {
        // Futuramente, você pode adicionar uma verificação de segurança aqui
        // para garantir que apenas garçons acessem esta página.
        // Ex: $this->requireRole('Garcom');

        // 1. Pega a conexão com o banco de dados
        $pdo = Database::getConnection();

        // 2. Cria uma instância do Model de Mesas
        $mesaModel = new Mesa($pdo);

        // 3. Busca a lista de todas as mesas da empresa do usuário logado
        $empresa_id = $_SESSION['empresa_id'] ?? null;
        if (!$empresa_id) {
            // Lida com o caso de não encontrar a empresa na sessão (ex: redireciona para o login)
            header('Location: ' . BASE_PATH . '/login');
            exit;
        }
        $mesas = $mesaModel->buscarTodasPorEmpresa($empresa_id);

        // 4. Carrega o arquivo da View ('mesaview.php') e passa os dados das mesas para ele
        $this->loadView('mesaview', [
            'mesas' => $mesas
        ]);
    }
}