<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/author_migration.php';
requireAdmin();
$pdo = Database::getInstance();
$erro = '';
$plan = null;
try {
    $plan = authorNullableMigrationPlan($pdo);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) { http_response_code(403); exit('Acesso Negado (CSRF).'); }
        if (($_POST['action'] ?? '') !== 'allow_removed_author') { http_response_code(400); exit('Acao invalida.'); }
        if ($plan !== null) $pdo->exec($plan);
        if (authorNullableMigrationPlan($pdo) !== null) throw new RuntimeException('A coluna continua obrigatoria.');
        header('Location: usuarios.php?msg=schema_fixed');
        exit;
    }
} catch (Throwable $e) {
    error_log('Falha na migracao de autor_id: ' . $e->getMessage());
    $erro = 'Nao foi possivel aplicar a correcao. Verifique se o usuario do banco possui permissao ALTER. Nenhum usuario ou noticia foi excluido.';
}
$page_title = 'Corrigir vinculo de autores';
require_once __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:850px;padding:24px">
<h2>Permitir excluir autores mantendo as noticias</h2>
<p>O banco antigo exige um autor em todas as materias. Esta correcao permite que uma noticia continue salva quando seu autor for removido.</p>
<p>A alteracao preserva os registros, o tipo da coluna e a chave estrangeira. Nenhum usuario ou noticia sera excluido nesta etapa.</p>
<?php if ($erro): ?><div role="alert" style="padding:16px;background:#fee2e2;color:#991b1b"><?= escape($erro) ?></div>
<?php elseif ($plan !== null): ?>
<p>Alteracao que sera aplicada ao banco:</p>
<pre style="white-space:pre-wrap;padding:16px;background:#f1f5f9"><?= escape($plan) ?>;</pre>
<form method="POST" action="corrigir_banco.php">
<input type="hidden" name="csrf_token" value="<?= escape(getCsrfToken()) ?>">
<input type="hidden" name="action" value="allow_removed_author">
<button type="submit" class="btn btn-accent">Aplicar correcao do banco</button>
</form>
<?php else: ?><p role="status">O banco ja aceita autores removidos. Voce pode voltar e excluir o usuario.</p><?php endif; ?>
<a href="usuarios.php" class="btn btn-outline" style="margin-top:16px">Voltar aos usuarios</a>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
