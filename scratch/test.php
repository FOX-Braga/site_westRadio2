<?php
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getInstance();
$stmt = $pdo->query("SELECT id, titulo, destaque, status, criado_em FROM noticias ORDER BY criado_em DESC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
