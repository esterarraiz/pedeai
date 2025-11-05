<?php

namespace App\Tests\Unit\Models;

use App\Tests\TestCase;
use App\Models\AdminDashboardModel;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\After;

class AdminDashboardModelTest extends TestCase
{
    private MockObject $pdoMock;
    private MockObject $stmtMock; // Usado para testes simples (1 query)
    private AdminDashboardModel $adminDashboardModel;

    #[Before]
    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() == PHP_SESSION_NONE) {
            $_SESSION = [];
        }

        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
        $this->adminDashboardModel = new AdminDashboardModel($this->pdoMock);
    }

    #[After]
    protected function tearDown(): void
    {
        $_SESSION = [];
        unset($this->pdoMock, $this->stmtMock, $this->adminDashboardModel);
    }

    //================================================================
    // Testes para getMetricas
    //================================================================

    #[Test]
    public function test_getMetricas_retorna_dados_corretos()
    {
        // 1. Arrange
        $empresa_id = 1;

        // Precisamos de 3 mocks de statement, um para cada query
        $stmtFaturamentoMock = $this->createMock(PDOStatement::class);
        $stmtPedidosMock = $this->createMock(PDOStatement::class);
        $stmtMesasMock = $this->createMock(PDOStatement::class);

        // Configura o PDO mock para retornar os statements na ordem em que são chamados
        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls(
                $stmtFaturamentoMock, // 1ª chamada (faturamento)
                $stmtPedidosMock,     // 2ª chamada (pedidos)
                $stmtMesasMock        // 3ª chamada (mesas)
            );

        // Configura o 'execute' para cada statement
        $stmtFaturamentoMock->method('execute')->with([':empresa_id' => $empresa_id]);
        $stmtPedidosMock->method('execute')->with([':empresa_id' => $empresa_id]);
        $stmtMesasMock->method('execute')->with([':empresa_id' => $empresa_id]);

        // Configura o 'fetch' para cada statement
        $stmtFaturamentoMock->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['faturamento_dia' => '250.75']); // DB retorna strings

        $stmtPedidosMock->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['pedidos_andamento' => '8']); // DB retorna strings

        $stmtMesasMock->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['mesas_ocupadas' => '5', 'total_mesas' => '10']); // DB retorna strings
        
        // Resultado esperado (com os tipos corretos convertidos pelo método)
        $esperado = [
            'faturamento_dia' => 250.75, // float
            'pedidos_andamento' => 8,    // int
            'mesas_ocupadas' => 5,       // int
            'total_mesas' => 10          // int
        ];

        // 2. Act
        $resultado = $this->adminDashboardModel->getMetricas($empresa_id);

        // 3. Assert
        $this->assertEquals($esperado, $resultado);
    }

    #[Test]
    public function test_getMetricas_com_valores_nulos_retorna_zeros()
    {
        // 1. Arrange
        $empresa_id = 2; // Empresa sem dados

        $stmtFaturamentoMock = $this->createMock(PDOStatement::class);
        $stmtPedidosMock = $this->createMock(PDOStatement::class);
        $stmtMesasMock = $this->createMock(PDOStatement::class);

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls(
                $stmtFaturamentoMock,
                $stmtPedidosMock,
                $stmtMesasMock
            );
        
        // Simula o caso de 'fetch' não encontrar resultados
        
        // Se a query (SUM) não retornar linhas, fetch() retorna 'false'
        $stmtFaturamentoMock->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(false); 
        
        // Se a query (COUNT) não retornar linhas, fetch() retorna 'false'
        $stmtPedidosMock->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(false);
        
        // Se a query (COUNTs) não retornar linhas, fetch() retorna 'false'
        $stmtMesasMock->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(false);

        // Resultado esperado (com os tipos corretos convertidos pelo método)
        $esperado = [
            'faturamento_dia' => 0.0,
            'pedidos_andamento' => 0,
            'mesas_ocupadas' => 0,
            'total_mesas' => 0
        ];

        // 2. Act
        $resultado = $this->adminDashboardModel->getMetricas($empresa_id);

        // 3. Assert
        // Testa a lógica '?? 0' do modelo
        $this->assertEquals($esperado, $resultado);
    }

    //================================================================
    // Testes para getPedidosRecentes
    //================================================================

    #[Test]
    public function test_getPedidosRecentes_retorna_lista_de_pedidos()
    {
        // 1. Arrange
        $empresa_id = 1;
        $dadosFalsos = [
            [
                'mesa_numero' => 5, 'garcom_nome' => 'João',
                'valor_total' => 50.00, 'status' => 'em_preparo'
            ],
            [
                'mesa_numero' => 2, 'garcom_nome' => 'Maria',
                'valor_total' => 30.50, 'status' => 'pronto'
            ]
        ];

        // Este teste só tem 1 query, podemos usar o $this->stmtMock padrão
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        
        $this->stmtMock->method('execute')->with([':empresa_id' => $empresa_id]);
        $this->stmtMock->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($dadosFalsos);
        
        // 2. Act
        $resultado = $this->adminDashboardModel->getPedidosRecentes($empresa_id);

        // 3. Assert
        $this->assertIsArray($resultado);
        $this->assertCount(2, $resultado);
        $this->assertEquals($dadosFalsos, $resultado);
    }

    #[Test]
    public function test_getPedidosRecentes_com_nenhum_pedido_retorna_array_vazio()
    {
        // 1. Arrange
        $empresa_id = 1;
        
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
        
        $this->stmtMock->method('execute')->with([':empresa_id' => $empresa_id]);
        $this->stmtMock->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([]); // Nenhum pedido encontrado
        
        // 2. Act
        $resultado = $this->adminDashboardModel->getPedidosRecentes($empresa_id);

        // 3. Assert
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }
}
