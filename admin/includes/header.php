<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requirePanelAccess();
header('Content-Type: text/html; charset=utf-8');
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$panelRole = isAdmin() ? 'Administrador' : 'Jornalista';
$navigation = [
    ['index.php', 'Visão geral', 'fa-chart-line', false],
    ['noticias.php', 'Notícias', 'fa-newspaper', false],
    ['videos.php', 'Vídeos e lives', 'fa-video', false],
    ['categorias.php', 'Categorias', 'fa-tags', true],
    ['banners.php', 'Banners', 'fa-bullhorn', true],
    ['comentarios.php', 'Comentários', 'fa-comments', true],
    ['usuarios.php', 'Equipe e usuários', 'fa-users', true],
    ['usuarios.php?filter=colunista', 'Colunistas', 'fa-pen-nib', true],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape($page_title ?? 'Visão geral') ?> · Painel 96 News</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css?v=2">
    <script src="<?= BASE_URL ?>/assets/js/admin.js?v=2" defer></script>
</head>
<body class="panel-body">
<a href="#panel-content" class="skip-link">Ir para o conteúdo</a>
<div class="admin-wrapper">
    <aside class="admin-sidebar" id="panel-navigation" aria-label="Menu do painel">
        <a href="<?= BASE_URL ?>/admin/index.php" class="panel-brand"><span class="brand-mark">96<span>NEWS</span></span><span class="brand-caption">CENTRAL EDITORIAL</span></a>
        <div class="nav-caption">Área de trabalho</div>
        <nav class="admin-nav" aria-label="Navegação principal"><ul>
        <?php foreach ($navigation as [$href, $label, $icon, $adminOnly]):
            if ($adminOnly && !isAdmin()) continue;
            $active = $currentPage === strtok($href, '?');
            if ($currentPage === 'noticia_form.php' && $href === 'noticias.php') $active = true;
            if ($currentPage === 'video_form.php' && $href === 'videos.php') $active = true;
            if ($currentPage === 'usuario_form.php' && $href === 'usuarios.php') $active = true;
            if ($currentPage === 'usuarios.php') $active = $href === (($_GET['filter'] ?? '') === 'colunista' ? 'usuarios.php?filter=colunista' : 'usuarios.php');
        ?>
            <li><a href="<?= BASE_URL ?>/admin/<?= escape($href) ?>" <?= $active ? 'class="active" aria-current="page"' : '' ?>><i class="fas <?= escape($icon) ?>" aria-hidden="true"></i><?= escape($label) ?></a></li>
        <?php endforeach; ?>
        </ul></nav>
        <div class="sidebar-bottom"><a href="<?= BASE_URL ?>/" target="_blank" rel="noopener"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i> Abrir o portal</a><span>96 News · <?= escape($panelRole) ?></span></div>
    </aside>
    <button class="nav-backdrop" type="button" aria-label="Fechar menu" tabindex="-1" hidden></button>
    <main class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-heading"><button class="menu-toggle icon-button" type="button" aria-label="Abrir menu" aria-controls="panel-navigation" aria-expanded="false"><i class="fas fa-bars" aria-hidden="true"></i></button><div><span class="eyebrow">Painel editorial</span><h1><?= escape($page_title ?? 'Visão geral') ?></h1></div></div>
            <div class="topbar-account"><span class="account-avatar" aria-hidden="true"><?= escape(mb_strtoupper(mb_substr($_SESSION['user_nome'] ?? 'U', 0, 1))) ?></span><div class="account-details"><strong><?= escape($_SESSION['user_nome'] ?? 'Usuário') ?></strong><small><?= escape($panelRole) ?></small></div><a href="<?= BASE_URL ?>/logout.php" class="btn btn-sm btn-outline">Sair</a></div>
        </header>
        <div class="admin-content" id="panel-content" tabindex="-1">