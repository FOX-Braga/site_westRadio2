<?php
// Roteamento de contingência
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$base_path = rtrim(dirname($script_name), '/\\');
$request_uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));

if ($base_path !== '' && strpos($request_uri, $base_path) === 0) {
    $request_uri = substr($request_uri, strlen($base_path));
}

if ($request_uri !== '/' && str_ends_with($request_uri, '/')) {
    $request_uri = rtrim($request_uri, '/');
}

if (preg_match('/^\/categoria\/([a-zA-Z0-9_-]+)$/', $request_uri, $matches)) {
    $_GET['slug'] = $matches[1];
    require_once __DIR__ . '/categoria.php';
    exit;
}

if (preg_match('/^\/noticia\/([a-zA-Z0-9_-]+)$/', $request_uri, $matches)) {
    $_GET['slug'] = $matches[1];
    require_once __DIR__ . '/noticia.php';
    exit;
}

if ($request_uri === '/ao-vivo') { require_once __DIR__ . '/aovivo.php'; exit; }
if ($request_uri === '/busca') { require_once __DIR__ . '/busca.php'; exit; }
if ($request_uri === '/sobre') { require_once __DIR__ . '/sobre.php'; exit; }
if ($request_uri === '/contato') { require_once __DIR__ . '/contato.php'; exit; }
if ($request_uri === '/login') { require_once __DIR__ . '/login.php'; exit; }
if ($request_uri === '/cadastro') { require_once __DIR__ . '/cadastro.php'; exit; }
if ($request_uri === '/perfil') { require_once __DIR__ . '/perfil.php'; exit; }
if ($request_uri === '/migrar-banco') { require_once __DIR__ . '/migrar-banco.php'; exit; }

$page_title = "Home";
require_once __DIR__ . '/includes/header.php';

