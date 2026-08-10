<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

header("Content-Type: text/xml;charset=iso-8859-1");

$pdo = Database::getInstance();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Home
echo '  <url>' . "\n";
echo '    <loc>' . BASE_URL . '/</loc>' . "\n";
echo '    <changefreq>hourly</changefreq>' . "\n";
echo '    <priority>1.0</priority>' . "\n";
echo '  </url>' . "\n";

// Categories
$stmtCats = $pdo->query("SELECT slug FROM categorias");
while ($cat = $stmtCats->fetch()) {
    echo '  <url>' . "\n";
    echo '    <loc>' . BASE_URL . '/categoria/' . $cat['slug'] . '</loc>' . "\n";
    echo '    <changefreq>daily</changefreq>' . "\n";
    echo '    <priority>0.8</priority>' . "\n";
    echo '  </url>' . "\n";
}

// News
$stmtNews = $pdo->query("SELECT slug, criado_em FROM noticias WHERE status = 'publicado' ORDER BY criado_em DESC LIMIT 1000");
while ($n = $stmtNews->fetch()) {
    echo '  <url>' . "\n";
    echo '    <loc>' . BASE_URL . '/noticia/' . $n['slug'] . '</loc>' . "\n";
    echo '    <lastmod>' . date('Y-m-d\TH:i:s+00:00', strtotime($n['criado_em'])) . '</lastmod>' . "\n";
    echo '    <changefreq>monthly</changefreq>' . "\n";
    echo '    <priority>0.6</priority>' . "\n";
    echo '  </url>' . "\n";
}

echo '</urlset>';
