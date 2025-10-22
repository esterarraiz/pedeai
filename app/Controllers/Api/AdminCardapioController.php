<?php

namespace App\Controllers\Api;

use App\Core\JsonController;
use App\Models\CardapioModel; // (Certifique-se que este é o nome do seu Model)
use Config\Database;

/**
 * Controla todas as ações da API para o Gerenciador de Cardápio.
 * Herda de JsonController para ter os métodos de autenticação e resposta JSON.
 */
class AdminCardapioController extends JsonController 
{
    private $cardapioModel;
    private $empresa_id;

    public function __construct($route_params = [])
    {
        parent::__construct($route_params); // Inicia sessão e define header JSON
        
        // Métodos herdados do Controller.php
        $this->requireLogin(); 
        
        if ($_SESSION['user_cargo'] !== 'administrador') {
            $this->jsonError('Acesso negado. Requer privilégios de administrador.', 403);
        }
        
        $pdo = Database::getConnection();
        $this->cardapioModel = new CardapioModel($pdo); // (Ajuste o nome do Model se for diferente)
        $this->empresa_id = $_SESSION['empresa_id'];
    }

    /**
     * [GET] /api/admin/cardapio
     * Lista todos os itens e categorias.
     * Substitui o método gerenciarCardapio().
     */
    public function listar()
    {
        $itensCardapio = $this->cardapioModel->buscarItensAgrupados($this->empresa_id);
        $categorias = $this->cardapioModel->buscarTodasCategorias($this->empresa_id);
        
        $this->jsonResponse([
            'status'     => 'success',
            'cardapio'   => $itensCardapio,
            'categorias' => $categorias
        ], 200);
    }

    /**
     * [POST] /api/admin/cardapio
     * Cria um novo item no cardápio.
     * Substitui o método adicionarItem().
     */
    public function criar()
    {
        $dados = $this->getJsonData(); // Pega dados do JSON: {"nome":"...", "preco":"..."}

        $preco_formatado = str_replace(',', '.', $dados['preco'] ?? '0');

        $dadosItem = [
            'nome'         => filter_var($dados['nome'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
            'descricao'    => filter_var($dados['descricao'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
            'preco'        => $preco_formatado,
            'categoria_id' => filter_var($dados['categoria_id'] ?? '', FILTER_SANITIZE_NUMBER_INT),
            'empresa_id'   => $this->empresa_id
        ];

        // TODO: Adicionar validação de dados (verificar se nome ou preco estão vazios)

        $sucesso = $this->cardapioModel->criarItem($dadosItem);

        if ($sucesso) {
            $this->jsonResponse(['status' => 'success', 'message' => "Item '{$dadosItem['nome']}' adicionado."], 201);
        } else {
            $this->jsonError('Erro ao adicionar o item.', 500);
        }
    }
    
    /**
     * [PUT] /api/admin/cardapio/{id}
     * Atualiza um item existente.
     * Substitui o método editarItem().
     */
    public function atualizar($params)
    {
        $id = filter_var($params['id'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
        $dados = $this->getJsonData(); //

        if (!$id) {
            $this->jsonError('ID do item não fornecido.', 400);
        }

        $preco_formatado = str_replace(',', '.', $dados['preco'] ?? '0');

        $dadosItem = [
            'id'           => $id,
            'nome'         => filter_var($dados['nome'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
            'descricao'    => filter_var($dados['descricao'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
            'preco'        => $preco_formatado,
            'categoria_id' => filter_var($dados['categoria_id'] ?? '', FILTER_SANITIZE_NUMBER_INT),
            'empresa_id'   => $this->empresa_id
        ];

        $sucesso = $this->cardapioModel->atualizarItem($dadosItem);

        if ($sucesso) {
            $this->jsonResponse(['status' => 'success', 'message' => "Item '{$dadosItem['nome']}' atualizado."], 200);
        } else {
            $this->jsonError('Erro ao atualizar o item. Verifique os dados.', 500);
        }
    }

    /**
     * [DELETE] /api/admin/cardapio/{id}
     * Remove um item.
     * Substitui o método removerItem().
     */
    public function remover($params)
    {
        $id = filter_var($params['id'] ?? 0, FILTER_SANITIZE_NUMBER_INT);

        if (!$id) {
            $this->jsonError('ID do item não fornecido.', 400);
        }

        $sucesso = $this->cardapioModel->removerItem((int)$id, $this->empresa_id);

        if ($sucesso) {
            $this->jsonResponse(['status' => 'success', 'message' => 'Item removido com sucesso!'], 200);
        } else {
            $this->jsonError('Erro ao remover o item.', 500);
        }
    }
}