<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\Mesa;
use App\Models\PedidoModel;
use App\Models\PagamentoModel;
use Config\Database;

class CaixaApiController extends Controller
{
    private $pdo;

    public function __construct()
    {
        parent::__construct();
        ini_set('display_errors', 0); 
        set_error_handler(function ($severity, $message, $file, $line) {
            if (error_reporting() === 0) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        if (!isset($_SESSION['user_id'])) {
             header('Content-Type: application/json');
             http_response_code(401);
             echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
             exit;
        }
        
        if (!in_array($_SESSION['user_cargo'], ['caixa', 'administrador'])) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
            exit;
        }
        
        $this->pdo = Database::getConnection();
    }
    
    public function listarMesasComContaAberta()
    {
        header('Content-Type: application/json');
        try {
            $mesaModel = new Mesa($this->pdo);
            $empresa_id = $_SESSION['empresa_id'];
            $mesas = $mesaModel->buscarMesasComContaAberta($empresa_id);
            echo json_encode(['success' => true, 'mesas' => $mesas]);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar mesas: ' . $e->getMessage()]);
            exit;
        }
    }

    public function obterDetalhesConta($params)
    {
        header('Content-Type: application/json');
        $mesa_id = $params['id'] ?? null;
        if (!$mesa_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID da mesa não fornecido.']);
            exit;
        }
        try {
            $pedidoModel = new PedidoModel($this->pdo);
            $empresa_id = $_SESSION['empresa_id'];
            $pedido = $pedidoModel->buscarItensDoUltimoPedidoDaMesa($mesa_id, $empresa_id);
            if (!$pedido) {
                echo json_encode(['success' => true, 'pedido' => null, 'message' => 'Nenhum pedido ativo encontrado para esta mesa.']);
                exit;
            }
            echo json_encode(['success' => true, 'pedido' => $pedido]);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    public function processarPagamento()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
            exit;
        }

        try {
            $jsonPayload = file_get_contents('php://input');
            $data = json_decode($jsonPayload, true);

            $mesa_id = filter_var($data['mesa_id'] ?? null, FILTER_SANITIZE_NUMBER_INT);
            $valor_pago_str = str_replace(',', '.', $data['valor_pago'] ?? '0');
            $valor_pago = filter_var($valor_pago_str, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            
            // ===== A CORREÇÃO ESTÁ AQUI =====
            $metodo_pagamento = filter_var($data['metodo_pagamento'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            $funcionario_id = $_SESSION['user_id'] ?? null;
            
            if (!$mesa_id || !$metodo_pagamento || !$funcionario_id) {
                throw new \Exception("Dados do pagamento incompletos.");
            }
            
            $pagamentoModel = new PagamentoModel($this->pdo);
            $pagamentoModel->registrarPagamento($mesa_id, $valor_pago, $metodo_pagamento, $funcionario_id);
            
            echo json_encode(['success' => true, 'message' => 'Pagamento registrado com sucesso!']);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            error_log("Erro na API de pagamento: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
}