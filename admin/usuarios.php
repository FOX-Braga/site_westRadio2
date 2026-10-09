<?php
$filtro = $_GET['filter'] ?? '';
$page_title = ($filtro == 'colunista') ? "Colunistas e Autores" : "Gerenciar Usuários";
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/editorial_delete.php';

$pdo = Database::getInstance();

// Excluir usuário (não permite excluir a si mesmo)
// REGRA: As notícias são independentes do usuário. Ao excluir um usuário/perfil,
// as notícias continuam intactas no site com o nome do autor preservado.
$deleteId = $_POST['delete'] ?? $_GET['delete'] ?? null;
if ($deleteId !== null) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? $_GET['token'] ?? '')) { http_response_code(403); exit('Acesso Negado (CSRF).'); }
    $id = filter_var($deleteId, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) { http_response_code(400); exit('Usuario invalido.'); }
    if ($id === (int) $_SESSION['user_id']) {
        $erro = 'Voce nao pode excluir a sua propria conta ativa.';
    } else {
        try {
            editorialDeleteUser($pdo, $id);
            header('Location: usuarios.php?' . http_build_query(['filter'=>$filtro, 'msg'=>'deleted']));
            exit;
        } catch (Throwable $e) {
            error_log('Falha ao excluir usuario: ' . $e->getMessage());
            $erro = editorialDeleteError($e);
        }
    }
}

// Alterar tipo (admin/jornalista/user/colunista)
if (isset($_GET['set_role']) && is_numeric($_GET['id']) && in_array($_GET['set_role'], ['admin', 'jornalista', 'user', 'colunista'])) {
    if (!validateCsrfToken($_GET['token'] ?? '')) die("Acesso Negado (CSRF).");
    $id = (int)$_GET['id'];
    $role = $_GET['set_role'];
    if ($id != $_SESSION['user_id']) {
        $pdo->prepare("UPDATE usuarios SET tipo = ? WHERE id = ?")->execute([$role, $id]);
        header("Location: usuarios.php" . ($filtro ? "?filter=$filtro" : ""));
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';

if ($filtro == 'colunista') {
    $usuarios = $pdo->query("SELECT id, nome, email, tipo, criado_em FROM usuarios WHERE tipo IN ('admin', 'jornalista', 'colunista') ORDER BY criado_em DESC")->fetchAll();
    $titulo_pagina = "Colunistas e Autores da Redação";
} else {
    $usuarios = $pdo->query("SELECT id, nome, email, tipo, criado_em FROM usuarios ORDER BY criado_em DESC")->fetchAll();
    $titulo_pagina = "Usuários Cadastrados";
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 style="font-size: 1.4rem; font-weight: 800; margin: 0; color: var(--adm-text-main);">
            <?= $titulo_pagina ?>
        </h2>
        <p style="margin: 4px 0 0 0; color: var(--adm-text-muted); font-size: 0.875rem;">
            <?= count($usuarios) ?> contas registradas no sistema
        </p>
    </div>
    <a href="usuario_form.php<?= $filtro == 'colunista' ? '?tipo=colunista' : '' ?>" class="btn btn-accent">
        <i class="fas fa-user-plus"></i> Novo <?= $filtro == 'colunista' ? 'Colunista' : 'Usuário' ?>
    </a>
</div>

<?php if (isset($erro)): ?>
    <div style="padding: 14px 18px; background-color: var(--adm-danger-bg); color: var(--adm-danger-text); margin-bottom: 20px; border-radius: var(--adm-radius-sm); font-weight: 600;">
        <i class="fas fa-exclamation-circle"></i> <?= escape($erro) ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
    <div style="padding: 14px 18px; background-color: var(--adm-success-bg); color: var(--adm-success-text); margin-bottom: 20px; border-radius: var(--adm-radius-sm); font-weight: 600;">
        <i class="fas fa-check-circle"></i> Usuário excluído com sucesso. (As matérias vinculadas continuam salvas e visíveis no portal).
    </div>
<?php endif; ?>

<div class="card" style="padding: 0; overflow: hidden;">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Nome / E-mail</th>
                    <th>Nível de Acesso</th>
                    <th>Data Cadastro</th>
                    <th style="text-align: right;">Gerenciar Acesso</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: #e2e8f0; color: #475569; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; flex-shrink: 0;">
                                <?= strtoupper(substr($u['nome'], 0, 1)) ?>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: var(--adm-text-main); font-size: 0.925rem;">
                                    <?= escape($u['nome']) ?>
                                    <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                        <span style="font-size: 0.7rem; color: var(--adm-accent); font-weight: 700; margin-left: 4px;">(Você)</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--adm-text-muted);">
                                    <?= escape($u['email']) ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if ($u['tipo'] == 'admin'): ?>
                            <span class="badge badge-warning">Administrador</span>
                        <?php elseif ($u['tipo'] == 'jornalista'): ?>
                            <span class="badge badge-info">Jornalista</span>
                        <?php elseif ($u['tipo'] == 'colunista'): ?>
                            <span class="badge badge-info" style="background: #f3e8ff; color: #7e22ce;">Colunista</span>
                        <?php else: ?>
                            <span class="badge badge-success">Leitor / Assinante</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size: 0.85rem; color: var(--adm-text-muted); white-space: nowrap;">
                        <?= date('d/m/Y', strtotime($u['criado_em'])) ?>
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <div style="display: inline-flex; gap: 8px; align-items: center;">
                                <form action="usuarios.php" method="GET" style="margin: 0;">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="token" value="<?= getCsrfToken() ?>">
                                    <?php if ($filtro): ?><input type="hidden" name="filter" value="<?= escape($filtro) ?>"><?php endif; ?>
                                    
                                    <select name="set_role" onchange="if(confirm('Deseja alterar o perfil deste usuário?')) this.form.submit(); else this.value='<?= $u['tipo'] ?>';" class="form-control btn-sm" style="padding: 6px 10px; font-size: 0.8rem; height: 36px; min-width: 130px;">
                                        <option value="user" <?= $u['tipo'] == 'user' ? 'selected' : '' ?>>Leitor Comum</option>
                                        <option value="jornalista" <?= $u['tipo'] == 'jornalista' ? 'selected' : '' ?>>Jornalista</option>
                                        <option value="colunista" <?= $u['tipo'] == 'colunista' ? 'selected' : '' ?>>Colunista</option>
                                        <option value="admin" <?= $u['tipo'] == 'admin' ? 'selected' : '' ?>>Administrador</option>
                                    </select>
                                </form>

                                <form method="POST" action="usuarios.php?filter=<?= urlencode($filtro) ?>" style="display:inline" onsubmit="return confirm('Excluir este usuario? As noticias continuarao preservadas.')">
                                    <input type="hidden" name="delete" value="<?= (int) $u['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= escape(getCsrfToken()) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline btn-icon btn-danger" title="Excluir usuario"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        <?php else: ?>
                            <span style="font-size: 0.8rem; color: var(--adm-text-light); font-style: italic;">Conta em uso</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
