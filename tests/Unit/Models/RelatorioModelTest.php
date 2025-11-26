<?php

namespace App\Tests\Models;

use App\Models\RelatorioModel;
use App\Tests\Utils\FakePDO;
use App\Tests\Utils\FakePDOStatement;
use PHPUnit\Framework\TestCase;

class RelatorioModelTest extends TestCase
{
    private FakePDO $pdo;
    private RelatorioModel $model;

    protected function setUp(): void
    {
        $this->pdo = new FakePDO();
        $this->model = new RelatorioModel($this->pdo);
    }

    public function testBuscarSumarioVendasRetornaValoresCorretos(): void
    {
        $stmt = new FakePDOStatement();
        $stmt->fetchStack = [[
            'faturamento_total' => 2500.75,
            'total_pedidos' => 30,
            'total_itens_vendidos' => 120
        ]];
        $this->pdo->addMockStatement($stmt);

        $resultado = $this->model->buscarSumarioVendas(1, '2025-01-01', '2025-01-31');

        $this->assertSame(2500.75, $resultado['faturamento_total']);
        $this->assertSame(30, $resultado['total_pedidos']);
        $this->assertSame(120, $resultado['total_itens_vendidos']);
    }

    public function testBuscarSumarioVendasRetornaZerosQuandoNaoHaResultados(): void
    {
        $stmt = new FakePDOStatement();
        $stmt->fetchStack = [false]; // Nenhum resultado
        $this->pdo->addMockStatement($stmt);

        $resultado = $this->model->buscarSumarioVendas(1, '2025-01-01', '2025-01-31');

        $this->assertSame(0.0, $resultado['faturamento_total']);
        $this->assertSame(0, $resultado['total_pedidos']);
        $this->assertSame(0, $resultado['total_itens_vendidos']);
    }

    public function testBuscarTransacoesRetornaArrayDeTransacoes(): void
    {
        $stmt = new FakePDOStatement();
        $stmt->fetchAllResult = [
            [
                'data_pagamento' => '2025-01-10 12:00:00',
                'pedido_id' => 1,
                'mesa_numero' => 5,
                'funcionario_nome' => 'Carlos',
                'valor_pago' => 75.50,
                'metodo_pagamento' => 'Cartão'
            ],
            [
                'data_pagamento' => '2025-01-11 18:00:00',
                'pedido_id' => 2,
                'mesa_numero' => 3,
                'funcionario_nome' => 'Ana',
                'valor_pago' => 60.00,
                'metodo_pagamento' => 'Pix'
            ]
        ];
        $this->pdo->addMockStatement($stmt);

        $resultado = $this->model->buscarTransacoes(1, '2025-01-01', '2025-01-31');

        $this->assertCount(2, $resultado);
        $this->assertSame('Carlos', $resultado[0]['funcionario_nome']);
        $this->assertSame('Pix', $resultado[1]['metodo_pagamento']);
    }

    public function testBuscarItensMaisVendidosRetornaListaCorreta(): void
    {
        $stmt = new FakePDOStatement();
        $stmt->fetchAllResult = [
            ['item_nome' => 'Pizza Calabresa', 'total_vendido' => 40],
            ['item_nome' => 'Lasanha', 'total_vendido' => 25]
        ];
        $this->pdo->addMockStatement($stmt);

        $resultado = $this->model->buscarItensMaisVendidos(1, '2025-01-01', '2025-01-31');

        $this->assertCount(2, $resultado);
        $this->assertSame('Pizza Calabresa', $resultado[0]['item_nome']);
        $this->assertSame(25, $resultado[1]['total_vendido']);
    }

    public function testBuscarItensMaisVendidosRetornaArrayVazioQuandoNaoHaDados(): void
    {
        $stmt = new FakePDOStatement();
        $stmt->fetchAllResult = [];
        $this->pdo->addMockStatement($stmt);

        $resultado = $this->model->buscarItensMaisVendidos(1, '2025-01-01', '2025-01-31');

        $this->assertSame([], $resultado);
    }
}
