<?php
$page_title = "Gerenciar Banners Publicitários";
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/includes/header.php';

$pdo = Database::getInstance();

$erro = '';
$sucesso = '';

// Adicionar / Editar Banner
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) die("Acesso Negado (CSRF).");
    $titulo = trim($_POST['titulo']);
    $link = trim($_POST['link']);
    $status = $_POST['status'] ?? 'ativo';
    $id = $_POST['id'] ?? null;
    
    if (empty($titulo) || empty($link)) {
        $erro = "Os campos Título e Link são obrigatórios.";
    } else {
        $imagem_nome = null;
        
        // Verifica se há upload de imagem
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadImage($_FILES['imagem'], 'banners');
            if ($upload['success']) {
                $imagem_nome = $upload['filename'];
            } else {
                $erro = $upload['message'];
            }
        }
        
        if (empty($erro)) {
            if ($id) {
                // Se enviou imagem nova, atualiza a imagem. Se não, mantem a antiga
                if ($imagem_nome) {
                    $stmt = $pdo->prepare("UPDATE banners SET titulo = ?, link = ?, status = ?, imagem = ? WHERE id = ?");
                    $stmt->execute([$titulo, $link, $status, $imagem_nome, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE banners SET titulo = ?, link = ?, status = ? WHERE id = ?");
                    $stmt->execute([$titulo, $link, $status, $id]);
                }
                $sucesso = "Banner atualizado com sucesso.";
            } else {
                if (!$imagem_nome) {
                    $erro = "A imagem é obrigatória para novos banners.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO banners (titulo, imagem, link, status) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$titulo, $imagem_nome, $link, $status]);
                    $sucesso = "Banner criado com sucesso.";
                }
            }
        }
    }
}

// Ativar / Desativar rápido
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    if (!validateCsrfToken($_GET['token'] ?? '')) die("Acesso Negado (CSRF).");
    $id = $_GET['toggle'];
    $stmt = $pdo->prepare("SELECT status FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    $curr = $stmt->fetchColumn();
    
    if ($curr) {
        $new_status = ($curr === 'ativo') ? 'inativo' : 'ativo';
        $pdo->prepare("UPDATE banners SET status = ? WHERE id = ?")->execute([$new_status, $id]);
        header("Location: banners.php?msg=status");
        exit;
    }
}

// Excluir Banner
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (!validateCsrfToken($_GET['token'] ?? '')) die("Acesso Negado (CSRF).");
    $id = $_GET['delete'];
    
    $stmt = $pdo->prepare("SELECT imagem FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetchColumn();
    
    if ($img && file_exists(__DIR__ . '/../uploads/banners/' . $img)) {
        unlink(__DIR__ . '/../uploads/banners/' . $img);
    }
    
    $pdo->prepare("DELETE FROM banners WHERE id = ?")->execute([$id]);
    header("Location: banners.php?msg=deleted");
    exit;
}

$banners = $pdo->query("SELECT * FROM banners ORDER BY criado_em DESC")->fetchAll();

