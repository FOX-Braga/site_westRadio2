<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requirePanelAccess();
header('Content-Type: text/html; charset=utf-8');

$curPage = basename($_SERVER['PHP_SELF']);
$curFilter = $_GET['filter'] ?? '';

// Contagem de comentários pendentes para badge
$comentarios_pendentes = 0;
if (isAdmin()) {
    try {
        $comentarios_pendentes = (int)Database::getInstance()->query("SELECT COUNT(*) FROM comentarios WHERE status = 'pendente'")->fetchColumn();
    } catch (Exception $e) {}
}

$userNome = $_SESSION['user_nome'] ?? 'Usuário';
$userTipo = $_SESSION['user_tipo'] ?? 'Editor';
$userInitial = strtoupper(substr($userNome, 0, 1));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?= isset($page_title) ? escape($page_title) . ' | Redação 96 News' : 'Painel de Controle | 96 News' ?></title>
    
    <!-- Fonte Editorial Moderna & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Principal do Painel -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    
    <!-- QuillJS para Editor Rico -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
</head>
<body>

<div class="admin-wrapper" id="adminWrapper">
    <!-- Backdrop para fechar o menu no celular -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Sidebar Editorial -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <a href="<?= BASE_URL ?>/admin/<?= isAdmin() ? 'index.php' : 'noticias.php' ?>" class="sidebar-logo">
                <span class="sidebar-logo-text">96 NEWS</span>
                <span class="sidebar-logo-badge">Redação</span>
            </a>
            <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Fechar menu">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <nav class="admin-nav">
            <p class="nav-section-title">Conteúdo</p>
            <ul>
                <?php if (isAdmin()): ?>
                <li>
                    <a href="<?= BASE_URL ?>/admin/index.php" class="<?= $curPage === 'index.php' ? 'active' : '' ?>">
                        <i class="fas fa-chart-pie"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="<?= BASE_URL ?>/admin/noticias.php" class="<?= in_array($curPage, ['noticias.php', 'noticia_form.php']) ? 'active' : '' ?>">
                        <i class="fas fa-newspaper"></i>
                        <span><?= isAdmin() ? 'Notícias' : 'Minhas matérias' ?></span>
                    </a>
                </li><?php if (isAdmin()): ?>
                <li>
                    <a href="<?= BASE_URL ?>/admin/videos.php" class="<?= $curPage === 'videos.php' ? 'active' : '' ?>">
                        <i class="fas fa-play-circle"></i>
                        <span>Vídeos & Lives</span>
                    </a>
                </li><?php endif; ?>
            </ul>

            <?php if (isAdmin()): ?>
            <p class="nav-section-title">Gestão Editorial</p>
            <ul>
                <li>
                    <a href="<?= BASE_URL ?>/admin/categorias.php" class="<?= $curPage === 'categorias.php' ? 'active' : '' ?>">
                        <i class="fas fa-folder"></i>
                        <span>Categorias</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/admin/banners.php" class="<?= $curPage === 'banners.php' ? 'active' : '' ?>">
                        <i class="fas fa-bullhorn"></i>
                        <span>Banners & Anúncios</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/admin/comentarios.php" class="<?= $curPage === 'comentarios.php' ? 'active' : '' ?>">
                        <i class="fas fa-comments"></i>
                        <span>Comentários</span>
                        <?php if ($comentarios_pendentes > 0): ?>
                            <span class="nav-badge"><?= $comentarios_pendentes ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>

            <p class="nav-section-title">Equipe</p>
            <ul>
                <li>
                    <a href="<?= BASE_URL ?>/admin/usuarios.php" class="<?= ($curPage === 'usuarios.php' && $curFilter !== 'colunista') ? 'active' : '' ?>">
                        <i class="fas fa-users-cog"></i>
                        <span>Todos Usuários</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/admin/usuarios.php?filter=colunista" class="<?= ($curPage === 'usuarios.php' && $curFilter === 'colunista') ? 'active' : '' ?>">
                        <i class="fas fa-pen-fancy"></i>
                        <span>Colunistas & Autores</span>
                    </a>
                </li>
            </ul>
            <?php endif; ?>

            <p class="nav-section-title">Atalhos</p>
            <ul>
                <li>
                    <a href="<?= BASE_URL ?>/" target="_blank" rel="noopener">
                        <i class="fas fa-arrow-up-right-from-square"></i>
                        <span>Visualizar Site</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/logout.php" style="color: #f87171;">
                        <i class="fas fa-sign-out-alt" style="color: #f87171;"></i>
                        <span>Encerrar Sessão</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Card do Usuário Logado -->
        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <?= $userInitial ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= escape($userNome) ?></div>
                <div class="sidebar-user-role"><?= escape($userTipo) ?></div>
            </div>
        </div>
    </aside>

    <!-- Conteúdo Principal -->
    <main class="admin-main">
        <!-- Topbar com Ação Rápida e Botão Mobile -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Abrir Navegação">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="topbar-title"><?= isset($page_title) ? escape($page_title) : 'Painel de Controle' ?></h1>
            </div>
            
            <div class="topbar-right">
                <a href="<?= BASE_URL ?>/admin/noticia_form.php" class="btn btn-sm btn-accent" title="Publicar Nova Notícia">
                    <i class="fas fa-plus"></i>
                    <span class="btn-text-desktop">Nova Matéria</span>
                </a>
                <a href="<?= BASE_URL ?>/logout.php" class="btn btn-sm btn-outline" title="Sair da Conta">
                    <i class="fas fa-power-off"></i>
                </a>
            </div>
        </header>

        <div class="admin-content">
