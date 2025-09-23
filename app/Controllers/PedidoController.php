<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Mesa;
use App\Models\PedidoModel;
use App\Models\CardapioModel;
use Config\Database;

class PedidoController extends Controller
{
    public function showFormNovoPedido($params)
    {
        $mesaId = $params[0] ?? null;

        if (!$mesaId || !is_numeric($mesaId)) {
            header('Location: /dashboard/garcom?status=mesa_invalida');
            exit;
        }

        try {
            $pdo = Database::getConnection();
            $empresa_id = $_SESSION['empresa_id'] ?? null;

            if (!$empresa_id) {
                throw new \Exception("ID da empresa não encontrado na sessão.");
            }
            
            $cardapioModel = new CardapioModel($pdo); 
            $itensCardapio = $cardapioModel->buscarItensAgrupados($empresa_id); 
            
            $this->renderView('pedidos/novo', [
                'pageTitle' => 'Lançar Pedido para a Mesa ' . htmlspecialchars($mesaId),
                'mesa_id'   => (int)$mesaId,
                'cardapio'  => $itensCardapio
            ]);

        } catch (\Exception $e) {
            error_log("Erro em showFormNovoPedido: " . $e->getMessage());
            $this->renderView('error', ['message' => 'Não foi possível carregar a página de pedidos.']);
        }
    }

    public function criarPedido()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dashboard/garcom');
            exit;
        }

        $mesa_id = filter_input(INPUT_POST, 'mesa_id', FILTER_SANITIZE_NUMBER_INT);
        $itens_pedido = $_POST['itens'] ?? [];
        $itens_validos = array_filter($itens_pedido, fn($qtd) => is_numeric($qtd) && $qtd > 0);

        if (empty($itens_validos) || !$mesa_id) {
            $_SESSION['error_message'] = "Pedido inválido ou sem itens.";
            header('Location: /dashboard/garcom');
            exit;
        }

        $pdo = null;
        try {
            $pdo = Database::getConnection();

            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
            }

            $pedidoModel = new PedidoModel($pdo);
            $mesaModel = new Mesa($pdo);

            $empresa_id = $_SESSION['empresa_id'] ?? null;
            $funcionario_id = $_SESSION['user_id'] ?? null;

            $pedidoSucesso = $pedidoModel->criarNovoPedido($empresa_id, $mesa_id, $funcionario_id, $itens_validos);
            if (!$pedidoSucesso) {
                throw new \Exception("Falha ao registrar os itens do pedido.");
            }

            $mesaSucesso = $mesaModel->atualizarStatus($mesa_id, 'Ocupada');
            if (!$mesaSucesso) {
                throw new \Exception("Falha ao atualizar o status da mesa.");
            }

            $pdo->commit();

            $_SESSION['success_message'] = "Pedido lançado e mesa ocupada com sucesso!";
            header('Location: /dashboard/garcom');
            exit;

        } catch (\Exception $e) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // 🔹 Debug detalhado
            $erro = $e->getMessage();
            $dados = json_encode([
                'empresa_id' => $empresa_id,
                'mesa_id' => $mesa_id,
                'itens' => $itens_validos
            ]);

            error_log("Erro ao criar pedido: $erro | Dados: $dados");

            // Mostra o erro na página para teste (remover em produção)
            die("Erro ao criar pedido: $erro <br> Dados enviados: $dados");
        }
    }
}
