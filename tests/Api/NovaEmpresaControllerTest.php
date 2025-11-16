<?php

namespace Tests\Api;

use App\Controllers\Api\NovaEmpresaController;
use App\Models\EmpresaModel;
use App\Models\DetalhesEmpresasModel;
use App\Models\Funcionario;
use Exception;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \App\Controllers\Api\NovaEmpresaController
 */
class NovaEmpresaControllerTest extends TestCase
{
    /** @var NovaEmpresaController&MockObject */
    private $controllerMock;

    /** @var EmpresaModel&MockObject */
    private $empresaModelMock;

    /** @var DetalhesEmpresasModel&MockObject */
    private $detalhesModelMock;

    /** @var Funcionario&MockObject */
    private $funcionarioModelMock;

    /** @var PDO&MockObject */
    private $pdoMock;

    // Propriedades para capturar a saída
    private $capturedResponseData;
    private $capturedResponseCode;

    // Dados de input válidos para os testes
    private $validInput;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Criar Mocks
        $this->pdoMock = $this->createMock(PDO::class);
        $this->empresaModelMock = $this->createMock(EmpresaModel::class);
        $this->detalhesModelMock = $this->createMock(DetalhesEmpresasModel::class);
        $this->funcionarioModelMock = $this->createMock(Funcionario::class);

        // 2. Criar Mock do Controller
        $this->controllerMock = $this->getMockBuilder(NovaEmpresaController::class)
            ->onlyMethods([
                'jsonResponse',
                'getPdo',
                'getEmpresaModel',
                'getDetalhesEmpresasModel',
                'getFuncionarioModel',
                'getRawInput',
                'setSuccessSession'
            ])
            ->getMock();

        // 3. Configurar os mocks dos getters
        $this->controllerMock->method('getPdo')->willReturn($this->pdoMock);
        $this->controllerMock->method('getEmpresaModel')->willReturn($this->empresaModelMock);
        $this->controllerMock->method('getDetalhesEmpresasModel')->willReturn($this->detalhesModelMock);
        $this->controllerMock->method('getFuncionarioModel')->willReturn($this->funcionarioModelMock);

        // 4. Configurar mocks de "efeitos colaterais"
        $this->controllerMock->method('jsonResponse')
            ->willReturnCallback([$this, 'mockJsonResponseCallback']);

        // 5. Definir um input padrão válido
        $this->validInput = [
            'cnpj' => '12.345.678/0001-99',
            'nome_estabelecimento' => 'Restaurante Teste',
            'nome_proprietario' => 'Proprietário Teste',
            'email' => 'teste@email.com',
            'telefone' => '99999-9999',
            'endereco' => 'Rua dos Testes, 123',
            'senha' => 'senha123',
            'confirm_senha' => 'senha123'
        ];
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
     * Helper para mockar o input JSON
     */
    private function mockRawInput(array $data)
    {
        $this->controllerMock->method('getRawInput')
            ->willReturn(json_encode($data));
    }

    // --- Teste de Sucesso ---

    public function testProcessRegistrationSuccess()
    {
        // Arrange
        $this->mockRawInput($this->validInput);
        $this->detalhesModelMock->expects($this->once())->method('findByEmail')->willReturn(false);
        $this->funcionarioModelMock->expects($this->once())->method('buscarPorEmail')->willReturn(false);
        $this->detalhesModelMock->expects($this->once())->method('findByCnpj')->willReturn(false);
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->once())->method('commit');
        $this->pdoMock->expects($this->never())->method('rollBack');
        $this->empresaModelMock->expects($this->once())
            ->method('create')
            ->with('Restaurante Teste')
            ->willReturn(99); 
        $this->detalhesModelMock->expects($this->once())
            ->method('create')
            ->with(99, $this->validInput); 
        $this->funcionarioModelMock->expects($this->once())
            ->method('criar')
            ->with(99, 1, 'Proprietário Teste', 'teste@email.com', 'senha123');
        $this->controllerMock->expects($this->once())
            ->method('setSuccessSession')
            ->with('Conta criada com sucesso! Faça o login.');

        // Act
        $this->controllerMock->processRegistration();

