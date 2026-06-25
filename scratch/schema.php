<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/../database.sqlite');
$stmt = $pdo->query("PRAGMA table_info(noticias)");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
