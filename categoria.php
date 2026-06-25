<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header("Location: " . BASE_URL);
    exit;
}

$pdo = Database::getInstance();

// Buscar a categoria
$stmtCat = $pdo->prepare("SELECT * FROM categorias WHERE slug = ?");
$stmtCat->execute([$slug]);
$categoria = $stmtCat->fetch();

if (!$categoria) {
    header("HTTP/1.0 404 Not Found");
    $page_title = "Categoria Não Encontrada";
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="container" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 55vh; text-align: center; padding: 60px 20px;">
        <div style="font-size: 6rem; color: var(--color-primary); margin-bottom: 25px; animation: pulse 2s infinite;">
            <i class="fas fa-tags" style="opacity: 0.5;"></i>
        </div>
        <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 15px; color: var(--color-text);">Oops! Categoria não encontrada</h1>
        <p style="font-size: 1.1rem; color: var(--color-text-muted); max-width: 600px; margin-bottom: 35px; line-height: 1.6;">
            A categoria que você selecionou não existe ou foi removida do nosso portal de notícias.
        </p>
        <div style="display: flex; gap: 15px; flex-wrap: wrap; justify-content: center;">
            <a href="<?= BASE_URL ?>/" class="btn" style="padding: 12px 30px; font-size: 0.95rem; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-home"></i> Voltar para a Home
            </a>
            <a href="<?= BASE_URL ?>/busca.php" class="btn btn-outline" style="padding: 12px 30px; font-size: 0.95rem; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-search"></i> Pesquisar Notícias
            </a>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Paginação
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Contar total de notícias na categoria
$stmtCount = $pdo->prepare("SELECT COUNT(*) as total FROM noticias WHERE categoria_id = ? AND status = 'publicado'");
$stmtCount->execute([$categoria['id']]);
$total = $stmtCount->fetch()['total'];
$total_pages = ceil($total / $limit);

// Buscar notícias
$stmtNoticias = $pdo->prepare("SELECT n.*, c.nome as categoria_nome, c.slug as categoria_slug 
                               FROM noticias n 
                               JOIN categorias c ON n.categoria_id = c.id 
                               WHERE n.categoria_id = ? AND n.status = 'publicado' 
                               ORDER BY n.criado_em DESC LIMIT $limit OFFSET $offset");
$stmtNoticias->execute([$categoria['id']]);
$noticias = $stmtNoticias->fetchAll();

$page_title = $categoria['nome'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 15px;">
    <div style="margin: 30px 0; border-left: 6px solid <?= escape($categoria['cor']) ?>; padding-left: 20px; display: flex; flex-direction: column; align-items: flex-start;">
        <h1 style="font-size: 2.6rem; font-weight: 800; margin: 0; color: var(--color-text); letter-spacing: -0.8px; text-transform: capitalize;"><?= escape($categoria['nome']) ?></h1>
        <p style="margin-top: 8px; font-size: 1.05rem; color: var(--color-text-muted); font-weight: 500;">Últimas notícias sobre <?= strtolower(escape($categoria['nome'])) ?></p>
    </div>

    <div class="news-grid-4" style="margin-bottom: 50px;">
        <?php foreach ($noticias as $item): ?>
        <div class="news-card-v">
            <a href="<?= BASE_URL ?>/noticia/<?= escape($item['slug']) ?>">
                <div class="img-wrapper">
                    <img src="<?= $item['imagem_destacada'] ? (str_starts_with($item['imagem_destacada'], 'data:') ? $item['imagem_destacada'] : BASE_URL . '/uploads/noticias/' . $item['imagem_destacada']) : 'https://placehold.co/600x400/eeeeee/999999?text=Sem+Imagem' ?>" alt="<?= escape($item['titulo']) ?>" loading="lazy">
                </div>
                <span class="tag" style="color: <?= escape($categoria['cor']) ?>"><?= escape($item['categoria_nome']) ?></span>
                <h3><?= escape($item['titulo']) ?></h3>
                <p><i class="far fa-clock"></i> <?= date('d/m/Y', strtotime($item['criado_em'])) ?></p>
            </a>
        </div>
        <?php endforeach; ?>
        <?php if (count($noticias) === 0): ?>
            <p style="grid-column: 1 / -1; font-family: var(--font-family); color: var(--color-text-muted); font-size: 1.1rem;">Nenhuma notícia encontrada nesta categoria.</p>
        <?php endif; ?>
    </div>

    <!-- Paginação -->
    <?php if ($total_pages > 1): ?>
    <div style="display: flex; justify-content: center; gap: 10px; margin-bottom: 50px;">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?p=<?= $i ?>" class="btn <?= $i === $page ? '' : 'btn-outline' ?>" style="<?= $i === $page ? 'background-color:' . escape($categoria['cor']) . ';' : 'border-color:' . escape($categoria['cor']) . '; color:' . escape($categoria['cor']) . ';' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
