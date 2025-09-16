<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Mesa;
use Config\Database;

class MesaController extends Controller
{
    /**
     * Ação: Exibe a página de Gerenciamento de Mesas
     */
    public function index()
    {
        // Este método requireLogin() deve existir no seu Controller base
        // para garantir que apenas usuários logados acessem.
        // $this->requireLogin(); 

        // 1. Obter a conexão com o banco de dados
        $pdo = Database::getConnection();

        // 2. Instanciar o Model de Mesas
        $mesaModel = new Mesa($pdo);

        // 3. Buscar todas as mesas da empresa logada
        // O ID da empresa deve vir da sessão do usuário
        $empresa_id = $_SESSION['empresa_id'] ?? null;
        $mesas = $mesaModel->buscarTodasPorEmpresa($empresa_id);

        // 4. Carregar a View e passar os dados das mesas para ela
        // Carrega a view de login
        // Carrega a view de login
         $this->loadView('mesaview', [
         'mesas' => $mesas
            ]);
    }
}