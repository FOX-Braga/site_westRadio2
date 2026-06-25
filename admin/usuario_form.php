<?php
$tipo_pre = $_GET['tipo'] ?? '';
$page_title = ($tipo_pre === 'colunista') ? "Novo Colunista" : "Novo Usuário";
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/includes/header.php';

$pdo = Database::getInstance();
$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrf_token)) {
        die("Erro de segurança (CSRF). Ação não permitida.");
    }
    
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);
    $tipo = $_POST['tipo'];
    
    if (empty($nome) || empty($email) || empty($senha)) {
        $erro = "Todos os campos (Nome, E-mail e Senha) são obrigatórios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Formato de e-mail inválido.";
    } elseif (strlen($senha) < 6) {
        $erro = "A senha deve ter pelo menos 6 caracteres.";
    } else {
        // Verificar se e-mail já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erro = "Este e-mail já está cadastrado no sistema.";
        } else {
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo, verificado) VALUES (?, ?, ?, ?, 1)");
            if ($stmt->execute([$nome, $email, $senhaHash, $tipo])) {
                $sucesso = "Usuário cadastrado com sucesso!";
            } else {
                $erro = "Erro ao cadastrar o usuário no banco de dados.";
            }
        }
    }
}
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <h3 style="margin-bottom: 20px;"><?= $page_title === "Novo Colunista" ? "Cadastrar Novo Colunista" : "Cadastrar Novo Usuário" ?></h3>
    
    <?php if ($erro): ?>
        <div style="padding: 10px; background-color: #fee2e2; color: #991b1b; margin-bottom: 15px; border-radius: 4px;"><?= escape($erro) ?></div>
    <?php endif; ?>
    <?php if ($sucesso): ?>
        <div style="padding: 10px; background-color: #dcfce7; color: #166534; margin-bottom: 15px; border-radius: 4px;"><?= escape($sucesso) ?></div>
        <script>setTimeout(() => window.location.href='usuarios.php', 2000);</script>
    <?php endif; ?>
    
    <form action="" method="POST">
        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
        
        <div class="form-group">
            <label>Nome Completo</label>
            <input type="text" name="nome" class="form-control" required placeholder="Ex: João da Silva">
        </div>
        
        <div class="form-group">
            <label>E-mail</label>
            <input type="email" name="email" class="form-control" required placeholder="joao@exemplo.com">
        </div>
        
        <div class="form-group">
            <label>Senha Inicial</label>
            <input type="password" name="senha" class="form-control" required minlength="6" placeholder="Mínimo 6 caracteres">
        </div>
        
        <?php $tipo_pre = $_GET['tipo'] ?? ''; ?>
        <div class="form-group">
            <label>Nível de Acesso (Tipo)</label>
            <select name="tipo" class="form-control" required>
                <option value="user" <?= $tipo_pre == 'user' ? 'selected' : '' ?>>Usuário Comum (Apenas comenta)</option>
                <option value="jornalista" <?= $tipo_pre == 'jornalista' ? 'selected' : '' ?>>Jornalista (Pode postar notícias)</option>
                <option value="colunista" <?= $tipo_pre == 'colunista' ? 'selected' : '' ?>>Colunista (Opinião)</option>
                <option value="admin" <?= $tipo_pre == 'admin' ? 'selected' : '' ?>>Administrador (Acesso total)</option>
            </select>
        </div>
        
        <div style="display: flex; gap: 15px; margin-top: 30px;">
            <button type="submit" class="btn" style="flex-grow: 1;">Criar Conta</button>
            <a href="usuarios.php" class="btn btn-outline" style="text-align: center; width: 120px;">Voltar</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
