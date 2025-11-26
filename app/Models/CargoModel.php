<?php

namespace App\Models;

use PDO;

class CargoModel
{
    private $db;

    public function __construct($pdo_connection)
    {
        $this->db = $pdo_connection;
    }

    /**
     * Busca todos os cargos disponíveis.
     */
    public function buscarTodos(): array
    {
        $sql = "SELECT id, nome_cargo FROM cargos ORDER BY nome_cargo ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
