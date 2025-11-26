<?php

namespace App\Tests\Unit\Models;

use App\Models\DetalhesEmpresasModel; // A classe que estamos testando
use PHPUnit\Framework\TestCase;
use PDO; // Importa o PDO real
use PDOException;

use App\Tests\Utils\FakePDO;
use App\Tests\Utils\FakePDOStatement;

class DetalhesEmpresasModelTest extends TestCase
{
    private FakePDO $pdoFake;
    private DetalhesEmpresasModel $model;

    protected function setUp(): void
    {
        $this->pdoFake = new FakePDO();
        // O construtor do Model espera a conexão PDO
        $this->model = new DetalhesEmpresasModel($this->pdoFake);
    }

    protected function tearDown(): void
    {
        // Limpa a fila de statements após cada teste
        $this->pdoFake->reset();
        parent::tearDown();
    }

    /** @test */
    public function test_create_caminho_feliz_retorna_true()
    {
        // Arrange
        $stmt = new FakePDOStatement();
        // O execute() do FakePDOStatement retorna true por padrão,
        // que é o que o método `create` espera em caso de sucesso.
        $this->pdoFake->addMockStatement($stmt);
        
        $dados = [
            'cnpj' => '12345678901234',
            'nome_proprietario' => 'Proprietario Teste',
            'email' => 'teste@email.com',
            'telefone' => '11999998888',
            'endereco' => 'Rua Teste, 123',
            'senha' => 'senha123' // O model cuidará de hashear isso
        ];

        // Act
        $result = $this->model->create(1, $dados);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function test_create_falha_pdo_lanca_excecao()
    {
        // Arrange
        $stmt = new FakePDOStatement();
        // Configura o statement para lançar uma exceção ao ser executado
        $stmt->throwException = new PDOException("Erro de DB simulado");
        $this->pdoFake->addMockStatement($stmt);

        $dados = [
            'cnpj' => '123',
            'nome_proprietario' => 'Teste',
            'email' => 'email@teste.com',
            'telefone' => '123',
            'endereco' => 'Rua',
            'senha' => '123'
        ];

        // Assert
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage("Erro de DB simulado");
        
        // Act
        $this->model->create(1, $dados);
    }

    /** @test */
    public function test_findByEmail_encontra_usuario()
    {
        // Arrange
        $dbResult = ['id' => 1, 'email' => 'teste@email.com', 'senha' => 'hash123'];
        $stmt = new FakePDOStatement();
        // Adiciona o resultado esperado à pilha do fetch()
        $stmt->fetchStack[] = $dbResult;
        $this->pdoFake->addMockStatement($stmt);

        // Act
        $result = $this->model->findByEmail('teste@email.com');

        // Assert
        $this->assertEquals($dbResult, $result);
    }

    /** @test */
    public function test_findByEmail_nao_encontra_usuario_retorna_false()
    {
        // Arrange
        $stmt = new FakePDOStatement();
        // Simula o fetch() retornando false (nada encontrado)
        $stmt->fetchStack[] = false;
        $this->pdoFake->addMockStatement($stmt);

        // Act
        $result = $this->model->findByEmail('naoexiste@email.com');

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function test_findByCnpj_encontra_empresa()
    {
        // Arrange
        $dbResult = ['id' => 1, 'cnpj' => '123456'];
        $stmt = new FakePDOStatement();
        $stmt->fetchStack[] = $dbResult;
        $this->pdoFake->addMockStatement($stmt);

        // Act
        $result = $this->model->findByCnpj('123456');

        // Assert
        $this->assertEquals($dbResult, $result);
    }

    /** @test */
    public function test_findByCnpj_nao_encontra_empresa_retorna_false()
    {
        // Arrange
        $stmt = new FakePDOStatement();
        $stmt->fetchStack[] = false;
        $this->pdoFake->addMockStatement($stmt);

        // Act
        $result = $this->model->findByCnpj('000000');

        // Assert
        $this->assertFalse($result);
    }
}