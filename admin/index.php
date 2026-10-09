<?php
require_once __DIR__ . '/../includes/news_queries.php';
$page_title = "Dashboard";
require_once __DIR__ . '/../includes/auth.php';
if (isJornalista()) { 
    header("Location: noticias.php"); 
    exit; 
}
require_once __DIR__ . '/includes/header.php';

$pdo = Database::getInstance();
$authorSql = newsAuthorSql($pdo);
$imageSql = newsImageSql($pdo);

// Estatísticas
$total_noticias = (int)$pdo->query("SELECT COUNT(*) FROM noticias")->fetchColumn();
$total_usuarios = (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$total_comentarios = (int)$pdo->query("SELECT COUNT(*) FROM comentarios WHERE status = 'pendente'")->fetchColumn();
$total_views = (int)$pdo->query("SELECT SUM(visualizacoes) FROM noticias")->fetchColumn();

// Últimas 6 notícias adicionadas
$ultimas = $pdo->query("
    SELECT n.id, n.titulo, n.slug, n.status, n.visualizacoes, n.criado_em,
           COALESCE(c.nome, 'Geral') as categoria,
           {$authorSql} as autor
    FROM noticias n 
    LEFT JOIN categorias c ON n.categoria_id = c.id 
    LEFT JOIN usuarios u ON n.autor_id = u.id 
    ORDER BY n.criado_em DESC 
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

// Data formatada em português
$diasSemana = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
$meses = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$hojeTexto = $diasSemana[(int)date('w')] . ', ' . date('j') . ' de ' . $meses[(int)date('n')] . ' de ' . date('Y');
?>

<!-- Saudação e Data -->
<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 4px; color: var(--adm-text-main);">
            Olá, <?= escape($userNome) ?>! 👋
        </h2>
        <p style="margin: 0; color: var(--adm-text-muted); font-size: 0.9rem;">
            <?= $hojeTexto ?> &bull; Panorama geral do portal <b>96 News</b>
        </p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="<?= BASE_URL ?>/admin/noticia_form.php" class="btn btn-accent">
            <i class="fas fa-plus"></i> Escrever Matéria
        </a>
        <a href="<?= BASE_URL ?>/" target="_blank" class="btn btn-outline">
            <i class="fas fa-external-link-alt"></i> Ver Portal
        </a>
    </div>
</div>

<!-- Métricas Principais -->
<div class="stat-grid">
    <div class="stat-card">
        <div>
            <div class="stat-label">Total de Matérias</div>
            <div class="stat-value"><?= number_format($total_noticias, 0, ',', '.') ?></div>
        </div>
        <div class="stat-icon primary">
            <i class="fas fa-newspaper"></i>
        </div>
    </div>

    <div class="stat-card">
        <div>
            <div class="stat-label">Visualizações Totais</div>
            <div class="stat-value"><?= number_format($total_views, 0, ',', '.') ?></div>
        </div>
        <div class="stat-icon blue">
            <i class="fas fa-chart-line"></i>
        </div>
    </div>

    <div class="stat-card">
        <div>
            <div class="stat-label">Comentários Pendentes</div>
            <div class="stat-value" style="<?= $total_comentarios > 0 ? 'color: var(--adm-accent);' : '' ?>">
                <?= $total_comentarios ?>
            </div>
        </div>
        <div class="stat-icon amber">
            <i class="fas fa-comments"></i>
        </div>
    </div>

    <div class="stat-card">
        <div>
            <div class="stat-label">Usuários Cadastrados</div>
            <div class="stat-value"><?= number_format($total_usuarios, 0, ',', '.') ?></div>
        </div>
        <div class="stat-icon emerald">
            <i class="fas fa-users"></i>
        </div>
    </div>
</div>

<!-- Tabela de Últimas Matérias -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-clock"></i> Últimas Matérias Publicadas
        </h3>
        <a href="noticias.php" class="btn btn-sm btn-outline">
            Ver Todas (<?= $total_noticias ?>)
        </a>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 45%;">Título</th>
                    <th>Categoria</th>
                    <th>Autor</th>
                    <th>Status</th>
                    <th>Visualizações</th>
                    <th>Data</th>
                    <th style="text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ultimas)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px 20px; color: var(--adm-text-muted);">
                        <i class="fas fa-inbox" style="font-size: 2rem; display: block; margin-bottom: 10px; color: var(--adm-text-light);"></i>
                        Nenhuma notícia cadastrada ainda.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($ultimas as $item): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: var(--adm-text-main); font-size: 0.95rem; margin-bottom: 2px;">
                                <a href="noticia_form.php?id=<?= $item['id'] ?>" style="color: inherit;">
                                    <?= escape($item['titulo']) ?>
                                </a>
                            </div>
                        </td>
                        <td>
                            <span style="font-weight: 500; font-size: 0.85rem; color: var(--adm-text-muted);">
                                <?= escape($item['categoria']) ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-size: 0.85rem; color: var(--adm-text-muted);">
                                <i class="far fa-user" style="margin-right: 4px; font-size: 0.8rem;"></i> <?= escape($item['autor']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($item['status'] === 'publicado'): ?>
                                <span class="badge badge-success">
                                    <i class="fas fa-circle" style="font-size: 0.5rem;"></i> Publicado
                                </span>
                            <?php else: ?>
                                <span class="badge badge-warning">
                                    <i class="fas fa-circle" style="font-size: 0.5rem;"></i> Rascunho
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: 600;">
                            <?= number_format($item['visualizacoes'] ?? 0, 0, ',', '.') ?>
                        </td>
                        <td style="font-size: 0.85rem; color: var(--adm-text-muted); white-space: nowrap;">
                            <?= date('d/m/Y', strtotime($item['criado_em'])) ?>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="<?= BASE_URL ?>/noticia/<?= escape($item['slug']) ?>" target="_blank" class="btn btn-sm btn-outline btn-icon" title="Ver no site">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="noticia_form.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline btn-icon" title="Editar matéria">
                                <i class="fas fa-pen"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
