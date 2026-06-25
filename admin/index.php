<?php
$page_title = "Dashboard";
require_once __DIR__ . '/../includes/auth.php';
if (isJornalista()) { header("Location: noticias.php"); exit; }
require_once __DIR__ . '/includes/header.php';

$pdo = Database::getInstance();

// Estatísticas
$total_noticias = $pdo->query("SELECT COUNT(*) FROM noticias")->fetchColumn();
$total_usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$total_comentarios = $pdo->query("SELECT COUNT(*) FROM comentarios WHERE status = 'pendente'")->fetchColumn();
$total_views = $pdo->query("SELECT SUM(visualizacoes) FROM noticias")->fetchColumn();

// Últimas notícias adicionadas
$ultimas = $pdo->query("SELECT n.titulo, n.status, c.nome as categoria 
                        FROM noticias n 
                        JOIN categorias c ON n.categoria_id = c.id 
                        ORDER BY n.criado_em DESC LIMIT 5")->fetchAll();
?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="card" style="border-left: 4px solid var(--admin-primary);">
        <h3 style="color: var(--admin-text-light); font-size: 0.9rem; text-transform: uppercase;">Total de Notícias</h3>
        <p style="font-size: 2rem; font-weight: bold; margin-top: 10px;"><?= $total_noticias ?></p>
    </div>
    
    <div class="card" style="border-left: 4px solid #3b82f6;">
        <h3 style="color: var(--admin-text-light); font-size: 0.9rem; text-transform: uppercase;">Total de Visualizações</h3>
        <p style="font-size: 2rem; font-weight: bold; margin-top: 10px;"><?= $total_views ?? 0 ?></p>
    </div>
    
    <div class="card" style="border-left: 4px solid #eab308;">
        <h3 style="color: var(--admin-text-light); font-size: 0.9rem; text-transform: uppercase;">Comentários Pendentes</h3>
        <p style="font-size: 2rem; font-weight: bold; margin-top: 10px;"><?= $total_comentarios ?></p>
    </div>
    
    <div class="card" style="border-left: 4px solid #8b5cf6;">
        <h3 style="color: var(--admin-text-light); font-size: 0.9rem; text-transform: uppercase;">Usuários Registrados</h3>
        <p style="font-size: 2rem; font-weight: bold; margin-top: 10px;"><?= $total_usuarios ?></p>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom: 20px;">Últimas Notícias Adicionadas</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Categoria</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ultimas as $item): ?>
                <tr>
                    <td><?= escape($item['titulo']) ?></td>
                    <td><?= escape($item['categoria']) ?></td>
                    <td>
                        <?php if ($item['status'] === 'publicado'): ?>
                            <span class="badge badge-success">Publicado</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Rascunho</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
