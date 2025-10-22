<?php
// Arquivo: Config/Database.php (versão final e correta)

namespace Config;

use PDO;
use PDOException;

class Database
{
    public static function getConnection(): PDO
    {
        // Os nomes aqui AGORA CORRESPONDEM EXATAMENTE ao seu arquivo .env
        $host   = $_ENV['DB_HOST'] ?? 'localhost';
        $db     = $_ENV['DB_DATABASE'] ?? '';   
        $user   = $_ENV['DB_USERNAME'] ?? '';    
        $pass   = $_ENV['DB_PASSWORD'] ?? '';    
        $port   = $_ENV['DB_PORT'] ?? '5432';
        $driver = $_ENV['DB_CONNECTION'] ?? 'pgsql';

        // Verifica se as variáveis foram carregadas
        if (empty($host) || empty($db) || empty($user)) {
            die("❌ Erro de configuração: Uma ou mais variáveis de banco de dados (DB_HOST, DB_DATABASE, DB_USERNAME) não foram carregadas do arquivo .env. Verifique se o arquivo .env existe na raiz do projeto e está preenchido corretamente.");
        }

        try {
            // A string de conexão (DSN) para o Supabase precisa do sslmode=require
            $dsn = "$driver:host=$host;port=$port;dbname=$db;sslmode=require";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            // Retorna a nova conexão segura com o Supabase
            return new PDO($dsn, $user, $pass, $options);

        } catch (PDOException $e) {
            die("❌ Erro de conexão com o banco de dados: " . $e->getMessage());
        }
    }
}