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
    $cor = trim($_POST['cor']);
    $id = $_POST['id'] ?? null;
    $slug = createSlug($nome);

    if (empty($nome)) {
        $erro = "O nome da categoria é obrigatório.";
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE categorias SET nome = ?, slug = ?, cor = ? WHERE id = ?");
            if ($stmt->execute([$nome, $slug, $cor, $id])) {
                $sucesso = "Categoria atualizada.";
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO categorias (nome, slug, cor) VALUES (?, ?, ?)");
            if ($stmt->execute([$nome, $slug, $cor])) {
                $sucesso = "Categoria criada.";
            }
        }
    }
}

// Excluir Categoria
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (!validateCsrfToken($_GET['token'] ?? '')) die("Acesso Negado (CSRF).");
    $id = $_GET['delete'];
    
    // Verificação de segurança: Não excluir se houver notícias
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM noticias WHERE categoria_id = ?");
    $stmtCheck->execute([$id]);
    $total_noticias_vinculadas = $stmtCheck->fetchColumn();
    
    if ($total_noticias_vinculadas > 0) {
        $erro = "Não é possível excluir esta categoria. Existem " . $total_noticias_vinculadas . " notícia(s) vinculada(s) a ela.";
    } else {
        $pdo->prepare("DELETE FROM categorias WHERE id = ?")->execute([$id]);
        header("Location: categorias.php?msg=deleted");
        exit;
    }
}

$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll();

$edit_cat = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_cat = $stmt->fetch();
}
?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
    
    <!-- Formulário -->
    <div class="card" style="height: fit-content;">
        <h3 style="margin-bottom: 20px;"><?= $edit_cat ? 'Editar Categoria' : 'Nova Categoria' ?></h3>
        
        <?php if ($erro): ?>
            <div style="padding: 10px; background-color: #fee2e2; color: #991b1b; margin-bottom: 15px; border-radius: 4px;"><?= escape($erro) ?></div>
        <?php endif; ?>
        <?php if ($sucesso || (isset($_GET['msg']) && $_GET['msg'] == 'deleted')): ?>
            <div style="padding: 10px; background-color: #dcfce7; color: #166534; margin-bottom: 15px; border-radius: 4px;"><?= $sucesso ?: 'Categoria excluída.' ?></div>
        <?php endif; ?>

        <form action="categorias.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <?php if ($edit_cat): ?>
                <input type="hidden" name="id" value="<?= $edit_cat['id'] ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label>Nome</label>
                <input type="text" name="nome" class="form-control" value="<?= $edit_cat ? escape($edit_cat['nome']) : '' ?>" required>
            </div>
            
            <div class="form-group">
                <label>Cor Hexadecimal (Ex: #1b5e20)</label>
                <input type="color" name="cor" class="form-control" value="<?= $edit_cat ? escape($edit_cat['cor']) : '#1b5e20' ?>" style="height: 50px; padding: 5px;">
            </div>
            
            <button type="submit" class="btn" style="width: 100%;"><?= $edit_cat ? 'Atualizar' : 'Salvar' ?></button>
            <?php if ($edit_cat): ?>
                <a href="categorias.php" class="btn btn-outline" style="display: block; text-align: center; margin-top: 10px; width: 100%;">Cancelar Edição</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabela -->
    <div class="card">
        <h3 style="margin-bottom: 20px;">Categorias Existentes</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Slug</th>
                        <th>Cor</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categorias as $cat): ?>
                    <tr>
                        <td><?= escape($cat['nome']) ?></td>
                        <td><?= escape($cat['slug']) ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 20px; height: 20px; border-radius: 4px; background-color: <?= escape($cat['cor']) ?>;"></div>
                                <?= escape($cat['cor']) ?>
                            </div>
                        </td>
                        <td>
                            <a href="categorias.php?edit=<?= $cat['id'] ?>" class="btn btn-sm" style="background-color: #3b82f6;"><i class="fas fa-edit"></i></a>
                            <a href="categorias.php?delete=<?= $cat['id'] ?>&token=<?= getCsrfToken() ?>" class="btn btn-sm" style="background-color: #ef4444;" onclick="return confirm('Excluir esta categoria apagará todas as notícias atreladas a ela. Continuar?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
