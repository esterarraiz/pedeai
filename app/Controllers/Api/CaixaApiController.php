<?php

namespace App\Controllers\Api;

use App\Core\JsonController;
use App\Models\Mesa;
use App\Models\PedidoModel;
use App\Models\PagamentoModel;
use Config\Database;
use Exception;

class CaixaApiController extends JsonController
{
    private $pdo;
    private $empresa_id;
    private $funcionario_id;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->empresa_id = $_SESSION['empresa_id'] ?? null;
        $this->funcionario_id = $_SESSION['user_id'] ?? null;
    }
    
    public function getMesasAbertas()
    {
        try {
            if (!$this->empresa_id) { throw new Exception("Sessão inválida."); }
            $mesaModel = new Mesa($this->pdo);
            $mesas = $mesaModel->buscarMesasComContaAberta($this->empresa_id);
            $this->jsonResponse(['success' => true, 'mesas' => $mesas]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Erro ao buscar mesas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint: GET /api/caixa/conta/{id}
     * CORRIGIDO: Este método agora envia a chave 'pedidos' (plural)
     * e chama o método correto 'buscarPedidosPorMesa'.
     */
    public function getDetalhesConta($params)
    {
        $mesa_id = $params['id'] ?? null;
        if (!$mesa_id) {
            return $this->jsonResponse(['success' => false, 'message' => 'ID da mesa não fornecido.'], 400);
        }
        
        try {
            if (!$this->empresa_id) { throw new Exception("Sessão inválida."); }
            
            $pedidoModel = new PedidoModel($this->pdo);
            
            // --- MUDANÇA AQUI ---
            // Chamamos o método que você já tem: buscarItensDoUltimoPedidoDaMesa
            // Ele já busca apenas o pedido mais recente e retorna um único array ou null.
            $ultimo_pedido = $pedidoModel->buscarItensDoUltimoPedidoDaMesa((int)$mesa_id, $this->empresa_id); 
            // --- FIM DA MUDANÇA ---
            
            
            if (empty($ultimo_pedido)) {
                // Nenhum pedido encontrado, retorna array vazio
                return $this->jsonResponse(['success' => true, 'pedidos' => [], 'message' => 'Nenhum pedido ativo encontrado.']);
            }
            
            // --- MUDANÇA NA RESPOSTA ---
            // Colocamos o único pedido (que é um array) dentro de outro array
            // para que o JSON final seja: { "pedidos": [ { ...pedido... } ] }
            // Isso mantém a consistência para o seu frontend.
            $this->jsonResponse(['success' => true, 'pedidos' => [$ultimo_pedido]]); 
            // --- FIM DA MUDANÇA ---
            
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function processarPagamento()
    {
        try {
            $data = $this->getJsonData(); 
            $mesa_id = filter_var($data['mesa_id'] ?? null, FILTER_SANITIZE_NUMBER_INT);
            $valor_pago_str = str_replace(',', '.', $data['valor_pago'] ?? '0');
            $valor_pago = filter_var($valor_pago_str, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $metodo_pagamento = filter_var($data['metodo_pagamento'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            if (!$mesa_id || !$metodo_pagamento || !$this->funcionario_id) {
                throw new Exception("Dados do pagamento incompletos.");
            }
            
            // 1. Registra o pagamento, fecha o pedido e libera a mesa.
            // Toda a lógica agora está centralizada no PagamentoModel
            // e protegida por uma transação.
            $pagamentoModel = new PagamentoModel($this->pdo);
            $pagamentoModel->registrarPagamento($mesa_id, (float)$valor_pago, $metodo_pagamento, $this->funcionario_id); 
            
            // --- LÓGICA REDUNDANTE REMOVIDA ---
            // Os passos 2 e 3 que estavam aqui foram removidos
            // porque já são executados dentro do registrarPagamento().
            
            $this->jsonResponse(['success' => true, 'message' => 'Pagamento registrado e mesa liberada com sucesso!']);
            
        } catch (Exception $e) {
            error_log("Erro na API de pagamento: " . $e->getMessage());
            // Retorna a mensagem de erro direto do Model
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

