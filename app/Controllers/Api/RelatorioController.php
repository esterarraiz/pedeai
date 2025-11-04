<?php


namespace App\Controllers\Api;

use App\Core\JsonController;
use App\Models\RelatorioModel;
use Config\Database;
use Exception;

class RelatorioController extends JsonController
{
    private RelatorioModel $relatorioModel;
    private ?int $empresa_id;

    public function __construct($route_params = [])
    {
        parent::__construct($route_params); // Inicia sessão e define header JSON
        
        $this->requireLoginApi(); 
        
        if ($_SESSION['user_cargo'] !== 'administrador') {
            $this->jsonError('Acesso negado. Requer privilégios de administrador.', 403);
        }
        
        $pdo = Database::getConnection();
        $this->relatorioModel = new RelatorioModel($pdo);
        $this->empresa_id = $_SESSION['empresa_id'];
    }

    /**
     * [GET] /api/relatorios/vendas
     * Busca todos os dados para o relatório de vendas.
     */
    public function getRelatorioVendas()
    {
        try {
            // Define as datas padrão (hoje) se não forem fornecidas
            $hoje = date('Y-m-d');
            $data_inicio = filter_input(INPUT_GET, 'data_inicio', FILTER_SANITIZE_SPECIAL_CHARS) ?: $hoje;
            $data_fim = filter_input(INPUT_GET, 'data_fim', FILTER_SANITIZE_SPECIAL_CHARS) ?: $hoje;

            // 1. Buscar Sumário
            $sumario = $this->relatorioModel->buscarSumarioVendas($this->empresa_id, $data_inicio, $data_fim);

            // 2. Calcular Ticket Médio
            if ($sumario['total_pedidos'] > 0) {
                $sumario['ticket_medio'] = $sumario['faturamento_total'] / $sumario['total_pedidos'];
            } else {
                $sumario['ticket_medio'] = 0;
            }

            // 3. Buscar Transações
            $transacoes = $this->relatorioModel->buscarTransacoes($this->empresa_id, $data_inicio, $data_fim);

            // 4. Buscar Itens Mais Vendidos
            $itens_mais_vendidos = $this->relatorioModel->buscarItensMaisVendidos($this->empresa_id, $data_inicio, $data_fim);

            // 5. Enviar Resposta
            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'sumario' => $sumario,
                    'transacoes' => $transacoes,
                    'itens_mais_vendidos' => $itens_mais_vendidos,
                    'periodo' => [
                        'data_inicio' => $data_inicio,
                        'data_fim' => $data_fim
                    ]
                ]
            ]);

        } catch (Exception $e) {
            $this->jsonError('Erro ao buscar relatórios: ' . $e->getMessage(), 500);
        }
    }
}