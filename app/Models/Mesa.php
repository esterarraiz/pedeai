<?php

namespace App\Models;

class Mesa
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Busca todas as mesas de uma empresa específica, ordenadas pelo número.
     *
     * @param int $empresa_id O ID da empresa.
     * @return array A lista de mesas ou um array vazio se não houver.
     */
    public function buscarTodasPorEmpresa(int $empresa_id): array
    {
        $sql = "SELECT id, numero, status FROM mesas WHERE empresa_id = ? ORDER BY numero ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$empresa_id]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}