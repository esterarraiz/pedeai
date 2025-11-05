<?php

namespace App\Tests\Unit\Models;

use App\Tests\TestCase;
use App\Models\CardapioModel;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\After;

class CardapioModelTest extends TestCase
{
    private MockObject $pdoMock;
    private MockObject $stmtMock;
    private CardapioModel $cardapioModel;

    /**
     * Configuração executada ANTES de cada teste.
     */
    #[Before]
    protected function setUp(): void
    {
        parent::setUp();

        // Garante que a sessão esteja limpa
        if (session_status() == PHP_SESSION_NONE) {
            $_SESSION = [];
        }

        // Cria mocks para as classes PDO e PDOStatement
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
        
        // Instancia o Model, injetando o mock do PDO
        $this->cardapioModel = new CardapioModel($this->pdoMock);
    }

    /**
     * Limpeza executada APÓS cada teste.
     */
    #[After]
    protected function tearDown(): void
    {
        $_SESSION = [];
        unset($this->pdoMock, $this->stmtMock, $this->cardapioModel);
    }

    //================================================================
    // Testes para buscarItensAgrupados
    //================================================================

    #[Test]
    public function test_buscarItensAgrupados_retorna_itens_agrupados_por_categoria()
    {
        // 1. Arrange
        $empresa_id = 1;
        
        // Dados FALSOS que simulamos vir do banco de dados (antes do agrupamento)
        $dadosDBFalsos = [
            [
                'id' => 1, 'nome' => 'Refrigerante', 'descricao' => 'Lata 350ml', 'preco' => 5.00,
                'categoria_nome' => 'Bebidas', 'categoria_id' => 10
            ],
            [
                'id' => 2, 'nome' => 'Suco Natural', 'descricao' => 'Copo 500ml', 'preco' => 8.00,
                'categoria_nome' => 'Bebidas', 'categoria_id' => 10
            ],
            [
                'id' => 3, 'nome' => 'X-Burger', 'descricao' => 'Pão, carne, queijo', 'preco' => 15.00,
                'categoria_nome' => 'Lanches', 'categoria_id' => 20
            ]
        ];
        
        // O resultado ESPERADO (APÓS o agrupamento feito pelo método)
        $resultadoEsperado = [
            'Bebidas' => [
                $dadosDBFalsos[0],
                $dadosDBFalsos[1]
            ],
            'Lanches' => [
                $dadosDBFalsos[2]
            ]
        ];

        // Configuração dos Mocks
        $this->stmtMock->method('execute')->with([$empresa_id]);
        $this->stmtMock->method('fetchAll')->with(\PDO::FETCH_ASSOC)->willReturn($dadosDBFalsos);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        // 2. Act
        $resultado = $this->cardapioModel->buscarItensAgrupados($empresa_id);

        // 3. Assert
        $this->assertIsArray($resultado);
        $this->assertCount(2, $resultado, "Deveria haver 2 categorias (Bebidas, Lanches)");
        $this->assertArrayHasKey('Bebidas', $resultado);
        $this->assertArrayHasKey('Lanches', $resultado);
        $this->assertCount(2, $resultado['Bebidas'], "Deveria haver 2 itens em Bebidas");
        $this->assertCount(1, $resultado['Lanches'], "Deveria haver 1 item em Lanches");
        $this->assertEquals('Refrigerante', $resultado['Bebidas'][0]['nome']);
        $this->assertEquals($resultadoEsperado, $resultado);
    }

    #[Test]
    public function test_buscarItensAgrupados_sem_itens_retorna_array_vazio()
    {
        // 1. Arrange
        $empresa_id = 2; // Uma empresa sem itens
        $dadosDBFalsos = []; // Simula DB retornando vazio

        $this->stmtMock->method('execute')->with([$empresa_id]);
        $this->stmtMock->method('fetchAll')->with(\PDO::FETCH_ASSOC)->willReturn($dadosDBFalsos);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        // 2. Act
        $resultado = $this->cardapioModel->buscarItensAgrupados($empresa_id);

        // 3. Assert
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado, "O array de cardápio agrupado deveria estar vazio");
    }

    //================================================================
    // Testes para buscarTodasCategorias
    //================================================================

    #[Test]
    public function test_buscarTodasCategorias_retorna_lista_de_categorias()
    {
        // 1. Arrange
        $empresa_id = 1;
        $dadosFalsos = [
            ['id' => 10, 'nome' => 'Bebidas'],
            ['id' => 20, 'nome' => 'Lanches'],
            ['id' => 30, 'nome' => 'Sobremesas']
        ];
        
        $this->stmtMock->method('execute')->with([$empresa_id]);
        $this->stmtMock->method('fetchAll')->with(\PDO::FETCH_ASSOC)->willReturn($dadosFalsos);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        // 2. Act
        $resultado = $this->cardapioModel->buscarTodasCategorias($empresa_id);

        // 3. Assert
        $this->assertIsArray($resultado);
        $this->assertCount(3, $resultado);
        $this->assertEquals('Bebidas', $resultado[0]['nome']);
        $this->assertEquals($dadosFalsos, $resultado);
    }

    //================================================================
    // Testes para criarItem
    //================================================================

    #[Test]
    public function test_criarItem_retorna_true_em_sucesso()
    {
        // 1. Arrange
        $dados = [
            'empresa_id' => 1,
            'categoria_id' => 10,
            'nome' => 'Novo Item',
            'descricao' => 'Descricao do item',
            'preco' => 9.99
        ];
        
        $paramsEsperados = [
            ':empresa_id'   => $dados['empresa_id'],
            ':categoria_id' => $dados['categoria_id'],
            ':nome'         => $dados['nome'],
            ':descricao'    => $dados['descricao'],
            ':preco'        => $dados['preco']
        ];

        $this->stmtMock->method('execute')->with($paramsEsperados)->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        // 2. Act
        $resultado = $this->cardapioModel->criarItem($dados);

        // 3. Assert
        $this->assertTrue($resultado);
    }

    //================================================================
    // Testes para atualizarItem
    //================================================================

    #[Test]
    public function test_atualizarItem_retorna_true_em_sucesso()
    {
        // 1. Arrange
        $dados = [
            'categoria_id' => 20,
            'nome' => 'Item Atualizado',
            'descricao' => 'Descricao atualizada',
            'preco' => 12.50,
            'id' => 5,
            'empresa_id' => 1
        ];

        $paramsEsperados = [
            ':categoria_id' => $dados['categoria_id'],
            ':nome'         => $dados['nome'],
            ':descricao'    => $dados['descricao'],
            ':preco'        => $dados['preco'],
            ':id'           => $dados['id'],
            ':empresa_id'   => $dados['empresa_id']
        ];
        
        $this->stmtMock->method('execute')->with($paramsEsperados)->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        // 2. Act
        $resultado = $this->cardapioModel->atualizarItem($dados);

        // 3. Assert
        $this->assertTrue($resultado);
    }

    //================================================================
    // Testes para removerItem
    //================================================================

    #[Test]
    public function test_removerItem_retorna_true_em_sucesso()
    {
        // 1. Arrange
        $id = 5;
        $empresa_id = 1;
        
        $paramsEsperados = [
            ':id'         => $id,
            ':empresa_id' => $empresa_id
        ];

        $this->stmtMock->method('execute')->with($paramsEsperados)->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        // 2. Act
        $resultado = $this->cardapioModel->removerItem($id, $empresa_id);

        // 3. Assert
        $this->assertTrue($resultado);
    }
}
