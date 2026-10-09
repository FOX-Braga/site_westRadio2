<?php
/** Uploads editoriais em partes: arquivos temporarios fora da raiz publica. */
function editorialUploadDirectory() {
    $root = defined('EDITORIAL_TEMP_DIR') ? EDITORIAL_TEMP_DIR : sys_get_temp_dir() . '/96news-editorial-' . substr(hash('sha256', __DIR__), 0, 16);
    $directory = $root . '/' . (int) ($_SESSION['user_id'] ?? 0);
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Não foi possível criar a área temporária de envio.');
    }
    return $directory;
}

function editorialUploadPath($token) {
    if (!is_string($token) || !preg_match('/\A[a-f0-9]{48}\z/', $token)) {
        throw new RuntimeException('Identificador de envio inválido.');
    }
    return editorialUploadDirectory() . '/' . $token;
}

function editorialReadUpload($token, $kind) {
    $base = editorialUploadPath($token);
    $data = is_file($base . '.json') ? json_decode(file_get_contents($base . '.json'), true) : null;
    if (!$data || $data['user_id'] !== (int) $_SESSION['user_id'] || $data['kind'] !== $kind || ($data['status'] ?? '') !== 'ready') {
        throw new RuntimeException('O envio não foi concluído ou não pertence ao seu usuário.');
    }
    if ($kind === 'content' && !is_file($base . '.part')) throw new RuntimeException('O texto enviado expirou. Envie novamente.');
    return $data;
}

function editorialConsumeUpload($token, $kind) {
    editorialReadUpload($token, $kind);
    $base = editorialUploadPath($token);
    if ($kind === 'content' && is_file($base . '.part')) unlink($base . '.part');
    if (is_file($base . '.json')) unlink($base . '.json');
}

function editorialMediaRoot() {
    return rtrim(defined('EDITORIAL_MEDIA_DIR') ? EDITORIAL_MEDIA_DIR : UPLOAD_DIR, '/\\');
}

function editorialImageFile($temporary, $moveUploaded = true) {
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporary);
    $extensions = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp', 'image/gif'=>'gif'];
    if (!isset($extensions[$mime])) throw new RuntimeException('Selecione uma imagem JPG, PNG, WEBP ou GIF válida.');
    $directory = editorialMediaRoot() . '/noticias';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) throw new RuntimeException('Não foi possível preparar a pasta de imagens.');
    $filename = 'img_' . bin2hex(random_bytes(24)) . '.' . $extensions[$mime];
    $destination = $directory . '/' . $filename;
    $moved = $moveUploaded ? move_uploaded_file($temporary, $destination) : rename($temporary, $destination);
    if (!$moved) throw new RuntimeException('Não foi possível armazenar a imagem. Confira o espaço disponível.');
    chmod($destination, 0644);
    return $filename;
}