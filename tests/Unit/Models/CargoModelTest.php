<?php

namespace App\Tests\Unit\Models;

use App\Tests\TestCase;
use App\Models\CargoModel;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\After;

class CargoModelTest extends TestCase
{
    private MockObject $pdoMock;
    private MockObject $stmtMock;
    private CargoModel $cargoModel;

    #[Before]
    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() == PHP_SESSION_NONE) {
            $_SESSION = [];
        }

        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
        $this->cargoModel = new CargoModel($this->pdoMock);
    }

    #[After]
    protected function tearDown(): void
    {
        $_SESSION = [];
        unset($this->pdoMock, $this->stmtMock, $this->cargoModel);
    }

    //================================================================
    // Testes para buscarTodos
    //================================================================

    #[Test]
    public function test_buscarTodos_retorna_lista_de_cargos()
    {
        // 1. Arrange
        $sqlEsperado = "SELECT id, nome_cargo FROM cargos ORDER BY nome_cargo ASC";
        $dadosFalsos = [
            ['id' => 1, 'nome_cargo' => 'Admin'],
            ['id' => 3, 'nome_cargo' => 'Cozinha'],
            ['id' => 2, 'nome_cargo' => 'Garçom']
        ];

        // Configura o mock do PDO para esperar a query
        $this->pdoMock->method('query')
            ->with($sqlEsperado)
            ->willReturn($this->stmtMock);
            
        // Configura o mock do Statement para retornar os dados falsos
        $this->stmtMock->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($dadosFalsos);

        // 2. Act
        $resultado = $this->cargoModel->buscarTodos();

        // 3. Assert
        $this->assertIsArray($resultado);
        $this->assertCount(3, $resultado);
        $this->assertEquals($dadosFalsos, $resultado);
    }

    #[Test]
    public function test_buscarTodos_sem_cargos_retorna_array_vazio()
    {
        // 1. Arrange
        $sqlEsperado = "SELECT id, nome_cargo FROM cargos ORDER BY nome_cargo ASC";
        
        $this->pdoMock->method('query')
            ->with($sqlEsperado)
            ->willReturn($this->stmtMock);
            
        // Simula o DB não retornando nenhum cargo
        $this->stmtMock->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([]);

        // 2. Act
        $resultado = $this->cargoModel->buscarTodos();

        // 3. Assert
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }
}
