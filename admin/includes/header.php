<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requirePanelAccess();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? escape($page_title) . ' - Admin 96 News' : 'Admin 96 News' ?></title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Adicionando o CSS base da aplicação para reaproveitar botões e forms -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    
    <!-- QuillJS para Editor Rico -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
</head>
<body>

<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-logo logo-container" style="display: flex; align-items: center; justify-content: center; padding: 20px;">
            <a href="<?= BASE_URL ?>/admin/index.php" class="logo" style="display: block; width: 100%; text-align: center;">
                <img src="<?= BASE_URL ?>/assets/logo-96news.png" alt="96News" style="max-width: 140px; height: auto; mix-blend-mode: multiply; object-fit: contain;">
            </a>
        </div>
        <nav class="admin-nav">
            <ul>
                <?php if (isAdmin()): ?>
                <li><a href="<?= BASE_URL ?>/admin/index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <?php endif; ?>
                <li><a href="<?= BASE_URL ?>/admin/noticias.php"><i class="fas fa-newspaper"></i> Notícias</a></li>
                <?php if (isAdmin()): ?>
                <li><a href="<?= BASE_URL ?>/admin/categorias.php"><i class="fas fa-tags"></i> Categorias</a></li>
                <li><a href="<?= BASE_URL ?>/admin/banners.php"><i class="fas fa-bullhorn"></i> Banners</a></li>
                <?php endif; ?>
                <li><a href="<?= BASE_URL ?>/admin/videos.php"><i class="fas fa-video"></i> Vídeos & Lives</a></li>
                <?php if (isAdmin()): ?>
                <li><a href="<?= BASE_URL ?>/admin/comentarios.php"><i class="fas fa-comments"></i> Comentários</a></li>
                <li><a href="<?= BASE_URL ?>/admin/usuarios.php"><i class="fas fa-users"></i> Usuários</a></li>
                <li><a href="<?= BASE_URL ?>/admin/usuarios.php?filter=colunista"><i class="fas fa-pen-nib"></i> Colunistas</a></li>
                <?php endif; ?>
                <li><a href="<?= BASE_URL ?>/" target="_blank"><i class="fas fa-external-link-alt"></i> Ver Site</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <h2 style="font-size: 1.2rem; margin: 0;"><?= isset($page_title) ? escape($page_title) : 'Dashboard' ?></h2>
            </div>
            <div>
                <span style="margin-right: 15px;">Olá, <?= escape($_SESSION['user_nome']) ?></span>
                <a href="<?= BASE_URL ?>/logout.php" class="btn btn-sm btn-outline">Sair</a>
            </div>
        </header>

        <div class="admin-content">
