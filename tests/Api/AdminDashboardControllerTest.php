<?php

namespace Tests\Api;

use App\Controllers\Api\AdminDashboardController;
use App\Models\AdminDashboardModel;
use Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \App\Controllers\Api\AdminDashboardController
 */
class AdminDashboardControllerTest extends TestCase
{
    /** @var AdminDashboardController&MockObject */
    private $controllerMock;

    /** @var AdminDashboardModel&MockObject */
    private $modelMock;

    // Propriedades para capturar a saída
    private $capturedResponseData;
    private $capturedResponseCode;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Criar Mocks
        $this->modelMock = $this->createMock(AdminDashboardModel::class);

        // 2. Criar Mock do Controller
        $this->controllerMock = $this->getMockBuilder(AdminDashboardController::class)
            ->disableOriginalConstructor() // Pula o __construct() (que usa $_SESSION e DB)
            ->onlyMethods([
                'jsonResponse',
                'jsonError', // Precisamos mockar o jsonError também
                'getDashboardModel'
            ])
            ->getMock();

        // 3. Configurar os mocks dos getters
        $this->controllerMock->method('getDashboardModel')->willReturn($this->modelMock);

        // 4. Configurar mocks de "efeitos colaterais"
        $this->controllerMock->method('jsonResponse')
            ->willReturnCallback([$this, 'mockJsonResponseCallback']);
        
        $this->controllerMock->method('jsonError')
            ->willReturnCallback([$this, 'mockJsonErrorCallback']);

        // 5. Injetar a propriedade privada 'empresa_id' que o construtor pularia
        $this->setPrivateProperty($this->controllerMock, 'empresa_id', 123);
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
     * Callback para capturar a saída do jsonError
     */
    public function mockJsonErrorCallback($message, $statusCode = 500)
    {
        // O jsonError geralmente envia um formato {'message': ...}
        $this->capturedResponseData = ['success' => false, 'message' => $message];
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

    // --- Teste de Sucesso ---

    public function testGetDadosDashboardSuccess()
    {
        // Arrange
        $metricasMock = ['total_vendas' => 1500, 'pedidos_hoje' => 30];
        $pedidosMock = [
            ['id' => 10, 'valor_total' => 50.00],
            ['id' => 9, 'valor_total' => 25.50]
        ];

        // Configura o Model
        $this->modelMock->expects($this->once())
            ->method('getMetricas')
            ->with(123) // Verifica se usou a empresa_id injetada
            ->willReturn($metricasMock);
            
        $this->modelMock->expects($this->once())
            ->method('getPedidosRecentes')
            ->with(123)
            ->willReturn($pedidosMock);

        // Act
        $this->controllerMock->getDadosDashboard();

        // Assert
        $this->assertEquals(200, $this->capturedResponseCode);
        $this->assertEquals(true, $this->capturedResponseData['success']);
        $this->assertEquals($metricasMock, $this->capturedResponseData['data']['metricas']);
        $this->assertEquals($pedidosMock, $this->capturedResponseData['data']['pedidos_recentes']);
    }

    // --- Teste de Exceção ---

    public function testGetDadosDashboardException()
    {
        // Arrange
        // Simula uma falha no primeiro método do model
        $this->modelMock->expects($this->once())
            ->method('getMetricas')
            ->with(123)
            ->willThrowException(new Exception("Erro de conexão com o banco"));

        // O segundo método do model (getPedidosRecentes) nunca deve ser chamado
        $this->modelMock->expects($this->never())
            ->method('getPedidosRecentes');

        // Act
        $this->controllerMock->getDadosDashboard();

        // Assert
        $this->assertEquals(500, $this->capturedResponseCode);
        $this->assertEquals(false, $this->capturedResponseData['success']);
        $this->assertStringContainsString('Erro de conexão com o banco', $this->capturedResponseData['message']);
    }
}