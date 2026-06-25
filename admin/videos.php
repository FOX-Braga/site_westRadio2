<?php
$page_title = "Gerenciar Vídeos e Lives";
require_once __DIR__ . '/includes/header.php';

$pdo = Database::getInstance();

// Lógica de exclusão
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (!validateCsrfToken($_GET['token'] ?? '')) die("Acesso Negado (CSRF).");
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM videos WHERE id = ?")->execute([$id]);
    header("Location: videos.php?msg=deleted");
    exit;
}

// Listagem de vídeos
$stmt = $pdo->query("SELECT * FROM videos ORDER BY criado_em DESC");
$videos = $stmt->fetchAll();
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin:0;">Vídeos e Lives Cadastrados</h3>
        <a href="video_form.php" class="btn"><i class="fas fa-plus"></i> Novo Vídeo/Live</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <div style="padding: 10px; background-color: #dcfce7; color: #166534; margin-bottom: 15px; border-radius: 4px;">Vídeo excluído com sucesso!</div>
    <?php endif; ?>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>YouTube ID</th>
                    <th>Tipo</th>
                    <th>Duração</th>
                    <th>Data Cadastro</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($videos as $v): ?>
                <tr>
                    <td><?= $v['id'] ?></td>
                    <td><?= escape($v['titulo']) ?></td>
                    <td><code><?= escape($v['youtube_id']) ?></code></td>
                    <td>
                        <span class="badge <?= $v['tipo'] == 'live' ? 'badge-warning' : 'badge-success' ?>">
                            <?= strtoupper($v['tipo']) ?>
                        </span>
                    </td>
                    <td><?= escape($v['duracao'] ?: '-') ?></td>
                    <td><?= date('d/m/Y', strtotime($v['criado_em'])) ?></td>
                    <td>
                        <a href="https://www.youtube.com/watch?v=<?= escape($v['youtube_id']) ?>" target="_blank" class="btn btn-sm btn-outline" title="Ver no YouTube"><i class="fas fa-external-link-alt"></i></a>
                        <a href="video_form.php?id=<?= $v['id'] ?>" class="btn btn-sm" style="background-color: #3b82f6;" title="Editar"><i class="fas fa-edit"></i></a>
                        <a href="videos.php?delete=<?= $v['id'] ?>&token=<?= getCsrfToken() ?>" class="btn btn-sm" style="background-color: #ef4444;" onclick="return confirm('Tem certeza que deseja remover este vídeo?')" title="Excluir"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (count($videos) === 0): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--admin-text-light);">Nenhum vídeo cadastrado.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
