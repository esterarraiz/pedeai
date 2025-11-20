<?php

namespace App\Controllers;

use App\Core\Controller;
// Os Models (Funcionario, CargoModel) não são mais necessários aqui
use Config\Database;

class FuncionarioController extends Controller
{
    /**
     * Exibe a página (casca) principal de gerenciamento de funcionários.
     * O JavaScript fará o fetch dos dados.
     */
    public function index()
    {
        $this->loadView('funcionarios/index', [
            'activePage' => 'funcionarios' // Define a página ativa
        ]);
    }

    /**
     * Exibe a página (casca) do formulário de criação.
     * O JavaScript irá buscar os cargos e submeter o formulário.
     */
    public function showCreateForm()
    {
        $this->loadView('funcionarios/form', [
            'activePage' => 'funcionarios',
            'funcionario_id' => null // Indica ao JS que é modo de criação
        ]);
    }

    /**
     * Exibe a página (casca) do formulário de edição.
     * O ID é passado para o JavaScript através dos dados da view.
     */
    public function showEditForm($params)
    {
        $this->loadView('funcionarios/form', [
            'funcionario_id' => $params['id'] ?? null, // Passa o ID para o JS
            'activePage' => 'funcionarios'
        ]);
    }
    
    // Os métodos create(), update(), toggleStatus(), redefinirSenha() foram REMOVIDOS daqui.
    // A sua lógica agora reside em Api\FuncionarioController.
}

