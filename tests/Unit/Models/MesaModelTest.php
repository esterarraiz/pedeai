<?php

namespace App\Tests\Unit\Models;

use App\Models\Mesa; // Classe que estamos testando
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use Exception;
use App\Tests\Utils\FakePDO;
use App\Tests\Utils\FakePDOStatement;



// ------------------------------------------------------------------
// CLASSE DE TESTE DO MESA MODEL
// ------------------------------------------------------------------

class MesaModelTest extends TestCase
{
    private FakePDO $pdoFake;
    private Mesa $mesaModel;

    protected function setUp(): void
    {
        $this->pdoFake = new FakePDO();
        $this->mesaModel = new Mesa($this->pdoFake);
    }

    protected function tearDown(): void
    {
        $this->pdoFake->reset();
        parent::tearDown();
    }

    /** @test */
    public function test_buscarTodasPorEmpresa_retorna_array_correto()
    {
        // Arrange: Prepara o resultado que o DB deve retornar
        $dbResult = [
            ['id' => 1, 'numero' => 1, 'status' => 'disponivel'],
            ['id' => 2, 'numero' => 2, 'status' => 'ocupada']
        ];
        $stmt = new FakePDOStatement();
        $stmt->fetchAllResult = $dbResult;
        $this->pdoFake->addMockStatement($stmt);

        // Act: Executa o método
        $resultado = $this->mesaModel->buscarTodasPorEmpresa(1);

        // Assert: Verifica se o resultado é o esperado
        $this->assertEquals($dbResult, $resultado);
    }

    /** @test */
    public function test_atualizarStatus_retorna_true_em_sucesso()
    {
        // Arrange: Prepara o statement (execute() retorna true por padrão)
        $stmt = new FakePDOStatement();
        $this->pdoFake->addMockStatement($stmt);

        // Act
        $resultado = $this->mesaModel->atualizarStatus(1, 'ocupada');

        // Assert
        $this->assertTrue($resultado);
    }

    /** @test */
    public function test_atualizarStatus_lanca_excecao_em_erro_pdo()
    {
        // Arrange: Configura o statement para lançar uma PDOException
        $stmt = new FakePDOStatement();
        $stmt->throwException = new PDOException("Erro de DB simulado");
        $this->pdoFake->addMockStatement($stmt);

        // Assert: Espera que o método capture a PDOException e lance uma Exception genérica
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Erro de banco de dados ao tentar atualizar a mesa.");

        // Act
        $this->mesaModel->atualizarStatus(1, 'ocupada');
    }

    /** @test */
    public function test_buscarPorId_encontra_mesa()
    {
        // Arrange
        $dbResult = ['id' => 5, 'numero' => 5, 'status' => 'disponivel'];
        $stmt = new FakePDOStatement();
        $stmt->fetchStack[] = $dbResult; // fetch() usa a fetchStack
        $this->pdoFake->addMockStatement($stmt);

        // Act
        $resultado = $this->mesaModel->buscarPorId(5);

        // Assert
        $this->assertEquals($dbResult, $resultado);
    }

    /** @test */
    public function test_buscarPorId_nao_encontra_mesa()
    {
        // Arrange
        $stmt = new FakePDOStatement();
        $stmt->fetchStack[] = false; // Simula fetch() retornando false
        $this->pdoFake->addMockStatement($stmt);

        // Act
        $resultado = $this->mesaModel->buscarPorId(999);

        // Assert
        $this->assertFalse($resultado);
    }

    /** @test */
    public function test_liberarMesa_retorna_true_quando_afeta_linhas()
    {
        // Arrange
        $stmt = new FakePDOStatement();
        $stmt->rowCountResult = 1; // 1 linha afetada
        $this->pdoFake->addMockStatement($stmt);

        // Act
        $resultado = $this->mesaModel->liberarMesa(1, 1);

        // Assert
        $this->assertTrue($resultado);
    }

    /** @test */
    public function test_liberarMesa_retorna_false_quando_nao_afeta_linhas()
    {
        // Arrange
        $stmt = new FakePDOStatement();
        $stmt->rowCountResult = 0; // 0 linhas afetadas (ex: mesa_id ou empresa_id não bateu)
        $this->pdoFake->addMockStatement($stmt);

        // Act
        $resultado = $this->mesaModel->liberarMesa(999, 1);

        // Assert
        $this->assertFalse($resultado);
    }

    /** @test */
    public function test_liberarMesa_retorna_false_em_pdo_exception()
    {
        // Arrange: Configura o statement para lançar uma PDOException
        $stmt = new FakePDOStatement();
        $stmt->throwException = new PDOException("Erro de DB simulado");
        $this->pdoFake->addMockStatement($stmt);

        // Act: O método deve capturar a exceção e retornar false
        $resultado = $this->mesaModel->liberarMesa(1, 1);

        // Assert
        $this->assertFalse($resultado);
    }

    /** @test */
    public function test_buscarMesasOcupadasOuPagamento_retorna_array()
    {
        // Arrange
        $dbResult = [
            ['id' => 2, 'numero' => 2, 'status' => 'ocupada'],
            ['id' => 3, 'numero' => 3, 'status' => 'aguardando_pagamento']
        ];
        $stmt = new FakePDOStatement();
        $stmt->fetchAllResult = $dbResult;
        $this->pdoFake->addMockStatement($stmt);

        // Act
        $resultado = $this->mesaModel->buscarMesasOcupadasOuPagamento(1);

        // Assert
        $this->assertEquals($dbResult, $resultado);
    }

    /** @test */
    public function test_buscarMesasComContaAberta_retorna_array()
    {
        // Arrange
        $dbResult = [
            ['id' => 2, 'numero' => 2, 'status' => 'ocupada'],
            ['id' => 3, 'numero' => 3, 'status' => 'aguardando_pagamento']
        ];
        $stmt = new FakePDOStatement();
        $stmt->fetchAllResult = $dbResult;
        $this->pdoFake->addMockStatement($stmt);

        // Act
        $resultado = $this->mesaModel->buscarMesasComContaAberta(1);

        // Assert
        $this->assertEquals($dbResult, $resultado);
    }
}
