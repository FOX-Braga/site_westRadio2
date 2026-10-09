<?php
// O PHP esvazia POST/FILES quando o corpo excede post_max_size, antes do CSRF.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $postLimit = trim(ini_get('post_max_size'));
    $unit = strtolower(substr($postLimit, -1));
    $multipliers = ['k'=>1024, 'm'=>1048576, 'g'=>1073741824];
    $limitBytes = (float) $postLimit * ($multipliers[$unit] ?? 1);
    if ($limitBytes > 0 && (float) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $limitBytes) {
        http_response_code(413);
        header('Content-Type: text/plain; charset=utf-8');
        exit('O envio excedeu o limite de ' . $postLimit . ' do servidor. Reduza as imagens ou o conteúdo e tente novamente.');
    }
}
$page_title = isset($_GET['id']) ? "Editar Matéria" : "Nova Matéria";

// Incluir conexões básicas antes do HTML para poder fazer redirects
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requirePanelAccess();
require_once __DIR__ . '/../includes/editorial_uploads.php';
require_once __DIR__ . '/../includes/news_queries.php';

$pdo = Database::getInstance();
$hasAuthorName = newsHasColumn($pdo, 'autor_nome');
$hasUrgent = newsHasColumn($pdo, 'urgente');
$hasSchedule = newsHasColumn($pdo, 'data_agendamento');

$id = $_GET['id'] ?? null;
$noticia = null;

