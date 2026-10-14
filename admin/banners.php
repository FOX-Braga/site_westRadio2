<?php
$page_title = 'Gerenciar Banners Publicitários';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/banner_admin.php';
$pdo = Database::getInstance();
$erro = '';
$available = false;
$edit = null;
$banners = $categories = [];
try {
    bannerInstall($pdo);
    $available = true;
    $categories = bannerCategories($pdo);
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if (bannerPostExceeded()) {
            http_response_code(413);
            throw new RuntimeException('O envio excedeu o limite total do servidor. Atualize a página e envie novamente; as imagens serão enviadas em partes.');
        }
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) { http_response_code(403); exit('Acesso negado (CSRF).'); }
        $action = $_POST['action'] ?? 'save';
        $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]);
        if ($id === false) throw new RuntimeException('Campanha inválida.');
        $edit = $id ? bannerFind($pdo, $id) : null;
        if ($id && !$edit) { http_response_code(404); throw new RuntimeException('Campanha não encontrada.'); }
        if ($action === 'delete' && $edit) bannerDelete($pdo, $edit);
        elseif ($action === 'toggle' && $edit) $pdo->prepare('UPDATE banners SET status = ? WHERE id = ?')->execute([$edit['status'] === 'ativo' ? 'pausado' : 'ativo', $id]);
        elseif ($action === 'save') bannerSave($pdo, $_POST, $_FILES, $edit);
        else throw new RuntimeException('Ação inválida.');
        header('Location: banners.php?msg=' . ($action === 'delete' ? 'deleted' : ($action === 'toggle' ? 'status' : 'saved')));
        exit;
    }
    if (isset($_GET['edit'])) {
        $id = filter_var($_GET['edit'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        $edit = $id ? bannerFind($pdo, $id) : null;
        if (!$edit) { http_response_code(404); throw new RuntimeException('Campanha não encontrada.'); }
    }
} catch (RuntimeException $error) {
    if ($error instanceof PDOException) {
        error_log('Admin Banners: ' . $error->getMessage());
        $erro = 'Não foi possível acessar ou salvar os banners. Confira as permissões do banco e o log da hospedagem.';
    } else $erro = $error->getMessage();
}
if ($available) $banners = $pdo->query('SELECT b.*, s.* FROM banners b LEFT JOIN banner_settings s ON s.banner_id = b.id ORDER BY b.criado_em DESC, b.id DESC')->fetchAll(PDO::FETCH_ASSOC);
$values = $edit ?: ['status'=>'pausado'];
if ($erro && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? 'save') === 'save') $values = array_merge($values, $_POST);
$selectedSlots = is_array($values['slots'] ?? null) ? $values['slots'] : (json_decode($values['slots'] ?? '[]', true) ?: []);
$selectedCategories = is_array($values['categories'] ?? null) ? $values['categories'] : (json_decode($values['categories'] ?? '[]', true) ?: []);
if ($erro && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $values['show_home'] = isset($_POST['show_home']);
    $values['all_categories'] = isset($_POST['all_categories']);
}
$messages = ['saved'=>'Campanha salva com sucesso.', 'deleted'=>'Campanha excluída.', 'status'=>'Status atualizado.'];
require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/banner-admin.css?v=<?= filemtime(__DIR__ . '/../assets/css/banner-admin.css') ?>">
<div class="banner-admin">
    <p class="banner-help">Controle a publicidade da Home e de cada editoria. Nenhuma página ou posição é selecionada automaticamente. Campanhas no mesmo slot alternam a cada 5 segundos.</p>
    <?php if ($erro): ?><p class="banner-notice banner-error" role="alert"><?= escape($erro) ?></p><?php endif; ?>
    <?php if (isset($messages[$_GET['msg'] ?? ''])): ?><p class="banner-notice" role="status"><?= escape($messages[$_GET['msg']]) ?></p><?php endif; ?>
    <?php if ($available): ?>
    <div class="banner-admin-grid">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><?= $edit ? 'Editar campanha' : 'Cadastrar campanha' ?></h3></div>
            <form id="banner-campaign-form" action="banners.php" method="POST" enctype="multipart/form-data" data-turbo="false" data-upload-url="<?= BASE_URL ?>/admin/banner_upload.php" data-max-file-size="<?= bannerMaxUploadSize() ?>">
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                <input type="hidden" name="action" value="save">
                <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
                <input type="hidden" name="imagem_token" value="<?= escape($values['imagem_token'] ?? '') ?>">
                <input type="hidden" name="imagem_mobile_token" value="<?= escape($values['imagem_mobile_token'] ?? '') ?>">
                <div class="form-group"><label for="ad-title">Nome da campanha</label><input id="ad-title" class="form-control" name="titulo" maxlength="255" value="<?= escape($values['titulo'] ?? '') ?>" required></div>
                <div class="form-group"><label for="ad-advertiser">Nome do anunciante</label><input id="ad-advertiser" class="form-control" name="anunciante" maxlength="255" value="<?= escape($values['anunciante'] ?? '') ?>" required></div>
                <div class="form-group"><label for="ad-link">Link de destino</label><input id="ad-link" class="form-control" type="url" name="link" maxlength="255" placeholder="https://" value="<?= escape($values['link'] ?? '') ?>" required></div>
                <div class="form-group">
                    <label for="ad-desktop">Banner Desktop / Tablet (obrigatório)</label>
                    <?php if (!empty($edit['imagem'])): ?><img class="banner-preview" src="<?= escape(bannerUrl($edit['imagem'])) ?>" alt="Criativo desktop atual"><?php endif; ?>
                    <input id="ad-desktop" type="file" class="form-control" name="imagem" accept="image/webp,image/jpeg,image/png" <?= empty($edit['imagem']) && empty($values['imagem_token']) ? 'required' : '' ?>>
                </div>
                <div class="form-group">
                    <label for="ad-mobile">Banner Mobile (opcional)</label>
                    <?php if (!empty($edit['imagem_mobile'])): ?>
                        <img class="banner-preview" src="<?= escape(bannerUrl($edit['imagem_mobile'])) ?>" alt="Criativo mobile atual">
                        <label class="banner-choice"><input type="checkbox" name="remove_mobile" value="1" <?= !empty($values['remove_mobile']) ? 'checked' : '' ?>> Remover criativo mobile atual</label>
                    <?php endif; ?>
                    <input id="ad-mobile" type="file" class="form-control" name="imagem_mobile" accept="image/webp,image/jpeg,image/png">
                    <p class="banner-help">WebP, JPG, JPEG ou PNG, até <?= escape(bannerUploadLimitLabel()) ?> por arquivo. As imagens são enviadas automaticamente em partes. Sem arte mobile, a arte desktop mantém sua proporção. Laterais ficam ocultas em telas sem espaço.</p>
                </div>
                <fieldset><legend>Exibir em</legend>
                    <label class="banner-choice"><input type="checkbox" name="show_home" value="1" <?= !empty($values['show_home']) ? 'checked' : '' ?>> Exibir também na Home</label>
                    <label class="banner-choice"><input type="checkbox" name="all_categories" value="1" <?= !empty($values['all_categories']) ? 'checked' : '' ?>> Exibir em todas as editorias</label>
                    <p class="banner-help">Todas as editorias inclui editorias futuras e não inclui a Home. Ao desmarcar, somente as editorias selecionadas abaixo recebem a campanha.</p>
                    <div class="banner-choices"><?php foreach ($categories as $category): ?>
                        <label class="banner-choice"><input type="checkbox" name="categories[]" value="<?= escape($category['slug']) ?>" <?= in_array($category['slug'], $selectedCategories, true) ? 'checked' : '' ?>> <?= escape($category['nome']) ?></label>
                    <?php endforeach; ?></div>
                </fieldset>
                <fieldset><legend>Posições do anúncio</legend>
                    <div class="banner-choices"><?php foreach (bannerSlots() as $code => $label): ?>
                        <label class="banner-choice"><input type="checkbox" name="slots[]" value="<?= $code ?>" <?= in_array($code, $selectedSlots, true) ? 'checked' : '' ?>> <?= $label ?></label>
                    <?php endforeach; ?></div>
                </fieldset>
                <details class="banner-sizes" open><summary>Tamanhos dos banners</summary>
                    <p>Topo (recomendado): desktop/tablet 970 × 90 px; mobile 320 × 100 px.</p>
                    <p>Meio e rodapé (recomendados): desktop/tablet 970 × 250 px; mobile 320 × 100 ou 300 × 250 px.</p>
                    <p>Laterais (permitidos): 120 × 600, 160 × 600 ou 300 × 600 px. Novos criativos laterais precisam ter exatamente um desses tamanhos e são exibidos na largura do formato, somente quando há espaço livre.</p>
                    <p>As artes laterais já cadastradas continuam disponíveis. Ao substituir a imagem, utilize um dos formatos permitidos. Arquivos aceitos: WebP, JPG, JPEG e PNG.</p>
                </details>
                <div class="banner-choices">
                    <?php foreach (['starts_at'=>'Data inicial', 'ends_at'=>'Data final'] as $field => $label): ?>
                    <div class="form-group"><label for="ad-<?= $field ?>"><?= $label ?></label><input id="ad-<?= $field ?>" type="datetime-local" class="form-control" name="<?= $field ?>" value="<?= escape(!empty($values[$field]) ? substr(str_replace(' ', 'T', $values[$field]), 0, 16) : '') ?>"></div>
                    <?php endforeach; ?>
                </div>
                <p class="banner-help">Horário de São Paulo (America/Sao_Paulo). Agora: <?= escape(bannerNow()) ?>. Data inicial vazia: início imediato. Data final vazia: sem data final.</p>
                <div class="form-group"><label for="ad-status">Status</label><select id="ad-status" name="status" class="form-control">
                    <option value="pausado" <?= ($values['status'] ?? '') !== 'ativo' ? 'selected' : '' ?>>Pausado</option>
                    <option value="ativo" <?= ($values['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                </select></div>
                <p id="banner-upload-status" class="banner-help" role="status" aria-live="polite" hidden></p>
                <button class="btn btn-accent" type="submit"><?= $edit ? 'Salvar alterações' : 'Cadastrar campanha' ?></button>
                <?php if ($edit): ?><a class="btn btn-outline" href="banners.php">Cancelar edição</a><?php endif; ?>
            </form>
        </div>
        <div class="card banner-list">
            <div class="card-header"><h3 class="card-title">Campanhas (<?= count($banners) ?>)</h3></div>
            <?php if (!$banners): ?><p class="banner-help">Nenhuma campanha cadastrada.</p><?php endif; ?>
            <?php foreach ($banners as $banner):
                $places = json_decode($banner['categories'] ?? '[]', true) ?: [];
                if (!empty($banner['all_categories'])) $places = ['Todas as editorias'];
                if (!empty($banner['show_home'])) array_unshift($places, 'Home');
                $positions = json_decode($banner['slots'] ?? '[]', true) ?: [];
            ?>
            <article class="banner-campaign">
                <img class="banner-preview" src="<?= escape(bannerUrl($banner['imagem'])) ?>" alt="<?= escape($banner['titulo']) ?>" loading="lazy">
                <h4><?= escape($banner['titulo']) ?></h4>
                <p><?= escape($banner['anunciante'] ?? '') ?> · <strong><?= $banner['status'] === 'ativo' ? 'Ativo' : 'Pausado' ?></strong></p>
                <p class="banner-help"><?= escape(implode(', ', $places)) ?></p>
                <p class="banner-help"><?= escape(implode(', ', array_map(function ($slot) { return bannerSlots()[$slot] ?? $slot; }, $positions))) ?></p>
                <p class="banner-help"><?= escape($banner['starts_at'] ?? 'Início imediato') ?> → <?= escape($banner['ends_at'] ?? 'Sem data final') ?></p>
                <p class="banner-help"><?= escape(bannerVisibilityMessage($banner)) ?></p>
                <div class="banner-actions">
                    <a class="btn btn-sm btn-outline" href="banners.php?edit=<?= (int)$banner['id'] ?>">Editar</a>
                    <?php foreach (['toggle'=>($banner['status'] === 'ativo' ? 'Pausar' : 'Ativar'), 'delete'=>'Excluir'] as $action => $label): ?>
                    <form method="POST" action="banners.php" <?= $action === 'delete' ? 'onsubmit="return confirm(\'Excluir esta campanha?\')"' : '' ?>>
                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>"><input type="hidden" name="id" value="<?= (int)$banner['id'] ?>"><input type="hidden" name="action" value="<?= $action ?>">
                        <button class="btn btn-sm btn-outline" type="submit"><?= $label ?></button>
                    </form>
                    <?php endforeach; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<script src="<?= BASE_URL ?>/assets/js/banner-admin.js?v=<?= filemtime(__DIR__ . '/../assets/js/banner-admin.js') ?>" defer></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
