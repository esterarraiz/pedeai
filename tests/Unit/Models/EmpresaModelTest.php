<?php

namespace App\Tests\Unit\Models;

use App\Models\EmpresaModel; // A classe que estamos testando
use PHPUnit\Framework\TestCase;
use PDO; // Importa o PDO real
use PDOException;

use App\Tests\Utils\FakePDO;
use App\Tests\Utils\FakePDOStatement;

class EmpresaModelTest extends TestCase
{
    private FakePDO $pdoFake;
    private EmpresaModel $model;

    protected function setUp(): void
    {
        $this->pdoFake = new FakePDO();
        $this->model = new EmpresaModel($this->pdoFake);
    }

    protected function tearDown(): void
    {
        // Limpa a fila de statements após cada teste
        $this->pdoFake->reset();
        parent::tearDown();
    }

    /** @test */
    public function test_create_sucesso_retorna_novo_id()
    {
        // Arrange: Prepara o statement falso
        $stmt = new FakePDOStatement();
        
        // Simula o fetchColumn() retornando o novo ID '123'
        $stmt->fetchColumnStack[] = 123; 
        
        // Adiciona o statement à fila do FakePDO
        $this->pdoFake->addMockStatement($stmt);

        // Act
        $result = $this->model->create('Nova Empresa Teste');

        // Assert
        $this->assertEquals(123, $result);
    }

    /** @test */
    public function test_create_falha_pdo_lanca_excecao()
    {
        // Arrange
        $stmt = new FakePDOStatement();
        
        // Configura o statement para lançar uma exceção ao ser executado
        $stmt->throwException = new PDOException("Erro de duplicidade simulado");
        
        $this->pdoFake->addMockStatement($stmt);

        // Assert: Espera que a exceção seja lançada
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage("Erro de duplicidade simulado");
        
        // Act
        $this->model->create('Empresa Duplicada');
    }

    /** @test */
    public function test_create_falha_fetchColumn_retorna_false()
    {
        // Arrange
        $stmt = new FakePDOStatement();
        
        // Simula o fetchColumn() retornando false
        // (Isso pode acontecer se o INSERT funcionar, mas o RETURNING falhar)
        $stmt->fetchColumnStack[] = false;
        
        $this->pdoFake->addMockStatement($stmt);

        // Act
        $result = $this->model->create('Empresa Fantasma');

        // Assert
        $this->assertFalse($result);
    }
}