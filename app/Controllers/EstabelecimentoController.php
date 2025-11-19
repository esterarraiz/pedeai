<?php

namespace App\Controllers;

use App\Core\Controller; // Segue seu padrão

class EstabelecimentoController extends Controller
{
    /**
     * Construtor para proteger o controlador.
     */
    public function __construct()
    {
        parent::__construct();
        // Garante que apenas utilizadores logados podem aceder
        // (O Router já filtra para 'administrador', mas isso é uma dupla garantia)
        $this->requireLogin(); 
    }

    /**
     * Carrega a view principal de gerenciamento do estabelecimento.
     */
    public function index()
    {
        // Usa o método loadView, como no seu exemplo
        $this->loadView('admin/estabelecimento', [
            'pageTitle' => 'Meu Estabelecimento' // Usando pageTitle, como no seu exemplo
        ]);
    }
}