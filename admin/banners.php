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

<div class="editorial-grid">
    
    <!-- Formulário -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-bullhorn"></i> <?= $edit_banner ? 'Editar Banner' : 'Cadastrar Novo Banner' ?>
            </h3>
        </div>
        
        <?php if ($erro): ?>
            <div style="padding: 12px 16px; background-color: var(--adm-danger-bg); color: var(--adm-danger-text); margin-bottom: 16px; border-radius: var(--adm-radius-sm); font-weight: 600; font-size: 0.875rem;">
                <i class="fas fa-exclamation-circle"></i> <?= escape($erro) ?>
            </div>
        <?php endif; ?>
        <?php if ($sucesso || (isset($_GET['msg']) && $_GET['msg'] == 'deleted') || (isset($_GET['msg']) && $_GET['msg'] == 'status')): ?>
            <div style="padding: 12px 16px; background-color: var(--adm-success-bg); color: var(--adm-success-text); margin-bottom: 16px; border-radius: var(--adm-radius-sm); font-weight: 600; font-size: 0.875rem;">
                <i class="fas fa-check-circle"></i> 
                <?php 
                    if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') echo "Banner excluído com sucesso.";
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
                <input type="text" name="titulo" class="form-control" value="<?= $edit_banner ? escape($edit_banner['titulo']) : '' ?>" placeholder="Ex: Patrocinador Principal" required>
            </div>
            
            <div class="form-group">
                <label>Link de Destino</label>
                <input type="url" name="link" class="form-control" value="<?= $edit_banner ? escape($edit_banner['link']) : '' ?>" placeholder="https://exemplo.com.br" required>
            </div>
            
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control" style="font-weight: 600;">
                    <option value="ativo" <?= ($edit_banner && $edit_banner['status'] == 'ativo') ? 'selected' : '' ?>>🟢 Ativo (Visível no portal)</option>
                    <option value="inativo" <?= ($edit_banner && $edit_banner['status'] == 'inativo') ? 'selected' : '' ?>>⚪ Inativo (Oculto)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Imagem do Banner</label>
                <div class="image-preview-wrapper">
                    <?php if ($edit_banner && $edit_banner['imagem']): ?>
                        <img id="image-preview-target" src="<?= BASE_URL ?>/uploads/banners/<?= escape($edit_banner['imagem']) ?>" style="width: 100%; max-height: 180px; object-fit: contain; background: #0f172a; border-radius: var(--adm-radius-sm); border: 1px solid var(--adm-border); margin-bottom: 12px; display: block;">
                    <?php endif; ?>
                </div>
                <input type="file" name="imagem" class="form-control" accept="image/*" <?= !$edit_banner ? 'required' : '' ?> style="padding: 10px;">
                <small style="display: block; margin-top: 6px; color: var(--adm-text-muted); font-size: 0.75rem;">Formato horizontal recomendado: 970x250px ou 1050x250px (JPG, PNG ou WEBP).</small>
            </div>
            
            <button type="submit" class="btn btn-accent" style="width: 100%; margin-top: 10px;">
                <i class="fas fa-check"></i> <?= $edit_banner ? 'Atualizar Banner' : 'Cadastrar Banner' ?>
            </button>
            <?php if ($edit_banner): ?>
                <a href="banners.php" class="btn btn-outline" style="width: 100%; margin-top: 8px;">Cancelar Edição</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabela -->
    <div class="card" style="padding: 0; overflow: hidden; margin-bottom: 0;">
        <div style="padding: 20px 24px; border-bottom: 1px solid var(--adm-border-subtle);">
            <h3 class="card-title">
                <i class="fas fa-images"></i> Banners Ativos e Histórico (<?= count($banners) ?>)
            </h3>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 100px;">Prévia</th>
                        <th>Campanha</th>
                        <th>Status</th>
                        <th style="text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($banners) > 0): ?>
                        <?php foreach ($banners as $banner): ?>
                        <tr>
                            <td>
                                <img src="<?= BASE_URL ?>/uploads/banners/<?= escape($banner['imagem']) ?>" style="width: 90px; height: 35px; object-fit: cover; border-radius: 4px; border: 1px solid var(--adm-border); display: block;">
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--adm-text-main); font-size: 0.9rem;"><?= escape($banner['titulo']) ?></div>
                                <a href="<?= escape($banner['link']) ?>" target="_blank" style="font-size: 0.8rem; color: #2563eb; text-decoration: underline;"><?= escape(mb_strimwidth($banner['link'], 0, 35, '...')) ?></a>
                            </td>
                            <td>
                                <?php if ($banner['status'] == 'ativo'): ?>
                                    <span class="badge badge-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #f1f5f9; color: var(--adm-text-muted);">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="banners.php?toggle=<?= $banner['id'] ?>&token=<?= getCsrfToken() ?>" class="btn btn-sm btn-outline btn-icon" title="<?= $banner['status'] == 'ativo' ? 'Ocultar no site' : 'Ativar no site' ?>">
                                    <i class="fas <?= $banner['status'] == 'ativo' ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                </a>
                                <a href="banners.php?edit=<?= $banner['id'] ?>" class="btn btn-sm btn-outline btn-icon" title="Editar">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <a href="banners.php?delete=<?= $banner['id'] ?>&token=<?= getCsrfToken() ?>" class="btn btn-sm btn-outline btn-icon btn-danger" onclick="return confirm('Deseja realmente remover este banner?')" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 40px 20px; color: var(--adm-text-muted);">
                                <i class="fas fa-bullhorn" style="font-size: 2rem; display: block; margin-bottom: 10px; color: var(--adm-text-light);"></i>
                                Nenhum banner cadastrado ainda.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
