<?php

namespace Tests\Api;

use App\Controllers\Api\EstabelecimentoController;
use App\Models\Mesa;
use Exception;
use PDO;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionProperty;

/**
 * @covers \App\Controllers\Api\EstabelecimentoController
 */
class EstabelecimentoControllerTest extends TestCase
{
    /** @var EstabelecimentoController&MockObject */
    private $controllerMock;

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

        // 1. Criar mocks para as dependências
        $this->mesaModelMock = $this->createMock(Mesa::class);
        $this->pdoMock = $this->createMock(PDO::class);

        // 2. Criar o mock do Controller
        $this->controllerMock = $this->getMockBuilder(EstabelecimentoController::class)
            ->disableOriginalConstructor() 
            ->onlyMethods(['jsonResponse', 'getMesaModel', 'getRawInput']) 
            ->getMock();

        // 3. Configurar os métodos sobrescritos
        
        $this->controllerMock->method('getMesaModel')
            ->willReturn($this->mesaModelMock);

        $this->controllerMock->method('jsonResponse')
            ->willReturnCallback([$this, 'mockJsonResponseCallback']);

        // 4. Injetar dependências privadas (que o construtor faria)
        $this->setPrivateProperty($this->controllerMock, 'pdo', $this->pdoMock);
        $this->setPrivateProperty($this->controllerMock, 'empresa_id', 123); 
    }

    /**
     * Função auxiliar para capturar a saída do jsonResponse mockado.
     */
    public function mockJsonResponseCallback($data, $statusCode = 200)
    {
        $this->capturedResponseData = $data;
        $this->capturedResponseCode = $statusCode;
    }

    /**
     * Função auxiliar para definir propriedades privadas/protegidas em objetos mockados.
     */
    private function setPrivateProperty($object, $propertyName, $value)
    {
        $reflection = new \ReflectionClass(get_parent_class($object)); 
        
        $property = $reflection->getProperty($propertyName); 
        $property->setAccessible(true); // Permite acesso a propriedades privadas
        $property->setValue($object, $value);
    }

    // --- Testes para listarMesas ---

    public function testListarMesasSuccess()
    {
        // Arrange
        $expectedMesas = [['id' => 1, 'numero' => 1], ['id' => 2, 'numero' => 2]];
        $this->mesaModelMock->expects($this->once())
            ->method('buscarTodasPorEmpresa')
            ->with(123) 
            ->willReturn($expectedMesas);

        // Act
        $this->controllerMock->listarMesas();

        // Assert
        $this->assertEquals(200, $this->capturedResponseCode);
        $this->assertEquals(['success' => true, 'data' => $expectedMesas], $this->capturedResponseData);
    }

    public function testListarMesasException()
    {
        // Arrange
        $this->mesaModelMock->expects($this->once())
            ->method('buscarTodasPorEmpresa')
            ->with(123)
            ->willThrowException(new Exception("DB Error"));

        // Act
        $this->controllerMock->listarMesas();

        // Assert
        $this->assertEquals(500, $this->capturedResponseCode);
        $this->assertEquals(false, $this->capturedResponseData['success']);
        $this->assertStringContainsString('DB Error', $this->capturedResponseData['message']);
    }

    // --- Testes para criarMesas ---

    public function testCriarMesasSuccess()
    {
        // Arrange
        $this->controllerMock->method('getRawInput')->willReturn('{"quantidade": 2}');
        
        // Configura a transação
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('commit');
        $this->pdoMock->expects($this->never())->method('rollBack');

        $this->mesaModelMock->expects($this->once())
            ->method('buscarUltimoNumero')
            ->with(123)
            ->willReturn(10); // Próxima mesa será a 11

        // ===================================================================
        // == CORREÇÃO AQUI (Linha 140) ==
        // O método 'withConsecutive()' não existe no PHPUnit 12.
        // Substituímos por um 'willReturnCallback' que conta as chamadas.
        // ===================================================================
        $callCount = 0;
        $this->mesaModelMock->expects($this->exactly(2))
            ->method('criar')
            ->willReturnCallback(function ($data) use (&$callCount) {
                if ($callCount === 0) {
                    // Verificação da primeira chamada
                    $this->assertEquals(['empresa_id' => 123, 'numero' => 11, 'status' => 'disponivel'], $data);
                } else if ($callCount === 1) {
                    // Verificação da segunda chamada
                    $this->assertEquals(['empresa_id' => 123, 'numero' => 12, 'status' => 'disponivel'], $data);
                }
                $callCount++;
                return true; // Retorna 'true' como o mock original faria
            });
        
        // Act
        $this->controllerMock->criarMesas();

        // Assert
        $this->assertEquals(200, $this->capturedResponseCode);
        $this->assertEquals(true, $this->capturedResponseData['success']);
        $this->assertStringContainsString('2 mesas criadas', $this->capturedResponseData['message']);
    }

    public function testCriarMesasQuantidadeInvalida()
    {
        // Arrange
        $this->controllerMock->method('getRawInput')->willReturn('{"quantidade": 999}');
        
        // Act
        $this->controllerMock->criarMesas();

        // Assert
        $this->assertEquals(400, $this->capturedResponseCode);
        $this->assertStringContainsString('Quantidade inválida', $this->capturedResponseData['message']);
    }

    public function testCriarMesasRollbackOnException()
    {
        // Arrange
        $this->controllerMock->method('getRawInput')->willReturn('{"quantidade": 1}');
        
        // Configura a transação
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->never())->method('commit'); // Commit não deve ser chamado
        $this->pdoMock->expects($this->once())->method('rollBack'); // Rollback DEVE ser chamado

        $this->mesaModelMock->expects($this->once())
            ->method('buscarUltimoNumero')
            ->willReturn(10);

        $this->mesaModelMock->expects($this->once())
            ->method('criar')
            ->willThrowException(new Exception("Falha no SQL"));

        // Act
        $this->controllerMock->criarMesas();

        // Assert
        $this->assertEquals(500, $this->capturedResponseCode);
        $this->assertStringContainsString('Falha no SQL', $this->capturedResponseData['message']);
    }

    // --- Testes para excluirMesa ---

    public function testExcluirMesaSuccess()
    {
        // Arrange
        $this->controllerMock->method('getRawInput')->willReturn('{"id": 5}');
        $mesaMock = (object) [
            'id' => 5,
            'empresa_id' => 123, // Pertence à empresa
            'status' => 'disponivel' // Está disponível
        ];

        $this->mesaModelMock->expects($this->once())
            ->method('buscarPorId')
            ->with(5)
            ->willReturn($mesaMock);
        
        $this->mesaModelMock->expects($this->once())
            ->method('excluir')
            ->with(5);

        // Act
        $this->controllerMock->excluirMesa();

        // Assert
        $this->assertEquals(200, $this->capturedResponseCode);
        $this->assertEquals(true, $this->capturedResponseData['success']);
    }

    public function testExcluirMesaNaoEncontrada()
    {
        // Arrange
        $this->controllerMock->method('getRawInput')->willReturn('{"id": 999}');
        
        $this->mesaModelMock->expects($this->once())
            ->method('buscarPorId')
            ->with(999)
            ->willReturn(false); // Mesa não existe

        // Act
        $this->controllerMock->excluirMesa();

        // Assert
        $this->assertEquals(404, $this->capturedResponseCode);
        $this->assertStringContainsString('Mesa não encontrada', $this->capturedResponseData['message']);
    }

    public function testExcluirMesaOutraEmpresa()
    {
        // Arrange
        $this->controllerMock->method('getRawInput')->willReturn('{"id": 5}');
        $mesaMock = (object) [
            'id' => 5,
            'empresa_id' => 999, // NÃO pertence à empresa 123
            'status' => 'disponivel'
        ];

        $this->mesaModelMock->expects($this->once())
            ->method('buscarPorId')
            ->with(5)
            ->willReturn($mesaMock);
        
        // Act
        $this->controllerMock->excluirMesa();

        // Assert
        $this->assertEquals(404, $this->capturedResponseCode);
        $this->assertStringContainsString('não pertence', $this->capturedResponseData['message']);
    }

    public function testExcluirMesaOcupada()
    {
        // Arrange
        $this->controllerMock->method('getRawInput')->willReturn('{"id": 5}');
        $mesaMock = (object) [
            'id' => 5,
            'empresa_id' => 123,
            'status' => 'ocupada' // NÃO está disponível
        ];

        $this->mesaModelMock->expects($this->once())
            ->method('buscarPorId')
            ->with(5)
            ->willReturn($mesaMock);
        
        // Act
        $this->controllerMock->excluirMesa();

        // Assert
        $this->assertEquals(400, $this->capturedResponseCode);
        $this->assertStringContainsString('mesa que está ocupada', $this->capturedResponseData['message']);
    }
}