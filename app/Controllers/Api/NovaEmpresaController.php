<?php
// Caminho: app/Controllers/Api/NovaEmpresaController.php

namespace App\Controllers\Api;

use App\Core\Controller;
use Config\Database;
use App\Models\EmpresaModel;
use App\Models\DetalhesEmpresasModel;
use App\Models\Funcionario;
use PDOException;
use Exception;
use PDO;

class NovaEmpresaController extends Controller
{
    // As propriedades privadas ($db, $empresaModel, etc.) foram REMOVIDAS.
    // O construtor agora está (quase) vazio.
    public function __construct()
    {
        parent::__construct();
        // A conexão e a instanciação dos models não acontecem mais aqui.
    }

    // --- MÉTODOS DE INJEÇÃO (PARA TESTES) ---

    /**
     * Obtém a conexão PDO.
     */
    protected function getPdo(): PDO
    {
        // Usamos static para reutilizar a conexão na mesma requisição
        static $pdo = null;
        if ($pdo === null) {
            $pdo = Database::getConnection();
        }
        return $pdo;
    }

    /**
     * Obtém o model EmpresaModel.
     */
    protected function getEmpresaModel(): EmpresaModel
    {
        return new EmpresaModel($this->getPdo());
    }

    /**
     * Obtém o model DetalhesEmpresasModel.
     */
    protected function getDetalhesEmpresasModel(): DetalhesEmpresasModel
    {
        return new DetalhesEmpresasModel($this->getPdo());
    }

    /**
     * Obtém o model Funcionario.
     */
    protected function getFuncionarioModel(): Funcionario
    {
        return new Funcionario($this->getPdo());
    }

    /**
     * Obtém o input JSON cru.
     */
    protected function getRawInput(): string
    {
        return file_get_contents('php://input') ?: '';
    }

    /**
     * Define a sessão de sucesso.
     */
    protected function setSuccessSession(string $message): void
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['form_success'] = $message;
    }

    // --- ENDPOINT DA API ---

    /**
     * Ação: [POST] /api/registrar
     */
    public function processRegistration()
    {
        // MUDANÇA: Usa o método mockável
        $json = $this->getRawInput();
        $dados = json_decode($json, true);

        $dadosLimpos = [
            'cnpj'                  => filter_var($dados['cnpj'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
            'nome_estabelecimento'  => filter_var($dados['nome_estabelecimento'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
            'nome_proprietario'     => filter_var($dados['nome_proprietario'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
            'email'                 => filter_var($dados['email'] ?? '', FILTER_VALIDATE_EMAIL),
            'telefone'              => filter_var($dados['telefone'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
            'endereco'              => filter_var($dados['endereco'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
            'senha'                 => $dados['senha'] ?? '',
            'confirm_senha'         => $dados['confirm_senha'] ?? ''
        ];

        // --- VALIDAÇÃO (sem alteração de lógica) ---
        if (empty($dadosLimpos['cnpj']) || empty($dadosLimpos['nome_estabelecimento']) || empty($dadosLimpos['nome_proprietario']) || empty($dadosLimpos['email']) || empty($dadosLimpos['senha'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Erro: Todos os campos obrigatórios devem ser preenchidos.'], 400);
        }
        if (!$dadosLimpos['email']) {
             return $this->jsonResponse(['success' => false, 'message' => 'Erro: O e-mail fornecido não é válido.'], 400);
        }
        if (strlen($dadosLimpos['senha']) < 6) {
             return $this->jsonResponse(['success' => false, 'message' => 'Erro: A senha deve ter no mínimo 6 caracteres.'], 400);
        }
        if ($dadosLimpos['senha'] !== $dadosLimpos['confirm_senha']) {
            return $this->jsonResponse(['success' => false, 'message' => 'Erro: As senhas não conferem.'], 400);
        }

        // --- VALIDAÇÃO DE DUPLICIDADE (no Banco) ---
        try {
            // MUDANÇA: Usa os getters dos models
            if ($this->getDetalhesEmpresasModel()->findByEmail($dadosLimpos['email']) || $this->getFuncionarioModel()->buscarPorEmail($dadosLimpos['email'])) {
                 return $this->jsonResponse(['success' => false, 'message' => 'Erro: Este e-mail já está cadastrado.'], 400);
            }
            if ($this->getDetalhesEmpresasModel()->findByCnpj($dadosLimpos['cnpj'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Erro: Este CNPJ já está cadastrado.'], 400);
            }
        } catch (Exception $e) { 
             error_log("Erro na pré-validação (API): " . $e->getMessage());
             return $this->jsonResponse(['success' => false, 'message' => 'Erro de validação: ' . $e->getMessage()], 500);
        }

        // --- CRIAÇÃO (COM TRANSAÇÃO EM 3 PASSOS) ---
        
        // MUDANÇA: Obtém o PDO pelo getter
        $db = $this->getPdo();
        
        try {
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->beginTransaction();

            // PASSO 1: Criar a 'empresa' (tabela principal)
            // MUDANÇA: Usa o getter
            $novo_id_empresa = $this->getEmpresaModel()->create($dadosLimpos['nome_estabelecimento']); 

            // PASSO 2: Criar os 'detalhes_empresas' (tabela de detalhes)
            // MUDANÇA: Usa o getter
            $this->getDetalhesEmpresasModel()->create($novo_id_empresa, $dadosLimpos);

            // PASSO 3: Criar o 'funcionario' admin
            // MUDANÇA: Usa o getter
            $this->getFuncionarioModel()->criar(
                (int)$novo_id_empresa,
                1, // cargo_id 1 = 'administrador'
                $dadosLimpos['nome_proprietario'],
                $dadosLimpos['email'],
                $dadosLimpos['senha'] 
            );

            $db->commit();
            
            // MUDANÇA: Usa o método mockável de sessão
            $this->setSuccessSession('Conta criada com sucesso! Faça o login.');
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Conta criada com sucesso!',
                'empresa_id' => $novo_id_empresa
            ]);

        } catch (PDOException $e) { 
            if ($db->inTransaction()) { $db->rollBack(); }
            error_log("Erro no registo (API - PDO): " . $e->getMessage());
            
            if ($e->getCode() == '23505') { 
                 $this->jsonResponse(['success' => false, 'message' => 'Erro: O E-mail ou CNPJ já está registado.'], 400);
            } else {
                 $this->jsonResponse(['success' => false, 'message' => 'Erro de BD: ' . $e->getMessage()], 500);
            }

        } catch (Exception $e) { 
             if ($db->inTransaction()) { $db->rollBack(); }
             error_log("Erro no registo (API - Geral): " . $e->getMessage());
             $this->jsonResponse(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], 500);
        }
    }

    /* --- Método Helper de Resposta JSON --- */
    // MUDANÇA: de 'private' para 'protected'
    protected function jsonResponse($data, $statusCode = 200)
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}