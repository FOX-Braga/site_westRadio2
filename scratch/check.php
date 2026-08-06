<?php
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getInstance();
$stmt = $pdo->query("SELECT criado_em, SUBSTR(criado_em, 12, 2) as hr FROM noticias LIMIT 5");
print_r($stmt->fetchAll());
