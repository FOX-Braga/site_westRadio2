<?php
$page_title = "Cadastro";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) {
    header("Location: " . BASE_URL . "/perfil.php");
    exit;
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senha_confirma = $_POST['senha_confirma'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($csrf_token)) {
        $erro = "Token de segurança inválido.";
    } elseif (empty($nome) || empty($email) || empty($senha)) {
        $erro = "Por favor, preencha todos os campos.";
    } elseif ($senha !== $senha_confirma) {
        $erro = "As senhas não conferem.";
    } elseif (strlen($senha) < 6) {
        $erro = "A senha deve ter pelo menos 6 caracteres.";
    } else {
        $pdo = Database::getInstance();
        
        // Verifica se e-mail já existe
        $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmtCheck->execute([$email]);
        
        if ($stmtCheck->fetch()) {
            $erro = "Este e-mail já está cadastrado.";
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo, verificado) VALUES (?, ?, ?, 'user', 1)");
            
            if ($stmt->execute([$nome, $email, $hash])) {
                $sucesso = "Cadastro realizado com sucesso! Você já pode fazer login.";
            } else {
                $erro = "Erro ao cadastrar. Tente novamente.";
            }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="display: flex; justify-content: center; align-items: center; min-height: 70vh;">
    <div style="background-color: var(--color-surface); padding: 40px; border-radius: 8px; box-shadow: var(--shadow-md); width: 100%; max-width: 500px;">
        <h2 style="text-align: center; margin-bottom: 20px; color: var(--color-primary);">Criar Conta</h2>
        
        <?php if ($erro): ?>
            <div style="background-color: #fee2e2; color: #991b1b; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-size: 0.9rem;">
                <?= escape($erro) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($sucesso): ?>
            <div style="background-color: #dcfce7; color: #166534; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-size: 0.9rem;">
                <?= escape($sucesso) ?>
            </div>
            <a href="<?= BASE_URL ?>/login.php" class="btn" style="width: 100%; text-align: center;">Ir para o Login</a>
        <?php else: ?>
            <form action="cadastro.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                
                <div class="form-group">
                    <label for="nome">Nome Completo</label>
                    <input type="text" id="nome" name="nome" class="form-control" value="<?= isset($_POST['nome']) ? escape($_POST['nome']) : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= isset($_POST['email']) ? escape($_POST['email']) : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" class="form-control" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="senha_confirma">Confirmar Senha</label>
                    <input type="password" id="senha_confirma" name="senha_confirma" class="form-control" required minlength="6">
                </div>
                
                <button type="submit" class="btn" style="width: 100%;">Cadastrar</button>
            </form>
            
            <div style="text-align: center; margin-top: 20px; font-size: 0.9rem;">
                Já tem uma conta? <a href="<?= BASE_URL ?>/login.php" style="color: var(--color-primary); font-weight: 600;">Entrar</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
