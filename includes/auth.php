<?php
require_once __DIR__ . '/../config/config.php';

// Atualiza o nível do usuário na sessão em tempo real (evita problemas de cache)
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/database.php';
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("SELECT tipo FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $tipo = $stmt->fetchColumn();
    if ($tipo) {
        $_SESSION['user_tipo'] = $tipo;
    } else {
        unset($_SESSION['user_id'], $_SESSION['user_tipo'], $_SESSION['user_nome'], $_SESSION['user_foto']);
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

function isAdmin() {
    return isset($_SESSION['user_tipo']) && $_SESSION['user_tipo'] === 'admin';
}

function isJornalista() {
    return isset($_SESSION['user_tipo']) && $_SESSION['user_tipo'] === 'jornalista';
}

function hasPanelAccess() {
    return isAdmin() || isJornalista();
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        denyPanelAccess();
    }
}

function denyPanelAccess() {
    http_response_code(403);
    exit('Acesso negado. Seu cargo não permite esta ação.');
}

function requirePanelAccess() {
    requireLogin();
    if (!hasPanelAccess()) denyPanelAccess();
    // Lista explicita: novas paginas administrativas nao ficam liberadas por acidente.
    $page = basename($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '');
    if (isJornalista() && !in_array($page, ['index.php', 'noticias.php', 'noticia_form.php', 'upload.php'], true)) {
        denyPanelAccess();
    }
}

function requireNewsAccess($id) {
    $stmt = Database::getInstance()->prepare('SELECT autor_id FROM noticias WHERE id = ?');
    $stmt->execute([$id]);
    $news = $stmt->fetch();
    if (!$news) { http_response_code(404); exit('Matéria não encontrada.'); }
    if (!isAdmin() && (int) $news['autor_id'] !== (int) $_SESSION['user_id']) denyPanelAccess();
}
function loginUser($user) {
    session_regenerate_id(true); // Previne Session Fixation
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nome'] = $user['nome'];
    $_SESSION['user_tipo'] = $user['tipo'];
    $_SESSION['user_foto'] = $user['foto_perfil'];
}

function logoutUser() {
    session_unset();
    session_destroy();
}
