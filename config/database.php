<?php
require_once 'config.php';

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        // Se FORCE_SUPABASE estiver ativado para testes, conecta diretamente ao Supabase
        if (defined('FORCE_SUPABASE') && FORCE_SUPABASE) {
            try {
                $this->conn = $this->connectSupabase($options);
                return;
            } catch (PDOException $e) {
                error_log("Supabase connection failed: " . $e->getMessage());
                die("Erro de conexão (Supabase - Teste): " . $e->getMessage());
            }
        }

        // Caso contrário, tenta conectar ao Banco de Dados Principal (SQLite ou MySQL)
        try {
            if (defined('DB_DRIVER') && DB_DRIVER === 'sqlite') {
                $dsn = "sqlite:" . __DIR__ . "/../database.sqlite";
                $this->conn = new PDO($dsn, null, null, $options);
                $this->conn->exec("PRAGMA foreign_keys = ON;");
            } else {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
            }
        } catch (PDOException $primaryException) {
            // Se o principal falhar, tenta o banco de dados secundário (Supabase)
            error_log("Primary DB failed (" . $primaryException->getMessage() . "). Tentando conexão de contingência com o Supabase...");
            
            try {
                $this->conn = $this->connectSupabase($options);
            } catch (PDOException $secondaryException) {
                error_log("Secondary DB (Supabase) failed: " . $secondaryException->getMessage());
                die("Desculpe, ocorreu um erro de conexão com os bancos de dados principal e secundário. Tente novamente mais tarde.");
            }
        }
    }

    private function connectSupabase($options) {
        $dsn = "pgsql:host=" . SUPABASE_HOST . ";port=" . SUPABASE_PORT . ";dbname=" . SUPABASE_NAME;
        return new PDO($dsn, SUPABASE_USER, SUPABASE_PASS, $options);
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance->conn;
    }
}
