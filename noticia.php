<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header("Location: " . BASE_URL);
    exit;
}

$pdo = Database::getInstance();

// Buscar a notícia
$stmt = $pdo->prepare("SELECT n.*, c.nome as categoria_nome, c.slug as categoria_slug, u.nome as autor_nome, u.bio as autor_bio, u.foto_perfil as autor_foto 
                       FROM noticias n 
                       JOIN categorias c ON n.categoria_id = c.id 
                       JOIN usuarios u ON n.autor_id = u.id 
                       WHERE n.slug = ? AND n.status = 'publicado'");
$stmt->execute([$slug]);
$noticia = $stmt->fetch();

if (!$noticia) {
    header("HTTP/1.0 404 Not Found");
    $page_title = "Notícia Não Encontrada";
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="container" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 55vh; text-align: center; padding: 60px 20px;">
        <div style="font-size: 5rem; color: var(--color-primary); margin-bottom: 25px;">
            <i class="far fa-frown"></i>
        </div>
        <h1 style="font-size: 2.5rem; font-family: var(--font-title); font-weight: 800; margin-bottom: 15px;">Página Não Encontrada</h1>
        <p style="font-size: 1.1rem; color: var(--color-text-muted); max-width: 600px; margin-bottom: 35px; line-height: 1.6;">
            A notícia que você está procurando pode ter sido removida, alterada ou não existe mais no nosso portal.
        </p>
        <div style="display: flex; gap: 15px; justify-content: center;">
            <a href="<?= BASE_URL ?>/" class="btn">Voltar para a Home</a>
            <a href="<?= BASE_URL ?>/busca.php" class="btn" style="background: transparent; color: var(--color-text); border: 1px solid var(--color-border);">Pesquisar Notícias</a>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Incrementar visualizações
$pdo->prepare("UPDATE noticias SET visualizacoes = visualizacoes + 1 WHERE id = ?")->execute([$noticia['id']]);

// Lógica de Comentário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'comment') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) die("Acesso Negado (CSRF).");
    if (isset($_SESSION['user_id']) && !empty($_POST['conteudo'])) {
        $conteudo = htmlspecialchars(trim($_POST['conteudo']));
        $stmtCom = $pdo->prepare("INSERT INTO comentarios (noticia_id, usuario_id, conteudo, status) VALUES (?, ?, ?, 'aprovado')");
        $stmtCom->execute([$noticia['id'], $_SESSION['user_id'], $conteudo]);
        header("Location: " . BASE_URL . "/noticia/" . $slug . "#comentarios");
        exit;
    }
}

// Lógica de Curtir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'like') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) die("Acesso Negado (CSRF).");
    if (isset($_SESSION['user_id'])) {
        try {
            $stmtLike = $pdo->prepare("INSERT INTO curtidas (noticia_id, usuario_id) VALUES (?, ?)");
            $stmtLike->execute([$noticia['id'], $_SESSION['user_id']]);
        } catch (PDOException $e) {
            // Ignora se já curtiu
        }
        header("Location: " . BASE_URL . "/noticia/" . $slug);
        exit;
    }
}

