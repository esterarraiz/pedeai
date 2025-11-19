<?php

namespace Tests\Api;

use App\Controllers\Api\FuncionarioController;
use App\Models\Funcionario;
use Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \App\Controllers\Api\FuncionarioController
 */
class FuncionarioControllerTest extends TestCase
{
    /** @var FuncionarioController&MockObject */
    private $controllerMock;

    /** @var Funcionario&MockObject */
    private $modelMock;

    // Propriedades para capturar a saída
    private $capturedResponseData;
    private $capturedResponseCode;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Criar mock do Model
        $this->modelMock = $this->createMock(Funcionario::class);

        // 2. Criar mock do Controller
        $this->controllerMock = $this->getMockBuilder(FuncionarioController::class)
            ->disableOriginalConstructor() // Pula o __construct() (que usa $_SESSION e DB)
            ->onlyMethods(['jsonResponse', 'getFuncionarioModel', 'getJsonData']) // Métodos que vamos sobrescrever
            ->getMock();

        // 3. Configurar os métodos sobrescritos
        $this->controllerMock->method('getFuncionarioModel')
            ->willReturn($this->modelMock);

        $this->controllerMock->method('jsonResponse')
            ->willReturnCallback([$this, 'mockJsonResponseCallback']);
        
        // 4. Injetar a dependência (empresa_id) que o construtor pularia
        // Por padrão, simulamos um login bem-sucedido
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
     * Helper para definir propriedades privadas (usando a correção do get_parent_class)
     */
    private function setPrivateProperty($object, $propertyName, $value)
    {
        $reflection = new \ReflectionClass(get_parent_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    // --- Testes para listar ---

    public function testListarSuccess()
    {
        // Arrange
        $expected = [['id' => 1, 'nome' => 'Funcionário Teste']];
        $this->modelMock->expects($this->once())
            ->method('buscarTodosPorEmpresa')
            ->with(123) // Verifica se o ID da empresa (injetado) foi usado
            ->willReturn($expected);

        // Act
        $this->controllerMock->listar();

        // Assert
        $this->assertEquals(200, $this->capturedResponseCode);
        $this->assertEquals($expected, $this->capturedResponseData);
    }

    public function testListarSemEmpresaId()
    {
        // Arrange
        // Sobrescreve a 'empresa_id' do setUp para este teste
        $this->setPrivateProperty($this->controllerMock, 'empresa_id', null);

        // Act
        $this->controllerMock->listar();

        // Assert
        $this->assertEquals(500, $this->capturedResponseCode);
        $this->assertStringContainsString('Empresa não identificada', $this->capturedResponseData['message']);
    }

    // --- Testes para getFuncionario ---

    public function testGetFuncionarioSuccess()
    {
        // Arrange
        $expected = (object) ['id' => 10, 'nome' => 'Funcionário 10'];
        $this->modelMock->expects($this->once())
            ->method('buscarPorId')
            ->with(10)
            ->willReturn($expected);

        // Act
        $this->controllerMock->getFuncionario(['id' => 10]);

        // Assert
        $this->assertEquals(200, $this->capturedResponseCode);
        $this->assertEquals($expected, $this->capturedResponseData);
    }

    public function testGetFuncionarioSemId()
    {
        // Arrange (sem ID nos parâmetros)
        
        // Act
        $this->controllerMock->getFuncionario([]);

        // Assert
        $this->assertEquals(400, $this->capturedResponseCode);
        $this->assertStringContainsString('ID não fornecido', $this->capturedResponseData['message']);
    }

    // --- Testes para criar ---

    public function testCriarSuccess()
    {
        // Arrange
        $input = [
            'nome' => 'Novo Funcionário',
            'email' => 'novo@teste.com',
            'cargo_id' => 2,
            'senha' => 'senha123'
        ];
        $this->controllerMock->method('getJsonData')->willReturn($input);
        
        $this->modelMock->expects($this->once())
            ->method('criar')
            ->with(123, 2, 'Novo Funcionário', 'novo@teste.com', 'senha123')
            ->willReturn(true);

        // Act
        $this->controllerMock->criar();

        // Assert
        $this->assertEquals(200, $this->capturedResponseCode);
        $this->assertEquals(true, $this->capturedResponseData['success']);
    }

    public function testCriarCamposFaltando()
    {
        // Arrange
        $input = ['nome' => 'Incompleto']; // Faltam email, cargo, senha
        $this->controllerMock->method('getJsonData')->willReturn($input);

        // Act
        $this->controllerMock->criar();

        // Assert
        $this->assertEquals(400, $this->capturedResponseCode);
        $this->assertStringContainsString('Todos os campos são obrigatórios', $this->capturedResponseData['message']);
    }

    public function testCriarSemEmpresaId()
    {
        // Arrange
        $this->setPrivateProperty($this->controllerMock, 'empresa_id', null);
        $input = [
            'nome' => 'Novo Funcionário',
            'email' => 'novo@teste.com',
            'cargo_id' => 2,
            'senha' => 'senha123'
        ];
        $this->controllerMock->method('getJsonData')->willReturn($input);

        // Act
        $this->controllerMock->criar();

        // Assert (A validação !$this->empresa_id deve falhar)
        $this->assertEquals(400, $this->capturedResponseCode);
        $this->assertStringContainsString('Todos os campos são obrigatórios', $this->capturedResponseData['message']);
    }

    // --- Testes para atualizar ---

    public function testAtualizarSuccess()
    {
        // Arrange
        $input = [
            'id' => 5,
            'nome' => 'Nome Atualizado',
            'email' => 'atualizado@teste.com',
            'cargo_id' => 3,
            'senha' => 'novaSenha' // Testando com senha
        ];
        $this->controllerMock->method('getJsonData')->willReturn($input);
        
        $this->modelMock->expects($this->once())
            ->method('atualizar')
            ->with(5, 3, 'Nome Atualizado', 'atualizado@teste.com', 'novaSenha')
            ->willReturn(true);

        // Act
        $this->controllerMock->atualizar();

        // Assert
        $this->assertEquals(200, $this->capturedResponseCode);
        $this->assertEquals(true, $this->capturedResponseData['success']);
    }

    public function testAtualizarSemSenha()
    {
        // Arrange
        $input = [
            'id' => 5,
            'nome' => 'Nome Atualizado',
            'email' => 'atualizado@teste.com',
            'cargo_id' => 3,
            'senha' => null // Testando sem senha
        ];
        $this->controllerMock->method('getJsonData')->willReturn($input);
        
        $this->modelMock->expects($this->once())
            ->method('atualizar')
            ->with(5, 3, 'Nome Atualizado', 'atualizado@teste.com', null) // Deve passar null
            ->willReturn(true);

        // Act
        $this->controllerMock->atualizar();

        // Assert
        $this->assertEquals(200, $this->capturedResponseCode);
    }

    // --- Testes para toggleStatus ---

    public function testToggleStatusSuccess()
    {
        // Arrange
        $input = ['id' => 7, 'status' => false];
        $this->controllerMock->method('getJsonData')->willReturn($input);

        $this->modelMock->expects($this->once())
            ->method('atualizarStatus')
            ->with(7, false) // Testa a conversão para booleano
            ->willReturn(true);
            
        // Act
        $this->controllerMock->toggleStatus();

        // Assert
        $this->assertEquals(200, $this->capturedResponseCode);
        $this->assertEquals(true, $this->capturedResponseData['success']);
    }

    // --- Testes para redefinirSenha ---
    
    public function testRedefinirSenhaSuccess()
    {
        // Arrange
        $input = ['id' => 8, 'senha' => 'senhaForte'];
        $this->controllerMock->method('getJsonData')->willReturn($input);

        $this->modelMock->expects($this->once())
            ->method('redefinirSenha')
            ->with(8, 'senhaForte')
            ->willReturn(true);
            
        // Act
        $this->controllerMock->redefinirSenha();

        // Assert
        $this->assertEquals(200, $this->capturedResponseCode);
        $this->assertStringContainsString('Senha redefinida', $this->capturedResponseData['message']);
    }
}