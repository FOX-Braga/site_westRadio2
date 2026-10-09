<?php
$page_title = "Gerenciar Notícias";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requirePanelAccess();
$page_title = isJornalista() ? 'Minhas matérias' : 'Gerenciar Notícias';

$pdo = Database::getInstance();

// Lógica de exclusão de notícia (Apenas no painel de notícias!)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    requireAdmin();
    if (!validateCsrfToken($_GET['token'] ?? '')) die("Acesso Negado (CSRF).");
    $id = (int)$_GET['delete'];
    
    // Buscar a imagem para excluir do disco se for arquivo físico
    $stmtImg = $pdo->prepare("SELECT imagem_destacada FROM noticias WHERE id = ?");
    $stmtImg->execute([$id]);
    $img = $stmtImg->fetchColumn();
    
    if ($img && !str_starts_with($img, 'data:') && file_exists(__DIR__ . '/../uploads/noticias/' . $img)) {
        unlink(__DIR__ . '/../uploads/noticias/' . $img);
    }
    
    $pdo->prepare("DELETE FROM noticias WHERE id = ?")->execute([$id]);
    header("Location: noticias.php?msg=deleted");
    exit;
}

// Filtros e Busca
$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';

$whereClauses = [];
$params = [];
if (isJornalista()) {
    $whereClauses[] = "n.autor_id = ?";
    $params[] = (int) $_SESSION['user_id'];
}

