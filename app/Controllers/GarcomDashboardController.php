<?php
// Ficheiro: app/Controllers/GarcomDashboardController.php (Versão Final e Limpa)

namespace App\Controllers;

use App\Core\Controller;

class GarcomDashboardController extends Controller
{
    public function __construct()
    {
        // O construtor da classe pai (App\Core\Controller) agora trata do início da sessão
        // e da verificação do login.
        parent::__construct();
        $this->requireLogin();
        
        // Verifica se o utilizador logado tem o cargo correto
        if ($_SESSION['user_cargo'] !== 'garçom') {
            // Se não for um garçom, nega o acesso.
            header('Location: /acesso-negado'); // Pode criar uma view para isto se quiser
            exit;
        }
    }

    /**
     * Carrega o novo dashboard interativo do garçom, que consome a API.
     */
    public function index()
    {
        // Este controller agora tem a única responsabilidade de carregar a view
        // correta. Toda a lógica de dados é tratada pela API e pelo JavaScript.
        $this->loadView('garcom/dashboard_api', [
            'pageTitle' => 'Dashboard do Garçom'
        ]);
    }
}