if ($id) {
    requireNewsAccess($id);
    $stmt = $pdo->prepare("SELECT * FROM noticias WHERE id = ?");
    $stmt->execute([$id]);
    $noticia = $stmt->fetch();
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrf_token)) {
        http_response_code(403);
        die("Erro de segurança (CSRF). Ação não permitida.");
    }
    $titulo = trim($_POST['titulo']);
    $subtitulo = trim($_POST['subtitulo']);
    $categoria_id = $_POST['categoria_id'];
    
    $contentToken = $_POST['conteudo_token'] ?? '';
    $coverToken = $_POST['imagem_token'] ?? '';
    try {
        if ($contentToken !== '') {
            editorialReadUpload($contentToken, 'content');
            $conteudo = file_get_contents(editorialUploadPath($contentToken) . '.part');
            if ($conteudo === false) throw new RuntimeException('Não foi possível ler o texto enviado.');
        } elseif (!empty($_POST['conteudo_b64'])) {
            // Compatibilidade com formularios antigos, sem desativar CSRF.
            $conteudo = base64_decode($_POST['conteudo_b64'], true);
            if ($conteudo === false) throw new RuntimeException('O texto recebido está incompleto. Tente novamente.');
        } else {
            $conteudo = $_POST['conteudo'] ?? '';
        }
    } catch (RuntimeException $error) { $erro = $error->getMessage(); $conteudo = ''; }
    $status = $_POST['status'];
    $data_agendamento = !empty($_POST['data_agendamento']) ? date('Y-m-d H:i:s', strtotime($_POST['data_agendamento'])) : null;
    $destaque = isAdmin() ? (isset($_POST['destaque']) ? 1 : 0) : (int) ($noticia['destaque'] ?? 0);
    $urgente = isAdmin() ? (isset($_POST['urgente']) ? 1 : 0) : (int) ($noticia['urgente'] ?? 0);
    if ($data_agendamento !== null && !$hasSchedule) $erro = 'O banco ainda nao possui suporte a agendamento. Remova a data para publicar agora.';
    if ($urgente && !$hasUrgent) $erro = 'O banco ainda nao possui suporte a noticias urgentes. Desmarque essa opcao para salvar.';
    $slug = createSlug($titulo);
    
    // Garantir que o slug seja único
    $stmtSlug = $pdo->prepare("SELECT COUNT(*) FROM noticias WHERE slug = ? AND id != ?");
    $stmtSlug->execute([$slug, $id ?? 0]);
    if ($stmtSlug->fetchColumn() > 0) {
        $slug = $slug . '-' . time();
    }

    $imagem_destacada = $noticia['imagem_destacada'] ?? null;

    try {
        if ($coverToken !== '') {
            $uploadedCover = editorialReadUpload($coverToken, 'cover');
            $imagem_destacada = $uploadedCover['filename'];
        } elseif (isset($_FILES['imagem']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['imagem']['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('A imagem excedeu o limite de envio direto do servidor. Use o editor com JavaScript para enviá-la em partes.');
            // Sem limite fixo de 5 MB e sem converter a capa para Base64 no banco.
            $imagem_destacada = editorialImageFile($_FILES['imagem']['tmp_name']);
        }
    } catch (RuntimeException $error) { $erro = $error->getMessage(); }
    if (empty($erro)) {
        $autor_id_post = null;
        if (isAdmin() && isset($_POST['autor_id'])) {
            $autor_id_post = !empty($_POST['autor_id']) ? (int)$_POST['autor_id'] : null;
        } else {
            $autor_id_post = $_SESSION['user_id'] ?? null;
        }

        // Determina o nome do autor para desacoplamento completo do usuário
        $autor_nome_post = null;
        if ($autor_id_post) {
            $stmtAutor = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
            $stmtAutor->execute([$autor_id_post]);
            $autor_nome_post = $stmtAutor->fetchColumn();
        }
        if (!$autor_nome_post) {
            $autor_nome_post = $noticia['autor_nome'] ?? $_SESSION['user_nome'] ?? 'Redação';
        }

        // Apenas campos existentes entram no INSERT/UPDATE; bancos antigos continuam funcionando.
        $values = [
            'titulo'=>$titulo, 'subtitulo'=>$subtitulo, 'slug'=>$slug, 'conteudo'=>$conteudo,
            'imagem_destacada'=>$imagem_destacada, 'autor_id'=>$autor_id_post,
            'categoria_id'=>$categoria_id, 'status'=>$status, 'destaque'=>$destaque,
        ];
        if ($hasAuthorName) $values['autor_nome'] = $autor_nome_post;
        if ($hasUrgent) $values['urgente'] = $urgente;
        if ($hasSchedule) $values['data_agendamento'] = $data_agendamento;
        try {
            $pdo->beginTransaction();
            if (isAdmin() && $destaque == 1) $pdo->exec("UPDATE noticias SET destaque = 0");
            if ($id) {
                $assignments = implode(', ', array_map(function ($column) { return $column . '=?'; }, array_keys($values)));
                $stmt = $pdo->prepare("UPDATE noticias SET $assignments WHERE id=?");
                $stmt->execute(array_merge(array_values($values), [$id]));
            } else {
                $columns = implode(', ', array_keys($values));
                $placeholders = implode(', ', array_fill(0, count($values), '?'));
                $stmt = $pdo->prepare("INSERT INTO noticias ($columns) VALUES ($placeholders)");
                $stmt->execute(array_values($values));
            }
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Falha ao salvar noticia: ' . $e->getMessage());
            $erro = 'Nao foi possivel salvar a materia. O erro foi registrado no log da hospedagem.';
        }
        if (empty($erro)) {
            if ($contentToken !== '') editorialConsumeUpload($contentToken, 'content');
            if ($coverToken !== '') editorialConsumeUpload($coverToken, 'cover');
            header("Location: noticias.php");
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';

$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll();

$autores = [];
if (isAdmin()) {
    $autores = $pdo->query("SELECT id, nome, tipo FROM usuarios WHERE tipo IN ('admin', 'jornalista', 'colunista') ORDER BY nome ASC")->fetchAll();
}

$autor_id_pre = $_GET['autor_id'] ?? ($noticia['autor_id'] ?? $_SESSION['user_id']);

$cat_slug_pre = $_GET['cat_slug'] ?? '';
$categoria_id_pre = $_POST['categoria_id'] ?? $noticia['categoria_id'] ?? '';
if (!$categoria_id_pre && $cat_slug_pre) {
    $stmt = $pdo->prepare("SELECT id FROM categorias WHERE slug = ?");
    $stmt->execute([$cat_slug_pre]);
    $categoria_id_pre = $stmt->fetchColumn();
}
?>

<!-- Topo do Formulário -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 style="font-size: 1.4rem; font-weight: 800; margin: 0; color: var(--adm-text-main);">
            <?= $noticia ? 'Editar Matéria' : 'Nova Matéria' ?>
        </h2>
        <p style="margin: 4px 0 0 0; color: var(--adm-text-muted); font-size: 0.875rem;">
            <?= $noticia ? 'Atualize as informações editoriais da matéria' : 'Preencha o conteúdo editorial e configure a publicação' ?>
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="noticias.php" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Voltar à Lista
        </a>
    </div>
</div>

<?php if ($erro): ?>
    <div style="padding: 14px 18px; background-color: var(--adm-danger-bg); color: var(--adm-danger-text); margin-bottom: 24px; border-radius: var(--adm-radius-sm); font-weight: 600; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-exclamation-triangle"></i> <?= escape($erro) ?>
    </div>
<?php endif; ?>

<form action="" method="POST" enctype="multipart/form-data" id="form-noticia" data-upload-url="<?= escape(BASE_URL . '/admin/upload.php') ?>">
    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
    <input type="hidden" name="imagem_token" value="<?= escape($_POST['imagem_token'] ?? '') ?>">
    <input type="hidden" name="conteudo_token" value="">
    <div id="editor-upload-status" role="status" aria-live="polite" hidden style="padding:12px 16px;margin-bottom:16px;border:1px solid var(--adm-border);border-radius:8px;"></div>

    <div class="editorial-grid">
        
        <!-- Coluna Esquerda: Conteúdo Principal -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-pen-fancy"></i> Texto Editorial
                    </h3>
                </div>
                
                <div class="form-group">
                    <label>Título da Matéria <span style="color: var(--adm-accent);">*</span></label>
                    <input type="text" name="titulo" class="form-control" value="<?= escape($_POST['titulo'] ?? ($noticia['titulo'] ?? '')) ?>" placeholder="Escreva uma manchete clara, atrativa e informativa..." style="font-size: 1.15rem; font-weight: 700; padding: 14px 16px;" required>
                </div>
                
                <div class="form-group">
                    <label>Subtítulo / Linha Fina (Opcional)</label>
                    <input type="text" name="subtitulo" class="form-control" value="<?= escape($_POST['subtitulo'] ?? ($noticia['subtitulo'] ?? '')) ?>" placeholder="Resumo de uma frase que complementa o título na home e na página...">
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Corpo do Texto</label>
                    <input type="hidden" name="conteudo">
                    <input type="hidden" name="conteudo_b64" id="conteudo_b64">
                    <div id="editor-container"><?= isset($conteudo) && $_SERVER['REQUEST_METHOD'] === 'POST' ? $conteudo : ($noticia['conteudo'] ?? '') ?></div>
                </div>
            </div>
        </div>

        <!-- Coluna Direita: Metadados, Imagem e Publicação -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            
            <!-- Card de Publicação -->
            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-paper-plane"></i> Publicação
                    </h3>
                </div>

                <div class="form-group">
                    <label>Status da Notícia</label>
                    <select name="status" class="form-control" style="font-weight: 600;">
                        <option value="publicado" <?= (!$noticia || ($noticia && $noticia['status'] == 'publicado')) ? 'selected' : '' ?>>
                            🟢 Publicado (No ar no portal)
                        </option>
                        <option value="rascunho" <?= ($noticia && $noticia['status'] == 'rascunho') ? 'selected' : '' ?>>
                            🟡 Rascunho (Apenas na redação)
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Categoria <span style="color: var(--adm-accent);">*</span></label>
                    <select name="categoria_id" class="form-control" required>
                        <option value="">Selecione uma categoria...</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($categoria_id_pre == $cat['id']) ? 'selected' : '' ?>><?= escape($cat['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if (isAdmin()): ?>
                <div class="form-group">
                    <label>Autor / Assinatura</label>
                    <select name="autor_id" class="form-control">
                        <option value="" <?= empty($autor_id_pre) ? 'selected' : '' ?>>-- Redação 96 News --</option>
                        <?php foreach ($autores as $autor): ?>
                            <option value="<?= $autor['id'] ?>" <?= ($autor_id_pre == $autor['id']) ? 'selected' : '' ?>>
                                <?= escape($autor['nome']) ?> (<?= ucfirst($autor['tipo']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: var(--adm-text-muted); font-size: 0.75rem; display: block; margin-top: 5px;">
                        <i class="fas fa-shield-alt"></i> A matéria permanecerá preservada mesmo se o perfil for excluído futuramente.
                    </small>
                </div>
                <?php endif; ?>

                <div class="form-group" style="margin-bottom: 0;">
                    <label>Agendar Publicação (Opcional)</label>
                    <input type="datetime-local" name="data_agendamento" class="form-control" value="<?= $noticia && isset($noticia['data_agendamento']) ? escape(date('Y-m-d\TH:i', strtotime($noticia['data_agendamento']))) : '' ?>">
                </div>
            </div>

            <!-- Card Imagem Destacada -->
            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-image"></i> Imagem Principal
                    </h3>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <div class="image-preview-wrapper">
                        <?php if ($noticia && $noticia['imagem_destacada']): ?>
                            <img id="image-preview-target" 
                                 src="<?= str_starts_with($noticia['imagem_destacada'], 'data:') ? $noticia['imagem_destacada'] : BASE_URL . '/uploads/noticias/' . escape($noticia['imagem_destacada']) ?>" 
                                 alt="Imagem da notícia"
                                 style="width: 100%; max-height: 220px; object-fit: cover; border-radius: var(--adm-radius-sm); border: 1px solid var(--adm-border); margin-bottom: 12px; display: block;">
                        <?php endif; ?>
                    </div>

                    <label><?= ($noticia && $noticia['imagem_destacada']) ? 'Substituir Imagem' : 'Enviar Foto de Capa' ?></label>
                    <input type="file" name="imagem" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif" <?= !$noticia && empty($_POST['imagem_token']) ? 'required' : '' ?> style="padding: 10px;">
                    <small style="display: block; margin-top: 6px; color: var(--adm-text-muted); font-size: 0.75rem;">
                        JPG, PNG, WEBP ou GIF. Arquivos grandes são enviados em partes, com progresso. Proporção recomendada: 16:9.
                    </small>
                </div>
            </div>

            <?php if (isAdmin()): ?>
            <!-- Card Destaques & Visibilidade -->
            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bullseye"></i> Posição no Portal
                    </h3>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="destaque" <?= ($noticia && $noticia['destaque']) ? 'checked' : '' ?> style="width: 18px; height: 18px; margin-top: 2px; accent-color: var(--adm-primary);">
                        <div>
                            <span style="font-weight: 700; color: var(--adm-text-main); font-size: 0.9rem;">Manchete Principal</span>
                            <span style="display: block; font-size: 0.75rem; color: var(--adm-text-muted); font-weight: normal; margin-top: 2px;">
                                Exibe em destaque no topo da página inicial do portal.
                            </span>
                        </div>
                    </label>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="urgente" <?= ($noticia && ($noticia['urgente'] ?? 0)) ? 'checked' : '' ?> style="width: 18px; height: 18px; margin-top: 2px; accent-color: var(--adm-accent);">
                        <div>
                            <span style="font-weight: 700; color: var(--adm-accent); font-size: 0.9rem;">Alerta de Plantão (Urgente)</span>
                            <span style="display: block; font-size: 0.75rem; color: var(--adm-text-muted); font-weight: normal; margin-top: 2px;">
                                Ativa a tarja de notícia urgente em tempo real no topo do site.
                            </span>
                        </div>
                    </label>
                </div>
            </div>

            <?php endif; ?>
            <!-- Botão Salvar / Publicar -->
            <button type="submit" class="btn btn-accent" style="width: 100%; padding: 14px; font-size: 1rem; font-weight: 700; box-shadow: var(--adm-shadow-md);">
                <i class="fas fa-check"></i> <?= $noticia ? 'Salvar Alterações' : 'Publicar no Portal' ?>
            </button>

        </div>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
