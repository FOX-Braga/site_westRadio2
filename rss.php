<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = Database::getInstance();

// Pega as últimas 20 notícias publicadas
$stmt = $pdo->prepare("
    SELECT n.id, n.titulo, n.subtitulo, n.slug, n.conteudo, n.criado_em, c.nome as categoria 
    FROM noticias n 
    LEFT JOIN categorias c ON n.categoria_id = c.id 
    WHERE n.status = 'publicado' 
    ORDER BY n.criado_em DESC 
    LIMIT 20
");
$stmt->execute();
$noticias = $stmt->fetchAll();

// Configura o cabeçalho para XML
header('Content-Type: application/rss+xml; charset=utf-8');

echo '<?xml version="1.0" encoding="UTF-8" ?>';
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title><?= SITE_NAME ?> - Últimas Notícias</title>
    <link><?= BASE_URL ?></link>
    <description><?= SITE_DESC ?></description>
    <language>pt-br</language>
    <atom:link href="<?= BASE_URL ?>/rss.php" rel="self" type="application/rss+xml" />
    
    <?php foreach ($noticias as $n): ?>
    <item>
      <title><?= htmlspecialchars($n['titulo'], ENT_XML1) ?></title>
      <link><?= BASE_URL ?>/noticia/<?= $n['slug'] ?></link>
      <description><?= htmlspecialchars($n['subtitulo'] ?: strip_tags(substr($n['conteudo'], 0, 200)) . '...', ENT_XML1) ?></description>
      <category><?= htmlspecialchars($n['categoria'], ENT_XML1) ?></category>
      <pubDate><?= date(DATE_RSS, strtotime($n['criado_em'])) ?></pubDate>
      <guid isPermaLink="true"><?= BASE_URL ?>/noticia/<?= $n['slug'] ?></guid>
    </item>
    <?php endforeach; ?>
    
  </channel>
</rss>