        // Assert
        $this->assertEquals(200, $this->capturedResponseCode);
        $this->assertEquals(true, $this->capturedResponseData['success']);
        $this->assertStringContainsString('Conta criada com sucesso', $this->capturedResponseData['message']);
    }

    // --- Testes de Validação ---

    public function testProcessRegistrationFail_MissingFields()
    {
        // Arrange
        $invalidInput = $this->validInput;
        unset($invalidInput['cnpj']); 
        $this->mockRawInput($invalidInput);
        $this->detalhesModelMock->expects($this->never())->method('findByEmail');
        $this->pdoMock->expects($this->never())->method('beginTransaction');

        // Act
        $this->controllerMock->processRegistration();

        // Assert
        $this->assertEquals(400, $this->capturedResponseCode);
        $this->assertStringContainsString('obrigatórios devem ser preenchidos', $this->capturedResponseData['message']);
    }

    public function testProcessRegistrationFail_PasswordMismatch()
    {
        // Arrange
        $invalidInput = $this->validInput;
        $invalidInput['confirm_senha'] = 'senhaERRADA';
        $this->mockRawInput($invalidInput);

        // Act
        $this->controllerMock->processRegistration();

        // Assert
        $this->assertEquals(400, $this->capturedResponseCode);
        $this->assertStringContainsString('As senhas não conferem', $this->capturedResponseData['message']);
    }

    // --- Testes de Duplicidade ---

    public function testProcessRegistrationFail_EmailExists()
    {
        // Arrange
        $this->mockRawInput($this->validInput);
        $this->detalhesModelMock->expects($this->once())
            ->method('findByEmail')
            ->willReturn(true); 
        $this->detalhesModelMock->expects($this->never())->method('findByCnpj');
        $this->pdoMock->expects($this->never())->method('beginTransaction');

        // Act
        $this->controllerMock->processRegistration();

        // Assert
        $this->assertEquals(400, $this->capturedResponseCode);
        $this->assertStringContainsString('e-mail já está cadastrado', $this->capturedResponseData['message']);
    }

    public function testProcessRegistrationFail_CnpjExists()
    {
        // Arrange
        $this->mockRawInput($this->validInput);
        $this->detalhesModelMock->expects($this->once())->method('findByEmail')->willReturn(false);
        $this->funcionarioModelMock->expects($this->once())->method('buscarPorEmail')->willReturn(false);
        $this->detalhesModelMock->expects($this->once())
            ->method('findByCnpj')
            ->willReturn(true); 
        $this->pdoMock->expects($this->never())->method('beginTransaction');

        // Act
        $this->controllerMock->processRegistration();

        // Assert
        $this->assertEquals(400, $this->capturedResponseCode);
        $this->assertStringContainsString('CNPJ já está cadastrado', $this->capturedResponseData['message']);
    }

    // --- Testes de Transação (Rollback) ---

    public function testProcessRegistrationFail_RollbackOnStep3()
    {
        // Arrange
        $this->mockRawInput($this->validInput);
        $this->detalhesModelMock->method('findByEmail')->willReturn(false);
        $this->funcionarioModelMock->method('buscarPorEmail')->willReturn(false);
        $this->detalhesModelMock->method('findByCnpj')->willReturn(false);
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->never())->method('commit');
        $this->pdoMock->expects($this->once())->method('rollBack');
        $this->pdoMock->method('inTransaction')->willReturn(true); 
        $this->empresaModelMock->method('create')->willReturn(99);
        $this->detalhesModelMock->method('create')->willReturn(true);
        $this->funcionarioModelMock->expects($this->once())
            ->method('criar')
            ->willThrowException(new Exception("Erro ao criar funcionário"));

        // Act
        // == CORREÇÃO AQUI (Linha 234) ==
        $this->controllerMock->processRegistration();

        // Assert
        $this->assertEquals(500, $this->capturedResponseCode);
        $this->assertStringContainsString('Erro ao criar funcionário', $this->capturedResponseData['message']);
    }

    public function testProcessRegistrationFail_RollbackOnPdoException()
    {
        // Arrange
        // == CORREÇÃO AQUI (Linha 244) ==
        $this->mockRawInput($this->validInput);
        $this->detalhesModelMock->method('findByEmail')->willReturn(false);
        $this->funcionarioModelMock->method('buscarPorEmail')->willReturn(false);
        $this->detalhesModelMock->method('findByCnpj')->willReturn(false);
        // == CORREÇÃO AQUI ==
        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->never())->method('commit');
        // == CORREÇÃO AQUI ==
        $this->pdoMock->expects($this->once())->method('rollBack');
        // == CORREÇÃO AQUI ==
        $this->pdoMock->method('inTransaction')->willReturn(true);

        $mockException = $this->createMock(PDOException::class);
        $reflection = new \ReflectionClass(\Exception::class); // Reflete a classe PAI
        $property = $reflection->getProperty('code');
        $property->setAccessible(true);
        $property->setValue($mockException, '23505'); // Força o código de violação única
        
        $this->empresaModelMock->method('create')->willThrowException($mockException);
        
        // Act
        // == CORREÇÃO AQUI ==
        $this->controllerMock->processRegistration();

        // Assert
        // == CORREÇÃO AQUI ==
        $this->assertEquals(400, $this->capturedResponseCode);
        $this->assertStringContainsString('E-mail ou CNPJ já está registado', $this->capturedResponseData['message']);
    }
}