<?php
// Caminho: app/Controllers/Api/NovaEmpresaController.php
// (CORRIGIDO para importar a classe PDO)

namespace App\Controllers\Api;

use App\Core\Controller;
use Config\Database;
use App\Models\EmpresaModel;      
use App\Models\DetalhesEmpresasModel; 
use App\Models\Funcionario;           
use PDOException; 
use Exception;      
use PDO; // <--- ================ LINHA ADICIONADA ================

class NovaEmpresaController extends Controller
{
    private $db;
    private $empresaModel;
    private $detalhesEmpresasModel;
    private $funcionarioModel;

    public function __construct()
    {
        parent::__construct(); 
        $this->db = Database::getConnection(); 
        
        $this->empresaModel = new EmpresaModel($this->db);
        $this->detalhesEmpresasModel = new DetalhesEmpresasModel($this->db);
        $this->funcionarioModel = new Funcionario($this->db); 
    }

    /**
     * Ação: [POST] /api/registrar
     */
    public function processRegistration()
    {
        $json = file_get_contents('php://input');
        $dados = json_decode($json, true); 

        $dadosLimpos = [
            'cnpj'                   => filter_var($dados['cnpj'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
            'nome_estabelecimento'   => filter_var($dados['nome_estabelecimento'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
            'nome_proprietario'      => filter_var($dados['nome_proprietario'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
            'email'                  => filter_var($dados['email'] ?? '', FILTER_VALIDATE_EMAIL),
            'telefone'               => filter_var($dados['telefone'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
            'endereco'               => filter_var($dados['endereco'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
            'senha'                  => $dados['senha'] ?? '',
            'confirm_senha'          => $dados['confirm_senha'] ?? ''
        ];

        // --- VALIDAÇÃO ---
        // (Sem alterações aqui... o código de validação está correto)
        if (empty($dadosLimpos['cnpj']) || empty($dadosLimpos['nome_estabelecimento']) || empty($dadosLimpos['nome_proprietario']) || empty($dadosLimpos['email']) || empty($dadosLimpos['senha'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Erro: Todos os campos obrigatórios devem ser preenchidos.'], 400);
            return;
        }
        if (!$dadosLimpos['email']) {
             $this->jsonResponse(['success' => false, 'message' => 'Erro: O e-mail fornecido não é válido.'], 400);
             return;
        }
        if (strlen($dadosLimpos['senha']) < 6) {
             $this->jsonResponse(['success' => false, 'message' => 'Erro: A senha deve ter no mínimo 6 caracteres.'], 400);
             return;
        }
        if ($dadosLimpos['senha'] !== $dadosLimpos['confirm_senha']) {
            $this->jsonResponse(['success' => false, 'message' => 'Erro: As senhas não conferem.'], 400);
            return;
        }

        // --- VALIDAÇÃO DE DUPLICIDADE (no Banco) ---
        try {
            if ($this->detalhesEmpresasModel->findByEmail($dadosLimpos['email']) || $this->funcionarioModel->buscarPorEmail($dadosLimpos['email'])) {
                 $this->jsonResponse(['success' => false, 'message' => 'Erro: Este e-mail já está cadastrado.'], 400);
                 return;
            }
            if ($this->detalhesEmpresasModel->findByCnpj($dadosLimpos['cnpj'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Erro: Este CNPJ já está cadastrado.'], 400);
                return;
            }
        } catch (Exception $e) { 
             error_log("Erro na pré-validação (API): " . $e->getMessage());
             $this->jsonResponse(['success' => false, 'message' => 'Erro de validação: ' . $e->getMessage()], 500);
             return;
        }

        // --- CRIAÇÃO (COM TRANSAÇÃO EM 3 PASSOS) ---
        try {
            // A linha 94 agora funciona por causa do 'use PDO;' no topo
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->beginTransaction();

            // PASSO 1: Criar a 'empresa' (tabela principal)
            $novo_id_empresa = $this->empresaModel->create($dadosLimpos['nome_estabelecimento']); 

            // PASSO 2: Criar os 'detalhes_empresas' (tabela de detalhes)
            $this->detalhesEmpresasModel->create($novo_id_empresa, $dadosLimpos);

            // PASSO 3: Criar o 'funcionario' admin
            // Esta linha agora vai funcionar
            $this->funcionarioModel->criar(
                (int)$novo_id_empresa,
                1, // cargo_id 1 = 'administrador'
                $dadosLimpos['nome_proprietario'],
                $dadosLimpos['email'],
                $dadosLimpos['senha'] 
            );

            $this->db->commit();
            
            if (session_status() == PHP_SESSION_NONE) { session_start(); }
            $_SESSION['form_success'] = 'Conta criada com sucesso! Faça o login.';
            
            $this->jsonResponse(['success' => true, 'message' => 'Conta criada com sucesso!']);

        } catch (PDOException $e) { 
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Erro no registo (API - PDO): " . $e->getMessage());
            
            if ($e->getCode() == '23505') { 
                 $this->jsonResponse(['success' => false, 'message' => 'Erro: O E-mail ou CNPJ já está registado.'], 400);
            } else {
                 $this->jsonResponse(['success' => false, 'message' => 'Erro de BD: ' . $e->getMessage()], 500);
            }

        } catch (Exception $e) { 
             if ($this->db->inTransaction()) {
                $this->db->rollBack();
             }
             error_log("Erro no registo (API - Geral): " . $e->getMessage());
             $this->jsonResponse(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], 500);
        }
    }

    /* --- Método Helper de Resposta JSON --- */
    private function jsonResponse($data, $statusCode = 200)
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}