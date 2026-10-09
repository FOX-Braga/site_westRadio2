<?php
// Entrega capas legadas em Base64 sem inclui-las no HTML de todas as listagens.
require_once __DIR__ . '/config/database.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) { http_response_code(404); exit; }
// A imagem e publica; liberar a sessao evita serializar o carregamento das capas.
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
$pdo = Database::getInstance();
$stmt = $pdo->prepare("SELECT imagem_destacada FROM noticias WHERE id = ? AND status = 'publicado' AND (data_agendamento IS NULL OR data_agendamento <= ?)");
$stmt->execute([$id, date('Y-m-d H:i:s')]);
$image = $stmt->fetchColumn();
$stmt->closeCursor();
if (!$image || !preg_match('~\Adata:(image/(?:jpeg|png|webp));base64,~', $image, $match)) {
    http_response_code(404); exit;
}
$etag = '"' . hash('sha256', $image) . '"';
header('Cache-Control: public, max-age=300');
header('ETag: ' . $etag);
header('X-Content-Type-Options: nosniff');
if (trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
    http_response_code(304); exit;
}
$binary = base64_decode(substr($image, strlen($match[0])), true);
unset($image);
if ($binary === false) { http_response_code(404); exit; }
header('Content-Type: ' . $match[1]);
header('Content-Length: ' . strlen($binary));
echo $binary;