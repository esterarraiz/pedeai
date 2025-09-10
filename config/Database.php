<?php


namespace Config;

use PDO;
use PDOException;

class Database
{
    private static $pdo = null;


    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {

            $host = $_ENV['DB_HOST'];
            $db   = $_ENV['DB_DATABASE'];
            $user = $_ENV['DB_USERNAME'];
            $pass = $_ENV['DB_PASSWORD'];
            $port = $_ENV['DB_PORT'];

            try {
                $dsn = "pgsql:host=" . $host . ";port=" . $port . ";dbname=" . $db;
                self::$pdo = new PDO($dsn, $user, $pass);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            } catch (PDOException $e) {
                die("❌ Erro de conexão com o banco de dados: " . $e->getMessage());
            }
        }
        
        return self::$pdo;
    }
}