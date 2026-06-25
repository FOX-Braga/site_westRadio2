<?php
$page_title = "Login";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Previne brute-force simples
    sleep(1);
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($csrf_token)) {
        $erro = "Token de segurança inválido.";
    } elseif (empty($email) || empty($senha)) {
        $erro = "Por favor, preencha todos os campos.";
    } else {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user['senha'])) {
            loginUser($user);
            if (hasPanelAccess()) {
                header("Location: " . BASE_URL . "/admin/index.php");
            } else {
                header("Location: " . BASE_URL . "/perfil.php");
            }
            exit;
        } else {
            $erro = "E-mail ou senha incorretos.";
        }
    }
}

if (isLoggedIn()) {
    header("Location: " . BASE_URL . "/perfil.php");
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="display: flex; justify-content: center; align-items: center; min-height: 60vh;">
    <div style="background-color: var(--color-surface); padding: 40px; border-radius: 8px; box-shadow: var(--shadow-md); width: 100%; max-width: 400px;">
        <h2 style="text-align: center; margin-bottom: 20px; color: var(--color-primary);">Entrar</h2>
        
        <?php if ($erro): ?>
            <div style="background-color: #fee2e2; color: #991b1b; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-size: 0.9rem;">
                <?= escape($erro) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" class="form-control" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" class="form-control" required>
            </div>
            
            <button type="submit" class="btn" style="width: 100%;">Fazer Login</button>
        </form>
        
        <div style="text-align: center; margin-top: 20px; font-size: 0.9rem;">
            Não tem uma conta? <a href="<?= BASE_URL ?>/cadastro.php" style="color: var(--color-primary); font-weight: 600;">Cadastre-se</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
