<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/banner_uploads.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); echo json_encode(['error'=>'Método não permitido.']); exit; }
if (bannerPostExceeded()) { http_response_code(413); echo json_encode(['error'=>'Esta parte excedeu o limite de envio do servidor.']); exit; }
if (!validateCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) { http_response_code(403); echo json_encode(['error'=>'Sua sessão expirou. Atualize a página e tente novamente.']); exit; }
session_write_close();
try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $chunk = $action === 'chunk' ? file_get_contents('php://input', false, null, 0, 262145) : null;
    echo json_encode(bannerUploadAction($action, array_merge($_POST, $_GET), $chunk));
} catch (RuntimeException $error) {
    http_response_code(400);
    echo json_encode(['error'=>$error->getMessage()], JSON_UNESCAPED_UNICODE);
}