$edit_banner = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_banner = $stmt->fetch();
}
?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
    
    <!-- Formulário -->
    <div class="card" style="height: fit-content;">
        <h3 style="margin-bottom: 20px;"><i class="fas fa-bullhorn"></i> <?= $edit_banner ? 'Editar Banner' : 'Novo Banner' ?></h3>
        
        <?php if ($erro): ?>
            <div style="padding: 10px; background-color: #fee2e2; color: #991b1b; margin-bottom: 15px; border-radius: 4px;"><?= escape($erro) ?></div>
        <?php endif; ?>
        <?php if ($sucesso || (isset($_GET['msg']) && $_GET['msg'] == 'deleted') || (isset($_GET['msg']) && $_GET['msg'] == 'status')): ?>
            <div style="padding: 10px; background-color: #dcfce7; color: #166534; margin-bottom: 15px; border-radius: 4px;">
                <?php 
                    if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') echo "Banner excluído.";
                    else if (isset($_GET['msg']) && $_GET['msg'] == 'status') echo "Status do banner atualizado.";
                    else echo $sucesso;
                ?>
            </div>
        <?php endif; ?>

        <form action="banners.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <?php if ($edit_banner): ?>
                <input type="hidden" name="id" value="<?= $edit_banner['id'] ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label>Título / Identificação</label>
                <input type="text" name="titulo" class="form-control" value="<?= $edit_banner ? escape($edit_banner['titulo']) : '' ?>" placeholder="Ex: Promoção Verão" required>
            </div>
            
            <div class="form-group">
                <label>Link de Destino</label>
                <input type="url" name="link" class="form-control" value="<?= $edit_banner ? escape($edit_banner['link']) : '' ?>" placeholder="https://..." required>
            </div>
            
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="ativo" <?= ($edit_banner && $edit_banner['status'] == 'ativo') ? 'selected' : '' ?>>Ativo</option>
                    <option value="inativo" <?= ($edit_banner && $edit_banner['status'] == 'inativo') ? 'selected' : '' ?>>Inativo</option>
                </select>
            </div>

            <div class="form-group">
                <label>Imagem do Banner</label>
                <?php if ($edit_banner && $edit_banner['imagem']): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="<?= BASE_URL ?>/uploads/banners/<?= escape($edit_banner['imagem']) ?>" style="max-width: 100%; height: auto; border-radius: 4px;">
                    </div>
                <?php endif; ?>
                <input type="file" name="imagem" class="form-control" accept="image/*" <?= !$edit_banner ? 'required' : '' ?>>
                <small style="color: var(--color-text-muted);">Tamanho recomendado: 970x250px ou 1050x250px (Formato Billboard Horizontal). JPG, PNG ou WEBP.</small>
            </div>
            
            <button type="submit" class="btn" style="width: 100%;"><?= $edit_banner ? 'Atualizar Banner' : 'Salvar Banner' ?></button>
            <?php if ($edit_banner): ?>
                <a href="banners.php" class="btn btn-outline" style="display: block; text-align: center; margin-top: 10px; width: 100%;">Cancelar Edição</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabela -->
    <div class="card">
        <h3 style="margin-bottom: 20px;">Banners Cadastrados</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">Imagem</th>
                        <th>Detalhes</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($banners) > 0): ?>
                        <?php foreach ($banners as $banner): ?>
                        <tr>
                            <td>
                                <img src="<?= BASE_URL ?>/uploads/banners/<?= escape($banner['imagem']) ?>" style="width: 80px; height: auto; border-radius: 4px;">
                            </td>
                            <td>
                                <strong><?= escape($banner['titulo']) ?></strong><br>
                                <a href="<?= escape($banner['link']) ?>" target="_blank" style="font-size: 0.85rem; color: var(--color-primary);"><?= escape($banner['link']) ?></a>
                            </td>
                            <td>
                                <?php if ($banner['status'] == 'ativo'): ?>
                                    <span style="background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">Ativo</span>
                                <?php else: ?>
                                    <span style="background: #f1f5f9; color: #475569; padding: 3px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="banners.php?toggle=<?= $banner['id'] ?>&token=<?= getCsrfToken() ?>" class="btn btn-sm" style="background-color: <?= $banner['status'] == 'ativo' ? '#64748b' : '#10b981' ?>;" title="<?= $banner['status'] == 'ativo' ? 'Desativar' : 'Ativar' ?>">
                                    <i class="fas <?= $banner['status'] == 'ativo' ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                </a>
                                <a href="banners.php?edit=<?= $banner['id'] ?>" class="btn btn-sm" style="background-color: #3b82f6;"><i class="fas fa-edit"></i></a>
                                <a href="banners.php?delete=<?= $banner['id'] ?>&token=<?= getCsrfToken() ?>" class="btn btn-sm" style="background-color: #ef4444;" onclick="return confirm('Tem certeza que deseja excluir este banner?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px; color: var(--color-text-muted);">Nenhum banner cadastrado ainda.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
