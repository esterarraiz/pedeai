<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Mesa;
use App\Models\PedidoModel;
use App\Models\PagamentoModel;
use Config\Database;

class Caixacontroller extends Controller
{
    /**
     * Mostra o resumo da conta de uma mesa específica.
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
            $pedidoModel = new PedidoModel($pdo);
            $empresa_id = $_SESSION['empresa_id'];
            $pedido = $pedidoModel->buscarItensDoUltimoPedidoDaMesa($mesa_id, $empresa_id);
            
            $mesaModel = new Mesa($pdo);
            $mesa = $mesaModel->buscarPorId($mesa_id);

            $this->loadView('caixa/resumo_conta', [
                'pageTitle' => 'Resumo da Conta - Mesa ' . $mesa['numero'],
                'pedido' => $pedido,
                'mesa' => $mesa
            ]);
        } catch (\Exception $e) {
            $this->loadView('error', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Processa o formulário de pagamento.
     */
    public function processarPagamento()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $mesa_id = filter_input(INPUT_POST, 'mesa_id', FILTER_SANITIZE_NUMBER_INT);
        $valor_pago_str = str_replace(',', '.', $_POST['valor_pago'] ?? '0');
        $valor_pago = filter_var($valor_pago_str, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $metodo_pagamento = filter_input(INPUT_POST, 'metodo_pagamento', FILTER_SANITIZE_STRING);
        $funcionario_id = $_SESSION['user_id'] ?? null;

        try {
            $pdo = Database::getConnection();
            $pagamentoModel = new PagamentoModel($pdo);
            $pagamentoModel->registrarPagamento($mesa_id, $valor_pago, $metodo_pagamento, $funcionario_id);
            
            header('Location: /dashboard/caixa?status=pagamento_sucesso');
            exit;
        } catch (\Exception $e) {
            header('Location: /caixa/conta/' . $mesa_id . '?error=' . urlencode($e->getMessage()));
            exit;
        }
    }
}