// Buscar comentários
$stmtComentarios = $pdo->prepare("SELECT c.*, u.nome as usuario_nome, u.foto_perfil as usuario_foto 
                                  FROM comentarios c JOIN usuarios u ON c.usuario_id = u.id 
                                  WHERE c.noticia_id = ? AND c.status = 'aprovado' ORDER BY c.criado_em DESC");
$stmtComentarios->execute([$noticia['id']]);
$comentarios = $stmtComentarios->fetchAll();

// Total de curtidas
$stmtLikes = $pdo->prepare("SELECT COUNT(*) as total FROM curtidas WHERE noticia_id = ?");
$stmtLikes->execute([$noticia['id']]);
$total_likes = $stmtLikes->fetch()['total'];

$ja_curtiu = false;
if (isset($_SESSION['user_id'])) {
    $stmtUserLike = $pdo->prepare("SELECT id FROM curtidas WHERE noticia_id = ? AND usuario_id = ?");
    $stmtUserLike->execute([$noticia['id'], $_SESSION['user_id']]);
    $ja_curtiu = $stmtUserLike->fetch() !== false;
}

$page_title = $noticia['titulo'];
$page_desc = $noticia['subtitulo'];
require_once __DIR__ . '/includes/header.php';
?>

<style>
/* Estilos Específicos da Página de Notícia */
.article-container { max-width: 800px; margin: 0 auto; padding: 40px 20px; }
.article-header { text-align: center; margin-bottom: 40px; }
.article-category { font-family: var(--font-family); color: var(--color-accent); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; font-size: 0.85rem; margin-bottom: 15px; display: inline-block; }
.article-title { font-size: 3rem; line-height: 1.15; letter-spacing: -0.5px; margin-bottom: 20px; }
.article-subtitle { font-family: var(--font-family); font-size: 1.25rem; font-weight: 400; color: var(--color-text-muted); line-height: 1.5; margin-bottom: 25px; }
.article-meta { font-family: var(--font-family); font-size: 0.85rem; color: var(--color-text-muted); display: flex; justify-content: center; gap: 20px; border-top: 1px solid var(--color-border); padding-top: 15px; }
.article-featured-image { width: 100%; max-height: 550px; border-radius: 4px; margin-bottom: 10px; }
.article-caption { font-family: var(--font-family); font-size: 0.8rem; color: #888; text-align: right; margin-bottom: 40px; }

.article-content { font-family: var(--font-family); font-size: 1.15rem; line-height: 1.8; color: var(--color-text); margin-bottom: 50px; }
.article-content p { margin-bottom: 25px; }
.article-content h2, .article-content h3 { font-family: var(--font-title); margin: 40px 0 20px; }
.article-content img { border-radius: 4px; margin: 20px 0; }
.article-content blockquote { border-left: 4px solid var(--color-primary); padding-left: 20px; font-style: italic; color: var(--color-text-muted); margin: 30px 0; }

.article-footer { border-top: 1px solid var(--color-border); border-bottom: 1px solid var(--color-border); padding: 20px 0; display: flex; justify-content: space-between; align-items: center; margin-bottom: 50px; }
.like-btn { background: none; border: 1px solid var(--color-border); padding: 8px 20px; font-family: var(--font-family); font-weight: 600; cursor: pointer; border-radius: 4px; color: var(--color-text); transition: var(--transition); }
.like-btn.liked { color: var(--color-accent); border-color: var(--color-accent); }
.like-btn:hover:not(:disabled) { border-color: var(--color-text); }
.share-links { display: flex; gap: 15px; }
.share-links a { color: var(--color-text-muted); font-size: 1.2rem; }
.share-links a:hover { color: var(--color-primary); }

.author-box { display: flex; gap: 20px; background-color: var(--color-surface); padding: 30px; border-radius: 4px; margin-bottom: 50px; align-items: center; border: 1px solid var(--color-border); }
.author-avatar { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; background: var(--color-border); }
.author-info h3 { font-family: var(--font-family); font-size: 1.1rem; margin-bottom: 5px; }
.author-info p { font-size: 0.95rem; color: var(--color-text-muted); }

/* Comentários */
.comments-section h3 { font-size: 1.5rem; margin-bottom: 30px; border-bottom: 2px solid var(--color-primary); padding-bottom: 10px; display: inline-block; }
.comment { background: var(--color-surface); padding: 20px; border-radius: 4px; margin-bottom: 20px; display: flex; gap: 15px; border: 1px solid var(--color-border); }
.comment-avatar { width: 45px; height: 45px; border-radius: 50%; background: var(--color-border); display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0; }
.comment-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
.comment-author { font-weight: 700; font-size: 0.95rem; }
.comment-date { font-size: 0.8rem; color: var(--color-text-muted); }

@media (max-width: 768px) {
    .article-title { font-size: 2.2rem; }
    .article-meta { flex-direction: column; gap: 5px; }
    .article-footer { flex-direction: column; gap: 20px; }
}
</style>

<article class="article-container">
    <header class="article-header">
        <a href="<?= BASE_URL ?>/categoria/<?= escape($noticia['categoria_slug']) ?>" class="article-category"><?= escape($noticia['categoria_nome']) ?></a>
        <h1 class="article-title"><?= escape($noticia['titulo']) ?></h1>
        <?php if ($noticia['subtitulo']): ?>
            <h2 class="article-subtitle"><?= escape($noticia['subtitulo']) ?></h2>
        <?php endif; ?>
        
        <div class="article-meta">
            <span><strong>Por <?= escape($noticia['autor_nome']) ?></strong></span>
            <span><?= date('d/m/Y \à\s H:i', strtotime($noticia['criado_em'])) ?></span>
            <span><i class="far fa-eye"></i> <?= $noticia['visualizacoes'] ?> visualizações</span>
        </div>
    </header>

    <?php if ($noticia['imagem_destacada']): ?>
        <img src="<?= str_starts_with($noticia['imagem_destacada'], 'data:') ? $noticia['imagem_destacada'] : BASE_URL . '/uploads/noticias/' . escape($noticia['imagem_destacada']) ?>" alt="<?= escape($noticia['titulo']) ?>" class="article-featured-image">
        <?php if ($noticia['legenda_imagem']): ?>
            <div class="article-caption"><?= escape($noticia['legenda_imagem']) ?></div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Inclui o CSS do QuillJS para renderizar a formatação no frontend -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        /* Ajustes finos para o Quill herdar o design do site */
        .ql-editor { padding: 0 !important; font-family: inherit !important; font-size: inherit !important; line-height: inherit !important; overflow-y: visible !important; }
        .ql-editor p { margin-bottom: 25px !important; }
        .ql-snow .ql-editor img { max-width: 100%; border-radius: 4px; margin: 20px 0; }
        .ql-editor h1, .ql-editor h2, .ql-editor h3 { font-family: var(--font-title) !important; color: var(--color-text) !important; margin: 30px 0 15px !important; font-weight: 700 !important; }
    </style>
    
    <div class="article-content ql-snow">
        <div class="ql-editor">
            <?= $noticia['conteudo'] ?>
        </div>
    </div>

    <div class="article-footer">
        <form action="" method="POST">
            <input type="hidden" name="action" value="like">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <button type="submit" class="like-btn <?= $ja_curtiu ? 'liked' : '' ?>" <?= !isset($_SESSION['user_id']) ? 'disabled title="Faça login para curtir"' : '' ?>>
                <i class="<?= $ja_curtiu ? 'fas' : 'far' ?> fa-heart"></i> <?= $total_likes ?> <?= $total_likes == 1 ? 'Curtida' : 'Curtidas' ?>
            </button>
        </form>
        
        <div class="share-links">
            <span style="font-size: 0.9rem; font-weight: 600; margin-right: 5px; color: var(--color-text-muted);">Compartilhe:</span>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(BASE_URL . '/noticia/' . $slug) ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
            <a href="https://twitter.com/intent/tweet?url=<?= urlencode(BASE_URL . '/noticia/' . $slug) ?>&text=<?= urlencode($noticia['titulo']) ?>" target="_blank"><i class="fab fa-twitter"></i></a>
            <a href="https://api.whatsapp.com/send?text=<?= urlencode($noticia['titulo'] . ' ' . BASE_URL . '/noticia/' . $slug) ?>" target="_blank"><i class="fab fa-whatsapp"></i></a>
        </div>
    </div>

    <!-- Autor -->
    <div class="author-box">
        <?php $foto_autor = $noticia['autor_foto'] ? BASE_URL . '/uploads/avatares/' . $noticia['autor_foto'] : 'https://placehold.co/100x100/eeeeee/999999?text=' . strtoupper(substr($noticia['autor_nome'], 0, 1)); ?>
        <img src="<?= $foto_autor ?>" alt="<?= escape($noticia['autor_nome']) ?>" class="author-avatar">
        <div class="author-info">
            <h3><?= escape($noticia['autor_nome']) ?></h3>
            <p><?= escape($noticia['autor_bio'] ?? 'Repórter e colaborador do portal.') ?></p>
        </div>
    </div>

    <!-- Comentários -->
    <div class="comments-section" id="comentarios">
        <h3>Comentários (<?= count($comentarios) ?>)</h3>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <form action="" method="POST" style="margin-bottom: 40px;">
                <input type="hidden" name="action" value="comment">
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                <textarea name="conteudo" class="form-control" rows="4" placeholder="Escreva seu comentário..." required style="margin-bottom: 15px; border-radius: 4px;"></textarea>
                <button type="submit" class="btn">Publicar Comentário</button>
            </form>
        <?php else: ?>
            <div style="padding: 25px; background-color: var(--color-surface); border: 1px solid var(--color-border); border-radius: 4px; margin-bottom: 40px; text-align: center;">
                <p style="margin-bottom: 15px; font-weight: 500;">Faça login ou cadastre-se para participar da discussão.</p>
                <a href="<?= BASE_URL ?>/login.php" class="btn">Entrar</a>
            </div>
        <?php endif; ?>

        <div class="comments-list">
            <?php foreach ($comentarios as $comentario): ?>
            <div class="comment">
                <div class="comment-avatar">
                    <?= strtoupper(substr($comentario['usuario_nome'], 0, 1)) ?>
                </div>
                <div style="flex-grow: 1;">
                    <div class="comment-header">
                        <span class="comment-author"><?= escape($comentario['usuario_nome']) ?></span>
                        <span class="comment-date"><?= date('d/m/Y \à\s H:i', strtotime($comentario['criado_em'])) ?></span>
                    </div>
                    <div class="comment-body" style="font-size: 0.95rem; line-height: 1.5;">
                        <?= nl2br(escape($comentario['conteudo'])) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (count($comentarios) === 0): ?>
                <p style="color: var(--color-text-muted); font-style: italic;">Não há comentários nesta matéria. Seja o primeiro a comentar!</p>
            <?php endif; ?>
        </div>
    </div>
</article>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
