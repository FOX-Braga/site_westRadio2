<?php
$page_title = "Gerenciar Usuários";
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/includes/header.php';

$pdo = Database::getInstance();

// Excluir usuário (não permite excluir a si mesmo)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (!validateCsrfToken($_GET['token'] ?? '')) die("Acesso Negado (CSRF).");
    $id = $_GET['delete'];
    if ($id != $_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id]);
        header("Location: usuarios.php?msg=deleted");
        exit;
    } else {
        $erro = "Você não pode excluir a si mesmo.";
    }
}

// Alterar tipo (admin/jornalista/user/colunista)
if (isset($_GET['set_role']) && is_numeric($_GET['id']) && in_array($_GET['set_role'], ['admin', 'jornalista', 'user', 'colunista'])) {
    if (!validateCsrfToken($_GET['token'] ?? '')) die("Acesso Negado (CSRF).");
    $id = $_GET['id'];
    $role = $_GET['set_role'];
    if ($id != $_SESSION['user_id']) {
        $pdo->prepare("UPDATE usuarios SET tipo = ? WHERE id = ?")->execute([$role, $id]);
        header("Location: usuarios.php");
        exit;
    }
}

$filtro = $_GET['filter'] ?? '';
if ($filtro == 'colunista') {
    $usuarios = $pdo->query("SELECT id, nome, email, tipo, criado_em FROM usuarios WHERE tipo IN ('admin', 'jornalista', 'colunista') ORDER BY criado_em DESC")->fetchAll();
    $titulo_pagina = "Colunistas e Autores";
} else {
    $usuarios = $pdo->query("SELECT id, nome, email, tipo, criado_em FROM usuarios ORDER BY criado_em DESC")->fetchAll();
    $titulo_pagina = "Usuários do Sistema";
}
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin:0;"><?= $titulo_pagina ?></h3>
        <a href="usuario_form.php<?= $filtro == 'colunista' ? '?tipo=colunista' : '' ?>" class="btn"><i class="fas fa-plus"></i> Novo <?= $filtro == 'colunista' ? 'Colunista' : 'Usuário' ?></a>
    </div>

    <?php if (isset($erro)): ?>
        <div style="padding: 10px; background-color: #fee2e2; color: #991b1b; margin-bottom: 15px; border-radius: 4px;"><?= escape($erro) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <div style="padding: 10px; background-color: #dcfce7; color: #166534; margin-bottom: 15px; border-radius: 4px;">Usuário excluído com sucesso!</div>
    <?php endif; ?>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Tipo</th>
                    <th>Data Cadastro</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= escape($u['nome']) ?></td>
                    <td><?= escape($u['email']) ?></td>
                    <td>
                        <?php if ($u['tipo'] == 'admin'): ?>
                            <span class="badge badge-warning">ADMINISTRADOR</span>
                        <?php elseif ($u['tipo'] == 'jornalista'): ?>
                            <span class="badge" style="background-color: #3b82f6; color: white;">JORNALISTA</span>
                        <?php elseif ($u['tipo'] == 'colunista'): ?>
                            <span class="badge" style="background-color: #8b5cf6; color: white;">COLUNISTA</span>
                        <?php else: ?>
                            <span class="badge badge-success">USUÁRIO</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d/m/Y', strtotime($u['criado_em'])) ?></td>
                    <td>
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <div style="display: inline-flex; gap: 8px; align-items: center;">
                                <form action="usuarios.php" method="GET" style="margin: 0; display: inline-block;">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="token" value="<?= getCsrfToken() ?>">
                                    <select name="set_role" onchange="if(confirm('Deseja alterar o tipo de acesso deste usuário?')) this.form.submit(); else this.value='<?= $u['tipo'] ?>';" class="form-control" style="padding: 4px; font-size: 0.8rem; height: auto;">
                                        <option value="user" <?= $u['tipo'] == 'user' ? 'selected' : '' ?>>Usuário Comum</option>
                                        <option value="jornalista" <?= $u['tipo'] == 'jornalista' ? 'selected' : '' ?>>Jornalista</option>
                                        <option value="colunista" <?= $u['tipo'] == 'colunista' ? 'selected' : '' ?>>Colunista</option>
                                        <option value="admin" <?= $u['tipo'] == 'admin' ? 'selected' : '' ?>>Administrador</option>
                                    </select>
                                </form>
                                <?php if (in_array($u['tipo'], ['admin', 'jornalista', 'colunista'])): ?>
                                    <a href="noticia_form.php?autor_id=<?= $u['id'] ?>&cat_slug=opiniao" class="btn btn-sm" style="background-color: var(--color-primary); color: white; border: none;" title="Adicionar Opinião/Comentário">
                                        <i class="fas fa-comment-dots"></i> Nova Opinião
                                    </a>
                                <?php endif; ?>
                                <a href="?delete=<?= $u['id'] ?>&token=<?= getCsrfToken() ?>" class="btn btn-sm" style="background-color: #ef4444;" onclick="return confirm('Deseja excluir este usuário do sistema?')" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <span style="color: var(--admin-text-light); font-size: 0.8rem; font-weight: 600;">(Seu Usuário)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
