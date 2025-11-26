<?php


namespace App\Models;

use PDO;
use Exception;

class RelatorioModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Busca os cartões de sumário (Faturamento, Pedidos, Itens Vendidos).
     */
    public function buscarSumarioVendas(int $empresa_id, string $data_inicio, string $data_fim): array
    {
        // Adiciona a hora final para incluir o dia inteiro
        $data_fim_completa = $data_fim . ' 23:59:59';

        $sql = "
            SELECT
                SUM(p.valor) AS faturamento_total,
                COUNT(DISTINCT pd.id) AS total_pedidos, -- Contagem distinta de pedidos
                (SELECT SUM(pi.quantidade) 
                    FROM pedido_itens pi 
                    JOIN pedidos p_sub ON pi.pedido_id = p_sub.id
                    JOIN pagamentos pag ON p_sub.id = pag.pedido_id
                    WHERE p_sub.empresa_id = :empresa_id 
                    AND pag.data_pagamento BETWEEN :data_inicio AND :data_fim) AS total_itens_vendidos
            FROM pagamentos p
            JOIN pedidos pd ON p.pedido_id = pd.id
            WHERE pd.empresa_id = :empresa_id
              AND p.data_pagamento BETWEEN :data_inicio AND :data_fim;
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':empresa_id' => $empresa_id,
            ':data_inicio' => $data_inicio,
            ':data_fim' => $data_fim_completa
        ]);
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        
        return [
            'faturamento_total' => (float) ($resultado['faturamento_total'] ?? 0),
            'total_pedidos' => (int) ($resultado['total_pedidos'] ?? 0),
            'total_itens_vendidos' => (int) ($resultado['total_itens_vendidos'] ?? 0)
        ];
    }

    
    public function buscarTransacoes(int $empresa_id, string $data_inicio, string $data_fim): array
    {
        $data_fim_completa = $data_fim . ' 23:59:59';

        $sql = "
            SELECT
                p.data_pagamento,
                pd.id AS pedido_id,
                m.numero AS mesa_numero,
                f.nome AS funcionario_nome,
                p.valor AS valor_pago,
                p.metodo_pagamento
            FROM pagamentos p
            JOIN pedidos pd ON p.pedido_id = pd.id
            JOIN mesas m ON pd.mesa_id = m.id
            JOIN funcionarios f ON p.funcionario_id = f.id
            WHERE pd.empresa_id = :empresa_id
              AND p.data_pagamento BETWEEN :data_inicio AND :data_fim
            ORDER BY p.data_pagamento DESC;
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':empresa_id' => $empresa_id,
            ':data_inicio' => $data_inicio,
            ':data_fim' => $data_fim_completa
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function buscarItensMaisVendidos(int $empresa_id, string $data_inicio, string $data_fim): array
    {
        $data_fim_completa = $data_fim . ' 23:59:59';

        $sql = "
            SELECT
                ci.nome AS item_nome,
                SUM(pi.quantidade) AS total_vendido
            FROM pagamentos p
            JOIN pedidos pd ON p.pedido_id = pd.id
            JOIN pedido_itens pi ON pd.id = pi.pedido_id
            JOIN cardapio_itens ci ON pi.item_id = ci.id
            WHERE pd.empresa_id = :empresa_id
              AND p.data_pagamento BETWEEN :data_inicio AND :data_fim
            GROUP BY ci.nome
            ORDER BY total_vendido DESC
            LIMIT 10;
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':empresa_id' => $empresa_id,
            ':data_inicio' => $data_inicio,
            ':data_fim' => $data_fim_completa
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}