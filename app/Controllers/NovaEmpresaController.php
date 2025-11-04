<?php

namespace App\Controllers;

// Importa as classes que vamos usar
use Config\Database;
use App\Models\NovaEmpresaModel;
use App\Models\Funcionario;
use PDOException; // Importante para apanhar o erro 23505

class NovaEmpresaController
{
    private $db;
    private $empresaModel;
    private $funcionarioModel; 

    public function __construct()
    {
        $this->db = Database::getConnection(); 
        $this->empresaModel = new NovaEmpresaModel($this->db);
        $this->funcionarioModel = new Funcionario($this->db);
    }

    /**
     * Ação: [GET] /registrar
     */
    public function showRegistrationForm()
    {
        $pageTitle = 'Criar Nova Conta';
        $this->renderView('auth/registrar', ['pageTitle' => $pageTitle]);
    }

    /**
     * Ação: [POST] /registrar
     * Recebe os dados, valida e tenta criar a empresa E o funcionário admin.
     */
    public function processRegistration()
    {
        // 1. Pega os dados
        $dados = [
            'cnpj'              => filter_input(INPUT_POST, 'cnpj', FILTER_SANITIZE_SPECIAL_CHARS),
            'nome_proprietario' => filter_input(INPUT_POST, 'nome_proprietario', FILTER_SANITIZE_SPECIAL_CHARS),
            'email'             => filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL),
            'telefone'          => filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_SPECIAL_CHARS),
            'endereco'          => filter_input(INPUT_POST, 'endereco', FILTER_SANITIZE_SPECIAL_CHARS),
            'senha'             => $_POST['senha'] ?? '',
            'confirm_senha'     => $_POST['confirm_senha'] ?? ''
        ];

        // --- VALIDAÇÃO (Campos em branco) ---
        if (empty($dados['cnpj']) || empty($dados['nome_proprietario']) || empty($dados['email']) || empty($dados['senha'])) {
            $this->redirectWithError('/registrar', 'Erro: Todos os campos obrigatórios devem ser preenchidos.');
            return;
        }
        if ($dados['senha'] !== $dados['confirm_senha']) {
            $this->redirectWithError('/registrar', 'Erro: As senhas não conferem.');
            return;
        }

        // --- VALIDAÇÃO (Duplicidade - AGORA VERIFICA AS DUAS TABELAS) ---
        try {
            if ($this->empresaModel->findByEmail($dados['email']) || $this->funcionarioModel->buscarPorEmail($dados['email'])) {
                 $this->redirectWithError('/registrar', 'Erro: Este e-mail já está cadastrado.');
                 return;
            }
            if ($this->empresaModel->findByCnpj($dados['cnpj'])) {
                $this->redirectWithError('/registrar', 'Erro: Este CNPJ já está cadastrado.');
                return;
            }
        } catch (\Exception $e) {
             // Isto acontece se o método buscarPorEmail() não existir
             error_log("Erro na pré-validação: " . $e->getMessage());
             $this->redirectWithError('/registrar', 'Erro interno ao validar. Tente novamente.');
             return;
        }


        // --- CRIAÇÃO (COM TRANSAÇÃO) ---
        try {
            $this->db->beginTransaction();

            // 1. Cria a empresa
            $novo_id_empresa = $this->empresaModel->create($dados); 
            if (!$novo_id_empresa) {
                throw new \Exception("Falha ao criar a empresa.");
            }

            // 2. Cria o funcionário
            $sucesso_funcionario = $this->funcionarioModel->criar(
                (int)$novo_id_empresa,
                1, // cargo_id 1 = 'administrador'
                $dados['nome_proprietario'],
                $dados['email'],
                $dados['senha']
            );

            $this->db->commit();
            $this->redirectWithSuccess('/login', 'Conta criada com sucesso! Faça o login.');

        } catch (PDOException $e) { // Apanha erros do Banco de Dados
            
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Erro no registro (PDO): " . $e->getMessage());
            
            // Checa pelo código de erro de duplicidade
            if ($e->getCode() == '23505') {
                 $this->redirectWithError('/registrar', 'Erro: O E-mail ou CNPJ já está cadastrado.');
            } else {
                 // Se for outro erro de BD, mostra a mensagem genérica
                 $this->redirectWithError('/registrar', 'Erro: Não foi possível criar sua conta. Tente novamente.');
            }

        } catch (\Exception $e) { // Apanha erros gerais (ex: método não encontrado)
            
             if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Erro no registro (Geral): " . $e->getMessage());
            $this.redirectWithError('/registrar', 'Erro: Não foi possível criar sua conta. Tente novamente.');
        }
    }


    /* --- Métodos de Ajuda (Helpers) --- */
    protected function renderView(string $viewName, array $data = [])
    {
        extract($data);
        require_once dirname(__DIR__) . '/Views/' . $viewName . '.php';
    }
    protected function redirectWithError(string $url, string $message)
    {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        $_SESSION['form_error'] = $message;
        header('Location: ' . $url);
        exit;
    }
    protected function redirectWithSuccess(string $url, string $message)
    {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        $_SESSION['form_success'] = $message;
        header('Location: ' . $url);
        exit;
    }
}