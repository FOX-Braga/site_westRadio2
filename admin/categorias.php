<?php
$page_title = "Gerenciar Categorias";
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/includes/header.php';

$pdo = Database::getInstance();

$erro = '';
$sucesso = '';

// Adicionar / Editar Categoria
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) die("Acesso Negado (CSRF).");
    $nome = trim($_POST['nome']);
    $cor = trim($_POST['cor']) ?: '#1b5e20';
    $id = $_POST['id'] ?? null;
    $slug = createSlug($nome);

    if (empty($nome)) {
        $erro = "O nome da categoria é obrigatório.";
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE categorias SET nome = ?, slug = ?, cor = ? WHERE id = ?");
            if ($stmt->execute([$nome, $slug, $cor, $id])) {
                $sucesso = "Categoria atualizada com sucesso.";
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO categorias (nome, slug, cor) VALUES (?, ?, ?)");
            if ($stmt->execute([$nome, $slug, $cor])) {
                $sucesso = "Categoria cadastrada com sucesso.";
            }
        }
    }
}

// Excluir Categoria
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (!validateCsrfToken($_GET['token'] ?? '')) die("Acesso Negado (CSRF).");
    $id = (int)$_GET['delete'];
    
    // Verificação de segurança: Não excluir se houver notícias vinculadas
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM noticias WHERE categoria_id = ?");
    $stmtCheck->execute([$id]);
    $total_noticias_vinculadas = (int)$stmtCheck->fetchColumn();
    
    if ($total_noticias_vinculadas > 0) {
        $erro = "Não é possível excluir esta categoria pois existem $total_noticias_vinculadas matéria(s) associada(s) a ela.";
    } else {
        $pdo->prepare("DELETE FROM categorias WHERE id = ?")->execute([$id]);
        header("Location: categorias.php?msg=deleted");
        exit;
    }
}

$categorias = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM noticias n WHERE n.categoria_id = c.id) as total_noticias FROM categorias c ORDER BY c.nome ASC")->fetchAll(PDO::FETCH_ASSOC);

$edit_cat = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_cat = $stmt->fetch();
}
?>

<div style="margin-bottom: 24px;">
    <h2 style="font-size: 1.4rem; font-weight: 800; margin: 0; color: var(--adm-text-main);">
        Categorias Editoriais
    </h2>
    <p style="margin: 4px 0 0 0; color: var(--adm-text-muted); font-size: 0.875rem;">
        Estruture as editorias e seções de notícias exibidas no portal
    </p>
</div>

<?php if ($erro): ?>
    <div style="padding: 14px 18px; background-color: var(--adm-danger-bg); color: var(--adm-danger-text); margin-bottom: 20px; border-radius: var(--adm-radius-sm); font-weight: 600;">
        <i class="fas fa-exclamation-circle"></i> <?= escape($erro) ?>
    </div>
<?php endif; ?>

<?php if ($sucesso || (isset($_GET['msg']) && $_GET['msg'] == 'deleted')): ?>
    <div style="padding: 14px 18px; background-color: var(--adm-success-bg); color: var(--adm-success-text); margin-bottom: 20px; border-radius: var(--adm-radius-sm); font-weight: 600;">
        <i class="fas fa-check-circle"></i> <?= $sucesso ?: 'Categoria excluída com sucesso.' ?>
    </div>
<?php endif; ?>

<div class="editorial-grid">
    
    <!-- Formulário -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-folder-plus"></i> <?= $edit_cat ? 'Editar Categoria' : 'Criar Nova Categoria' ?>
            </h3>
        </div>

        <form action="categorias.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <?php if ($edit_cat): ?>
                <input type="hidden" name="id" value="<?= $edit_cat['id'] ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label>Nome da Editoria</label>
                <input type="text" name="nome" class="form-control" placeholder="Ex: Tecnologia, Polícia, Agro..." value="<?= $edit_cat ? escape($edit_cat['nome']) : '' ?>" required>
            </div>
            
            <div class="form-group">
                <label>Cor de Identificação</label>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="color" name="cor" id="corInput" class="form-control" value="<?= $edit_cat ? escape($edit_cat['cor']) : '#1b5e20' ?>" style="width: 50px; height: 42px; padding: 4px; cursor: pointer;">
                    <span style="font-size: 0.85rem; color: var(--adm-text-muted);">Cor usada nas tags e no menu do site</span>
                </div>
            </div>
            
            <button type="submit" class="btn btn-accent" style="width: 100%; margin-top: 10px;">
                <i class="fas fa-check"></i> <?= $edit_cat ? 'Salvar Alterações' : 'Cadastrar Categoria' ?>
            </button>
            <?php if ($edit_cat): ?>
                <a href="categorias.php" class="btn btn-outline" style="width: 100%; margin-top: 8px;">Cancelar Edição</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabela de Categorias -->
    <div class="card" style="padding: 0; overflow: hidden; margin-bottom: 0;">
        <div style="padding: 20px 24px; border-bottom: 1px solid var(--adm-border-subtle);">
            <h3 class="card-title">
                <i class="fas fa-list"></i> Categorias Cadastradas (<?= count($categorias) ?>)
            </h3>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Slug</th>
                        <th>Matérias</th>
                        <th style="text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categorias as $cat): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 14px; height: 14px; border-radius: 4px; background-color: <?= escape($cat['cor']) ?>; flex-shrink: 0;"></div>
                                <span style="font-weight: 600; color: var(--adm-text-main);"><?= escape($cat['nome']) ?></span>
                            </div>
                        </td>
                        <td>
                            <span style="font-size: 0.85rem; color: var(--adm-text-muted);">/categoria/<?= escape($cat['slug']) ?></span>
                        </td>
                        <td>
                            <span class="badge" style="background: #f1f5f9; color: var(--adm-text-body); font-weight: 600;">
                                <?= $cat['total_noticias'] ?> matérias
                            </span>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="categorias.php?edit=<?= $cat['id'] ?>" class="btn btn-sm btn-outline btn-icon" title="Editar">
                                <i class="fas fa-pen"></i>
                            </a>
                            <a href="categorias.php?delete=<?= $cat['id'] ?>&token=<?= getCsrfToken() ?>" 
                               class="btn btn-sm btn-outline btn-icon btn-danger" 
                               onclick="return confirm('Deseja realmente excluir esta categoria?')" 
                               title="Excluir">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
