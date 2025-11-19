<?php

namespace App\Core;

// DEVE HERDAR DE CONTROLLER para ter acesso à sessão e autenticação
abstract class JsonController extends Controller
{
    /**
     * Construtor chama o pai (Controller) para iniciar a sessão
     */
    public function __construct($route_params = [])
    {
        parent::__construct($route_params); 
        
        // Define o cabeçalho padrão para JSON
        header('Content-Type: application/json; charset=utf-8');
    }

    /**
     * (NOVO MÉTODO)
     * Versão do 'requireLogin()' para APIs.
     * Em vez de redirecionar (o que envia HTML), ele envia um erro JSON 401.
     * Isso corrige o erro '<br />...'.
     */
    protected function requireLoginApi()
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->jsonError("Autenticação necessária. Faça o login novamente.", 401);
            exit;
        }
    }

    protected function getJsonData(): array
    {
        // file_get_contents('php://input') lê o corpo "cru" da requisição.
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->jsonResponse(['status' => 'error', 'message' => 'JSON inválido no corpo da requisição.'], 400);
        }
        
        return $data ?? [];
    }

    protected function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    /**
     * (NOVO MÉTODO) Helper para erros JSON.
     */
    protected function jsonError($message, $statusCode, $errors = [])
    {
        $response = ['status' => 'error', 'message' => $message];
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        $this->jsonResponse($response, $statusCode);
    }
}