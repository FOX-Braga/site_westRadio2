<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/editorial_uploads.php';
requirePanelAccess();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function uploadResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Allow: POST'); uploadResponse(['error'=>'Método não permitido.'], 405); }
if (!validateCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '')) uploadResponse(['error'=>'Sua sessão expirou. Atualize a página para continuar.'], 403);
$userId = (int) $_SESSION['user_id'];
// A sessao permanece disponivel para conferir propriedade, sem bloquear outras requisicoes.
session_write_close();
$handle = null;
try {
    $action = $_POST['action'] ?? '';
    if ($action === 'start') {
        $kind = $_POST['kind'] ?? '';
        $size = filter_var($_POST['size'] ?? '', FILTER_VALIDATE_INT);
        if (!in_array($kind, ['cover','image','video','content'], true) || !$size || $size < 1) throw new RuntimeException('Arquivo ou tamanho inválido.');
        $directory = editorialUploadDirectory();
        // Limpa apenas envios do proprio usuario abandonados ha mais de 24 horas.
        foreach (glob($directory . '/*.json') ?: [] as $old) {
            if (filemtime($old) < time() - 86400) {
                $part = substr($old, 0, -5) . '.part';
                if (is_file($part)) unlink($part);
                unlink($old);
            }
        }
        $free = disk_free_space($directory);
        if ($free !== false && $size > $free) throw new RuntimeException('Não há espaço disponível na hospedagem para este arquivo.');
        $token = bin2hex(random_bytes(24));
        $base = editorialUploadPath($token);
        $state = ['user_id'=>$userId, 'kind'=>$kind, 'size'=>$size, 'received'=>0, 'status'=>'uploading'];
        if (file_put_contents($base . '.json', json_encode($state), LOCK_EX) === false || file_put_contents($base . '.part', '') === false) throw new RuntimeException('Não foi possível iniciar o envio.');
        uploadResponse(['token'=>$token, 'received'=>0]);
    }
    $token = $_POST['token'] ?? '';
    $base = editorialUploadPath($token);
    $handle = is_file($base . '.json') ? fopen($base . '.json', 'r+') : false;
    if (!$handle || !flock($handle, LOCK_EX)) throw new RuntimeException('Envio não encontrado. Inicie novamente.');
    $state = json_decode(stream_get_contents($handle), true);
    if (!$state || $state['user_id'] !== $userId) throw new RuntimeException('Envio inválido.');
    if ($action === 'chunk') {
        if ($state['status'] !== 'uploading') throw new RuntimeException('Este envio já foi concluído.');
        $chunk = $_FILES['chunk'] ?? null;
        if (!$chunk || $chunk['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($chunk['tmp_name'])) throw new RuntimeException('Não foi possível receber esta parte do arquivo.');
        if ($chunk['size'] < 1 || $chunk['size'] > 1048576) throw new RuntimeException('Cada parte deve ter até 1 MB.');
        $offset = filter_var($_POST['offset'] ?? '', FILTER_VALIDATE_INT);
        if ($offset === false || $offset < 0 || $offset + $chunk['size'] > $state['size']) throw new RuntimeException('Posição ou tamanho de envio inválido.');
        $part = fopen($base . '.part', 'c+b');
        if (!$part) throw new RuntimeException('Não foi possível abrir o arquivo temporário.');
        try {
            if ($offset < $state['received'] && $offset + $chunk['size'] <= $state['received']) {
                // Repetir uma parte apos falha de rede nao duplica os bytes.
                fseek($part, $offset);
                if (hash('sha256', fread($part, $chunk['size'])) !== hash_file('sha256', $chunk['tmp_name'])) throw new RuntimeException('A parte repetida não corresponde ao arquivo.');
            } else {
                if ($offset !== $state['received']) throw new RuntimeException('As partes devem ser enviadas em ordem.');
                fseek($part, $offset);
                $input = fopen($chunk['tmp_name'], 'rb');
                if (!$input) throw new RuntimeException('Não foi possível ler a parte enviada.');
                try { $written = stream_copy_to_stream($input, $part); } finally { fclose($input); }
                if ($written !== (int) $chunk['size']) { ftruncate($part, $offset); throw new RuntimeException('O envio foi interrompido por falta de espaço.'); }
                $state['received'] += $written;
            }
        } finally { fclose($part); }
    } elseif ($action === 'finish') {
        if ($state['status'] !== 'ready') {
            clearstatcache(true, $base . '.part');
            if ($state['received'] !== $state['size'] || filesize($base . '.part') !== $state['size']) throw new RuntimeException('O arquivo ainda não foi enviado por inteiro.');
            if (in_array($state['kind'], ['cover','image'], true)) {
                $state['filename'] = editorialImageFile($base . '.part', false);
                $state['url'] = BASE_URL . '/uploads/noticias/' . $state['filename'];
            } elseif ($state['kind'] === 'video') {
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($base . '.part');
                $extensions = ['video/mp4'=>'mp4','video/webm'=>'webm','video/ogg'=>'ogv','application/ogg'=>'ogv'];
                if (!isset($extensions[$mime])) throw new RuntimeException('Selecione um vídeo MP4, WEBM ou OGV válido.');
                $directory = editorialMediaRoot() . '/midias';
                if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) throw new RuntimeException('Não foi possível preparar a pasta de vídeos.');
                $state['filename'] = 'video_' . bin2hex(random_bytes(24)) . '.' . $extensions[$mime];
                if (!rename($base . '.part', $directory . '/' . $state['filename'])) throw new RuntimeException('Não foi possível armazenar o vídeo.');
                chmod($directory . '/' . $state['filename'], 0644);
                $state['url'] = BASE_URL . '/uploads/midias/' . $state['filename'];
            }
            $state['status'] = 'ready';
        }
    } else throw new RuntimeException('Ação de envio inválida.');
    rewind($handle); ftruncate($handle, 0);
    if (fwrite($handle, json_encode($state)) === false) throw new RuntimeException('Não foi possível registrar o progresso.');
    fflush($handle); flock($handle, LOCK_UN); fclose($handle); $handle = null;
    uploadResponse(['token'=>$token, 'received'=>$state['received'], 'url'=>$state['url'] ?? null, 'filename'=>$state['filename'] ?? null]);
} catch (Throwable $error) {
    if (is_resource($handle)) { flock($handle, LOCK_UN); fclose($handle); }
    uploadResponse(['error'=>$error->getMessage()], 400);
}