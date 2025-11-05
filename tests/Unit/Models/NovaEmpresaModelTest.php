<?php

namespace App\Tests\Models;

use App\Models\NovaEmpresaModel;
use App\Tests\Utils\FakePDO;
use App\Tests\Utils\FakePDOStatement;
use PDOException;
use PHPUnit\Framework\TestCase;

class NovaEmpresaModelTest extends TestCase
{
    private FakePDO $pdo;
    private NovaEmpresaModel $model;

    protected function setUp(): void
    {
        $this->pdo = new FakePDO();
        $this->model = new NovaEmpresaModel($this->pdo);
    }

    public function testCreateRetornaIdQuandoSucesso(): void
    {
        $stmt = new FakePDOStatement();
        $stmt->fetchColumnStack = [42]; // simula ID retornado
        $this->pdo->addMockStatement($stmt);

        $dados = [
            'cnpj' => '12345678000199',
            'nome_proprietario' => 'Maria Silva',
            'email' => 'maria@teste.com',
            'telefone' => '(63) 99999-0000',
            'endereco' => 'Rua A, 123',
            'senha' => 'senha123'
        ];

        $id = $this->model->create($dados);

        $this->assertSame(42, $id);
    }

    public function testCreateLancaExcecaoQuandoErroNoDB(): void
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Erro simulado');

        $stmt = new FakePDOStatement();
        $stmt->throwException = new PDOException('Erro simulado');
        $this->pdo->addMockStatement($stmt);

        $dados = [
            'cnpj' => '11122233344455',
            'nome_proprietario' => 'João',
            'email' => 'joao@teste.com',
            'telefone' => '999999999',
            'endereco' => 'Rua B, 456',
            'senha' => '123'
        ];

        $this->model->create($dados);
    }

    public function testFindByEmailRetornaArrayQuandoEncontrado(): void
    {
        $stmt = new FakePDOStatement();
        $stmt->fetchStack = [['id' => 10, 'email' => 'teste@teste.com', 'senha' => 'hash']];
        $this->pdo->addMockStatement($stmt);

        $resultado = $this->model->findByEmail('teste@teste.com');

        $this->assertIsArray($resultado);
        $this->assertSame(10, $resultado['id']);
        $this->assertSame('teste@teste.com', $resultado['email']);
    }

    public function testFindByEmailRetornaFalseQuandoNaoEncontrado(): void
    {
        $stmt = new FakePDOStatement();
        $stmt->fetchStack = [false];
        $this->pdo->addMockStatement($stmt);

        $resultado = $this->model->findByEmail('naoexiste@teste.com');

        $this->assertFalse($resultado);
    }

    public function testFindByCnpjRetornaArrayQuandoEncontrado(): void
    {
        $stmt = new FakePDOStatement();
        $stmt->fetchStack = [['id' => 5, 'cnpj' => '12345678000199']];
        $this->pdo->addMockStatement($stmt);

        $resultado = $this->model->findByCnpj('12345678000199');

        $this->assertIsArray($resultado);
        $this->assertSame(5, $resultado['id']);
        $this->assertSame('12345678000199', $resultado['cnpj']);
    }

    public function testFindByCnpjRetornaFalseQuandoNaoEncontrado(): void
    {
        $stmt = new FakePDOStatement();
        $stmt->fetchStack = [false];
        $this->pdo->addMockStatement($stmt);

        $resultado = $this->model->findByCnpj('00000000000000');

        $this->assertFalse($resultado);
    }
}
