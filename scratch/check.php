<?php
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getInstance();

echo "Colunas de Noticias:\n";
try {
    print_r($pdo->query('PRAGMA table_info(noticias)')->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
