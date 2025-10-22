<?php
// Ficheiro: app/Controllers/CozinheiroDashboardController.php (Versão Corrigida e Final)

namespace App\Controllers;

use App\Core\Controller;
use App\Models\PedidoModel;
use Config\Database;

class CozinheiroDashboardController extends Controller
{
    /**
     * Carrega a view principal da cozinha com os pedidos pendentes.
     */
    public function index()
    {
        $pdo = Database::getConnection();
        $pedidoModel = new PedidoModel($pdo);

        $empresa_id = $_SESSION['empresa_id'] ?? 0;
        $pedidos = $pedidoModel->buscarPedidosParaCozinha($empresa_id);

        $this->loadView('cozinha/cozinhaview', [
            'pedidos' => $pedidos
        ]);
    }

    /**
     * API endpoint para marcar um pedido como 'pronto'.
     * Esta função é chamada via JavaScript (AJAX/Fetch).
     */
    public function marcarPronto()
    {
        // Define o cabeçalho da resposta como JSON para o JavaScript entender
        header('Content-Type: application/json');

        try {
            // Garante que a requisição é do tipo POST para segurança
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception("Método de requisição inválido. Apenas POST é permitido.");
            }
            
            // Pega o corpo da requisição POST (que o JavaScript envia como JSON)
            $json = file_get_contents('php://input');
            $data = json_decode($json);

            // Valida os dados recebidos
            $pedido_id = filter_var($data->id ?? null, FILTER_VALIDATE_INT);
            $empresa_id = $_SESSION['empresa_id'] ?? 0;

            if (!$pedido_id || !$empresa_id) {
                throw new \Exception("ID do pedido ou ID da empresa inválido.");
            }

            // Executa a lógica de negócio no Model
            $pdo = Database::getConnection();
            $pedidoModel = new PedidoModel($pdo);
            $sucesso = $pedidoModel->marcarComoPronto($pedido_id, $empresa_id);

            if ($sucesso) {
                // Se o model retornar true, a atualização foi bem-sucedida.
                echo json_encode(['success' => true, 'message' => "Pedido #{$pedido_id} marcado como pronto!"]);
            } else {
                // Se o model retornar false, a atualização falhou (0 linhas afetadas).
                // Isto acontece se o pedido não estava com o estado 'em_preparo'.
                throw new \Exception("O Pedido #{$pedido_id} não pôde ser atualizado. Ele pode já ter sido marcado como 'pronto' ou o seu estado não é 'em preparo'.");
            }

        } catch (\Exception $e) {
            // Em caso de qualquer erro, retorna uma resposta JSON com a mensagem de erro.
            http_response_code(400); // Código de erro para "Bad Request"
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        // Garante que o script para após enviar a resposta JSON
        exit;
    }
}