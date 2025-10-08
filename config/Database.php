<?php

namespace Config;

use PDO;
use PDOException;

class Database
{
    public static function getConnection(): PDO
    {
        $host   = $_ENV['DB_HOST'] ?? 'localhost';
        $db     = $_ENV['DB_DATABASE'] ?? '';
        $user   = $_ENV['DB_USERNAME'] ?? '';
        $pass   = $_ENV['DB_PASSWORD'] ?? '';
        $port   = $_ENV['DB_PORT'] ?? '5432';
        $driver = $_ENV['DB_CONNECTION'] ?? 'pgsql';

        try {
            // AQUI ESTÁ A MUDANÇA! Adicionamos o ";sslmode=require"
            $dsn = "$driver:host=$host;port=$port;dbname=$db;sslmode=require";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            // Retorna a nova conexão segura
            return new PDO($dsn, $user, $pass, $options);

        } catch (PDOException $e) {
            die("❌ Erro de conexão com o banco de dados: " . $e->getMessage());
        }
    }
}