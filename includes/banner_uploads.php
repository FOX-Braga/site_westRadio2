<?php
require_once __DIR__ . '/banners.php';

function bannerPostExceeded() {
    $limit = trim(ini_get('post_max_size'));
    $units = ['k'=>1024, 'm'=>1048576, 'g'=>1073741824];
    $bytes = (float)$limit * ($units[strtolower(substr($limit, -1))] ?? 1);
    return $bytes > 0 && (float)($_SERVER['CONTENT_LENGTH'] ?? 0) > $bytes;
}

/** Envios de banners isolados por usuário, fora da pasta pública. */
function bannerTempDirectory() {
    $root = defined('BANNER_TEMP_DIR') ? BANNER_TEMP_DIR : sys_get_temp_dir() . '/96news-banners-' . substr(hash('sha256', __DIR__), 0, 16);
    $directory = $root . '/' . (int)($_SESSION['user_id'] ?? 0);
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) throw new RuntimeException('Não foi possível preparar o envio dos banners.');
    return $directory;
}

function bannerTempPath($token) {
    if (!is_string($token) || !preg_match('/\A[a-f0-9]{48}\z/', $token)) throw new RuntimeException('Identificador de envio inválido.');
    return bannerTempDirectory() . '/' . $token;
}

function bannerReadyUpload($token, $kind) {
    $base = bannerTempPath($token);
    $state = is_file($base . '.json') ? json_decode(file_get_contents($base . '.json'), true) : null;
    if (!$state || $state['user_id'] !== (int)$_SESSION['user_id'] || $state['kind'] !== $kind || $state['status'] !== 'ready'
        || $state['created_at'] < time() - 86400 || !is_file($base . '.part') || filesize($base . '.part') !== $state['size']) {
        throw new RuntimeException('O envio do banner expirou ou está incompleto. Selecione a imagem novamente.');
    }
    return $base . '.part';
}

function bannerConsumeUpload($token, $kind) {
    bannerReadyUpload($token, $kind);
    $base = bannerTempPath($token);
    unlink($base . '.part');
    unlink($base . '.json');
}

function bannerUploadAction($action, array $input, $chunk = null) {
    if ($action === 'start') {
        $size = filter_var($input['size'] ?? '', FILTER_VALIDATE_INT);
        $kind = $input['kind'] ?? '';
        if (!in_array($kind, ['imagem','imagem_mobile'], true) || !$size || $size < 1 || $size > bannerMaxUploadSize()) throw new RuntimeException('Envie um criativo de até ' . bannerUploadLimitLabel() . '.');
        $directory = bannerTempDirectory();
        foreach (glob($directory . '/*.json') ?: [] as $old) {
            if (filemtime($old) < time() - 86400) {
                $part = substr($old, 0, -5) . '.part';
                if (is_file($part)) unlink($part);
                unlink($old);
            }
        }
        $free = disk_free_space($directory);
        if ($free !== false && $size > $free) throw new RuntimeException('Não há espaço disponível para receber este banner.');
        $token = bin2hex(random_bytes(24));
        $base = bannerTempPath($token);
        $state = ['user_id'=>(int)$_SESSION['user_id'], 'kind'=>$kind, 'size'=>$size, 'received'=>0, 'status'=>'uploading', 'created_at'=>time()];
        if (file_put_contents($base . '.part', '') === false || file_put_contents($base . '.json', json_encode($state), LOCK_EX) === false) throw new RuntimeException('Não foi possível iniciar o envio.');
        return ['token'=>$token, 'received'=>0];
    }
    $base = bannerTempPath($input['token'] ?? '');
    $handle = is_file($base . '.json') ? fopen($base . '.json', 'r+') : false;
    if (!$handle) throw new RuntimeException('Envio não encontrado. Selecione a imagem novamente.');
    try {
        if (!flock($handle, LOCK_EX)) throw new RuntimeException('Não foi possível acessar o envio.');
        $state = json_decode(stream_get_contents($handle), true);
        if (!$state || $state['user_id'] !== (int)$_SESSION['user_id'] || $state['created_at'] < time() - 86400) throw new RuntimeException('Envio inválido ou expirado.');
        if ($action === 'chunk') {
            if ($state['status'] !== 'uploading' || !is_string($chunk) || strlen($chunk) < 1 || strlen($chunk) > 262144) throw new RuntimeException('Parte de imagem inválida.');
            $offset = filter_var($input['offset'] ?? '', FILTER_VALIDATE_INT);
            $length = strlen($chunk);
            if ($offset === false || $offset < 0 || $offset + $length > $state['size']) throw new RuntimeException('Posição de envio inválida.');
            $part = fopen($base . '.part', 'c+b');
            if (!$part) throw new RuntimeException('Não foi possível gravar o banner.');
            try {
                fseek($part, $offset);
                if ($offset < $state['received'] && $offset + $length <= $state['received']) {
                    if (!hash_equals(hash('sha256', $chunk), hash('sha256', fread($part, $length)))) throw new RuntimeException('A parte repetida é diferente da imagem original.');
                } else {
                    if ($offset !== $state['received']) throw new RuntimeException('As partes devem ser enviadas em ordem.');
                    if (fwrite($part, $chunk) !== $length) { ftruncate($part, $offset); throw new RuntimeException('Não foi possível concluir o envio. Confira o espaço disponível.'); }
                    $state['received'] += $length;
                }
            } finally { fclose($part); }
        } elseif ($action === 'finish') {
            clearstatcache(true, $base . '.part');
            if ($state['received'] !== $state['size'] || filesize($base . '.part') !== $state['size']) throw new RuntimeException('O banner ainda não foi enviado por inteiro.');
            bannerValidateImage($base . '.part');
            $state['status'] = 'ready';
        } else throw new RuntimeException('Ação de envio inválida.');
        rewind($handle);
        ftruncate($handle, 0);
        if (fwrite($handle, json_encode($state)) === false) throw new RuntimeException('Não foi possível registrar o envio.');
        fflush($handle);
        return ['token'=>$input['token'], 'received'=>$state['received']];
    } finally { flock($handle, LOCK_UN); fclose($handle); }
}
