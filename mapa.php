<?php
$page_title = "Mapa do Site - West News";
require_once __DIR__ . '/includes/header.php';

$pdo = Database::getInstance();
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll();
?>

<div style="background-color: var(--color-surface); padding: 50px 0; border-bottom: 1px solid var(--color-border); margin-bottom: 40px;">
    <div class="container" style="max-width: 1000px;">
        <h1 style="font-family: var(--font-title); font-size: 2.5rem; color: var(--color-primary); margin-bottom: 10px;">Mapa do Site</h1>
        <p style="color: var(--color-text-muted); font-size: 1.1rem;">Navegue por toda a estrutura do portal West News.</p>
    </div>
</div>

<div class="container" style="max-width: 1000px; margin-bottom: 80px; min-height: 50vh;">
    
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px;">
        
        <!-- Institucional -->
        <div>
            <h3 style="font-size: 1.5rem; color: var(--color-primary); margin-bottom: 20px; border-bottom: 2px solid var(--color-border); padding-bottom: 10px;">Institucional</h3>
            <ul style="list-style: none; padding: 0; font-size: 1.1rem; line-height: 2;">
                <li><a href="<?= BASE_URL ?>/" style="color: var(--color-text); text-decoration: none;"><i class="fas fa-angle-right" style="color: var(--color-accent); margin-right: 8px;"></i> Página Inicial (Home)</a></li>
                <li><a href="<?= BASE_URL ?>/sobre.php" style="color: var(--color-text); text-decoration: none;"><i class="fas fa-angle-right" style="color: var(--color-accent); margin-right: 8px;"></i> Quem Somos</a></li>
                <li><a href="<?= BASE_URL ?>/contato.php" style="color: var(--color-text); text-decoration: none;"><i class="fas fa-angle-right" style="color: var(--color-accent); margin-right: 8px;"></i> Fale Conosco / Anuncie</a></li>
                <li><a href="<?= BASE_URL ?>/cadastro.php" style="color: var(--color-text); text-decoration: none;"><i class="fas fa-angle-right" style="color: var(--color-accent); margin-right: 8px;"></i> Assine o Portal</a></li>
                <li><a href="<?= BASE_URL ?>/login.php" style="color: var(--color-text); text-decoration: none;"><i class="fas fa-angle-right" style="color: var(--color-accent); margin-right: 8px;"></i> Login / Painel</a></li>
            </ul>
        </div>

        <!-- Editorias -->
        <div>
            <h3 style="font-size: 1.5rem; color: var(--color-primary); margin-bottom: 20px; border-bottom: 2px solid var(--color-border); padding-bottom: 10px;">Editorias</h3>
            <ul style="list-style: none; padding: 0; font-size: 1.1rem; line-height: 2;">
                <?php foreach ($categorias as $cat): ?>
                    <li><a href="<?= BASE_URL ?>/categoria/<?= escape($cat['slug']) ?>" style="color: var(--color-text); text-decoration: none;"><i class="fas fa-angle-right" style="color: var(--color-accent); margin-right: 8px;"></i> <?= escape($cat['nome']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Legal -->
        <div>
            <h3 style="font-size: 1.5rem; color: var(--color-primary); margin-bottom: 20px; border-bottom: 2px solid var(--color-border); padding-bottom: 10px;">Legal e Serviços</h3>
            <ul style="list-style: none; padding: 0; font-size: 1.1rem; line-height: 2;">
                <li><a href="<?= BASE_URL ?>/termos.php" style="color: var(--color-text); text-decoration: none;"><i class="fas fa-angle-right" style="color: var(--color-accent); margin-right: 8px;"></i> Termos de Uso</a></li>
                <li><a href="<?= BASE_URL ?>/privacidade.php" style="color: var(--color-text); text-decoration: none;"><i class="fas fa-angle-right" style="color: var(--color-accent); margin-right: 8px;"></i> Política de Privacidade</a></li>
                <li><a href="<?= BASE_URL ?>/cookies.php" style="color: var(--color-text); text-decoration: none;"><i class="fas fa-angle-right" style="color: var(--color-accent); margin-right: 8px;"></i> Política de Cookies</a></li>
                <li><a href="<?= BASE_URL ?>/rss.php" target="_blank" style="color: var(--color-text); text-decoration: none;"><i class="fas fa-rss" style="color: #f26522; margin-right: 8px;"></i> Feed RSS</a></li>
            </ul>
        </div>
        
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
