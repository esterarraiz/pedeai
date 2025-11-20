<?php

namespace App\Tests\Unit\Models;

use App\Tests\TestCase;
use App\Models\Funcionario;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\Test; // Importa o Atributo [Test]
use PHPUnit\Framework\Attributes\Before; // Importa o Atributo [Before]
use PHPUnit\Framework\Attributes\After; // Importa o Atributo [After]

class FuncionarioTest extends TestCase
{
    private MockObject $pdoMock;
    private MockObject $stmtMock;
    private Funcionario $funcionarioModel;

    /**
     * Esta função é executada ANTES de cada teste.
     */
    #[Before]
    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() == PHP_SESSION_NONE) {
            $_SESSION = [];
        }

        // Cria mocks para as classes PDO e PDOStatement
        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);
        
        // Instancia o Model, injetando o mock do PDO
        $this->funcionarioModel = new Funcionario($this->pdoMock);
    }

    /**
     * Limpa a sessão após cada teste.
     */
    #[After]
    protected function tearDown(): void
    {
        $_SESSION = [];
        // Limpa os mocks
        unset($this->pdoMock, $this->stmtMock, $this->funcionarioModel);
    }

    //================================================================
    // Testes para validarLogin (Testes existentes + Cobertura de Erro)
    //================================================================

    #[Test]
    public function test_validarLogin_com_credenciais_corretas_retorna_utilizador()
    {
        // 1. Arrange
        $email = 'teste@example.com';
        $senhaCorreta = 'senha123';
        $senhaHashada = password_hash($senhaCorreta, PASSWORD_DEFAULT);
        
        $dadosUtilizadorFalso = [
            'id' => 1, 'nome' => 'Utilizador Teste', 'email' => $email,
            'senha' => $senhaHashada, 'nome_cargo' => 'garçom', 'empresa_id' => 10
        ];

        // Configura os mocks
        $this->stmtMock->method('execute')->with(['empresa_id' => 1, 'email' => $email]);
        $this->stmtMock->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn($dadosUtilizadorFalso);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
            
        // 2. Act
        $resultado = $this->funcionarioModel->validarLogin(1, $email, $senhaCorreta);

        // 3. Assert
        $this->assertIsArray($resultado);
        $this->assertEquals($dadosUtilizadorFalso['id'], $resultado['id']);
        $this->assertArrayNotHasKey('senha', $resultado); // Garante que a senha foi removida
    }

    #[Test]
    public function test_validarLogin_com_senha_errada_retorna_false()
    {
        // 1. Arrange
        $senhaCorretaHashada = password_hash('senha123', PASSWORD_DEFAULT);
        $senhaErrada = 'senha_errada_456';
        $dadosUtilizadorFalso = ['id' => 1, 'senha' => $senhaCorretaHashada];

        $this->stmtMock->method('fetch')->willReturn($dadosUtilizadorFalso);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
            
        // 2. Act
        $resultado = $this->funcionarioModel->validarLogin(1, 'teste@example.com', $senhaErrada);

        // 3. Assert
        $this->assertFalse($resultado);
    }

    #[Test]
    public function test_validarLogin_com_email_nao_encontrado_retorna_false()
    {
        // 1. Arrange
        $this->stmtMock->method('fetch')->willReturn(false); // Simula que o usuário não foi encontrado
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);
            
        // 2. Act
        $resultado = $this->funcionarioModel->validarLogin(1, 'email@errado.com', 'qualquer_senha');

        // 3. Assert
        $this->assertFalse($resultado);
    }

    #[Test]
    public function test_validarLogin_captura_pdoexception_e_retorna_false()
    {
        // 1. Arrange
        // Força o 'prepare' ou 'execute' a lançar uma PDOException
        $this->pdoMock->method('prepare')->will($this->throwException(new PDOException("Erro de DB simulado")));
            
        // 2. Act
        $resultado = $this->funcionarioModel->validarLogin(1, 'email@errado.com', 'qualquer_senha');

        // 3. Assert
        // O método deve capturar a exceção e retornar false
        $this->assertFalse($resultado);
    }

    //================================================================
    // Testes para buscarTodosPorEmpresa
    //================================================================

    #[Test]
    public function test_buscarTodosPorEmpresa_retorna_array_de_funcionarios()
    {
        // 1. Arrange
        $empresa_id = 10;
        $dadosFalsos = [
            ['id' => 1, 'nome' => 'Ana', 'email' => 'ana@email.com', 'ativo' => true, 'nome_cargo' => 'Gerente'],
            ['id' => 2, 'nome' => 'Bruno', 'email' => 'bruno@email.com', 'ativo' => true, 'nome_cargo' => 'Garçom']
        ];

        $this->stmtMock->method('execute')->with([$empresa_id]);
        $this->stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($dadosFalsos);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        // 2. Act
        $resultado = $this->funcionarioModel->buscarTodosPorEmpresa($empresa_id);

        // 3. Assert
        $this->assertIsArray($resultado);
        $this->assertCount(2, $resultado);
        $this->assertEquals('Ana', $resultado[0]['nome']);
    }

    //================================================================
    // Testes para buscarPorId
    //================================================================

    #[Test]
    public function test_buscarPorId_encontra_e_retorna_funcionario()
    {
        // 1. Arrange
        $id_funcionario = 5;
        $dadosFalsos = ['id' => $id_funcionario, 'nome' => 'Carlos', 'email' => 'carlos@email.com', 'cargo_id' => 2];

        $this->stmtMock->method('execute')->with([$id_funcionario]);
        $this->stmtMock->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn($dadosFalsos);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        // 2. Act
        $resultado = $this->funcionarioModel->buscarPorId($id_funcionario);

        // 3. Assert
        $this->assertEquals($dadosFalsos, $resultado);
    }

    #[Test]
    public function test_buscarPorId_nao_encontra_e_retorna_false()
    {
        // 1. Arrange
        $id_funcionario = 999; // ID que não existe

        $this->stmtMock->method('execute')->with([$id_funcionario]);
        $this->stmtMock->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(false); // Simula não encontrar
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        // 2. Act
        $resultado = $this->funcionarioModel->buscarPorId($id_funcionario);

        // 3. Assert
        $this->assertFalse($resultado);
    }
    
    //================================================================
    // Testes para buscarPorEmail (Adicionado no Model)
    //================================================================
    
    #[Test]
    public function test_buscarPorEmail_encontra_e_retorna_funcionario()
    {
        // 1. Arrange
        $email = 'teste@example.com';
        $dadosFalsos = ['id' => 1, 'email' => $email];

        $this->stmtMock->method('execute')->with([$email]);
        $this->stmtMock->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn($dadosFalsos);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        // 2. Act
        $resultado = $this->funcionarioModel->buscarPorEmail($email);

        // 3. Assert
        $this->assertEquals($dadosFalsos, $resultado);
    }

    #[Test]
    public function test_buscarPorEmail_nao_encontra_e_retorna_false()
    {
        // 1. Arrange
        $email = 'naoexiste@example.com';

        $this->stmtMock->method('execute')->with([$email]);
        $this->stmtMock->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(false);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        // 2. Act
        $resultado = $this->funcionarioModel->buscarPorEmail($email);

        // 3. Assert
        $this->assertFalse($resultado);
    }

    //================================================================
    // Testes para criar
    //================================================================

    #[Test]
    public function test_criar_funcionario_retorna_true_em_sucesso()
    {
        // 1. Arrange
        // Esperamos que o 'execute' seja chamado com 5 parâmetros (o 5º sendo o hash)
        $this->stmtMock->method('execute')
            ->with($this->callback(function ($params) {
                return count($params) === 5 && // Verifica o número de parâmetros
                       $params[0] === 1 && // empresa_id
                       $params[1] === 2 && // cargo_id
                       $params[2] === 'Novo Nome' &&
                       $params[3] === 'novo@email.com' &&
                       password_verify('senha123', $params[4]); // Verifica o hash
            }))
            ->willReturn(true);
            
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        // 2. Act
        $resultado = $this->funcionarioModel->criar(1, 2, 'Novo Nome', 'novo@email.com', 'senha123');

        // 3. Assert
        $this->assertTrue($resultado);
    }

    #[Test]
    public function test_criar_funcionario_lanca_pdoexception_em_falha()
    {
        // 1. Arrange
        // Configura o 'execute' para lançar uma exceção (ex: email duplicado)
        $this->stmtMock->method('execute')
            ->will($this->throwException(new PDOException("Email duplicado", "23000")));
            
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        // 3. Assert
        // Espera que a exceção seja lançada (o controller irá tratá-la)
        $this->expectException(PDOException::class);
        
        // 2. Act
        $this->funcionarioModel->criar(1, 2, 'Novo Nome', 'novo@email.com', 'senha123');
    }

    //================================================================
    // Testes para atualizar
    //================================================================

    #[Test]
    public function test_atualizar_com_senha_retorna_true()
    {
        // 1. Arrange
        $id = 5;
        $cargo_id = 3;
        $nome = 'Nome Atualizado';
        $email = 'email@atualizado.com';
        $novaSenha = 'novaSenha456';

        $this->stmtMock->method('execute')
            ->with($this->callback(function ($params) use ($id, $cargo_id, $nome, $email, $novaSenha) {
                return count($params) === 5 &&
                       $params[0] === $cargo_id &&
                       $params[1] === $nome &&
                       $params[2] === $email &&
                       password_verify($novaSenha, $params[3]) &&
                       $params[4] === $id;
            }))
            ->willReturn(true);
            
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        // 2. Act
        $resultado = $this->funcionarioModel->atualizar($id, $cargo_id, $nome, $email, $novaSenha);

        // 3. Assert
        $this->assertTrue($resultado);
    }

    #[Test]
    public function test_atualizar_sem_senha_retorna_true()
    {
        // 1. Arrange
        $id = 6;
        $cargo_id = 4;
        $nome = 'Outro Nome';
        $email = 'outro@email.com';

        // Espera 4 parâmetros (sem senha)
        $this->stmtMock->method('execute')
            ->with([$cargo_id, $nome, $email, $id])
            ->willReturn(true);
            
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        // 2. Act
        // Chama o método com $senha = null (ou omitido)
        $resultado = $this->funcionarioModel->atualizar($id, $cargo_id, $nome, $email);

        // 3. Assert
        $this->assertTrue($resultado);
    }

    //================================================================
    // Testes para atualizarStatus
    //================================================================
    
    #[Test]
    public function test_atualizarStatus_retorna_true()
    {
        // 1. Arrange
        $id = 7;
        $novo_status = false;

        // Configura o mock para bindParam e execute
        $this->stmtMock->method('bindParam')->willReturn(true); // Simula o bind bem-sucedido
        $this->stmtMock->method('execute')->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        // 2. Act
        $resultado = $this->funcionarioModel->atualizarStatus($id, $novo_status);

        // 3. Assert
        $this->assertTrue($resultado);
    }
    
    //================================================================
    // Testes para redefinirSenha
    //================================================================
    
    #[Test]
    public function test_redefinirSenha_retorna_true()
    {
        // 1. Arrange
        $id = 8;
        $novaSenha = 'senhaSuperSegura';

        $this->stmtMock->method('execute')
            ->with($this->callback(function ($params) use ($id, $novaSenha) {
                return count($params) === 2 &&
                       password_verify($novaSenha, $params[0]) &&
                       $params[1] === $id;
            }))
            ->willReturn(true);
            
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        // 2. Act
        $resultado = $this->funcionarioModel->redefinirSenha($id, $novaSenha);

        // 3. Assert
        $this->assertTrue($resultado);
    }
}
