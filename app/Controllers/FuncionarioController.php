<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Funcionario;
use Config\Database;
use App\Models\CargoModel;

class FuncionarioController extends Controller
{
    /**
     * Exibe a página principal de gerenciamento de funcionários.
     */
    public function index()
    {
        $pdo = Database::getConnection();
        $funcionarioModel = new Funcionario($pdo);
        $empresa_id = $_SESSION['empresa_id'];
        
        $funcionarios = $funcionarioModel->buscarTodosPorEmpresa($empresa_id);

        $this->loadView('funcionarios/index', [
            'funcionarios' => $funcionarios,
            'activePage' => 'funcionarios' // Define a página ativa
        ]);

    }

    /**
     * Exibe o formulário de criação/edição.
     */
    public function showCreateForm()
    {
        $pdo = Database::getConnection();
        $cargoModel = new CargoModel($pdo);
        $cargos = $cargoModel->buscarTodos();

        $this->loadView('funcionarios/form', [
            'funcionario' => null, 
            'cargos' => $cargos, // Envia a lista de cargos para a view
            'activePage' => 'funcionarios'
        ]);
    }

    /**
     * Exibe o formulário para editar um funcionário existente.
     */
    public function showEditForm($params)
    {
        $funcionario_id = $params['id'] ?? null;

        // 1. Validação: Se não houver ID, redireciona de volta
        if (!$funcionario_id) {
            // Idealmente, adicionar uma mensagem de erro na sessão
            header('Location: /funcionarios');
            exit;
        }

        $pdo = Database::getConnection();
        $funcionarioModel = new Funcionario($pdo);
        
        // 2. Busca os dados do funcionário no banco
        $funcionario = $funcionarioModel->buscarPorId((int)$funcionario_id);

        // 3. Validação: Se o funcionário não for encontrado, redireciona
        if (!$funcionario) {
            header('Location: /funcionarios');
            exit;
        }
        
        // Futuramente, carregar os cargos aqui também
        // $cargos = (new CargoModel($pdo))->buscarTodos();

        // 4. Carrega a mesma view do formulário, mas agora passando os dados
        $this->loadView('funcionarios/form', [
            'funcionario' => $funcionario, // Passa os dados para preencher o formulário
            // 'cargos' => $cargos
        ]);
    }

    /**
     * Processa a criação de um novo funcionário a partir dos dados do formulário.
     */
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /funcionarios/novo');
            exit;
        }

        // CORREÇÃO: Usando htmlspecialchars para sanitização
        $nome = htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8');
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $cargo_id = filter_input(INPUT_POST, 'cargo_id', FILTER_SANITIZE_NUMBER_INT);
        $senha = $_POST['senha'];
        $empresa_id = $_SESSION['empresa_id'];

        if (!$nome || !$email || !$cargo_id || empty($senha)) {
            $_SESSION['error_message'] = "Todos os campos são obrigatórios.";
            header('Location: /funcionarios/novo');
            exit;
        }

        $pdo = Database::getConnection();
        $funcionarioModel = new Funcionario($pdo);

        $sucesso = $funcionarioModel->criar($empresa_id, (int)$cargo_id, $nome, $email, $senha);

        if ($sucesso) {
            $_SESSION['success_message'] = "Funcionário criado com sucesso!";
            header('Location: /funcionarios');
        } else {
            $_SESSION['error_message'] = "Erro ao criar o funcionário. Verifique se o e-mail já existe.";
            header('Location: /funcionarios/novo');
        }
        exit;
    }

    /**
     * Processa a atualização de um funcionário existente.
     */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /funcionarios');
            exit;
        }

        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        // CORREÇÃO: Usando htmlspecialchars para sanitização
        $nome = htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8');
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $cargo_id = filter_input(INPUT_POST, 'cargo_id', FILTER_SANITIZE_NUMBER_INT);
        $senha = $_POST['senha'] ?? null;

        if (!$id || !$nome || !$email || !$cargo_id) {
            $_SESSION['error_message'] = "Dados inválidos para atualização.";
            header('Location: /funcionarios/editar/' . $id);
            exit;
        }

        $pdo = Database::getConnection();
        $funcionarioModel = new Funcionario($pdo);

        $sucesso = $funcionarioModel->atualizar((int)$id, (int)$cargo_id, $nome, $email, $senha);

        if ($sucesso) {
            $_SESSION['success_message'] = "Funcionário atualizado com sucesso!";
            header('Location: /funcionarios');
        } else {
            $_SESSION['error_message'] = "Erro ao atualizar o funcionário. Verifique os dados e tente novamente.";
            header('Location: /funcionarios/editar/' . $id);
        }
        exit;
    }
    public function toggleStatus()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        
        $id = $data['id'] ?? null;
        $status = $data['status'] ?? null;

        if ($id === null || $status === null) {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
            exit;
        }

        $pdo = Database::getConnection();
        $model = new Funcionario($pdo);
        $sucesso = $model->atualizarStatus((int)$id, (bool)$status);

        if ($sucesso) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Falha ao atualizar o status.']);
        }
        exit;
    }

    /**
     * Redefine a senha de um funcionário via AJAX.
     */
    public function redefinirSenha()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        $id = $data['id'] ?? null;
        $senha = $data['senha'] ?? null;

        if (!$id || empty($senha)) {
            echo json_encode(['success' => false, 'message' => 'ID do funcionário ou nova senha não fornecidos.']);
            exit;
        }

        $pdo = Database::getConnection();
        $model = new Funcionario($pdo);
        $sucesso = $model->redefinirSenha((int)$id, $senha);

        if ($sucesso) {
            echo json_encode(['success' => true, 'message' => 'Senha redefinida com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Falha ao redefinir a senha.']);
        }
        exit;
    }

}