// Busca Destaques Principais
$stmt = $pdo->query("SELECT n.*, c.nome as categoria_nome, c.slug as categoria_slug, u.nome as autor_nome 
                     FROM noticias n 
                     JOIN categorias c ON n.categoria_id = c.id 
                     LEFT JOIN usuarios u ON n.autor_id = u.id
                     WHERE n.status = 'publicado' 
                     ORDER BY n.destaque DESC, n.criado_em DESC LIMIT 4");
$destaques = $stmt->fetchAll();
$noticia_destaque_principal = $destaques[0] ?? null;
$sub_destaques = array_slice($destaques, 1, 3);

// Busca Últimas Notícias (20 matérias)
$stmt = $pdo->query("SELECT n.*, c.nome as categoria_nome, c.slug as categoria_slug 
                  FROM noticias n JOIN categorias c ON n.categoria_id = c.id 
                  WHERE n.status = 'publicado' ORDER BY n.criado_em DESC LIMIT 20");
$todas_ultimas = $stmt->fetchAll();
$destaques_manha = array_slice($todas_ultimas, 0, 10);
$destaques_tarde = array_slice($todas_ultimas, 10, 10);

// Busca Seções Temáticas
$categories_slugs = ['mundo', 'politica', 'economia', 'cultura', 'tecnologia', 'saude', 'esportes', 'opiniao'];
$sections_data = [];
foreach ($categories_slugs as $slug) {
    $stmt = $pdo->prepare("SELECT n.*, c.nome as categoria_nome, c.slug as categoria_slug, u.nome as autor_nome, u.foto_perfil as autor_foto 
                           FROM noticias n JOIN categorias c ON n.categoria_id = c.id 
                           LEFT JOIN usuarios u ON n.autor_id = u.id
                           WHERE n.status = 'publicado' AND c.slug = ? ORDER BY n.criado_em DESC LIMIT 4");
    $stmt->execute([$slug]);
    $sections_data[$slug] = $stmt->fetchAll();
}

function renderImage($img) {
    if (!$img) return 'https://placehold.co/600x400/eeeeee/999999?text=Sem+Imagem';
    if (str_starts_with($img, 'data:')) return $img;
    return BASE_URL . '/uploads/noticias/' . $img;
}
?>

<div class="container" style="padding-top: 30px;">

    <!-- ================= HERO ================= -->
    <?php if ($noticia_destaque_principal): ?>
    <section class="hero-section">
        <div class="hero-grid">
            <!-- Manchete Principal -->
            <div class="hero-main">
                <a href="<?= BASE_URL ?>/noticia/<?= escape($noticia_destaque_principal['slug']) ?>">
                    <img src="<?= renderImage($noticia_destaque_principal['imagem_destacada']) ?>" alt="<?= escape($noticia_destaque_principal['titulo']) ?>" class="hero-img">
                    <span class="hero-tag"><?= escape($noticia_destaque_principal['categoria_nome']) ?></span>
                    <h1><?= escape($noticia_destaque_principal['titulo']) ?></h1>
                    <p><?= escape($noticia_destaque_principal['subtitulo']) ?></p>
                    <div class="hero-meta">
                        <span>Por <?= escape($noticia_destaque_principal['autor_nome'] ?? 'Redação') ?></span>
                        <span><?= date('d/m/Y', strtotime($noticia_destaque_principal['criado_em'])) ?></span>
                    </div>
                </a>
            </div>
            
            <!-- Notícias Menores ao Lado -->
            <div class="hero-side">
                <?php foreach ($sub_destaques as $item): ?>
                <div class="side-news">
                    <a href="<?= BASE_URL ?>/noticia/<?= escape($item['slug']) ?>">
                        <img src="<?= renderImage($item['imagem_destacada']) ?>" alt="<?= escape($item['titulo']) ?>" class="side-news-img">
                        <span class="hero-tag" style="font-size: 0.7rem;"><?= escape($item['categoria_nome']) ?></span>
                        <h3><?= escape($item['titulo']) ?></h3>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

</div>

<!-- 3. Feed de Matérias (Destaques da Manhã e Tarde) -->
<div class="ultimas-faixa">
    <div class="container">
        <!-- Destaques da Manhã -->
        <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 20px;">
            <div style="font-family: var(--font-title); font-weight: 800; font-size: 1.1rem; color: var(--color-primary); flex-shrink: 0; padding-right: 15px; border-right: 2px solid var(--color-border); width: 220px; text-transform: uppercase;">Destaques da Manhã</div>
            <div class="ultimas-container">
                <?php foreach ($destaques_manha as $item): ?>
                <div class="ultimas-item">
                    <span class="ultimas-time"><?= date('H:i', strtotime($item['criado_em'])) ?></span>
                    <a href="<?= BASE_URL ?>/noticia/<?= escape($item['slug']) ?>" class="ultimas-title" title="<?= escape($item['titulo']) ?>">
                        <?= escape($item['titulo']) ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Destaques da Tarde -->
        <?php if(count($destaques_tarde) > 0): ?>
        <div style="display: flex; gap: 20px; align-items: center;">
            <div style="font-family: var(--font-title); font-weight: 800; font-size: 1.1rem; color: var(--color-primary); flex-shrink: 0; padding-right: 15px; border-right: 2px solid var(--color-border); width: 220px; text-transform: uppercase;">Destaques da Tarde</div>
            <div class="ultimas-container">
                <?php foreach ($destaques_tarde as $item): ?>
                <div class="ultimas-item">
                    <span class="ultimas-time"><?= date('H:i', strtotime($item['criado_em'])) ?></span>
                    <a href="<?= BASE_URL ?>/noticia/<?= escape($item['slug']) ?>" class="ultimas-title" title="<?= escape($item['titulo']) ?>">
                        <?= escape($item['titulo']) ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="container">

    <!-- ================= MUNDO ================= -->
    <?php if (count($sections_data['mundo']) > 0): ?>
    <section>
        <div style="display: flex; justify-content: space-between; align-items: baseline;">
            <h2 class="section-title">Mundo</h2>
            <a href="<?= BASE_URL ?>/categoria/mundo" style="font-size: 0.85rem; font-weight: 700; color: var(--color-text-muted);">Ver mais <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="news-grid-4">
            <?php foreach ($sections_data['mundo'] as $item): ?>
            <div class="news-card-v">
                <a href="<?= BASE_URL ?>/noticia/<?= escape($item['slug']) ?>">
                    <div class="img-wrapper"><img src="<?= renderImage($item['imagem_destacada']) ?>" alt=""></div>
                    <span class="tag"><?= escape($item['categoria_nome']) ?></span>
                    <h3><?= escape($item['titulo']) ?></h3>
                    <p><i class="far fa-clock"></i> <?= date('d/m/Y', strtotime($item['criado_em'])) ?></p>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ================= ECONOMIA & MERCADO ================= -->
    <?php if (count($sections_data['economia']) > 0): ?>
    <section>
        <div class="eco-header">
            <h2 class="section-title">Economia</h2>
            <div class="eco-indicators">
                <div class="eco-ind">IBOV 128.500 <span class="ind-up"><i class="fas fa-caret-up"></i> 1.2%</span></div>
                <div class="eco-ind">DÓLAR R$ 5,42 <span class="ind-down"><i class="fas fa-caret-down"></i> 0.5%</span></div>
                <div class="eco-ind">EURO R$ 5,88 <span class="ind-down"><i class="fas fa-caret-down"></i> 0.3%</span></div>
                <div class="eco-ind">SELIC 10.50%</div>
            </div>
        </div>
        <div class="news-grid-4">
            <?php foreach ($sections_data['economia'] as $item): ?>
            <div class="news-card-v">
                <a href="<?= BASE_URL ?>/noticia/<?= escape($item['slug']) ?>">
                    <div class="img-wrapper"><img src="<?= renderImage($item['imagem_destacada']) ?>" alt=""></div>
                    <span class="tag" style="color: #16a34a;"><?= escape($item['categoria_nome']) ?></span>
                    <h3><?= escape($item['titulo']) ?></h3>
                    <p><?= escape($item['subtitulo']) ?></p>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ================= CULTURA (GRID RICO) ================= -->
    <?php if (count($sections_data['cultura']) > 0): ?>
    <section>
        <h2 class="section-title">Cultura & Entretenimento</h2>
        <div class="cultura-grid">
            <?php $main_cultura = $sections_data['cultura'][0]; $sub_cultura = array_slice($sections_data['cultura'], 1); ?>
            <div class="cultura-main">
                <a href="<?= BASE_URL ?>/noticia/<?= escape($main_cultura['slug']) ?>">
                    <img src="<?= renderImage($main_cultura['imagem_destacada']) ?>" alt="">
                    <div class="overlay">
                        <span class="tag" style="color: var(--color-accent); font-weight: 800; text-transform: uppercase; margin-bottom: 10px; display: block; font-family: var(--font-family); font-size: 0.8rem;"><?= escape($main_cultura['categoria_nome']) ?></span>
                        <h2><?= escape($main_cultura['titulo']) ?></h2>
                    </div>
                </a>
            </div>
            <div class="cultura-list">
                <?php foreach ($sub_cultura as $item): ?>
                <div class="cultura-list-item">
                    <a href="<?= BASE_URL ?>/noticia/<?= escape($item['slug']) ?>" style="display: flex; gap: 15px; align-items: center;">
                        <img src="<?= renderImage($item['imagem_destacada']) ?>" alt="">
                        <div>
                            <span class="tag" style="font-size: 0.7rem; color: var(--color-primary); font-weight: 800; text-transform: uppercase;"><?= escape($item['categoria_nome']) ?></span>
                            <h4><?= escape($item['titulo']) ?></h4>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ================= OUTRAS SEÇÕES (POLÍTICA / TECNOLOGIA / SAÚDE / ESPORTES) ================= -->
    <?php 
    $secondary_sections = ['politica' => 'Política', 'tecnologia' => 'Tecnologia', 'saude' => 'Saúde & Bem-Estar', 'esportes' => 'Esportes'];
    foreach ($secondary_sections as $slug => $title): 
        if (count($sections_data[$slug]) > 0):
    ?>
    <section>
        <div style="display: flex; justify-content: space-between; align-items: baseline;">
            <h2 class="section-title"><?= $title ?></h2>
            <a href="<?= BASE_URL ?>/categoria/<?= $slug ?>" style="font-size: 0.85rem; font-weight: 700; color: var(--color-text-muted);">Ver mais <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="news-grid-4">
            <?php foreach ($sections_data[$slug] as $item): ?>
            <div class="news-card-v">
                <a href="<?= BASE_URL ?>/noticia/<?= escape($item['slug']) ?>">
                    <div class="img-wrapper"><img src="<?= renderImage($item['imagem_destacada']) ?>" alt=""></div>
                    <span class="tag"><?= escape($item['categoria_nome']) ?></span>
                    <h3><?= escape($item['titulo']) ?></h3>
                    <p><i class="far fa-clock"></i> <?= date('d/m/Y', strtotime($item['criado_em'])) ?></p>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php 
        endif;
    endforeach; 
    ?>

    <!-- ================= OPINIÃO E COLUNISTAS ================= -->
    <?php if (count($sections_data['opiniao']) > 0): ?>
    <section style="background-color: var(--color-surface); padding: 40px; margin: 50px -20px; border-top: 1px solid var(--color-border); border-bottom: 1px solid var(--color-border);">
        <div class="container">
            <h2 class="section-title">Colunistas & Opinião</h2>
            <div class="opiniao-grid">
                <?php foreach ($sections_data['opiniao'] as $item): ?>
                <div class="opiniao-card">
                    <a href="<?= BASE_URL ?>/noticia/<?= escape($item['slug']) ?>">
                        <?php $foto_autor = $item['autor_foto'] ? BASE_URL . '/uploads/avatares/' . $item['autor_foto'] : 'https://placehold.co/100x100/eeeeee/999999?text=Autor'; ?>
                        <img src="<?= $foto_autor ?>" alt="Autor">
                        <div class="opiniao-autor"><?= escape($item['autor_nome'] ?? 'Colunista Convidado') ?></div>
                        <h3 class="opiniao-title">"<?= escape($item['titulo']) ?>"</h3>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

</div> <!-- end container -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
