<?php
$page_title = 'Visão geral';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requirePanelAccess();
$pdo = Database::getInstance();
$isEditor = isJornalista();
$params = $isEditor ? [(int) $_SESSION['user_id']] : [];
$scope = $isEditor ? ' WHERE autor_id = ?' : '';
$stmt = $pdo->prepare('SELECT status, data_agendamento, visualizacoes FROM noticias' . $scope);
$stmt->execute($params);
$stats = ['total' => 0, 'publicado' => 0, 'rascunho' => 0, 'agendado' => 0, 'views' => 0];
foreach ($stmt as $row) {
    $stats['total']++;
    $stats['views'] += (int) $row['visualizacoes'];
    if (!empty($row['data_agendamento']) && strtotime($row['data_agendamento']) > time()) $stats['agendado']++;
    elseif ($row['status'] === 'publicado') $stats['publicado']++;
    else $stats['rascunho']++;
}
$stmt = $pdo->prepare("SELECT n.id, n.titulo, n.status, n.data_agendamento, n.criado_em, COALESCE(c.nome, 'Sem categoria') AS categoria FROM noticias n LEFT JOIN categorias c ON c.id = n.categoria_id" . ($isEditor ? ' WHERE n.autor_id = ?' : '') . ' ORDER BY n.criado_em DESC, n.id DESC LIMIT 6');
$stmt->execute($params);
$latest = $stmt->fetchAll();
$pending = isAdmin() ? (int) $pdo->query("SELECT COUNT(*) FROM comentarios WHERE status = 'pendente'")->fetchColumn() : 0;
$mineQuery = $isEditor ? '&minhas=1' : '';
require __DIR__ . '/includes/header.php';
?>
<section class="page-intro"><div><span class="eyebrow"><?= $isEditor ? 'Sua produção editorial' : 'Sua redação em um só lugar' ?></span><h2>Bom trabalho começa por aqui.</h2><p><?= $isEditor ? 'Acompanhe suas matérias e retome o que está em produção.' : 'Acompanhe as publicações e organize as próximas entregas da equipe.' ?></p></div><a class="btn" href="noticia_form.php"><i class="fas fa-plus" aria-hidden="true"></i> Nova notícia</a></section>
<div class="metric-grid">
<?php foreach ([['total', $isEditor ? 'Minhas notícias' : 'Total de notícias', 'fa-newspaper', 'Matérias cadastradas', ''], ['publicado', 'Publicadas', 'fa-check', 'Disponíveis no portal', 'publicado'], ['rascunho', 'Rascunhos', 'fa-pen', 'Para continuar editando', 'rascunho'], ['agendado', 'Agendadas', 'fa-clock', 'Com publicação futura', 'agendado']] as [$key, $label, $icon, $hint, $filter]): ?>
<a class="metric-card" href="noticias.php?status=<?= $filter ?><?= $mineQuery ?>"><span class="metric-top"><?= $label ?><span class="metric-icon"><i class="fas <?= $icon ?>" aria-hidden="true"></i></span></span><strong class="metric-value"><?= number_format($stats[$key], 0, ',', '.') ?></strong><small><?= $hint ?> <span aria-hidden="true">↗</span></small></a>
<?php endforeach; ?>
</div>
<div class="dashboard-grid"><section class="card"><div class="section-heading"><div><h3><?= $isEditor ? 'Suas últimas notícias' : 'Últimas notícias da redação' ?></h3><p>Abra uma matéria para continuar a edição.</p></div><a href="noticias.php?<?= $isEditor ? 'minhas=1' : '' ?>" class="text-link">Ver todas →</a></div>
<?php if (!$latest): ?><div class="empty-state"><i class="fas fa-newspaper" aria-hidden="true"></i><strong>Seu próximo conteúdo começa aqui</strong><p>Crie uma notícia e acompanhe a publicação pelo painel.</p><a class="btn" href="noticia_form.php">Criar primeira notícia</a></div><?php else: ?>
<div class="table-responsive"><table><thead><tr><th>Notícia</th><th>Situação</th><th>Editar</th></tr></thead><tbody>
<?php foreach ($latest as $item): $scheduled = !empty($item['data_agendamento']) && strtotime($item['data_agendamento']) > time(); ?>
<tr><td><a class="article-title" href="noticia_form.php?id=<?= (int) $item['id'] ?>"><?= escape($item['titulo']) ?></a><span class="article-meta"><?= escape($item['categoria']) ?> · <?= date('d/m/Y', strtotime($item['criado_em'])) ?></span></td><td><span class="badge <?= $scheduled ? 'badge-scheduled' : ($item['status'] === 'publicado' ? 'badge-success' : 'badge-warning') ?>"><?= $scheduled ? 'Agendada' : ($item['status'] === 'publicado' ? 'Publicada' : 'Rascunho') ?></span></td><td><a class="icon-button" href="noticia_form.php?id=<?= (int) $item['id'] ?>" aria-label="Editar <?= escape($item['titulo']) ?>"><i class="fas fa-pen" aria-hidden="true"></i></a></td></tr>
<?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<aside><section class="card"><h3>Próximos passos</h3><a class="task-link" href="noticias.php?status=rascunho<?= $mineQuery ?>"><div><strong>Retomar rascunhos</strong><small>Conteúdos que aguardam publicação</small></div><span class="task-count"><?= $stats['rascunho'] ?></span></a><a class="task-link" href="noticias.php?status=agendado<?= $mineQuery ?>"><div><strong>Revisar agendamentos</strong><small>Confira as próximas publicações</small></div><span class="task-count"><?= $stats['agendado'] ?></span></a><?php if (isAdmin()): ?><a class="task-link" href="comentarios.php"><div><strong>Moderar comentários</strong><small>Mensagens aguardando aprovação</small></div><span class="task-count"><?= $pending ?></span></a><?php endif; ?></section>
<section class="card"><h3>Acesso rápido</h3><div class="quick-links"><a href="noticia_form.php"><i class="fas fa-pen-nib" aria-hidden="true"></i> Escrever notícia</a><a href="video_form.php"><i class="fas fa-video" aria-hidden="true"></i> Adicionar vídeo ou live</a><?php if (isAdmin()): ?><a href="usuarios.php"><i class="fas fa-users" aria-hidden="true"></i> Gerenciar equipe</a><?php endif; ?><a href="<?= BASE_URL ?>/" target="_blank" rel="noopener"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i> Conferir o portal</a></div></section><p class="muted" style="font-size:12px;padding:0 4px"><?= number_format($stats['views'], 0, ',', '.') ?> visualizações <?= $isEditor ? 'nas suas notícias' : 'nas notícias do portal' ?>.</p></aside></div>
<?php require __DIR__ . '/includes/footer.php'; ?>