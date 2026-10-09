<?php
$page_title = "Moderação de Comentários";
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/includes/header.php';

$pdo = Database::getInstance();

// Ações de Moderação
if (isset($_GET['action']) && isset($_GET['id'])) {
    if (!validateCsrfToken($_GET['token'] ?? '')) die("Acesso Negado (CSRF).");
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'approve') {
        $pdo->prepare("UPDATE comentarios SET status = 'aprovado' WHERE id = ?")->execute([$id]);
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE comentarios SET status = 'rejeitado' WHERE id = ?")->execute([$id]);
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM comentarios WHERE id = ?")->execute([$id]);
    }
    
    $filterRedirect = isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : '';
    header("Location: comentarios.php" . $filterRedirect);
    exit;
}

$statusFilter = $_GET['status'] ?? 'all';
$whereClause = "";
$params = [];

if (in_array($statusFilter, ['pendente', 'aprovado', 'rejeitado'])) {
    $whereClause = "WHERE c.status = ?";
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare("
    SELECT c.*, u.nome as usuario_nome, n.titulo as noticia_titulo, n.slug as noticia_slug
    FROM comentarios c 
    JOIN usuarios u ON c.usuario_id = u.id 
    JOIN noticias n ON c.noticia_id = n.id 
    $whereClause
    ORDER BY c.criado_em DESC
");
$stmt->execute($params);
$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contagem rápida para abas
$countPendente = (int)$pdo->query("SELECT COUNT(*) FROM comentarios WHERE status = 'pendente'")->fetchColumn();
$countTotal = (int)$pdo->query("SELECT COUNT(*) FROM comentarios")->fetchColumn();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 style="font-size: 1.4rem; font-weight: 800; margin: 0; color: var(--adm-text-main);">
            Moderação da Comunidade
        </h2>
        <p style="margin: 4px 0 0 0; color: var(--adm-text-muted); font-size: 0.875rem;">
            <?= $countTotal ?> comentários no total &bull; <?= $countPendente ?> aguardando aprovação
        </p>
    </div>
</div>

<!-- Filtros de Status -->
<div class="card" style="padding: 16px 20px; margin-bottom: 20px;">
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="comentarios.php" class="btn btn-sm btn-outline" style="<?= $statusFilter === 'all' ? 'background: var(--adm-primary); color: white; border-color: var(--adm-primary);' : '' ?>">
            Todos (<?= $countTotal ?>)
        </a>
        <a href="comentarios.php?status=pendente" class="btn btn-sm btn-outline" style="<?= $statusFilter === 'pendente' ? 'background: #b45309; color: white; border-color: #b45309;' : '' ?>">
            Pendentes (<?= $countPendente ?>)
        </a>
        <a href="comentarios.php?status=aprovado" class="btn btn-sm btn-outline" style="<?= $statusFilter === 'aprovado' ? 'background: #15803d; color: white; border-color: #15803d;' : '' ?>">
            Aprovados
        </a>
        <a href="comentarios.php?status=rejeitado" class="btn btn-sm btn-outline" style="<?= $statusFilter === 'rejeitado' ? 'background: #b91c1c; color: white; border-color: #b91c1c;' : '' ?>">
            Rejeitados
        </a>
    </div>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Leitor</th>
                    <th>Matéria</th>
                    <th style="width: 38%;">Comentário</th>
                    <th>Status</th>
                    <th style="text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($comentarios)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px 20px; color: var(--adm-text-muted);">
                        <i class="far fa-comments" style="font-size: 2rem; display: block; margin-bottom: 10px; color: var(--adm-text-light);"></i>
                        Nenhum comentário encontrado nesta categoria.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($comentarios as $com): ?>
                    <tr>
                        <td style="white-space: nowrap; font-size: 0.85rem; color: var(--adm-text-muted);">
                            <?= date('d/m/Y H:i', strtotime($com['criado_em'])) ?>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: var(--adm-text-main); font-size: 0.9rem;">
                                <?= escape($com['usuario_nome']) ?>
                            </div>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/noticia/<?= escape($com['noticia_slug']) ?>" target="_blank" style="font-size: 0.85rem; color: #2563eb; text-decoration: underline;" title="<?= escape($com['noticia_titulo']) ?>">
                                <?= escape(mb_strimwidth($com['noticia_titulo'], 0, 28, '...')) ?>
                            </a>
                        </td>
                        <td>
                            <div style="font-size: 0.9rem; color: var(--adm-text-body); line-height: 1.4; background: #f8fafc; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--adm-border-subtle);">
                                <?= escape($com['conteudo']) ?>
                            </div>
                        </td>
                        <td>
                            <?php
                            if ($com['status'] === 'aprovado') echo '<span class="badge badge-success"><i class="fas fa-circle" style="font-size:0.5rem"></i> Aprovado</span>';
                            elseif ($com['status'] === 'rejeitado') echo '<span class="badge badge-danger"><i class="fas fa-circle" style="font-size:0.5rem"></i> Rejeitado</span>';
                            else echo '<span class="badge badge-warning"><i class="fas fa-circle" style="font-size:0.5rem"></i> Pendente</span>';
                            ?>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <div style="display: inline-flex; gap: 6px;">
                                <?php if ($com['status'] !== 'aprovado'): ?>
                                    <a href="?action=approve&id=<?= $com['id'] ?>&token=<?= getCsrfToken() ?>&status=<?= urlencode($statusFilter) ?>" 
                                       class="btn btn-sm btn-outline btn-icon" 
                                       style="color: #15803d; border-color: #86efac;" 
                                       title="Aprovar comentário">
                                        <i class="fas fa-check"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($com['status'] !== 'rejeitado'): ?>
                                    <a href="?action=reject&id=<?= $com['id'] ?>&token=<?= getCsrfToken() ?>&status=<?= urlencode($statusFilter) ?>" 
                                       class="btn btn-sm btn-outline btn-icon" 
                                       style="color: #b45309; border-color: #fde68a;" 
                                       title="Rejeitar comentário">
                                        <i class="fas fa-ban"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="?action=delete&id=<?= $com['id'] ?>&token=<?= getCsrfToken() ?>&status=<?= urlencode($statusFilter) ?>" 
                                   class="btn btn-sm btn-outline btn-icon btn-danger" 
                                   onclick="return confirm('Deseja realmente excluir este comentário de forma permanente?')" 
                                   title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
