<?php
// Ficheiro: app/Controllers/Api/EstabelecimentoController.php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\Mesa; 
use Config\Database; 
use Exception; 
use PDO; // <-- 1. ADICIONADO IMPORT DO PDO

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
        
        // <-- 2. ADICIONADA LINHA PARA FORÇAR ERROS PDO -->
        // Isso fará com que o catch(Exception $e) receba a mensagem de erro real do SQL.
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $this->empresa_id = $_SESSION['empresa_id']; 
    }

    /**
     * Endpoint para listar todas as mesas da empresa.
     * (GET /api/estabelecimento/mesas)
     */
    public function listarMesas()
    {
        try {
            $mesaModel = new Mesa($this->pdo);
            $mesas = $mesaModel->buscarTodasPorEmpresa($this->empresa_id); 
            $this->jsonResponse(['success' => true, 'data' => $mesas]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Erro ao buscar mesas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint para criar novas mesas.
     * (POST /api/estabelecimento/mesas)
     * * === CORRIGIDO COM TRANSAÇÃO PDO ===
     */
    public function criarMesas()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true); 

        $quantidade = filter_var($data['quantidade'] ?? 0, FILTER_VALIDATE_INT);

        if ($quantidade <= 0 || $quantidade > 100) {
            return $this->jsonResponse(['success' => false, 'message' => 'Quantidade inválida (deve ser entre 1 e 100).'], 400);
        }

        // ==========================================================
        // == INÍCIO DA CORREÇÃO (Adicionando Transação) ==
        // ==========================================================
        try {
            // 1. Inicia a transação
            $this->pdo->beginTransaction();

            $mesaModel = new Mesa($this->pdo);
            $ultimoNumero = $mesaModel->buscarUltimoNumero($this->empresa_id);
            $numeroInicial = $ultimoNumero + 1;

            for ($i = 0; $i < $quantidade; $i++) {
                // Agora, se o criar() falhar, ele lançará uma PDOException por causa da linha no __construct
                $sucesso = $mesaModel->criar([
                    'empresa_id' => $this->empresa_id,
                    'numero' => $numeroInicial + $i,
                    'status' => 'disponivel'
                ]);

                // 2. Se o 'criar()' falhar, lança um erro para forçar o rollback
                // (Essa checagem é uma dupla garantia)
                if (!$sucesso) {
                    throw new Exception("Falha ao inserir a mesa número " . ($numeroInicial + $i));
                }
            }

            // 3. Se o loop terminou sem erros, CONFIRMA (salva) tudo no banco.
            $this->pdo->commit();

            $this->jsonResponse(['success' => true, 'message' => "$quantidade mesas criadas com sucesso!"]);

        } catch (Exception $e) { // <-- Este catch agora receberá a mensagem de erro real do SQL
            // 4. Se qualquer erro ocorreu, DESFAZ (rollback) tudo.
            $this->pdo->rollBack();
            
            error_log("Erro em criarMesas API (Transação revertida): " . $e->getMessage());
            
            // Agora a mensagem de erro será muito mais útil (ex: "Column 'empresa_id' cannot be null")
            $this->jsonResponse(['success' => false, 'message' => 'Erro interno ao criar as mesas: ' . $e->getMessage()], 500);
        }
        // ==========================================================
        // == FIM DA CORREÇÃO ==
        // ==========================================================
    }

    /**
     * Endpoint para excluir uma mesa.
     * (POST /api/estabelecimento/mesas/excluir)
     */
    public function excluirMesa()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true); 

        $mesaId = filter_var($data['id'] ?? 0, FILTER_VALIDATE_INT);

        if ($mesaId <= 0) {
            return $this->jsonResponse(['success' => false, 'message' => 'ID da mesa inválido.'], 400);
        }

        try {
            $mesaModel = new Mesa($this->pdo);
            $mesa = $mesaModel->buscarPorId($mesaId);

            if (!$mesa || $mesa->empresa_id != $this->empresa_id) {
                return $this->jsonResponse(['success' => false, 'message' => 'Mesa não encontrada ou não pertence a esta empresa.'], 404);
            }

            if ($mesa->status != 'disponivel') {
                 return $this->jsonResponse(['success' => false, 'message' => 'Não é possível excluir uma mesa que está ocupada.'], 400);
            }

            // O excluir() não precisa de transação por ser um comando único.
            $mesaModel->excluir($mesaId); 

            $this->jsonResponse(['success' => true, 'message' => 'Mesa excluída com sucesso.']);

        } catch (Exception $e) {
            error_log("Erro em excluirMesa API: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Erro interno ao excluir a mesa.'], 500);
        }
    }


    /**
     * Método auxiliar para padronizar as respostas JSON.
     */
    private function jsonResponse($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}

