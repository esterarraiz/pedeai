<?php

namespace App\Controllers\Api;

use App\Core\JsonController;
use App\Models\CardapioModel; 
use Config\Database;

class AdminCardapioController extends JsonController 
{
    private $cardapioModel;
    private $empresa_id;

    public function __construct($route_params = [])
    {
        parent::__construct($route_params); 
        
        $this->requireLoginApi(); 
        
        if ($_SESSION['user_cargo'] !== 'administrador' && $_SESSION['user_cargo'] !== 'gerente') {
            $this->jsonError('Acesso negado. Requer privilégios de administrador.', 403);
        }
        
        $pdo = Database::getConnection(); 
        $this->cardapioModel = new CardapioModel($pdo); 
        $this->empresa_id = $_SESSION['empresa_id'];
    }

    /**
     * Helper para lidar com o upload e salvar o arquivo.
     * Retorna a URL relativa para salvar no banco de dados.
     */
    private function handleImageUpload(): ?string
    {
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['imagem'];
            
            // Define o caminho absoluto para a pasta de destino
            $uploadDir = dirname(__DIR__, 3) . '/public/images/Cardapio/';
            
            // Cria o diretório se não existir
            if (!is_dir($uploadDir)) {
                // Tenta criar o diretório recursivamente com permissões 0777 (Verificar permissões no servidor)
                mkdir($uploadDir, 0777, true); 
            }

            // Garante que o nome do arquivo seja único para evitar colisões
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = time() . '_' . uniqid() . '.' . $ext;
            $targetPath = $uploadDir . $fileName;

            // Tenta mover o arquivo temporário para o destino final
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // Retorna a URL RELATIVA (que será usada na tag <img>)
                return '/images/Cardapio/' . $fileName; 
            } else {
                // Caso a função move_uploaded_file falhe (ex: permissão negada)
                throw new \Exception("Falha ao mover o arquivo de upload. Verifique as permissões da pasta 'public/images/Cardapio'.");
            }
        }
        return null; // Nenhuma imagem foi enviada
    }

    /**
     * [GET] /api/admin/cardapio
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
     */
    public function criar()
    {
        // 1. Dados vêm de $_POST (devido ao multipart/form-data)
        $dados = $_POST; 
        
        $preco_formatado = str_replace(',', '.', $dados['preco'] ?? '0');

        try {
            // 2. Faz o upload da imagem e pega a URL
            $imagem_url = $this->handleImageUpload(); 

        } catch (\Exception $e) {
            $this->jsonError("Erro no upload: " . $e->getMessage(), 500);
        }

        // Validação e Sanitização
        $dadosItem = [
            'nome'         => filter_var($dados['nome'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
            'descricao'    => filter_var($dados['descricao'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
            'preco'        => $preco_formatado,
            'categoria_id' => filter_var($dados['categoria_id'] ?? '', FILTER_SANITIZE_NUMBER_INT),
            'imagem_url'   => $imagem_url, // <-- USA A URL DO UPLOAD
            'empresa_id'   => $this->empresa_id
        ];

        $sucesso = $this->cardapioModel->criarItem($dadosItem); 

        if ($sucesso) {
            $this->jsonResponse(['status' => 'success', 'message' => "Item '{$dadosItem['nome']}' adicionado."], 201);
        } else {
            $this->jsonError('Erro ao adicionar o item.', 500);
        }
    }
    
    /**
     * [PUT/POST] /api/admin/cardapio/{id}
     */
    public function atualizar($params)
    {
        $id = filter_var($params['id'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
        $dados = $_POST; // Dados do formulário
        $imagem_url_atual = $dados['imagem_url_atual'] ?? null; // Pego do campo hidden da View

        if (!$id) {
            $this->jsonError('ID do item não fornecido.', 400);
        }

        $preco_formatado = str_replace(',', '.', $dados['preco'] ?? '0');

        $imagem_url_final = $imagem_url_atual; // Começa assumindo que vai manter a atual

        try {
            // 1. Tenta fazer o upload da nova imagem
            $nova_imagem_url = $this->handleImageUpload();

            if ($nova_imagem_url) {
                // 2. Se o upload for bem-sucedido, deleta a imagem antiga se ela existir (Lógica de limpeza)
                if ($imagem_url_atual && file_exists(dirname(__DIR__, 3) . '/public' . $imagem_url_atual)) {
                    // Ignora falha de unlink, pois o registro do BD será sobrescrito de qualquer forma.
                    @unlink(dirname(__DIR__, 3) . '/public' . $imagem_url_atual); 
                }
                $imagem_url_final = $nova_imagem_url; // Usa a nova URL
            }

        } catch (\Exception $e) {
            $this->jsonError("Erro no upload: " . $e->getMessage(), 500);
        }

        // Validação e Sanitização
        $dadosItem = [
            'id'           => $id,
            'nome'         => filter_var($dados['nome'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
            'descricao'    => filter_var($dados['descricao'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS),
            'preco'        => $preco_formatado,
            'categoria_id' => filter_var($dados['categoria_id'] ?? '', FILTER_SANITIZE_NUMBER_INT),
            'imagem_url'   => $imagem_url_final, // <-- URL final (nova ou antiga)
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
     */
    public function remover($params)
    {
        $id = filter_var($params['id'] ?? 0, FILTER_SANITIZE_NUMBER_INT);

        if (!$id) {
            $this->jsonError('ID do item não fornecido.', 400);
        }
        
        // Antes de remover do BD, buscamos para ver se há arquivo para deletar
        $item = $this->cardapioModel->buscarItemPorId($id, $this->empresa_id);
        
        if ($item && $item['imagem_url']) {
            $caminho_arquivo = dirname(__DIR__, 3) . '/public' . $item['imagem_url'];
            
            // Tentativa de remover o arquivo físico
            if (file_exists($caminho_arquivo)) {
                // Usa @ para suprimir erros caso o arquivo já tenha sido removido manualmente
                @unlink($caminho_arquivo); 
            }
        }
        
        $sucesso = $this->cardapioModel->removerItem((int)$id, $this->empresa_id); 

        if ($sucesso) {
            $this->jsonResponse(['status' => 'success', 'message' => 'Item removido com sucesso!'], 200);
        } else {
            $this->jsonError('Erro ao remover o item.', 500);
        }
    }
    
    // =========================================================
    // == MÉTODOS PARA GERENCIAMENTO DE CATEGORIAS (ADICIONADOS) ==
    // =========================================================

    /**
     * [POST] /api/admin/cardapio/categorias
     */
    public function criarCategoria()
    {
        // Usa o método base getJsonData, pois o frontend envia JSON para esta função específica.
        $dados = $this->getJsonData(); 
        $nome = filter_var($dados['nome'] ?? null, FILTER_SANITIZE_SPECIAL_CHARS);

        if (empty($nome)) {
            $this->jsonError('O nome da categoria é obrigatório.', 400);
        }

        try {
            $sucesso = $this->cardapioModel->criarCategoria($nome, $this->empresa_id);

            if ($sucesso) {
                $this->jsonResponse(['status' => 'success', 'message' => "Categoria '{$nome}' criada com sucesso!"], 201);
            } else {
                $this->jsonError('Erro ao criar a categoria.', 500);
            }
        } catch (\PDOException $e) {
            // Tenta identificar erro de duplicidade (código comum no MySQL é 23000, mas vamos usar a mensagem)
            if (strpos($e->getMessage(), 'Duplicate entry') !== false || $e->getCode() == '23000') {
                 $this->jsonError('Essa categoria já existe.', 409);
            } else {
                 error_log("Erro PDO na criação de categoria: " . $e->getMessage());
                 $this->jsonError('Erro interno do banco de dados.', 500);
            }
        }
    }

    /**
     * [DELETE] /api/admin/cardapio/categorias/{id}
     */
    public function removerCategoria($params)
    {
        $id = filter_var($params['id'] ?? 0, FILTER_SANITIZE_NUMBER_INT);

        if (!$id) {
            $this->jsonError('ID da categoria não fornecido.', 400);
        }

        try {
            $sucesso = $this->cardapioModel->removerCategoria((int)$id, $this->empresa_id);

            if ($sucesso) {
                $this->jsonResponse(['status' => 'success', 'message' => 'Categoria removida com sucesso!'], 200);
            } else {
                $this->jsonError('Nenhuma categoria encontrada com este ID, ou falha na remoção.', 404);
            }
        } catch (\PDOException $e) {
            // Código 1451 (MySQL) para Falha de Chave Estrangeira (itens vinculados)
            if ($e->getCode() == '1451') {
                 $this->jsonError('Não é possível remover. Existem itens de cardápio vinculados a esta categoria.', 409);
            } else {
                 error_log("Erro PDO na remoção de categoria: " . $e->getMessage());
                 $this->jsonError('Erro interno do banco de dados.', 500);
            }
        }
    }
}