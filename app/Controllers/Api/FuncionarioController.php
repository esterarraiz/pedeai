<?php

namespace App\Controllers\Api;

use App\Core\JsonController; // Herda do Controller Base de API
use App\Models\Funcionario;
use Config\Database;
use Exception;
use PDO; // Importar o PDO

class FuncionarioController extends JsonController
{
    // private Funcionario $funcionarioModel; // <-- 1. REMOVIDO
    private ?int $empresa_id;
    private $pdo; 

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        // $this->funcionarioModel = new Funcionario($this->pdo); // <-- 2. REMOVIDO
        $this->empresa_id = $_SESSION['empresa_id'] ?? null;
    }

    /**
     * 3. ADICIONADO: Factory method para o Model.
     * Isso permite que o teste injete um mock.
     */
    protected function getFuncionarioModel(): Funcionario
    {
        return new Funcionario($this->pdo);
    }

    /**
     * Endpoint: GET /api/funcionarios
     */
    public function listar()
    {
        try {
            if (!$this->empresa_id) { throw new Exception("Empresa não identificada."); }
            // 4. MUDANÇA: usa o factory method
            $funcionarios = $this->getFuncionarioModel()->buscarTodosPorEmpresa($this->empresa_id);
            $this->jsonResponse($funcionarios); 
        } catch (Exception $e) {
            $this->jsonResponse(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint: GET /api/funcionarios/{id}
     */
    public function getFuncionario($params)
    {
        try {
            $id = $params['id'] ?? null;
            if (!$id) { return $this->jsonResponse(['message' => 'ID não fornecido.'], 400); }
            
            // 4. MUDANÇA: usa o factory method
            $funcionario = $this->getFuncionarioModel()->buscarPorId((int)$id);
            $this->jsonResponse($funcionario);
        } catch (Exception $e) {
            $this->jsonResponse(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint: POST /api/funcionarios
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
            // 4. MUDANÇA: usa o factory method
            $sucesso = $this->getFuncionarioModel()->criar($this->empresa_id, (int)$cargo_id, $nome, $email, $senha);
            $this->jsonResponse(['success' => true, 'message' => 'Funcionário criado com sucesso!']);
        } catch (Exception $e) {
            $this->jsonResponse(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint: POST /api/funcionarios/atualizar
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
            // 4. MUDANÇA: usa o factory method
            $sucesso = $this->getFuncionarioModel()->atualizar((int)$id, (int)$cargo_id, $nome, $email, $senha);
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
            // 4. MUDANÇA: usa o factory method
            $sucesso = $this->getFuncionarioModel()->atualizarStatus((int)$id, (bool)$status);
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
            // 4. MUDANÇA: usa o factory method
            $sucesso = $this->getFuncionarioModel()->redefinirSenha((int)$id, $senha);
            $this->jsonResponse(['success' => $sucesso, 'message' => 'Senha redefinida com sucesso!']);
        } catch (Exception $e) {
            $this->jsonResponse(['message' => $e->getMessage()], 500);
        }
    }
}