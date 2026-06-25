<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Função para gerar slugs amigáveis
 */
function createSlug($string) {
    $string = mb_strtolower($string, 'UTF-8');
    $string = preg_replace('/[áàãâä]/u', 'a', $string);
    $string = preg_replace('/[éèêë]/u', 'e', $string);
    $string = preg_replace('/[íìîï]/u', 'i', $string);
    $string = preg_replace('/[óòõôö]/u', 'o', $string);
    $string = preg_replace('/[úùûü]/u', 'u', $string);
    $string = preg_replace('/[ç]/u', 'c', $string);
    $string = preg_replace('/[^a-z0-9 -]/', '', $string);
    $string = preg_replace('/[ -]+/', '-', $string);
    $string = trim($string, '-');
    return $string;
}

/**
 * Sanitizar saída para prevenir XSS
 */
function escape($string) {
    if ($string === null) return '';
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Formatar data para exibição
 */
function formatDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('d/m/Y H:i');
}

/**
 * Gerar Token CSRF
 */
function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validar Token CSRF
 */
function validateCsrfToken($token) {
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    return false;
}

/**
 * Função de upload de imagem genérica
 */
function uploadImage($file, $subDir = '') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Erro no upload.'];
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'message' => 'Arquivo excede o limite de 5MB.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Extensão de arquivo não permitida.'];
    }

    // Verificação rigorosa do MIME type para garantir que é realmente uma imagem e não um script disfarçado
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowed_mimes)) {
        return ['success' => false, 'message' => 'O arquivo enviado não é uma imagem válida. Possível tentativa de violação de segurança.'];
    }

    $filename = uniqid('img_') . '.' . $ext;
    $targetDir = UPLOAD_DIR . $subDir;
    
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $targetPath = rtrim($targetDir, '/') . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $filename];
    }

    return ['success' => false, 'message' => 'Falha ao mover o arquivo.'];
}


