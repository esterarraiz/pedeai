<?php

namespace App\Core;

abstract class JsonController
{
    protected function getJsonData(): array
    {
        // file_get_contents('php://input') lê o corpo "cru" da requisição.
        $jsonData = file_get_contents('php://input');
        return json_decode($jsonData, true) ?? [];
    }

    protected function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}