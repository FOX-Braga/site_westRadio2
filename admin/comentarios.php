<?php
$page_title = "Moderação de Comentários";
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/includes/header.php';

$pdo = Database::getInstance();

// Ações
if (isset($_GET['action']) && isset($_GET['id'])) {
    if (!validateCsrfToken($_GET['token'] ?? '')) die("Acesso Negado (CSRF).");
    $id = $_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'approve') {
        $pdo->prepare("UPDATE comentarios SET status = 'aprovado' WHERE id = ?")->execute([$id]);
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE comentarios SET status = 'rejeitado' WHERE id = ?")->execute([$id]);
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM comentarios WHERE id = ?")->execute([$id]);
    }
    
    header("Location: comentarios.php");
    exit;
}

$comentarios = $pdo->query("SELECT c.*, u.nome as usuario_nome, n.titulo as noticia_titulo 
                            FROM comentarios c 
                            JOIN usuarios u ON c.usuario_id = u.id 
                            JOIN noticias n ON c.noticia_id = n.id 
                            ORDER BY c.criado_em DESC")->fetchAll();
?>

<div class="card">
    <h3 style="margin-bottom: 20px;">Todos os Comentários</h3>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Usuário</th>
                    <th>Notícia</th>
                    <th>Comentário</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comentarios as $com): ?>
                <tr>
                    <td style="white-space: nowrap;"><?= date('d/m/Y H:i', strtotime($com['criado_em'])) ?></td>
                    <td><?= escape($com['usuario_nome']) ?></td>
                    <td><?= escape(mb_strimwidth($com['noticia_titulo'], 0, 30, '...')) ?></td>
                    <td><?= escape(mb_strimwidth($com['conteudo'], 0, 50, '...')) ?></td>
                    <td>
                        <?php
                        if ($com['status'] === 'aprovado') echo '<span class="badge badge-success">Aprovado</span>';
                        elseif ($com['status'] === 'rejeitado') echo '<span class="badge" style="background-color:#fee2e2; color:#991b1b;">Rejeitado</span>';
                        else echo '<span class="badge badge-warning">Pendente</span>';
                        ?>
                    </td>
                    <td style="white-space: nowrap;">
                        <?php if ($com['status'] !== 'aprovado'): ?>
                            <a href="?action=approve&id=<?= $com['id'] ?>&token=<?= getCsrfToken() ?>" class="btn btn-sm" style="background-color: #16a34a;" title="Aprovar"><i class="fas fa-check"></i></a>
                        <?php endif; ?>
                        <?php if ($com['status'] !== 'rejeitado'): ?>
                            <a href="?action=reject&id=<?= $com['id'] ?>&token=<?= getCsrfToken() ?>" class="btn btn-sm" style="background-color: #f59e0b;" title="Rejeitar"><i class="fas fa-ban"></i></a>
                        <?php endif; ?>
                        <a href="?action=delete&id=<?= $com['id'] ?>&token=<?= getCsrfToken() ?>" class="btn btn-sm" style="background-color: #ef4444;" onclick="return confirm('Excluir comentário permanentemente?')" title="Excluir"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
