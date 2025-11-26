<?php

namespace Tests\Api;

use App\Controllers\Api\PedidoController;
use App\Models\PedidoModel;
use App\Models\Mesa;
use Exception;
use PDO;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \App\Controllers\Api\PedidoController
 */
class PedidoControllerTest extends TestCase
{
    /** @var PedidoController&MockObject */
    private $controllerMock;

    /** @var PedidoModel&MockObject */
    private $pedidoModelMock;

    /** @var Mesa&MockObject */
    private $mesaModelMock;

    /** @var PDO&MockObject */
    private $pdoMock;

    // Propriedades para capturar a saída
    private $capturedResponseData;
    private $capturedResponseCode;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Criar mocks
        $this->pedidoModelMock = $this->createMock(PedidoModel::class);
        $this->mesaModelMock = $this->createMock(Mesa::class);
        $this->pdoMock = $this->createMock(PDO::class);

        // 2. Criar mock do Controller
        $this->controllerMock = $this->getMockBuilder(PedidoController::class)
            ->disableOriginalConstructor() // Pula o __construct() (que usa $_SESSION)
            ->onlyMethods([
                'jsonResponse', 
                'getJsonData', 
                'getPedidoModel', 
                'getMesaModel', 
                'getPdo' // Mockar todos os métodos de injeção
            ])
            ->getMock();

        // 3. Configurar os métodos sobrescritos
        $this->controllerMock->method('getPedidoModel')->willReturn($this->pedidoModelMock);
        $this->controllerMock->method('getMesaModel')->willReturn($this->mesaModelMock);
        $this->controllerMock->method('getPdo')->willReturn($this->pdoMock);

        $this->controllerMock->method('jsonResponse')
            ->willReturnCallback([$this, 'mockJsonResponseCallback']);
        
