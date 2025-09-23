<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CardapioModel;
use App\Models\PedidoModel;
use Config\Database;

class PedidoController extends Controller
{
    /**
     * Ação: Exibe o formulário para criar um novo pedido para uma mesa.
     */
    public function showFormNovoPedido($params )
    {
        // Garante que o usuário (garçom) está logado
        // $this->requireLogin(); -- Descomente se tiver um método helper
        $mesa_id = $params['id'];
        $pdo = Database::getConnection();
        
        // Busca os itens do cardápio para exibir
        $cardapioModel = new CardapioModel($pdo);
        $empresa_id = $_SESSION['empresa_id']; // Pega o ID da empresa da sessão
        $cardapio = $cardapioModel->buscarItensAgrupados($empresa_id);

        // Carrega a view do formulário, passando os dados necessários
        $this->loadView('pedidos/novo', [
            'mesa_id' => $mesa_id,
            'cardapio' => $cardapio
        ]);
    }

    /**
     * Ação: Processa os dados do formulário de novo pedido.
     */
    public function processarNovoPedido()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /mesas'); // Redireciona se não for POST
            exit;
        }

        // Pega os dados do formulário e da sessão
        $mesa_id = filter_input(INPUT_POST, 'mesa_id', FILTER_SANITIZE_NUMBER_INT);
        $itens_pedido = $_POST['itens'] ?? []; // Array com [item_id => quantidade]
        
        $empresa_id = $_SESSION['empresa_id'];
        $funcionario_id = $_SESSION['user_id']; // ID do garçom logado

        // Filtra para pegar apenas os itens com quantidade maior que zero
        $itens_validos = array_filter($itens_pedido, fn($qtd) => is_numeric($qtd) && $qtd > 0);

        if (empty($itens_validos)) {
            // Se nenhum item foi adicionado, volta para o form com erro
            $_SESSION['error_message'] = "Nenhum item foi adicionado ao pedido.";
            header('Location: /pedidos/novo/' . $mesa_id);
            exit;
        }

        $pdo = Database::getConnection();
        $pedidoModel = new PedidoModel($pdo);

        // Chama o método para criar o pedido no banco
        $sucesso = $pedidoModel->criarNovoPedido($empresa_id, $mesa_id, $funcionario_id, $itens_validos);

        if ($sucesso) {
            $_SESSION['success_message'] = "Pedido lançado com sucesso!";
            header('Location: /mesas'); // Sucesso, volta para a tela de mesas
        } else {
            $_SESSION['error_message'] = "Erro ao lançar o pedido. Tente novamente.";
            header('Location: /pedidos/novo/' . $mesa_id); // Erro, volta para o formulário
        }
        exit;
    }
}