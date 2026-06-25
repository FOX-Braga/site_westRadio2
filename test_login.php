<?php
require_once __DIR__ . '/config/database.php';
$pdo = Database::getInstance();

$email = 'braga@email.com';

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

print_r($user);
