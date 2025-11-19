<?php

namespace App\Tests\Unit\Models;

use App\Models\PedidoModel;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use Exception;
use App\Tests\Utils\FakePDO;
use App\Tests\Utils\FakePDOStatement;

// ------------------------------------------------------------------
// CLASSE DE TESTE
// ------------------------------------------------------------------

class PedidoModelTest extends TestCase
{
    private FakePDO $pdoFake;
    private PedidoModel $pedidoModel;

    protected function setUp(): void
    {
        $this->pdoFake = new FakePDO();
        $this->pedidoModel = new PedidoModel($this->pdoFake);
    }

    protected function tearDown(): void
    {
        // Limpa a fila de statements após cada teste
        $this->pdoFake->reset();
        parent::tearDown();
    }

    /** @test */
    public function test_buscarPedidosParaCozinha_agrupa_itens_corretamente()
    {
        $agora = date('Y-m-d H:i:s');
        $hora = date('H:i', strtotime($agora));

        // Simula o retorno do banco (SQL com JOINS)
        $dbResult = [
            // Pedido 100 (Mesa 5)
            ['pedido_id' => 100, 'data_abertura' => $agora, 'mesa_numero' => 5, 'quantidade' => 2, 'item_nome' => 'Pizza'],
            ['pedido_id' => 100, 'data_abertura' => $agora, 'mesa_numero' => 5, 'quantidade' => 1, 'item_nome' => 'Pepsi'],
            // Pedido 101 (Mesa 2)
            ['pedido_id' => 101, 'data_abertura' => $agora, 'mesa_numero' => 2, 'quantidade' => 1, 'item_nome' => 'X-Burger'],
        ];

        $stmt = new FakePDOStatement();
        $stmt->fetchAllResult = $dbResult;
        $this->pdoFake->addMockStatement($stmt);

        $resultado = $this->pedidoModel->buscarPedidosParaCozinha(1);

        // Deve retornar 2 pedidos
        $this->assertCount(2, $resultado);
        
        // Verifica Pedido 100
        $this->assertEquals(100, $resultado[0]['id']);
        $this->assertEquals('Mesa 05', $resultado[0]['mesa']);
        $this->assertEquals($hora, $resultado[0]['hora']);
        $this->assertCount(2, $resultado[0]['itens']); // 2 itens
        $this->assertEquals('Pizza', $resultado[0]['itens'][0]['nome']);
        $this->assertEquals(2, $resultado[0]['itens'][0]['quantidade']);
        
        // Verifica Pedido 101
        $this->assertEquals(101, $resultado[1]['id']);
        $this->assertEquals('Mesa 02', $resultado[1]['mesa']);
        $this->assertCount(1, $resultado[1]['itens']); // 1 item
        $this->assertEquals('X-Burger', $resultado[1]['itens'][0]['nome']);
    }

    /** @test */
    public function test_marcarComoPronto_retorna_true_quando_afeta_linhas()
    {
        $stmt = new FakePDOStatement();
        $stmt->rowCountResult = 1; // Afetou 1 linha
        $this->pdoFake->addMockStatement($stmt);

        $this->assertTrue($this->pedidoModel->marcarComoPronto(100, 1));
    }

    /** @test */
    public function test_marcarComoPronto_retorna_false_quando_nao_afeta_linhas()
    {
        $stmt = new FakePDOStatement();
        $stmt->rowCountResult = 0; // Afetou 0 linhas
        $this->pdoFake->addMockStatement($stmt);

        $this->assertFalse($this->pedidoModel->marcarComoPronto(999, 1));
    }

    /** @test */
    public function test_marcarComoPronto_retorna_false_em_pdo_exception()
    {
        $stmt = new FakePDOStatement();
        $stmt->throwException = new PDOException("Erro de DB simulado");
        $this->pdoFake->addMockStatement($stmt);

        $this->assertFalse($this->pedidoModel->marcarComoPronto(100, 1));
    }

    /** @test */
    public function test_buscarPedidosProntosPorEmpresa_retorna_array()
    {
        $dbResult = [
            ['mesa_id' => 1, 'mesa_numero' => 5],
            ['mesa_id' => 2, 'mesa_numero' => 10],
        ];

        $stmt = new FakePDOStatement();
        $stmt->fetchAllResult = $dbResult;
        $this->pdoFake->addMockStatement($stmt);

        $resultado = $this->pedidoModel->buscarPedidosProntosPorEmpresa(1);
        $this->assertEquals($dbResult, $resultado);
    }

