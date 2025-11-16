<?php

namespace Tests\Api;

use App\Controllers\Api\AuthController;
use App\Models\Funcionario;
use Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \App\Controllers\Api\AuthController
 */
class AuthControllerTest extends TestCase
{
    /** @var AuthController&MockObject */
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
        $this->controllerMock = $this->getMockBuilder(AuthController::class)
            ->onlyMethods([
                'jsonResponse', 
                'getJsonData',
                'getFuncionarioModel',
                'startUserSession', 
                'destroyUserSession'
            ])
            ->getMock();

        // 3. Configurar os métodos
        $this->controllerMock->method('getFuncionarioModel')
            ->willReturn($this->modelMock);

        $this->controllerMock->method('jsonResponse')
            ->willReturnCallback([$this, 'mockJsonResponseCallback']);
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

    // --- Testes de Login ---

    public function testLoginSuccess()
    {
        // Arrange
        $input = [
            'empresa_id' => 1,
            'email' => 'garcom@teste.com',
            'senha' => 'senha123'
        ];
        $userData = [
            'id' => 10,
            'nome' => 'Garçom Teste',
            'nome_cargo' => 'Garçom', 
            'empresa_id' => 1
        ];
        
        $this->controllerMock->method('getJsonData')->willReturn($input);

        // Configura o Model
        $this->modelMock->expects($this->once())
            ->method('validarLogin')
            ->with(1, 'garcom@teste.com', 'senha123')
            ->willReturn($userData);
        
        // Configura o mock de sessão
        $this->controllerMock->expects($this->once())
            ->method('startUserSession')
            ->with($userData); 

        // Act
        $this->controllerMock->login();

        // Assert
        $this->assertEquals(200, $this->capturedResponseCode);
        $this->assertEquals(true, isset($this->capturedResponseData['redirectTo']));
        $this->assertEquals('/dashboard/garcom', $this->capturedResponseData['redirectTo']);
    }

    public function testLoginFail_InvalidCredentials()
    {
        // Arrange
        $input = ['empresa_id' => 1, 'email' => 'a@b.com', 'senha' => 'errada'];
        $this->controllerMock->method('getJsonData')->willReturn($input);

        // Configura o Model para falhar a validação
        $this->modelMock->expects($this->once())
            ->method('validarLogin')
            ->willReturn(false); // Login falhou
        
        // A sessão NUNCA deve ser iniciada
        $this->controllerMock->expects($this->never())
            ->method('startUserSession');

        // Act
        $this->controllerMock->login();

        // Assert
        $this->assertEquals(401, $this->capturedResponseCode);
        $this->assertStringContainsString('incorretos', $this->capturedResponseData['message']);
    }

    public function testLoginFail_MissingFields()
    {
        // Arrange
        $input = ['email' => 'a@b.com', 'senha' => '123']; // Falta empresa_id
        $this->controllerMock->method('getJsonData')->willReturn($input);

        // O Model NUNCA deve ser chamado
        $this->modelMock->expects($this->never())->method('validarLogin');

        // Act
        $this->controllerMock->login();

        // Assert
        // == CORREÇÃO AQUI (Linha 149) ==
        $this->assertEquals(400, $this->capturedResponseCode);
        $this->assertStringContainsString('obrigatórios', $this->capturedResponseData['message']);
    }

    public function testLoginFail_DatabaseException()
    {
        // Arrange
        $input = ['empresa_id' => 1, 'email' => 'a@b.com', 'senha' => '123'];
        $this->controllerMock->method('getJsonData')->willReturn($input);

        // Configura o Model para lançar uma exceção
        // == CORREÇÃO AQUI (Linha 162) ==
        $this->modelMock->expects($this->once())
            ->method('validarLogin')
            ->willThrowException(new Exception("Erro de DB simulado"));
        
        // A sessão NUNCA deve ser iniciada
        // == CORREÇÃO AQUI ==
        $this->controllerMock->expects($this->never())
            ->method('startUserSession');

        // Act
        // == CORREÇÃO AQUI ==
        $this->controllerMock->login();

        // Assert
        $this->assertEquals(401, $this->capturedResponseCode);
        $this->assertStringContainsString('Erro de DB simulado', $this->capturedResponseData['message']);
    }

    // --- Teste de Logout ---

    public function testLogoutSuccess()
    {
        // Arrange
        // Esperamos que o método que destrói a sessão seja chamado
        // == CORREÇÃO AQUI (Linha 183) ==
        $this->controllerMock->expects($this->once())
            ->method('destroyUserSession');

        // Act
        // == CORREÇÃO AQUI ==
        $this->controllerMock->logout();

        // Assert
        $this->assertEquals(200, $this->capturedResponseCode);
        $this->assertStringContainsString('Logout efetuado', $this->capturedResponseData['message']);
    }
}