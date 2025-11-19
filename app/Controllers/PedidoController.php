<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CardapioModel; // Assume que este Model existe
use Config\Database;

class PedidoController extends Controller
{
    /**
     * Ação: Apenas exibe a página (view) para lançar um novo pedido.
     * A view conterá o JavaScript para buscar o cardápio e enviar o pedido via API.
     */
    public function showFormNovoPedido($params)
    {
        $mesaId = $params['id'] ?? null;
        if (!$mesaId || !is_numeric($mesaId)) {
            // Redireciona se o ID da mesa for inválido
            header('Location: /dashboard/garcom?status=mesa_invalida');
            exit;
        }

        try {
            // Poderia buscar o cardápio aqui OU deixar o JavaScript buscar via API
            $pdo = Database::getConnection();
            $empresa_id = $_SESSION['empresa_id'] ?? null;
            if (!$empresa_id) { throw new \Exception("ID da empresa não encontrado na sessão."); }
            
            // Exemplo buscando cardápio no PHP (pode ser feito via API no JS também)
            $cardapioModel = new CardapioModel($pdo); 
            $itensCardapio = $cardapioModel->buscarItensAgrupados($empresa_id); 

            $this->loadView('pedidos/novo', [
                'mesa_id'   => (int)$mesaId,
                'cardapio'  => $itensCardapio // Passa o cardápio para a view inicial
                // Não precisa mais de 'pageTitle' se o título for estático na view
            ]);

        } catch (\Exception $e) {
            error_log("Erro em showFormNovoPedido: " . $e->getMessage());
            $this->loadView('error/500', ['message' => 'Não foi possível carregar a página de pedidos.']);
        }
    }

}