    /** @test */
    public function test_buscarPedidosProntosPorEmpresa_retorna_array_vazio_em_pdo_exception()
    {
        $stmt = new FakePDOStatement();
        $stmt->throwException = new PDOException("Erro de DB simulado");
        $this->pdoFake->addMockStatement($stmt);

        // O método deve capturar a exceção e retornar []
        $resultado = $this->pedidoModel->buscarPedidosProntosPorEmpresa(1);
        $this->assertEquals([], $resultado);
    }

    /** @test */
    public function test_criarNovoPedido_caminho_feliz()
    {
        $empresa_id = 1; $mesa_id = 5; $funcionario_id = 2;
        $itens = [
            10 => 2, // 2x Item 10
            11 => 1, // 1x Item 11
        ];

        // 1. Prepara o statement para buscar preços (chamado 1 vez)
        $stmtPreco = new FakePDOStatement();
        // 1a. fetch() para o item 10
        $stmtPreco->fetchStack[] = ['preco' => 10.00, 'nome' => 'Pizza'];
        // 1b. fetch() para o item 11
        $stmtPreco->fetchStack[] = ['preco' => 5.00, 'nome' => 'Pepsi'];
        $this->pdoFake->addMockStatement($stmtPreco);

        // 2. Prepara o statement para INSERIR o pedido (chamado 1 vez)
        $stmtPedido = new FakePDOStatement();
        // 2a. fetchColumn() deve retornar o novo ID
        $stmtPedido->fetchColumnStack[] = 123; // Novo ID do pedido
        $this->pdoFake->addMockStatement($stmtPedido);

        // 3. Prepara o statement para INSERIR os itens (chamado 1 vez)
        $stmtItens = new FakePDOStatement();
        // (será executado 2 vezes, mas preparado 1)
        $this->pdoFake->addMockStatement($stmtItens);

        
        $novoPedidoId = $this->pedidoModel->criarNovoPedido($empresa_id, $mesa_id, $funcionario_id, $itens);

        $this->assertEquals(123, $novoPedidoId);
    }

    /** @test */
    public function test_criarNovoPedido_item_nao_encontrado_dispara_excecao()
    {
        $itens = [999 => 1]; // Item 999 não existe

        // 1. Prepara o statement de preço
        $stmtPreco = new FakePDOStatement();
        $stmtPreco->fetchStack[] = false; // Simula item não encontrado
        $this->pdoFake->addMockStatement($stmtPreco);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("O item com ID 999 não foi encontrado.");

        $this->pedidoModel->criarNovoPedido(1, 1, 1, $itens);
    }
    
    /** @test */
    public function test_criarNovoPedido_item_com_preco_invalido_dispara_excecao()
    {
        $itens = [10 => 1];

        // 1. Prepara o statement de preço
        $stmtPreco = new FakePDOStatement();
        $stmtPreco->fetchStack[] = ['preco' => null, 'nome' => 'Item Quebrado']; // Preço inválido
        $this->pdoFake->addMockStatement($stmtPreco);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("O item 'Item Quebrado' está com um preço inválido.");

        $this->pedidoModel->criarNovoPedido(1, 1, 1, $itens);
    }

    /** @test */
    public function test_buscarItensDoUltimoPedidoDaMesa_calcula_total_e_agrega_corretamente()
    {
        $dbResult = [
            ['pedido_id' => 100, 'status' => 'pronto', 'data_abertura' => '2025-01-01 20:00:00', 'quantidade' => 2, 'preco_unitario_momento' => 5.00, 'item_nome' => 'Pepsi'],
            ['pedido_id' => 100, 'status' => 'pronto', 'data_abertura' => '2025-01-01 20:00:00', 'quantidade' => 1, 'preco_unitario_momento' => 30.00, 'item_nome' => 'Pizza'],
        ];

        $stmt = new FakePDOStatement();
        $stmt->fetchAllResult = $dbResult;
        $this->pdoFake->addMockStatement($stmt);
        
        $pedido = $this->pedidoModel->buscarItensDoUltimoPedidoDaMesa(1, 1);

        $this->assertNotNull($pedido);
        $this->assertEquals(100, $pedido['id']);
        $this->assertEquals('pronto', $pedido['status']);
        $this->assertEquals('20:00', $pedido['hora']);
        $this->assertCount(2, $pedido['itens']);
        
        // (2 * 5.00) + (1 * 30.00) = 10.00 + 30.00 = 40.00
        $this->assertEquals(40.00, $pedido['total']);
    }

