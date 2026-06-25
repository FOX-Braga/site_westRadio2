<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$page_title = "Meu Perfil";
$pdo = Database::getInstance();
$user_id = $_SESSION['user_id'];

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $bio = trim($_POST['bio']);
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (validateCsrfToken($csrf_token)) {
        // Handle upload foto
        $foto_atual = $_POST['foto_atual'];
        $nova_foto = $foto_atual;

        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadImage($_FILES['foto_perfil'], 'avatares');
            if ($upload['success']) {
                $nova_foto = $upload['filename'];
                
                // Remove a foto antiga do servidor para não gastar espaço de disco desnecessariamente
                if (!empty($foto_atual) && file_exists(__DIR__ . '/uploads/avatares/' . $foto_atual)) {
                    unlink(__DIR__ . '/uploads/avatares/' . $foto_atual);
                }
            } else {
                $mensagem = $upload['message'];
            }
        }

        if (empty($mensagem)) {
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, bio = ?, foto_perfil = ? WHERE id = ?");
            if ($stmt->execute([$nome, $bio, $nova_foto, $user_id])) {
                $_SESSION['user_nome'] = $nome;
                $_SESSION['user_foto'] = $nova_foto;
                $mensagem = "Perfil atualizado com sucesso!";
            }
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 800px; padding: 40px 20px; min-height: 60vh;">
    <h1 class="section-title">Meu Perfil</h1>

    <?php if ($mensagem): ?>
        <div style="padding: 15px; background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; border-radius: 4px; margin-bottom: 20px;">
            <?= escape($mensagem) ?>
        </div>
    <?php endif; ?>

    <div style="background-color: var(--color-surface); padding: 30px; border-radius: 8px; box-shadow: var(--shadow-sm); display: flex; gap: 30px; flex-wrap: wrap;">
        
        <div style="flex: 1; min-width: 250px; text-align: center;">
            <div style="width: 150px; height: 150px; border-radius: 50%; background-color: var(--color-gray-medium); margin: 0 auto 20px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                <?php if ($user['foto_perfil']): ?>
                    <img src="<?= BASE_URL ?>/uploads/avatares/<?= escape($user['foto_perfil']) ?>" alt="Foto" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <span style="font-size: 4rem; font-weight: bold; color: var(--color-text-muted);"><?= strtoupper(substr($user['nome'], 0, 1)) ?></span>
                <?php endif; ?>
            </div>
            <p style="font-weight: bold; font-size: 1.2rem;"><?= escape($user['nome']) ?></p>
            <p style="color: var(--color-text-muted);"><?= escape($user['email']) ?></p>
            <p style="margin-top: 10px;"><span class="badge-urgente" style="background-color: var(--color-primary); color: white;"><?= strtoupper($user['tipo']) ?></span></p>
        </div>

        <div style="flex: 2; min-width: 300px;">
            <form action="perfil.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                <input type="hidden" name="foto_atual" value="<?= escape($user['foto_perfil']) ?>">
                
                <div class="form-group">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" class="form-control" value="<?= escape($user['nome']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="bio">Biografia</label>
                    <textarea id="bio" name="bio" class="form-control" rows="4"><?= escape($user['bio']) ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="foto_perfil">Alterar Foto de Perfil (JPG, PNG)</label>
                    <input type="file" id="foto_perfil" name="foto_perfil" class="form-control" accept="image/jpeg, image/png, image/webp">
                </div>
                
                <button type="submit" class="btn">Atualizar Perfil</button>
            </form>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