if ($search !== '') {
    $whereClauses[] = "(n.titulo LIKE ? OR n.slug LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($statusFilter === 'publicado') {
    $whereClauses[] = "n.status = 'publicado'";
} elseif ($statusFilter === 'rascunho') {
    $whereClauses[] = "n.status = 'rascunho'";
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(' AND ', $whereClauses) : "";

// Paginação
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM noticias n $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$total_pages = ceil($total / $limit);

// Busca Notícias
$query = "SELECT n.id, n.titulo, n.slug, n.status, n.visualizacoes, n.criado_em, n.destaque, n.urgente,
                 COALESCE(c.nome, 'Sem Categoria') as categoria, 
                 COALESCE(u.nome, n.autor_nome, 'Redação') as autor_nome 
          FROM noticias n 
          LEFT JOIN categorias c ON n.categoria_id = c.id 
          LEFT JOIN usuarios u ON n.autor_id = u.id 
          $whereSql
          ORDER BY n.criado_em DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header da Página -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 style="font-size: 1.4rem; font-weight: 800; margin: 0; color: var(--adm-text-main);">
            <?= isAdmin() ? 'Acervo de Matérias' : 'Minhas matérias' ?>
        </h2>
        <p style="margin: 4px 0 0 0; color: var(--adm-text-muted); font-size: 0.875rem;">
            <?= $total ?> <?= $total === 1 ? 'matéria encontrada' : 'matérias encontradas' ?>
        </p>
    </div>
    <a href="noticia_form.php" class="btn btn-accent">
        <i class="fas fa-plus"></i> Escrever Nova Matéria
    </a>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div style="padding: 14px 18px; background-color: var(--adm-success-bg); color: var(--adm-success-text); margin-bottom: 20px; border-radius: var(--adm-radius-sm); font-weight: 600; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-check-circle"></i> A matéria foi excluída com sucesso do portal.
    </div>
<?php endif; ?>

<!-- Barra de Filtros e Busca -->
<div class="card" style="padding: 16px 20px; margin-bottom: 20px;">
    <form method="GET" action="noticias.php" class="filter-bar" style="margin: 0;">
        <!-- Abas de Status -->
        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
            <a href="noticias.php?status=all<?= $search ? '&q=' . urlencode($search) : '' ?>" 
               class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-outline' : 'btn-outline' ?>" 
               style="<?= $statusFilter === 'all' ? 'background: var(--adm-primary); color: white; border-color: var(--adm-primary);' : '' ?>">
               Todas
            </a>
            <a href="noticias.php?status=publicado<?= $search ? '&q=' . urlencode($search) : '' ?>" 
               class="btn btn-sm btn-outline" 
               style="<?= $statusFilter === 'publicado' ? 'background: #15803d; color: white; border-color: #15803d;' : '' ?>">
               Publicadas
            </a>
            <a href="noticias.php?status=rascunho<?= $search ? '&q=' . urlencode($search) : '' ?>" 
               class="btn btn-sm btn-outline" 
               style="<?= $statusFilter === 'rascunho' ? 'background: #b45309; color: white; border-color: #b45309;' : '' ?>">
               Rascunhos
            </a>
        </div>

        <!-- Input de Pesquisa -->
        <div class="search-input-wrap">
            <i class="fas fa-search"></i>
            <input type="text" name="q" class="form-control" placeholder="Buscar por título ou slug..." value="<?= escape($search) ?>">
            <?php if ($statusFilter !== 'all'): ?>
                <input type="hidden" name="status" value="<?= escape($statusFilter) ?>">
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-sm btn-outline">
            Filtrar
        </button>
        <?php if ($search || $statusFilter !== 'all'): ?>
            <a href="noticias.php" class="btn btn-sm btn-outline" title="Limpar busca">
                <i class="fas fa-times"></i>
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabela de Matérias -->
<div class="card" style="padding: 0; overflow: hidden;">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 42%;">Matéria</th>
                    <th>Categoria</th>
                    <th>Autor</th>
                    <th>Status</th>
                    <th>Visualizações</th>
                    <th>Data</th>
                    <th style="text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($noticias)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 50px 20px; color: var(--adm-text-muted);">
                        <i class="fas fa-search" style="font-size: 2.2rem; display: block; margin-bottom: 12px; color: var(--adm-text-light);"></i>
                        Nenhuma matéria encontrada com os filtros selecionados.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($noticias as $item): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600; font-size: 0.95rem; margin-bottom: 4px;">
                                <a href="noticia_form.php?id=<?= $item['id'] ?>" style="color: var(--adm-text-main);">
                                    <?= escape($item['titulo']) ?>
                                </a>
                            </div>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
                                <?php if ($item['destaque']): ?>
                                    <span style="font-size: 0.7rem; font-weight: 700; color: #b45309; background: #fef3c7; padding: 2px 6px; border-radius: 4px;">
                                        <i class="fas fa-star" style="font-size: 0.65rem;"></i> MANCHETE
                                    </span>
                                <?php endif; ?>
                                <?php if ($item['urgente']): ?>
                                    <span style="font-size: 0.7rem; font-weight: 700; color: #be123c; background: #ffe4e6; padding: 2px 6px; border-radius: 4px;">
                                        <i class="fas fa-bolt" style="font-size: 0.65rem;"></i> URGENTE
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span style="font-size: 0.85rem; font-weight: 500; color: var(--adm-text-muted);">
                                <?= escape($item['categoria']) ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-size: 0.85rem; color: var(--adm-text-muted); display: inline-flex; align-items: center; gap: 5px;">
                                <i class="far fa-user" style="font-size: 0.75rem;"></i> <?= escape($item['autor_nome']) ?>
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
                        <td style="font-weight: 600; color: var(--adm-text-main);">
                            <?= number_format($item['visualizacoes'] ?? 0, 0, ',', '.') ?>
                        </td>
                        <td style="font-size: 0.85rem; color: var(--adm-text-muted); white-space: nowrap;">
                            <?= date('d/m/Y', strtotime($item['criado_em'])) ?>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="<?= BASE_URL ?>/noticia/<?= escape($item['slug']) ?>" target="_blank" class="btn btn-sm btn-outline btn-icon" title="Ver no Portal">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="noticia_form.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline btn-icon" title="Editar">
                                <i class="fas fa-pen"></i>
                            </a>
                            <?php if (isAdmin()): ?><a href="noticias.php?delete=<?= $item['id'] ?>&token=<?= getCsrfToken() ?>" class="btn btn-sm btn-outline btn-icon btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta matéria? Ela será removida permanentemente do portal.')" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </a><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    <?php if ($total_pages > 1): ?>
    <div style="padding: 16px 20px; border-top: 1px solid var(--adm-border-subtle); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <span style="font-size: 0.85rem; color: var(--adm-text-muted);">
            Página <?= $page ?> de <?= $total_pages ?>
        </span>
        <div style="display: flex; gap: 6px;">
            <?php if ($page > 1): ?>
                <a href="?p=<?= $page - 1 ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>" class="btn btn-sm btn-outline">
                    <i class="fas fa-chevron-left"></i> Anterior
                </a>
            <?php endif; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?p=<?= $page + 1 ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>" class="btn btn-sm btn-outline">
                    Próxima <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
