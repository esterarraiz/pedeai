<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\Mesa;
use Config\Database;
use Exception;
use PDO;

class EstabelecimentoController extends Controller
{
    private $pdo;
    private $empresa_id;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();

        if ($_SESSION['user_cargo'] !== 'administrador') {
            $this->jsonResponse(['success' => false, 'message' => 'Acesso negado. Requer privilégios de administrador.'], 403);
        }

        $this->pdo = Database::getConnection();
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->empresa_id = $_SESSION['empresa_id'];
    }

    // <-- MUDANÇA 1: Factory method para o Model -->
    protected function getMesaModel(): Mesa
    {
        return new Mesa($this->pdo);
    }

    // <-- MUDANÇA 2: Método para pegar o input cru -->
    protected function getRawInput(): string
    {
        // file_get_contents() retorna string ou false
        return file_get_contents('php://input') ?: '';
    }

    /**
     * Endpoint para listar todas as mesas da empresa.
     */
    public function listarMesas()
    {
        try {
            $mesaModel = $this->getMesaModel(); // <-- USA O FACTORY
            $mesas = $mesaModel->buscarTodasPorEmpresa($this->empresa_id);
            $this->jsonResponse(['success' => true, 'data' => $mesas]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Erro ao buscar mesas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint para criar novas mesas.
     */
    public function criarMesas()
    {
        $json = $this->getRawInput(); // <-- USA O MÉTODO DE INPUT
        $data = json_decode($json, true);

        $quantidade = filter_var($data['quantidade'] ?? 0, FILTER_VALIDATE_INT);

        if ($quantidade <= 0 || $quantidade > 100) {
            return $this->jsonResponse(['success' => false, 'message' => 'Quantidade inválida (deve ser entre 1 e 100).'], 400);
        }

        try {
            $this->pdo->beginTransaction();

            $mesaModel = $this->getMesaModel(); // <-- USA O FACTORY
            $ultimoNumero = $mesaModel->buscarUltimoNumero($this->empresa_id);
            $numeroInicial = $ultimoNumero + 1;

            for ($i = 0; $i < $quantidade; $i++) {
                $sucesso = $mesaModel->criar([
                    'empresa_id' => $this->empresa_id,
                    'numero' => $numeroInicial + $i,
                    'status' => 'disponivel'
                ]);
                if (!$sucesso) {
                    throw new Exception("Falha ao inserir a mesa número " . ($numeroInicial + $i));
                }
            }

            $this->pdo->commit();
            $this->jsonResponse(['success' => true, 'message' => "$quantidade mesas criadas com sucesso!"]);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Erro em criarMesas API (Transação revertida): " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Erro interno ao criar as mesas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint para excluir uma mesa.
     */
    public function excluirMesa()
    {
        $json = $this->getRawInput(); // <-- USA O MÉTODO DE INPUT
        $data = json_decode($json, true);

        $mesaId = filter_var($data['id'] ?? 0, FILTER_VALIDATE_INT);

        if ($mesaId <= 0) {
            return $this->jsonResponse(['success' => false, 'message' => 'ID da mesa inválido.'], 400);
        }

        try {
            $mesaModel = $this->getMesaModel(); // <-- USA O FACTORY
            $mesa = $mesaModel->buscarPorId($mesaId);

            if (!$mesa || $mesa->empresa_id != $this->empresa_id) {
                return $this->jsonResponse(['success' => false, 'message' => 'Mesa não encontrada ou não pertence a esta empresa.'], 404);
            }

            if ($mesa->status != 'disponivel') {
                return $this->jsonResponse(['success' => false, 'message' => 'Não é possível excluir uma mesa que está ocupada.'], 400);
            }

            $mesaModel->excluir($mesaId);
            $this->jsonResponse(['success' => true, 'message' => 'Mesa excluída com sucesso.']);
        } catch (Exception $e) {
            error_log("Erro em excluirMesa API: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Erro interno ao excluir a mesa.'], 500);
        }
    }


    /**
     * Método auxiliar para padronizar as respostas JSON.
     * // <-- MUDANÇA 3: private -> protected -->
     */
    protected function jsonResponse($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}