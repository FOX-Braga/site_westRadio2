<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$pdo = Database::getInstance();

// Hook: Garante a coluna data_agendamento e publica posts agendados automaticamente
try {
    @$pdo->exec("ALTER TABLE noticias ADD COLUMN data_agendamento DATETIME DEFAULT NULL");
} catch(Exception $e) {}

// Hook: Garante a criação da tabela de banners
try {
    $db_driver = DB_DRIVER ?? 'sqlite';
    if ($db_driver === 'sqlite') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS banners (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo VARCHAR(255) NOT NULL,
            imagem VARCHAR(255) NOT NULL,
            link VARCHAR(255) NOT NULL,
            status VARCHAR(20) DEFAULT 'ativo',
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    } else if ($db_driver === 'mysql') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS banners (
            id INT AUTO_INCREMENT PRIMARY KEY,
            titulo VARCHAR(255) NOT NULL,
            imagem VARCHAR(255) NOT NULL,
            link VARCHAR(255) NOT NULL,
            status VARCHAR(20) DEFAULT 'ativo',
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    } else if ($db_driver === 'pgsql') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS banners (
            id SERIAL PRIMARY KEY,
            titulo VARCHAR(255) NOT NULL,
            imagem VARCHAR(255) NOT NULL,
            link VARCHAR(255) NOT NULL,
            status VARCHAR(20) DEFAULT 'ativo',
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
} catch(Exception $e) {}

if (isset($pdo)) {
    $now = date('Y-m-d H:i:s');
    $pdo->prepare("UPDATE noticias SET status = 'publicado', data_agendamento = NULL, criado_em = ? WHERE status = 'rascunho' AND data_agendamento IS NOT NULL AND data_agendamento <= ?")->execute([$now, $now]);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? escape($page_title) . ' - ' . SITE_NAME : SITE_NAME . ' - ' . SITE_DESC ?></title>
    
    <!-- Meta tags SEO / Open Graph -->
    <meta name="description" content="<?= isset($page_desc) ? escape($page_desc) : SITE_DESC ?>">
    <meta property="og:title" content="<?= isset($page_title) ? escape($page_title) : SITE_NAME ?>">
    <meta property="og:description" content="<?= isset($page_desc) ? escape($page_desc) : SITE_DESC ?>">
    <meta property="og:type" content="website">
    
    <link rel="icon" href="<?= BASE_URL ?>/assets/images/westnews_logo.png" type="image/png">
    
    <!-- FontAwesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Fonte Estilo Antena 1 (Montserrat Italic) -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,800;1,800&display=swap" rel="stylesheet">
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>

<header class="site-header">
    <!-- Top Bar -->
    <div class="header-top">
        <div class="container">
            <div class="header-top-left">
                <span><i class="far fa-calendar-alt"></i> <?= date('d \d\e M, Y') ?></span>
                <span style="border-left: 1px solid var(--color-border); padding-left: 15px;"><i class="fas fa-cloud-sun"></i> 26°C Campo Grande</span>
            </div>
            <div class="header-top-right">
                <div class="header-social">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
                <span style="border-left: 1px solid var(--color-border); height: 15px; margin: 0 5px;"></span>
                <?php if (isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>/perfil.php" style="font-weight: 700; color: var(--color-primary);"><i class="far fa-user"></i> Olá, <?= escape(explode(' ', $_SESSION['user_nome'])[0]) ?></a>
                    <?php if (hasPanelAccess()): ?>
                        <a href="<?= BASE_URL ?>/admin/index.php">Painel</a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/logout.php">Sair</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php" style="font-weight: 700; color: var(--color-text);"><i class="far fa-user"></i> Entrar</a>
                    <a href="<?= BASE_URL ?>/cadastro.php" style="font-weight: 700; color: var(--color-accent);">Assine</a>
                <?php endif; ?>
                <button class="theme-toggle" aria-label="Tema" style="background: none; border: none; cursor: pointer; color: var(--color-text-muted); margin-left: 10px;"><i class="fas fa-moon"></i></button>
            </div>
        </div>
    </div>
    
    <!-- Main Logo Area & Linktree -->
    <div class="header-main">
        <div class="container" style="display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 20px;">
            <div style="display: flex; align-items: center; gap: 20px;">
                <button class="mobile-menu-btn" aria-label="Menu" onclick="document.getElementById('sidebar-menu').style.display='block'" style="position: static; transform: none;"><i class="fas fa-bars"></i></button>
            </div>

            <!-- Alteração da Logo Centralizada -->
            <div style="display: flex; justify-content: center;">
                <a href="<?= BASE_URL ?>/" class="logo" style="display: block; text-decoration: none;">
                    <img src="<?= BASE_URL ?>/assets/logo-96news.png" alt="96News" style="height: 140px; width: auto; mix-blend-mode: multiply; object-fit: contain;">
                </a>
            </div>

            <!-- Botão da Rádio alinhado à direita -->
            <div style="display: flex; gap: 15px;">
                <a href="<?= BASE_URL ?>/radio.php" style="background-color: #ffffff; color: #cc0000; border: 2px solid #cc0000; border-radius: 50px; font-weight: bold; padding: 10px 20px; display: flex; align-items: center; gap: 8px; text-decoration: none; transition: all 0.3s;" onmouseover="this.style.backgroundColor='#cc0000'; this.style.color='#ffffff';" onmouseout="this.style.backgroundColor='#ffffff'; this.style.color='#cc0000';"><i class="fas fa-broadcast-tower"></i> Ouça ao Vivo</a>
            </div>
        </div>
    </div>
    
    <!-- Sticky Nav -->
    <nav class="header-nav">
        <div class="container">
            <!-- 2. Menu de Navegação (Linha Única) -->
            <ul class="nav-links">
                <li><a href="<?= BASE_URL ?>/">HOME</a></li>
                <li><a href="<?= BASE_URL ?>/categoria/ultimas-noticias">ÚLTIMAS NOTÍCIAS</a></li>
                <li><a href="<?= BASE_URL ?>/categoria/politica">POLÍTICA</a></li>
                <li><a href="<?= BASE_URL ?>/categoria/policia">POLÍCIA</a></li>
                <li><a href="<?= BASE_URL ?>/categoria/economia">ECONOMIA</a></li>
                <li><a href="<?= BASE_URL ?>/categoria/saude">SAÚDE</a></li>
                <li><a href="<?= BASE_URL ?>/categoria/esportes">ESPORTES</a></li>
                <li><a href="<?= BASE_URL ?>/categoria/mundo">MUNDO</a></li>
                <li><a href="<?= BASE_URL ?>/categoria/brasil">BRASIL</a></li>
                
                <li><a href="<?= BASE_URL ?>/categoria/entretenimento">ENTRETENIMENTO</a></li>
                
                <!-- Dropdown Sanfona para Categorias Ocultas -->
                <li class="nav-dropdown">
                    <a href="javascript:void(0)" class="dropdown-trigger">MAIS ▾</a>
                    <ul class="dropdown-menu">
                        <li><a href="<?= BASE_URL ?>/categoria/cultura">CULTURA</a></li>
                        <li><a href="<?= BASE_URL ?>/categoria/tecnologia">TECNOLOGIA</a></li>
                        <li><a href="<?= BASE_URL ?>/categoria/ciencia">CIÊNCIA</a></li>
                        <li><a href="<?= BASE_URL ?>/categoria/turismo">TURISMO</a></li>
                        <li><a href="<?= BASE_URL ?>/categoria/opiniao">OPINIÃO</a></li>
                        <li><a href="<?= BASE_URL ?>/categoria/podcasts">PODCASTS</a></li>
                        <li><a href="<?= BASE_URL ?>/ao-vivo" style="color: var(--color-accent); font-weight: bold;"><i class="fas fa-circle" style="animation: blink-live 1.5s infinite;"></i> AO VIVO</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

<?php
// Carrossel de Banners no Topo (Substituindo os laterais)
try {
    if (isset($pdo)) {
        $stmtBanners = $pdo->query("SELECT * FROM banners WHERE status = 'ativo'");
        if ($stmtBanners) {
            $displayBanners = $stmtBanners->fetchAll();
            if (count($displayBanners) > 0) {
?>
                <div class="top-banner-container" style="padding: 25px 0; background-color: var(--color-bg); text-align: center;">
                    <div class="container" style="max-width: 1050px;">
                        <div id="top-banner-carousel" style="position: relative; overflow: hidden; width: 100%; display: flex; justify-content: center;">
                            <?php foreach ($displayBanners as $i => $banner): ?>
                                <a href="<?= escape($banner['link']) ?>" target="_blank" class="top-banner-slide" style="display: <?= $i === 0 ? 'block' : 'none' ?>; width: 100%;">
                                    <img src="<?= BASE_URL ?>/uploads/banners/<?= escape($banner['imagem']) ?>" alt="<?= escape($banner['titulo']) ?>" style="width: 100%; height: auto; max-height: 250px; object-fit: cover; margin: 0 auto;">
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const slides = document.querySelectorAll('.top-banner-slide');
                    if (slides.length <= 1) return;
                    let currentIndex = 0;
                    setInterval(() => {
                        slides[currentIndex].style.display = 'none';
                        currentIndex = (currentIndex + 1) % slides.length;
                        slides[currentIndex].style.display = 'block';
                    }, 5000); // Troca de banner a cada 5 segundos sem mostrar bublis
                });
                </script>
<?php
            }
        }
    }
} catch (Throwable $e) {
    echo "<!-- Erro Banners Topo: " . $e->getMessage() . " -->";
}
?>
    
    <!-- Menu Sanfona (Accordion) Sidebar -->
    <div id="sidebar-menu" style="display: none; position: fixed; left: 0; top: 0; width: 280px; height: 100vh; background: var(--color-surface); z-index: 10000; padding: 20px; box-shadow: 2px 0 10px rgba(0,0,0,0.5); overflow-y: auto;">
        <button onclick="this.parentElement.style.display='none'" style="float: right; border: none; background: transparent; font-size: 1.8rem; cursor: pointer; color: var(--color-text);">&times;</button>
        <h3 style="margin-bottom: 20px; border-bottom: 2px solid var(--color-primary); padding-bottom: 10px; color: var(--color-primary);">Mais Categorias</h3>
        <ul style="list-style: none; padding: 0;">
            <li style="margin-bottom: 15px;"><a href="<?= BASE_URL ?>/categoria/cultura" style="font-weight: 600; font-size: 1.1rem;"><i class="fas fa-chevron-right" style="font-size: 0.8rem; color: var(--color-accent); margin-right: 5px;"></i> Cultura</a></li>
            <li style="margin-bottom: 15px;"><a href="<?= BASE_URL ?>/categoria/tecnologia" style="font-weight: 600; font-size: 1.1rem;"><i class="fas fa-chevron-right" style="font-size: 0.8rem; color: var(--color-accent); margin-right: 5px;"></i> Tecnologia</a></li>
            <li style="margin-bottom: 15px;"><a href="<?= BASE_URL ?>/categoria/ciencia" style="font-weight: 600; font-size: 1.1rem;"><i class="fas fa-chevron-right" style="font-size: 0.8rem; color: var(--color-accent); margin-right: 5px;"></i> Ciência</a></li>
            <li style="margin-bottom: 15px;"><a href="<?= BASE_URL ?>/categoria/turismo" style="font-weight: 600; font-size: 1.1rem;"><i class="fas fa-chevron-right" style="font-size: 0.8rem; color: var(--color-accent); margin-right: 5px;"></i> Turismo</a></li>
            <li style="margin-bottom: 15px;"><a href="<?= BASE_URL ?>/categoria/opiniao" style="font-weight: 600; font-size: 1.1rem;"><i class="fas fa-chevron-right" style="font-size: 0.8rem; color: var(--color-accent); margin-right: 5px;"></i> Opinião</a></li>
            <li style="margin-bottom: 15px;"><a href="<?= BASE_URL ?>/categoria/podcasts" style="font-weight: 600; font-size: 1.1rem;"><i class="fas fa-chevron-right" style="font-size: 0.8rem; color: var(--color-accent); margin-right: 5px;"></i> Podcasts</a></li>
            <li style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--color-border);"><a href="<?= BASE_URL ?>/ao-vivo" style="color: var(--color-accent); font-weight: 700; font-size: 1.1rem;"><i class="fas fa-circle" style="animation: blink-live 1.5s infinite;"></i> Ao Vivo</a></li>
        </ul>
    </div>
</header>

<main class="site-main">
