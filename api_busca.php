<?php
require_once __DIR__ . '/includes/news_queries.php';
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
$imageSql = newsImageSql($pdo);

$stmt = $pdo->prepare("SELECT n.id, n.titulo, n.slug, {$imageSql} as imagem, c.nome as categoria_nome, c.cor as categoria_cor
                       FROM noticias n 
                       LEFT JOIN categorias c ON n.categoria_id = c.id
                       WHERE n.status = 'publicado' 
                       AND n.titulo LIKE ? 
                       ORDER BY n.criado_em DESC 
                       LIMIT 5");
$stmt->execute(['%' . $q . '%']);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Formatar imagem
foreach ($resultados as &$item) {
    $item['imagem'] = newsImageUrl($item['imagem']);
}
unset($item);

echo json_encode($resultados);
exit;