        // 4. Injetar as dependências (sessão)
        $this->setPrivateProperty($this->controllerMock, 'empresa_id', 123);
        $this->setPrivateProperty($this->controllerMock, 'funcionario_id', 456);
    }

    /**
     * Callback para capturar a saída do jsonResponse
     */
    public function mockJsonResponseCallback($data, $statusCode = 200)
    {
        $this->capturedResponseData = $data;
        $this->capturedResponseCode = $statusCode;
    }

    /**
     * Helper para definir propriedades privadas
     */
    private function setPrivateProperty($object, $propertyName, $value)
    {
        $reflection = new \ReflectionClass(get_parent_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    // --- Testes para criarPedido ---

    public function testCriarPedidoSuccess()
    {
        // Arrange
        $input = ['mesa_id' => 10, 'itens' => ['1' => 2, '5' => 1]]; // item_id => qtd
        $this->controllerMock->method('getJsonData')->willReturn($input);

        // Configura a transação
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('commit');
        $this->pdoMock->expects($this->never())->method('rollBack');

        // Configura o PedidoModel
        $this->pedidoModelMock->expects($this->once())
            ->method('criarNovoPedido')
            ->with(123, 10, 456, ['1' => 2, '5' => 1]) // Verifica todos os IDs
            ->willReturn(99); // Retorna o novo ID do pedido
        
        // Configura o MesaModel
        $this->mesaModelMock->expects($this->once())
            ->method('atualizarStatus')
            ->with(10, 'ocupada');

        // Act
        $this->controllerMock->criarPedido();

        // Assert
        $this->assertEquals(200, $this->capturedResponseCode);
        $this->assertStringContainsString('Pedido #99', $this->capturedResponseData['message']);
    }

    public function testCriarPedidoSemItens()
    {
        // Arrange
        $input = ['mesa_id' => 10, 'itens' => []]; // Sem itens
        $this->controllerMock->method('getJsonData')->willReturn($input);
        
        // Act
        $this->controllerMock->criarPedido();

        // Assert
        $this->assertEquals(400, $this->capturedResponseCode);
        $this->assertStringContainsString('Pedido inválido', $this->capturedResponseData['message']);
    }

    public function testCriarPedidoSemSessao()
    {
        // Arrange
        $this->setPrivateProperty($this->controllerMock, 'funcionario_id', null); // Simula sessão inválida
        $input = ['mesa_id' => 10, 'itens' => ['1' => 2]];
        $this->controllerMock->method('getJsonData')->willReturn($input);
        
        // Act
        $this->controllerMock->criarPedido();

        // Assert
        $this->assertEquals(401, $this->capturedResponseCode);
        $this->assertStringContainsString('Sessão inválida', $this->capturedResponseData['message']);
    }

    public function testCriarPedidoRollbackOnException()
    {
        // Arrange
        $input = ['mesa_id' => 10, 'itens' => ['1' => 2]];
        $this->controllerMock->method('getJsonData')->willReturn($input);

        // Configura a transação para falhar
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->never())->method('commit');
        $this->pdoMock->expects($this->once())->method('rollBack');
        $this->pdoMock->expects($this->once())->method('inTransaction')->willReturn(true);

        // Configura o PedidoModel para lançar uma exceção
        $this->pedidoModelMock->expects($this->once())
            ->method('criarNovoPedido')
            ->willThrowException(new Exception("Erro de DB simulado"));
        
        // O MesaModel não deve ser chamado
        $this->mesaModelMock->expects($this->never())->method('atualizarStatus');

        // Act
        $this->controllerMock->criarPedido();

        // Assert
        $this->assertEquals(500, $this->capturedResponseCode);
        $this->assertStringContainsString('Erro de DB simulado', $this->capturedResponseData['message']);
    }

    // --- Testes para getPedidosProntos ---

    public function testGetPedidosProntosSuccess()
    {
        // Arrange
        $expected = [['id' => 1, 'status' => 'pronto']];
        $this->pedidoModelMock->expects($this->once())
            ->method('buscarPedidosProntosPorEmpresa')
            ->with(123)
            ->willReturn($expected);

        // Act
        $this->controllerMock->getPedidosProntos();

        // Assert
        $this->assertEquals(200, $this->capturedResponseCode);
        $this->assertEquals(['pedidos' => $expected], $this->capturedResponseData);
    }

    // --- Testes para marcarPedidoEntregue ---

    public function testMarcarPedidoEntregueSuccess()
    {
        // Arrange
        $this->controllerMock->method('getJsonData')->willReturn(['pedido_id' => 20]);
        
        $this->pedidoModelMock->expects($this->once())
            ->method('marcarPedidosDaMesaComoEntregues') 
            ->with(20, 123) 
            ->willReturn(true);
        
        // Act
        $this->controllerMock->marcarPedidoEntregue();

        // Assert
        $this->assertEquals(200, $this->capturedResponseCode);
        $this->assertStringContainsString('marcado como entregue', $this->capturedResponseData['message']);
    }

    public function testMarcarPedidoEntregueFail()
    {
        // Arrange
        $this->controllerMock->method('getJsonData')->willReturn(['pedido_id' => 20]);
        
        $this->pedidoModelMock->expects($this->once())
            ->method('marcarPedidosDaMesaComoEntregues')
            ->with(20, 123)
            ->willReturn(false); // Model falhou (0 linhas afetadas)
        
        // Act
        $this->controllerMock->marcarPedidoEntregue();

        // Assert
        $this->assertEquals(500, $this->capturedResponseCode);
        $this->assertStringContainsString('Falha ao marcar pedido', $this->capturedResponseData['message']);
    }

    // --- Testes para marcarPedidoPronto ---

    public function testMarcarPedidoProntoSuccess()
    {
        // Arrange
        $this->controllerMock->method('getJsonData')->willReturn(['id' => 30]);
        $this->pedidoModelMock->expects($this->once())
            ->method('marcarComoPronto')
            ->with(30, 123)
            ->willReturn(true);
        
        // Act
        $this->controllerMock->marcarPedidoPronto();

        // Assert
        $this->assertEquals(200, $this->capturedResponseCode);
        $this->assertStringContainsString('marcado como pronto', $this->capturedResponseData['message']);
    }

    public function testMarcarPedidoProntoFail()
    {
        // Arrange
        $this->controllerMock->method('getJsonData')->willReturn(['id' => 30]);
        $this->pedidoModelMock->expects($this->once())
            ->method('marcarComoPronto')
            ->with(30, 123)
            ->willReturn(false); // Model falhou (0 linhas afetadas)
        
        // Act
        $this->controllerMock->marcarPedidoPronto();

        // Assert
        $this->assertEquals(500, $this->capturedResponseCode);
        // Verifica a exceção que o controller lança
        $this->assertStringContainsString('não pôde ser atualizado', $this->capturedResponseData['message']);
    }

    public function testMarcarPedidoProntoException()
    {
        // Arrange
        $this->controllerMock->method('getJsonData')->willReturn(['id' => 30]);
        $this->pedidoModelMock->expects($this->once())
            ->method('marcarComoPronto')
            ->with(30, 123)
            ->willThrowException(new Exception("Erro de PDO"));
        
        // Act
        $this->controllerMock->marcarPedidoPronto();

        // Assert
        $this->assertEquals(500, $this->capturedResponseCode);
        $this->assertStringContainsString('Erro de PDO', $this->capturedResponseData['message']);
    }
}