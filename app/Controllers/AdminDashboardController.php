<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CardapioModel;
use Config\Database;

class AdminDashboardController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        
        if ($_SESSION['user_cargo'] !== 'administrador') {
            header('Location: /acesso-negado');
            exit;
        }
    }

    public function index()
    {
        $this->loadView('dashboard/admin', ['pageTitle' => 'Dashboard Administrador']);
    }

    public function gerenciarCardapio()
    {
        $pdo = Database::getConnection();
        $cardapioModel = new CardapioModel($pdo);
        $empresa_id = $_SESSION['empresa_id'];

        $itensCardapio = $cardapioModel->buscarItensAgrupados($empresa_id);
        $categorias = $cardapioModel->buscarTodasCategorias($empresa_id);

        $this->loadView('admin/cardapio', [
            'pageTitle'  => 'Gerenciar Cardápio',
            'cardapio'   => $itensCardapio,
            'categorias' => $categorias,
            'activePage' => 'cardapio'
        ]);
    }

    /**
     * [CÓDIGO ADICIONADO] Processa a adição de um novo item.
     */
    public function adicionarItem()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 1. Pega os dados do formulário com segurança
            $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
            $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_SPECIAL_CHARS);
            $preco_str = $_POST['preco'] ?? '0';
            $categoria_id = filter_input(INPUT_POST, 'categoria_id', FILTER_SANITIZE_NUMBER_INT);

            // 2. [IMPORTANTE] Corrige o formato do preço (troca vírgula por ponto)
            $preco_formatado = str_replace(',', '.', $preco_str);

            // 3. Monta o array de dados
            $dados = [
                'nome'         => $nome,
                'descricao'    => $descricao,
                'preco'        => $preco_formatado,
                'categoria_id' => $categoria_id,
                'empresa_id'   => $_SESSION['empresa_id']
            ];

            // 4. Chama o Model para tentar salvar
            $pdo = Database::getConnection();
            $cardapioModel = new CardapioModel($pdo);
            $sucesso = $cardapioModel->criarItem($dados);

            // 5. [MELHORIA] Cria uma mensagem de feedback para o usuário
            if ($sucesso) {
                $_SESSION['feedback_success'] = "Item '{$nome}' adicionado com sucesso!";
            } else {
                $_SESSION['feedback_error'] = "Erro ao adicionar o item. Tente novamente.";
            }
        }
        header('Location: /dashboard/admin/cardapio');
        exit;
    }
    
    /**
     * [CÓDIGO ADICIONADO] Processa a edição de um item existente.
     */
    public function editarItem()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
            $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_SPECIAL_CHARS);
            $preco_str = $_POST['preco'] ?? '0';
            $categoria_id = filter_input(INPUT_POST, 'categoria_id', FILTER_SANITIZE_NUMBER_INT);

            $preco_formatado = str_replace(',', '.', $preco_str);

            $dados = [
                'id'           => $id,
                'nome'         => $nome,
                'descricao'    => $descricao,
                'preco'        => $preco_formatado,
                'categoria_id' => $categoria_id,
                'empresa_id'   => $_SESSION['empresa_id']
            ];

            $pdo = Database::getConnection();
            $cardapioModel = new CardapioModel($pdo);
            $sucesso = $cardapioModel->atualizarItem($dados);

            if ($sucesso) {
                $_SESSION['feedback_success'] = "Item '{$nome}' atualizado com sucesso!";
            } else {
                $_SESSION['feedback_error'] = "Erro ao atualizar o item. Verifique os dados.";
            }
        }
        header('Location: /dashboard/admin/cardapio');
        exit;
    }

    /**
     * [CÓDIGO ADICIONADO] Processa a remoção de um item.
     */
    public function removerItem()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            
            if ($id) {
                $pdo = Database::getConnection();
                $cardapioModel = new CardapioModel($pdo);
                $sucesso = $cardapioModel->removerItem((int)$id, $_SESSION['empresa_id']);

                if ($sucesso) {
                    $_SESSION['feedback_success'] = "Item removido com sucesso!";
                } else {
                    $_SESSION['feedback_error'] = "Erro ao remover o item.";
                }
            }
        }
        header('Location: /dashboard/admin/cardapio');
        exit;
    }
}