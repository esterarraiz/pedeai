<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Mesa;
use Config\Database;

class CaixaController extends Controller
{
    /**
     * Mostra a página de resumo da conta.
     * Os dados serão carregados via JavaScript (API).
     */
    public function verConta($params)
    {
        $mesa_id = $params['id'] ?? null;
        if (!$mesa_id) {
            header('Location: /dashboard/caixa');
            exit;
        }

        try {
            $pdo = Database::getConnection();
            $mesaModel = new Mesa($pdo);
            $mesa = $mesaModel->buscarPorId($mesa_id);

            // Apenas carrega a view com o número da mesa.
            // O restante dos dados (pedido, total) será carregado via API.
            $this->loadView('caixa/resumo_conta', [
                'pageTitle' => 'Resumo da Conta - Mesa ' . $mesa['numero'],
                'mesa' => $mesa
            ]);
        } catch (\Exception $e) {
            $this->loadView('error', ['message' => $e->getMessage()]);
        }
    }
    
    // O método processarPagamento() foi completamente removido daqui
    // pois sua lógica agora está na CaixaApiController.
}