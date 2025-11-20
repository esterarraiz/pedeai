<?php
namespace App\Tests\Utils;

use PDO;
use PDOException;
use PDOStatement;

class FakePDOStatement
{
    public array $fetchStack = [];
    public array $fetchAllResult = [];
    public array $fetchColumnStack = [];
    public int $rowCountResult = 1;
    public ?PDOException $throwException = null;

    public function execute($params = []): bool
    {
        if ($this->throwException) {
            throw $this->throwException;
        }
        return true;
    }

    public function bindValue($param, $value, $type = null): bool
    {
        return true;
    }

    // 🔧 Atualizado para compatibilidade com PHP 8.4
    public function fetch(
        int $mode = PDO::FETCH_DEFAULT,
        int $cursorOrientation = PDO::FETCH_ORI_NEXT,
        int $cursorOffset = 0
    ): mixed {
        return array_shift($this->fetchStack) ?? false;
    }

    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, ...$args): array
    {
        return $this->fetchAllResult;
    }

    public function fetchColumn(int $column = 0): mixed
    {
        return array_shift($this->fetchColumnStack) ?? false;
    }

    public function rowCount(): int
    {
        return $this->rowCountResult;
    }
}

class FakePDO extends PDO
{
    private array $statements = [];
    private int $statementIndex = 0;

    public function __construct() {}

    public function addMockStatement(FakePDOStatement $stmt): void
    {
        $this->statements[] = $stmt;
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        if (!isset($this->statements[$this->statementIndex])) {
            throw new \Exception("Chamada de prepare() não esperada para a query: $query");
        }

        $fake = $this->statements[$this->statementIndex++];

        // 🔧 Classe anônima com assinatura compatível com PHP 8.4
        return new class($fake) extends PDOStatement {
            private FakePDOStatement $delegate;

            public function __construct(FakePDOStatement $delegate)
            {
                $this->delegate = $delegate;
            }

            public function execute($params = []): bool { return $this->delegate->execute($params); }
            public function bindValue($param, $value, $type = null): bool { return $this->delegate->bindValue($param, $value, $type); }

            public function fetch(
                int $mode = PDO::FETCH_DEFAULT,
                int $cursorOrientation = PDO::FETCH_ORI_NEXT,
                int $cursorOffset = 0
            ): mixed {
                return $this->delegate->fetch($mode, $cursorOrientation, $cursorOffset);
            }

            public function fetchAll(int $mode = PDO::FETCH_DEFAULT, ...$args): array {
                return $this->delegate->fetchAll($mode, ...$args);
            }

            public function fetchColumn(int $column = 0): mixed {
                return $this->delegate->fetchColumn($column);
            }

            public function rowCount(): int { return $this->delegate->rowCount(); }
        };
    }

    public function reset(): void
    {
        $this->statements = [];
        $this->statementIndex = 0;
    }
}
