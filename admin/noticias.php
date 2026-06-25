<?php
$page_title = "Gerenciar Notícias";
require_once __DIR__ . '/includes/header.php';

$pdo = Database::getInstance();

// Lógica de exclusão
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (!validateCsrfToken($_GET['token'] ?? '')) die("Acesso Negado (CSRF).");
    $id = $_GET['delete'];
    // Buscar a imagem para excluir do disco
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

// Listagem com paginação
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$total = $pdo->query("SELECT COUNT(*) FROM noticias")->fetchColumn();
$total_pages = ceil($total / $limit);

$stmt = $pdo->query("SELECT n.id, n.titulo, n.slug, n.status, n.visualizacoes, n.criado_em, c.nome as categoria 
                     FROM noticias n 
                     JOIN categorias c ON n.categoria_id = c.id 
                     ORDER BY n.criado_em DESC LIMIT $limit OFFSET $offset");
$noticias = $stmt->fetchAll();
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin:0;">Notícias Cadastradas</h3>
        <a href="noticia_form.php" class="btn"><i class="fas fa-plus"></i> Nova Notícia</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <div style="padding: 10px; background-color: #dcfce7; color: #166534; margin-bottom: 15px; border-radius: 4px;">Notícia excluída com sucesso!</div>
    <?php endif; ?>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Categoria</th>
                    <th>Status</th>
                    <th>Views</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($noticias as $item): ?>
                <tr>
                    <td><?= $item['id'] ?></td>
                    <td><?= escape($item['titulo']) ?></td>
                    <td><?= escape($item['categoria']) ?></td>
                    <td>
                        <span class="badge <?= $item['status'] == 'publicado' ? 'badge-success' : 'badge-warning' ?>">
                            <?= ucfirst($item['status']) ?>
                        </span>
                    </td>
                    <td><?= $item['visualizacoes'] ?></td>
                    <td><?= date('d/m/Y', strtotime($item['criado_em'])) ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/noticia/<?= escape($item['slug']) ?>" target="_blank" class="btn btn-sm btn-outline" title="Ver"><i class="fas fa-eye"></i></a>
                        <a href="noticia_form.php?id=<?= $item['id'] ?>" class="btn btn-sm" style="background-color: #3b82f6;" title="Editar"><i class="fas fa-edit"></i></a>
                        <a href="noticias.php?delete=<?= $item['id'] ?>&token=<?= getCsrfToken() ?>" class="btn btn-sm" style="background-color: #ef4444;" onclick="return confirm('Tem certeza que deseja excluir esta notícia?')" title="Excluir"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Paginação -->
    <?php if ($total_pages > 1): ?>
    <div style="margin-top: 20px; display: flex; gap: 5px;">
        <?php for($i=1; $i<=$total_pages; $i++): ?>
            <a href="?p=<?= $i ?>" class="btn btn-sm <?= $i == $page ? '' : 'btn-outline' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
