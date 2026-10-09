<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

$q = $_GET['q'] ?? '';
$q = trim($q);

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$pdo = Database::getInstance();

$stmt = $pdo->prepare("SELECT n.id, n.titulo, n.slug, CASE WHEN SUBSTR(n.imagem_destacada, 1, 5) = 'data:' THEN '@image' ELSE n.imagem_destacada END as imagem, c.nome as categoria_nome, c.cor as categoria_cor
                       FROM noticias n 
                       JOIN categorias c ON n.categoria_id = c.id 
                       WHERE n.status = 'publicado' 
                       AND n.titulo LIKE ? 
                       ORDER BY n.criado_em DESC 
                       LIMIT 5");
$stmt->execute(['%' . $q . '%']);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Formatar imagem
foreach ($resultados as &$item) {
    $item['imagem'] = newsImageUrl($item['imagem'], $item['id']);
}
unset($item);
echo json_encode($resultados);
exit;
