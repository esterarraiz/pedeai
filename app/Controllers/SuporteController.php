<?php

namespace App\Controllers;

use App\Core\Controller;

class SuporteController extends Controller
{
    /**
     * Carrega a view principal de Suporte/Ajuda.
     */
    public function index()
    {
        // Se desejar que a página de suporte seja acessível apenas por usuários logados,
        // adicione $this->requireLogin(); aqui.
        
        $this->loadView('suporte/ajuda', [
            'pageTitle' => 'Suporte e Ajuda'
        ]);
    } // <--- CHAVE DE FECHAMENTO ADICIONADA AQUI

    /**
     * Carrega a view de Dúvidas Frequentes (FAQ).
     */
    public function showFaq()
    {
        $this->loadView('suporte/faq', [
            'pageTitle' => 'Dúvidas Frequentes'
        ]);
    }
}
// Removida a chave } extra no final, que era o fechamento do } da linha 22