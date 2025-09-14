<?php

namespace Config; // <-- CORREÇÃO: Removido o "App\"

use PDO;
use PDOException;

class Database
{
    /** @var PDO|null A instância única da conexão PDO */
    private static $pdo = null;

    private function __construct() {}
    private function __clone() {}

    /**
     * Obtém a instância única da conexão PDO.
     */
    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            $host = $_ENV['DB_HOST'];
            $db   = $_ENV['DB_DATABASE'];
            $user = $_ENV['DB_USERNAME'];
            $pass = $_ENV['DB_PASSWORD'];
            $port = $_ENV['DB_PORT'];
            $driver = $_ENV['DB_CONNECTION'] ?? 'pgsql';

            try {
                $dsn = "$driver:host=$host;port=$port;dbname=$db";
                self::$pdo = new PDO($dsn, $user, $pass);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            } catch (PDOException $e) {
                die("❌ Erro de conexão com o banco de dados: " . $e->getMessage());
            }
        }
        
        return self::$pdo;
    }
}
