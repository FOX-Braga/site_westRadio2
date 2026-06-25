<?php
/**
 * Endpoint para comunicação em tempo real da página Ao Vivo
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Forçar cabeçalho JSON
header('Content-Type: application/json');

$session_id = session_id();
if (empty($session_id)) {
    session_start();
    $session_id = session_id();
}

// Configura o usuário atual
if (isset($_SESSION['user_nome'])) {
    $nome_usuario = $_SESSION['user_nome'];
    $usuario_id = $_SESSION['user_id'];
} else {
    if (!isset($_SESSION['guest_name'])) {
        $_SESSION['guest_name'] = 'Visitante #' . rand(1000, 9999);
    }
    $nome_usuario = $_SESSION['guest_name'];
    $usuario_id = null;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'ping';
$pdo = Database::getInstance();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

try {
    // 1. Atualiza Ping do Usuário (Heartbeat)
    $stmtDel = $pdo->prepare("DELETE FROM aovivo_pings WHERE session_id = ?");
    $stmtDel->execute([$session_id]);
    
    $stmtIns = $pdo->prepare("INSERT INTO aovivo_pings (session_id, ultimo_ping) VALUES (?, CURRENT_TIMESTAMP)");
    $stmtIns->execute([$session_id]);
    
    // Limpa pings inativos (mais de 15 segundos)
    if ($driver === 'pgsql') {
        $pdo->query("DELETE FROM aovivo_pings WHERE ultimo_ping < NOW() - INTERVAL '15 seconds'");
    } else {
        $pdo->query("DELETE FROM aovivo_pings WHERE ultimo_ping < datetime('now', '-15 seconds')");
    }
    
    // 2. Se for envio de mensagem, processa a inserção
    if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Suporta tanto requisições POST tradicionais quanto JSON
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);
        $mensagem = trim($jsonData['mensagem'] ?? $_POST['mensagem'] ?? '');
        
        if (!empty($mensagem)) {
            // Limita tamanho da mensagem
            $mensagem = mb_substr($mensagem, 0, 100, 'UTF-8');
            
            $stmtMsg = $pdo->prepare("INSERT INTO chat_mensagens (usuario_id, nome_usuario, mensagem) VALUES (?, ?, ?)");
            $stmtMsg->execute([$usuario_id, $nome_usuario, $mensagem]);
        }
    }
    
    // 3. Busca total de espectadores ativos
    $viewersCount = $pdo->query("SELECT COUNT(*) FROM aovivo_pings")->fetchColumn();
    
    // 4. Busca as últimas 50 mensagens do chat
    $stmtMsgs = $pdo->query("SELECT c.id, c.usuario_id, c.nome_usuario, c.mensagem, c.criado_em, u.tipo 
                             FROM chat_mensagens c 
                             LEFT JOIN usuarios u ON c.usuario_id = u.id 
                             ORDER BY c.criado_em DESC LIMIT 50");
    $msgs = $stmtMsgs->fetchAll();
    
    // Inverte a ordem das mensagens para que fiquem cronologicamente crescentes no chat
    $msgs = array_reverse($msgs);
    
    // Formata mensagens para evitar XSS ao servir para o JS
    $formattedMsgs = [];
    foreach ($msgs as $m) {
        $formattedMsgs[] = [
            'id' => $m['id'],
            'nome_usuario' => escape($m['nome_usuario']),
            'mensagem' => escape($m['mensagem']),
            'criado_em' => $m['criado_em'],
            'tipo' => $m['tipo']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'viewers' => max(1, (int)$viewersCount),
        'messages' => $formattedMsgs
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
exit;
