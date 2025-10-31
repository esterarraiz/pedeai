<?php

namespace App\Controllers\Api;

use App\Core\JsonController; // Herda do Controller Base de API
use App\Models\Funcionario;
use Config\Database;
use Exception;

class FuncionarioController extends JsonController
{
    private Funcionario $funcionarioModel;
    private ?int $empresa_id;
    private $pdo; // Adicionado para ser consistente com o seu Model

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->funcionarioModel = new Funcionario($this->pdo);
        $this->empresa_id = $_SESSION['empresa_id'] ?? null;
    }

    /**
     * Endpoint: GET /api/funcionarios
     * Lista todos os funcionários.
     */
    public function listar()
    {
        try {
            if (!$this->empresa_id) { throw new Exception("Empresa não identificada."); }
            $funcionarios = $this->funcionarioModel->buscarTodosPorEmpresa($this->empresa_id);
            $this->jsonResponse($funcionarios); // Envia os dados como JSON
        } catch (Exception $e) {
            $this->jsonResponse(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint: GET /api/funcionarios/{id}
     * Busca um funcionário específico.
     */
    public function getFuncionario($params)
    {
        try {
            $id = $params['id'] ?? null;
            if (!$id) { return $this->jsonResponse(['message' => 'ID não fornecido.'], 400); }
            
            $funcionario = $this->funcionarioModel->buscarPorId((int)$id);
            $this->jsonResponse($funcionario);
        } catch (Exception $e) {
            $this->jsonResponse(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint: POST /api/funcionarios
     * Cria um novo funcionário.
     */
    public function criar()
    {
        $data = $this->getJsonData();
        
        $nome = htmlspecialchars($data['nome'] ?? '', ENT_QUOTES, 'UTF-8');
        $email = filter_var($data['email'] ?? null, FILTER_VALIDATE_EMAIL);
        $cargo_id = filter_var($data['cargo_id'] ?? null, FILTER_SANITIZE_NUMBER_INT);
        $senha = $data['senha'] ?? null;

        if (!$nome || !$email || !$cargo_id || empty($senha) || !$this->empresa_id) {
            return $this->jsonResponse(['message' => 'Todos os campos são obrigatórios.'], 400);
        }

        try {
            $sucesso = $this->funcionarioModel->criar($this->empresa_id, (int)$cargo_id, $nome, $email, $senha);
            $this->jsonResponse(['success' => true, 'message' => 'Funcionário criado com sucesso!']);
        } catch (Exception $e) {
            $this->jsonResponse(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint: POST /api/funcionarios/atualizar
     * Atualiza um funcionário.
     */
    public function atualizar()
    {
        $data = $this->getJsonData();
        
        $id = filter_var($data['id'] ?? null, FILTER_SANITIZE_NUMBER_INT);
        $nome = htmlspecialchars($data['nome'] ?? '', ENT_QUOTES, 'UTF-8');
        $email = filter_var($data['email'] ?? null, FILTER_VALIDATE_EMAIL);
        $cargo_id = filter_var($data['cargo_id'] ?? null, FILTER_SANITIZE_NUMBER_INT);
        $senha = $data['senha'] ?? null; // Senha é opcional

        if (!$id || !$nome || !$email || !$cargo_id) {
            return $this->jsonResponse(['message' => 'Dados inválidos para atualização.'], 400);
        }

        try {
            $sucesso = $this->funcionarioModel->atualizar((int)$id, (int)$cargo_id, $nome, $email, $senha);
            $this->jsonResponse(['success' => true, 'message' => 'Funcionário atualizado com sucesso!']);
        } catch (Exception $e) {
            $this->jsonResponse(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint: POST /api/funcionarios/status
     */
    public function toggleStatus()
    {
        $data = $this->getJsonData();
        $id = $data['id'] ?? null;
        $status = $data['status'] ?? null;

        if ($id === null || $status === null) {
            return $this->jsonResponse(['message' => 'Dados inválidos.'], 400);
        }

        try {
            $sucesso = $this->funcionarioModel->atualizarStatus((int)$id, (bool)$status);
            $this->jsonResponse(['success' => $sucesso, 'message' => 'Status atualizado.']);
        } catch (Exception $e) {
            $this->jsonResponse(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint: POST /api/funcionarios/redefinir-senha
     */
    public function redefinirSenha()
    {
        $data = $this->getJsonData();
        $id = $data['id'] ?? null;
        $senha = $data['senha'] ?? null;

        if (!$id || empty($senha)) {
            return $this->jsonResponse(['message' => 'ID ou nova senha não fornecidos.'], 400);
        }

        try {
            $sucesso = $this->funcionarioModel->redefinirSenha((int)$id, $senha);
            $this->jsonResponse(['success' => $sucesso, 'message' => 'Senha redefinida com sucesso!']);
        } catch (Exception $e) {
            $this->jsonResponse(['message' => $e->getMessage()], 500);
        }
    }
}

