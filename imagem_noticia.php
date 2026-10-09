<?php
// Entrega capas legadas separadamente, sem modificar o banco ou exigir conversao.
require_once __DIR__ . '/config/database.php';
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
if (!$id || $id < 1) { http_response_code(404); exit; }
$pdo = Database::getInstance();
$stmt = $pdo->prepare("SELECT imagem_destacada FROM noticias WHERE id = ? AND status = 'publicado'");
$stmt->execute([$id]);
$image = $stmt->fetchColumn();
if (!$image || !preg_match('~\Adata:(image/(?:jpeg|png|webp|gif));base64,~i', $image, $match)) { http_response_code(404); exit; }
$bytes = base64_decode(substr($image, strlen($match[0])), true);
unset($image);
if ($bytes === false || $bytes === '') { http_response_code(404); exit; }
$mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) { http_response_code(404); exit; }
$etag = '"' . hash('sha256', $bytes) . '"';
header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=3600');
header('ETag: ' . $etag);
if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) { http_response_code(304); exit; }
header('Content-Length: ' . strlen($bytes));
echo $bytes;
