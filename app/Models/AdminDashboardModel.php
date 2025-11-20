<?php
// Ficheiro: app/Models/AdminDashboardModel.php

namespace App\Models;

use PDO;

class AdminDashboardModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Busca os dados para os cartões principais do dashboard.
     */
    public function getMetricas(int $empresa_id): array
    {
        // Faturamento do dia
        $sql_faturamento = "
            SELECT SUM(valor) AS faturamento_dia
            FROM pagamentos p
            JOIN pedidos pd ON p.pedido_id = pd.id
            WHERE pd.empresa_id = :empresa_id AND DATE(p.data_pagamento) = CURRENT_DATE;
        ";
        $stmt_faturamento = $this->pdo->prepare($sql_faturamento);
        $stmt_faturamento->execute([':empresa_id' => $empresa_id]);
        $faturamento = $stmt_faturamento->fetch(PDO::FETCH_ASSOC)['faturamento_dia'] ?? 0;

        // Pedidos em andamento
        $sql_pedidos = "
            SELECT COUNT(id) AS pedidos_andamento
            FROM pedidos
            WHERE empresa_id = :empresa_id AND status IN ('em_preparo', 'pronto', 'entregue');
        ";
        $stmt_pedidos = $this->pdo->prepare($sql_pedidos);
        $stmt_pedidos->execute([':empresa_id' => $empresa_id]);
        $pedidos_andamento = $stmt_pedidos->fetch(PDO::FETCH_ASSOC)['pedidos_andamento'] ?? 0;

        // Mesas Ocupadas
        $sql_mesas = "
            SELECT 
                (SELECT COUNT(id) FROM mesas WHERE empresa_id = :empresa_id AND status = 'ocupada') AS mesas_ocupadas,
                (SELECT COUNT(id) FROM mesas WHERE empresa_id = :empresa_id) AS total_mesas;
        ";
        $stmt_mesas = $this->pdo->prepare($sql_mesas);
        $stmt_mesas->execute([':empresa_id' => $empresa_id]);
        $mesas = $stmt_mesas->fetch(PDO::FETCH_ASSOC);

        return [
            'faturamento_dia' => (float) $faturamento,
            'pedidos_andamento' => (int) $pedidos_andamento,
            'mesas_ocupadas' => (int) ($mesas['mesas_ocupadas'] ?? 0),
            'total_mesas' => (int) ($mesas['total_mesas'] ?? 0)
        ];
    }

    /**
     * Busca os pedidos mais recentes em andamento.
     */
    public function getPedidosRecentes(int $empresa_id): array
    {
        // Mantida a alteração: adicionando o ID do pedido
        $sql = "
            SELECT
                p.id AS pedido_id,
                m.numero AS mesa_numero,
                f.nome AS garcom_nome,
                p.valor_total,
                p.status
            FROM pedidos p
            JOIN mesas m ON p.mesa_id = m.id
            JOIN funcionarios f ON p.funcionario_id = f.id
            WHERE p.empresa_id = :empresa_id AND p.status IN ('em_preparo', 'pronto', 'entregue')
            ORDER BY p.data_abertura DESC
            LIMIT 5;
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':empresa_id' => $empresa_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
