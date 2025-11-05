<?php
namespace App\Tests\Utils;

use PDO;
use PDOException;
use PDOStatement;

class FakePDOStatement extends PDOStatement
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

    public function fetch($mode = null)
    {
        return array_shift($this->fetchStack) ?? false;
    }

    public function fetchAll($mode = null): array
    {
        return $this->fetchAllResult;
    }

    public function fetchColumn($column = 0)
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
        if (isset($this->statements[$this->statementIndex])) {
            return $this->statements[$this->statementIndex++];
        }
        throw new \Exception("Chamada de prepare() não esperada para a query: $query");
    }


    public function reset(): void
    {
        $this->statements = [];
        $this->statementIndex = 0;
    }
}
