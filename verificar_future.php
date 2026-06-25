<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$token = $_GET['token'] ?? '';
$sucesso = false;
$mensagem = '';

$pdo = Database::getInstance();

if (!empty($token)) {
    // Busca o usuário com o token correspondente
    $stmt = $pdo->prepare("SELECT id, nome, verificado FROM usuarios WHERE token_verificacao = ? LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['verificado'] == 1) {
            $sucesso = true;
            $mensagem = "Sua conta já havia sido verificada e está ativa.";
        } else {
            // Ativa a conta e zera o token
            $stmtUpdate = $pdo->prepare("UPDATE usuarios SET verificado = 1, token_verificacao = NULL WHERE id = ?");
            if ($stmtUpdate->execute([$user['id']])) {
                $sucesso = true;
                $mensagem = "Parabéns, <strong>" . escape($user['nome']) . "</strong>! Sua conta foi ativada com sucesso.";
            } else {
                $mensagem = "Ocorreu um erro ao ativar sua conta. Tente novamente mais tarde.";
            }
        }
    } else {
        $mensagem = "Token de verificação inválido ou expirado.";
    }
} else {
    $mensagem = "Nenhum código de verificação foi fornecido.";
}

$page_title = $sucesso ? "E-mail Confirmado" : "Erro de Verificação";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 55vh; text-align: center; padding: 60px 20px;">
    <?php if ($sucesso): ?>
        <div style="font-size: 6rem; color: var(--color-primary); margin-bottom: 25px;">
            <i class="far fa-check-circle"></i>
        </div>
        <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 15px; color: var(--color-text);">E-mail Confirmado!</h1>
        <p style="font-size: 1.1rem; color: var(--color-text-muted); max-width: 600px; margin-bottom: 35px; line-height: 1.6;">
            <?= $mensagem ?>
        </p>
        <a href="<?= BASE_URL ?>/login.php" class="btn" style="padding: 12px 30px; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; justify-content: center; max-width: 250px;">
            <i class="fas fa-sign-in-alt"></i> Ir para o Login
        </a>
    <?php else: ?>
        <div style="font-size: 6rem; color: #ef4444; margin-bottom: 25px;">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 15px; color: var(--color-text);">Falha na Verificação</h1>
        <p style="font-size: 1.1rem; color: var(--color-text-muted); max-width: 600px; margin-bottom: 35px; line-height: 1.6;">
            <?= $mensagem ?>
        </p>
        <a href="<?= BASE_URL ?>/" class="btn" style="padding: 12px 30px; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; justify-content: center; max-width: 250px;">
            <i class="fas fa-home"></i> Voltar para a Home
        </a>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
