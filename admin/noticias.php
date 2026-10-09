<?php
$page_title = 'Notícias';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requirePanelAccess();
$pdo = Database::getInstance();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) { http_response_code(403); exit('Acesso negado. Atualize a página e tente novamente.'); }
    $id = (int) $_POST['delete'];
    $stmtImg = $pdo->prepare('SELECT imagem_destacada FROM noticias WHERE id = ?');
    $stmtImg->execute([$id]);
    $img = $stmtImg->fetchColumn();
    $pdo->prepare('DELETE FROM noticias WHERE id = ?')->execute([$id]);
    if ($img && !str_starts_with($img, 'data:') && basename($img) === $img) {
        $imagePath = __DIR__ . '/../uploads/noticias/' . $img;
        if (is_file($imagePath)) unlink($imagePath);
    }
    header('Location: noticias.php?msg=deleted');
    exit;
}
$q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 160);
$status = in_array($_GET['status'] ?? '', ['publicado', 'rascunho', 'agendado'], true) ? $_GET['status'] : '';
$category = max(0, (int) ($_GET['categoria'] ?? 0));
$mine = ($_GET['minhas'] ?? '') === '1';
$where = []; $params = [];
if ($q !== '') { $where[] = '(n.titulo LIKE ? OR n.subtitulo LIKE ?)'; $params[] = '%' . $q . '%'; $params[] = '%' . $q . '%'; }
$now = date('Y-m-d H:i:s');
if ($status === 'agendado') { $where[] = 'n.data_agendamento > ?'; $params[] = $now; }
elseif ($status !== '') { $where[] = 'n.status = ? AND (n.data_agendamento IS NULL OR n.data_agendamento <= ?)'; $params[] = $status; $params[] = $now; }
if ($category) { $where[] = 'n.categoria_id = ?'; $params[] = $category; }
if ($mine) { $where[] = 'n.autor_id = ?'; $params[] = (int) $_SESSION['user_id']; }
$sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->prepare('SELECT COUNT(*) FROM noticias n' . $sqlWhere); $stmt->execute($params); $total = (int) $stmt->fetchColumn();
$limit = 15; $totalPages = max(1, (int) ceil($total / $limit));
$page = min($totalPages, max(1, (int) ($_GET['p'] ?? 1))); $offset = ($page - 1) * $limit;
$stmt = $pdo->prepare("SELECT n.id,n.titulo,n.slug,n.status,n.data_agendamento,n.visualizacoes,n.criado_em,COALESCE(c.nome,'Sem categoria') AS categoria,COALESCE(NULLIF(n.autor_nome,''),u.nome,'Redação') AS autor_nome FROM noticias n LEFT JOIN categorias c ON n.categoria_id=c.id LEFT JOIN usuarios u ON n.autor_id=u.id" . $sqlWhere . " ORDER BY n.criado_em DESC,n.id DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params); $noticias = $stmt->fetchAll();
$categories = $pdo->query('SELECT id,nome FROM categorias ORDER BY nome')->fetchAll();
$filterQuery = ['q'=>$q,'status'=>$status,'categoria'=>$category ?: '', 'minhas'=>$mine ? '1' : ''];
require __DIR__ . '/includes/header.php';
?>
<section class="page-intro"><div><h2>Organize sua produção.</h2><p>Encontre, revise e publique os conteúdos da redação.</p></div><a class="btn" href="noticia_form.php"><i class="fas fa-plus" aria-hidden="true"></i> Nova notícia</a></section>
<?php if (($_GET['msg'] ?? '') === 'deleted'): ?><div class="notice" role="status">Notícia excluída com sucesso.</div><?php endif; ?>
<section class="card"><form method="get" class="filter-bar" role="search" aria-label="Filtrar notícias"><div><label for="news-search">Buscar notícia</label><input class="form-control" id="news-search" name="q" value="<?= escape($q) ?>" placeholder="Busque pelo título ou subtítulo" type="search"></div><div><label for="news-status">Situação</label><select class="form-control" id="news-status" name="status"><option value="">Todas as situações</option><?php foreach (['publicado'=>'Publicadas','rascunho'=>'Rascunhos','agendado'=>'Agendadas'] as $value=>$label): ?><option value="<?= $value ?>" <?= $status === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div><div><label for="news-category">Categoria</label><select class="form-control" id="news-category" name="categoria"><option value="">Todas as categorias</option><?php foreach ($categories as $cat): ?><option value="<?= (int) $cat['id'] ?>" <?= $category === (int) $cat['id'] ? 'selected' : '' ?>><?= escape($cat['nome']) ?></option><?php endforeach; ?></select></div><div class="filter-actions"><button class="btn" type="submit">Filtrar</button><a class="btn btn-outline" href="noticias.php">Limpar</a></div><label style="display:flex;align-items:center;gap:8px;grid-column:1/-1;margin:0"><input type="checkbox" name="minhas" value="1" <?= $mine ? 'checked' : '' ?>> Somente minhas notícias</label></form>
<div class="section-heading"><h3><?= $mine ? 'Minhas notícias' : 'Notícias da redação' ?> <span class="badge"><?= $total ?></span></h3><span class="muted" style="font-size:12px">Mais recentes primeiro</span></div>
<?php if (!$noticias): ?><div class="empty-state"><i class="fas fa-magnifying-glass" aria-hidden="true"></i><strong>Nenhuma notícia encontrada</strong><p>Experimente outros filtros ou comece um novo conteúdo.</p><a class="btn btn-outline" href="noticias.php">Ver todas as notícias</a></div><?php else: ?>
<div class="table-responsive"><table><thead><tr><th>Notícia / autor</th><th>Categoria</th><th>Situação</th><th>Visualizações</th><th>Data</th><th>Ações</th></tr></thead><tbody>
<?php foreach ($noticias as $item): $scheduled = !empty($item['data_agendamento']) && strtotime($item['data_agendamento']) > time(); ?>
<tr><td><a class="article-title" href="noticia_form.php?id=<?= (int) $item['id'] ?>"><?= escape($item['titulo']) ?></a><span class="article-meta"><?= escape($item['autor_nome']) ?> · #<?= (int) $item['id'] ?></span></td><td><?= escape($item['categoria']) ?></td><td><span class="badge <?= $scheduled ? 'badge-scheduled' : ($item['status']==='publicado' ? 'badge-success' : 'badge-warning') ?>"><?= $scheduled ? 'Agendada' : ($item['status']==='publicado' ? 'Publicada' : 'Rascunho') ?></span><?php if ($scheduled): ?><span class="article-meta"><?= date('d/m/Y H:i', strtotime($item['data_agendamento'])) ?></span><?php endif; ?></td><td><?= number_format((int) $item['visualizacoes'],0,',','.') ?></td><td style="white-space:nowrap"><?= date('d/m/Y', strtotime($item['criado_em'])) ?></td><td><div class="row-actions"><?php if ($item['status']==='publicado' && !$scheduled): ?><a class="icon-button" href="<?= BASE_URL ?>/noticia/<?= escape($item['slug']) ?>" target="_blank" rel="noopener" aria-label="Ver <?= escape($item['titulo']) ?> no portal" title="Ver no portal"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></a><?php endif; ?><a class="icon-button" href="noticia_form.php?id=<?= (int) $item['id'] ?>" aria-label="Editar <?= escape($item['titulo']) ?>" title="Editar notícia"><i class="fas fa-pen" aria-hidden="true"></i></a><form method="post" data-confirm="Excluir esta notícia definitivamente? Esta ação não pode ser desfeita."><input type="hidden" name="csrf_token" value="<?= escape(getCsrfToken()) ?>"><input type="hidden" name="delete" value="<?= (int) $item['id'] ?>"><button class="icon-button danger" type="submit" aria-label="Excluir <?= escape($item['titulo']) ?>" title="Excluir notícia"><i class="fas fa-trash" aria-hidden="true"></i></button></form></div></td></tr>
<?php endforeach; ?></tbody></table></div>
<div class="pagination"><span>Mostrando <?= $offset + 1 ?>–<?= min($offset + $limit, $total) ?> de <?= $total ?> notícias</span><nav class="pagination-links" aria-label="Paginação das notícias"><?php if ($page > 1): ?><a class="btn btn-sm btn-outline" href="?<?= escape(http_build_query(array_merge($filterQuery,['p'=>$page-1]))) ?>">Anterior</a><?php endif; ?><?php for ($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++): ?><a class="btn btn-sm btn-outline" <?= $i===$page ? 'aria-current="page"' : '' ?> href="?<?= escape(http_build_query(array_merge($filterQuery,['p'=>$i]))) ?>"><?= $i ?></a><?php endfor; ?><?php if ($page < $totalPages): ?><a class="btn btn-sm btn-outline" href="?<?= escape(http_build_query(array_merge($filterQuery,['p'=>$page+1]))) ?>">Próxima</a><?php endif; ?></nav></div><?php endif; ?></section>
<?php require __DIR__ . '/includes/footer.php'; ?>