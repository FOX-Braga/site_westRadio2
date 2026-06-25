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
    if (!isAdmin()) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

function requirePanelAccess() {
    if (!hasPanelAccess()) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
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
