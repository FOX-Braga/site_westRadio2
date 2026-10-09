<?php
require_once __DIR__ . '/includes/news_queries.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$query = $_GET['q'] ?? '';
$noticias = [];

if (!empty($query)) {
    $pdo = Database::getInstance();
    $imageSql = newsImageSql($pdo);
    $searchTerm = "%{$query}%";
    
    // Detecta se está usando PostgreSQL (Supabase) para usar ILIKE (case-insensitive)
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $likeOp = ($driver === 'pgsql') ? 'ILIKE' : 'LIKE';
    
    // Buscar notícias que batem com o título, subtítulo ou conteúdo
    $stmt = $pdo->prepare("SELECT n.id, n.titulo, n.subtitulo, n.slug, n.criado_em, {$imageSql} AS imagem_destacada, c.nome as categoria_nome, c.slug as categoria_slug
                           FROM noticias n 
                           LEFT JOIN categorias c ON n.categoria_id = c.id
                           WHERE n.status = 'publicado' 
                           AND (n.titulo $likeOp ? OR n.subtitulo $likeOp ? OR n.conteudo $likeOp ?)
                           ORDER BY n.criado_em DESC LIMIT 30");
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $noticias = $stmt->fetchAll();
}

$page_title = "Busca: " . escape($query);
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="min-height: 50vh;">
    <div style="margin: 40px 0;">
        <h1 class="section-title">Resultados da Busca</h1>
        <p>Mostrando resultados para: <strong>"<?= escape($query) ?>"</strong></p>
    </div>

    <?php if (empty($query)): ?>
        <p style="font-family: var(--font-family); font-size: 1.1rem;">Por favor, digite um termo para buscar.</p>
    <?php elseif (count($noticias) === 0): ?>
        <p style="font-family: var(--font-family); font-size: 1.1rem; color: var(--color-text-muted);">Nenhuma notícia encontrada com este termo.</p>
    <?php else: ?>
        <div class="news-grid-4" style="margin-bottom: 50px;">
            <?php foreach ($noticias as $item): ?>
            <div class="news-card-v">
                <a href="<?= BASE_URL ?>/noticia/<?= escape($item['slug']) ?>">
                    <div class="img-wrapper">
                        <img src="<?= newsImageUrl($item['imagem_destacada']) ?>" alt="<?= escape($item['titulo']) ?>" loading="lazy">
                    </div>
                    <span class="tag"><?= escape($item['categoria_nome']) ?></span>
                    <h3><?= escape($item['titulo']) ?></h3>
                    <p><i class="far fa-clock"></i> <?= date('d/m/Y', strtotime($item['criado_em'])) ?></p>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