    /** @test */
    public function test_buscarItensDoUltimoPedidoDaMesa_retorna_null_se_vazio()
    {
        $stmt = new FakePDOStatement();
        $stmt->fetchAllResult = []; // Nenhum pedido encontrado
        $this->pdoFake->addMockStatement($stmt);
        
        $pedido = $this->pedidoModel->buscarItensDoUltimoPedidoDaMesa(1, 1);

        $this->assertNull($pedido);
    }

    /** @test */
    public function test_buscarPedidosPorMesa_agrupa_corretamente_e_calcula_subtotal()
    {
        $dbResult = [
            // Pedido 100
            ['pedido_id' => 100, 'status' => 'pronto', 'data_abertura' => '2025-01-01 20:00:00', 'quantidade' => 1, 'preco_unitario_momento' => 30.00, 'item_nome' => 'Pizza'],
            // Pedido 101
            ['pedido_id' => 101, 'status' => 'entregue', 'data_abertura' => '2025-01-01 20:10:00', 'quantidade' => 2, 'preco_unitario_momento' => 5.00, 'item_nome' => 'Pepsi'],
            ['pedido_id' => 101, 'status' => 'entregue', 'data_abertura' => '2025-01-01 20:10:00', 'quantidade' => 1, 'preco_unitario_momento' => 15.00, 'item_nome' => 'X-Burger'],
        ];
        
        $stmt = new FakePDOStatement();
        $stmt->fetchAllResult = $dbResult;
        $this->pdoFake->addMockStatement($stmt);

        $pedidos = $this->pedidoModel->buscarPedidosPorMesa(1, 1);

        $this->assertCount(2, $pedidos);

        // Pedido 100
        $this->assertEquals(100, $pedidos[0]['id']);
        $this->assertEquals('pronto', $pedidos[0]['status']);
        $this->assertEquals('20:00', $pedidos[0]['hora']);
        $this->assertCount(1, $pedidos[0]['itens']);
        $this->assertEquals(30.00, $pedidos[0]['subtotal']); // (1 * 30.00)

        // Pedido 101
        $this->assertEquals(101, $pedidos[1]['id']);
        $this->assertEquals('entregue', $pedidos[1]['status']);
        $this->assertEquals('20:10', $pedidos[1]['hora']);
        $this->assertCount(2, $pedidos[1]['itens']);
        $this->assertEquals(25.00, $pedidos[1]['subtotal']); // (2 * 5.00) + (1 * 15.00) = 10 + 15
    }

    /** @test */
    public function test_marcarPedidosDaMesaComoEntregues_retorna_true_com_linhas_afetadas()
    {
        $stmt = new FakePDOStatement();
        $stmt->rowCountResult = 2; // Afetou 2 pedidos
        $this->pdoFake->addMockStatement($stmt);

        $this->assertTrue($this->pedidoModel->marcarPedidosDaMesaComoEntregues(1, 1));
    }

    /** @test */
    public function test_marcarPedidosDaMesaComoEntregues_retorna_false_com_pdo_exception()
    {
        $stmt = new FakePDOStatement();
        $stmt->throwException = new PDOException("Erro de DB simulado");
        $this->pdoFake->addMockStatement($stmt);

        $this->assertFalse($this->pedidoModel->marcarPedidosDaMesaComoEntregues(1, 1));
    }

    /** @test */
    public function test_marcarPedidosDaMesaComoPagos_retorna_true_no_try()
    {
        $stmt = new FakePDOStatement();
        $this->pdoFake->addMockStatement($stmt);

        // O método original retorna true mesmo se rowCount for 0,
        // desde que não dê exceção.
        $this->assertTrue($this->pedidoModel->marcarPedidosDaMesaComoPagos(1, 1));
    }

    /** @test */
    public function test_marcarPedidosDaMesaComoPagos_retorna_false_com_pdo_exception()
    {
        $stmt = new FakePDOStatement();
        $stmt->throwException = new PDOException("Erro de DB simulado");
        $this->pdoFake->addMockStatement($stmt);

        $this->assertFalse($this->pedidoModel->marcarPedidosDaMesaComoPagos(1, 1));
    }
}

