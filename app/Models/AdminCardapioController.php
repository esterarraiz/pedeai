<?php

namespace App\Controllers\Api;

use App\Core\JsonController;
use App\Models\CardapioModel; //
use Config\Database;

class AdminCardapioController extends JsonController 
{
    private $cardapioModel;
    private $empresa_id;

    /**
     * (A CORREÇÃO ESTÁ AQUI)
     * Este método DEVE ser 'public' para que o Roteador possa chamá-lo.
     */
    public function __construct($route_params = [])
    {
        parent::__construct($route_params); //
        
        $this->requireLoginApi(); //
        
        // Esta é a linha 20 (ou próxima dela) que o log de erro mencionou
        if ($_SESSION['user_cargo'] !== 'administrador') {
            $this->jsonError('Acesso negado. Requer privilégios de administrador.', 403);
        }
        
        $pdo = Database::getConnection(); //
        $this->cardapioModel = new CardapioModel($pdo); //
        $this->empresa_id = $_SESSION['empresa_id'];
    }

    /**
     * [GET] /api/admin/cardapio
     */
    public function listar()
    {
        $itensCardapio = $this->cardapioModel->buscarItensAgrupados($this->empresa_id); //
        $categorias = $this->cardapioModel->buscarTodasCategorias($this->empresa_id); //
        
        $this->jsonResponse([
            'status'     => 'success',
            'cardapio'   => $itensCardapio,
            'categorias' => $categorias
        ], 200);
    }

    /**
     * [POST] /api/admin/cardapio
     */
    public function criar()
    {
        $dados = $this->getJsonData(); //

        $preco_formatado = str_replace(',', '.', $dados['preco'] ?? '0');

        $dadosItem = [
            'nome'         => filter_var($dados['nome'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
            'descricao'    => filter_var($dados['descricao'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
            'preco'        => $preco_formatado,
            'categoria_id' => filter_var($dados['categoria_id'] ?? '', FILTER_SANITIZE_NUMBER_INT),
            'empresa_id'   => $this->empresa_id
        ];

        $sucesso = $this->cardapioModel->criarItem($dadosItem); //

        if ($sucesso) {
            $this->jsonResponse(['status' => 'success', 'message' => "Item '{$dadosItem['nome']}' adicionado."], 201);
        } else {
            $this->jsonError('Erro ao adicionar o item.', 500);
        }
    }
    
    /**
     * [PUT] /api/admin/cardapio/{id}
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

        $sucesso = $this->cardapioModel->atualizarItem($dadosItem); //

        if ($sucesso) {
            $this->jsonResponse(['status' => 'success', 'message' => "Item '{$dadosItem['nome']}' atualizado."], 200);
        } else {
            $this->jsonError('Erro ao atualizar o item. Verifique os dados.', 500);
        }
    }

    /**
     * [DELETE] /api/admin/cardapio/{id}
     */
    public function remover($params)
    {
        $id = filter_var($params['id'] ?? 0, FILTER_SANITIZE_NUMBER_INT);

        if (!$id) {
            $this->jsonError('ID do item não fornecido.', 400);
        }

        $sucesso = $this->cardapioModel->removerItem((int)$id, $this->empresa_id); //

        if ($sucesso) {
            $this->jsonResponse(['status' => 'success', 'message' => 'Item removido com sucesso!'], 200);
        } else {
            $this->jsonError('Erro ao remover o item.', 500);
        }
    }
}