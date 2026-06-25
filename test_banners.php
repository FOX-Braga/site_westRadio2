<?php
require 'config/config.php';
require 'includes/Database.php';

try {
    $pdo = Database::getInstance();
    $db_driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
    $orderBy = ($db_driver === 'mysql') ? "ORDER BY RAND()" : "ORDER BY RANDOM()";
    echo "Querying with: $orderBy\n";
    $stmtBanners = $pdo->query("SELECT * FROM banners WHERE status = 'ativo' $orderBy LIMIT 2");
    $displayBanners = $stmtBanners->fetchAll();
    echo "Found: " . count($displayBanners) . " banners.\n";
    print_r($displayBanners);
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
