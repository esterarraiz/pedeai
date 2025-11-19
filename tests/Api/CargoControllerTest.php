<?php

namespace Tests\Api;

use App\Controllers\Api\CargoController;
use App\Models\CargoModel;
use Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Teste unitário para CargoController.
 *
 * @covers \App\Controllers\Api\CargoController
 */
class CargoControllerTest extends TestCase
{
    /** @var CargoModel&MockObject */
    private $cargoModelMock;

    /** @var CargoController */
    private $controller;

    // Propriedades para capturar a saída do jsonResponse
    private $responseData;
    private $responseCode;

    /**
     * Configura o ambiente de teste antes de cada método de teste.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Criamos um mock para o CargoModel
        $this->cargoModelMock = $this->createMock(CargoModel::class);

        // 2. Criamos uma instância ANÔNIMA do controller que nos permite:
        //    - Sobrescrever getModel() para retornar nosso mock.
        //    - Sobrescrever jsonResponse() para capturar os dados de saída.
        $this->controller = new class($this->cargoModelMock) extends CargoController {
            private $mockModel;
            public $testResponseData = null;
            public $testResponseCode = 0;

            public function __construct(CargoModel $mockModel)
            {
                $this->mockModel = $mockModel;
            }

            // Sobrescreve o factory method para injetar o mock
            protected function getModel(): CargoModel
            {
                return $this->mockModel;
            }

            // Sobrescreve o jsonResponse para capturar a saída
            public function jsonResponse($data, $statusCode = 200)
            {
                $this->testResponseData = $data;
                $this->testResponseCode = $statusCode;
            }
        };
    }

    /**
     * Testa o cenário de sucesso do método listar().
     */
    public function testListarSuccess()
    {
        // 1. Arrange (Configuração)
        $expectedCargos = [
            ['id' => 1, 'nome' => 'Gerente'],
            ['id' => 2, 'nome' => 'Vendedor']
        ];
        
        // Configuramos o mock para retornar os dados esperados
        $this->cargoModelMock
            ->expects($this->once()) // Esperamos que buscarTodos() seja chamado 1 vez
            ->method('buscarTodos')
            ->willReturn($expectedCargos);

        // 2. Act (Ação)
        // Chamamos o método a ser testado
        $this->controller->listar();

        // 3. Assert (Verificação)
        // Verificamos se o status code foi 200 (padrão)
        $this->assertEquals(200, $this->controller->testResponseCode);
        
        // Verificamos se os dados de resposta são os mesmos que o mock retornou
        $this->assertEquals($expectedCargos, $this->controller->testResponseData);
    }

    /**
     * Testa o cenário de exceção do método listar().
     */
    public function testListarThrowsException()
    {
        // 1. Arrange (Configuração)
        $errorMessage = "Erro ao buscar dados";
        
        // Configuramos o mock para LANÇAR uma exceção
        $this->cargoModelMock
            ->expects($this->once())
            ->method('buscarTodos')
            ->willThrowException(new Exception($errorMessage));

        // 2. Act (Ação)
        $this->controller->listar();

        // 3. Assert (Verificação)
        // Verificamos se o status code de erro é 500
        $this->assertEquals(500, $this->controller->testResponseCode);
        
        // Verificamos se a resposta contém a mensagem de erro correta
        $this->assertEquals(['message' => $errorMessage], $this->controller->testResponseData);
    }
}