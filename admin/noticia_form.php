<?php
$page_title = isset($_GET['id']) ? "Editar Notícia" : "Nova Notícia";

// Incluir conexões básicas antes do HTML para poder fazer redirects
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requirePanelAccess();

$pdo = Database::getInstance();

$id = $_GET['id'] ?? null;
$noticia = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM noticias WHERE id = ?");
    $stmt->execute([$id]);
    $noticia = $stmt->fetch();
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrf_token)) {
        die("Erro de segurança (CSRF). Ação não permitida.");
    }
    $titulo = trim($_POST['titulo']);
    $subtitulo = trim($_POST['subtitulo']);
    $categoria_id = $_POST['categoria_id'];
    $conteudo = $_POST['conteudo']; // HTML gerado pelo Quill
    $status = $_POST['status'];
    $data_agendamento = !empty($_POST['data_agendamento']) ? date('Y-m-d H:i:s', strtotime($_POST['data_agendamento'])) : null;
    $destaque = isset($_POST['destaque']) ? 1 : 0;
    $urgente = isset($_POST['urgente']) ? 1 : 0;
    $slug = createSlug($titulo);
    
    // Garantir que o slug seja único
    $stmtSlug = $pdo->prepare("SELECT COUNT(*) FROM noticias WHERE slug = ? AND id != ?");
    $stmtSlug->execute([$slug, $id ?? 0]);
    if ($stmtSlug->fetchColumn() > 0) {
        $slug = $slug . '-' . time();
    }

    $imagem_destacada = $noticia['imagem_destacada'] ?? null;

    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['imagem'];
        if ($file['size'] > 5242880) { // 5MB limit
            $erro = 'Arquivo excede o limite de 5MB.';
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($mime, $allowed_mimes)) {
                $erro = 'O arquivo enviado não é uma imagem válida.';
            } else {
                $base64 = base64_encode(file_get_contents($file['tmp_name']));
                $imagem_destacada = 'data:' . $mime . ';base64,' . $base64;
                
                // Remove a capa antiga do servidor se era um arquivo físico antigo
                if (!empty($noticia['imagem_destacada']) && !str_starts_with($noticia['imagem_destacada'], 'data:') && file_exists(__DIR__ . '/../uploads/noticias/' . $noticia['imagem_destacada'])) {
                    unlink(__DIR__ . '/../uploads/noticias/' . $noticia['imagem_destacada']);
                }
            }
        }
    }

    if (empty($erro)) {
        // Se a notícia atual for marcada como destaque, remove o destaque de todas as outras
        if ($destaque == 1) {
            $pdo->query("UPDATE noticias SET destaque = 0");
        }

        $autor_id_post = (isAdmin() && isset($_POST['autor_id'])) ? $_POST['autor_id'] : $_SESSION['user_id'];

        if ($id) {
            try {
                $stmt = $pdo->prepare("UPDATE noticias SET titulo=?, subtitulo=?, slug=?, conteudo=?, imagem_destacada=?, autor_id=?, categoria_id=?, status=?, destaque=?, urgente=?, data_agendamento=? WHERE id=?");
                $stmt->execute([$titulo, $subtitulo, $slug, $conteudo, $imagem_destacada, $autor_id_post, $categoria_id, $status, $destaque, $urgente, $data_agendamento, $id]);
                header("Location: noticias.php");
                exit;
            } catch (PDOException $e) {
                $erro = "Erro ao atualizar no banco: " . $e->getMessage();
            }
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO noticias (titulo, subtitulo, slug, conteudo, imagem_destacada, autor_id, categoria_id, status, destaque, urgente, data_agendamento) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$titulo, $subtitulo, $slug, $conteudo, $imagem_destacada, $autor_id_post, $categoria_id, $status, $destaque, $urgente, $data_agendamento]);
                header("Location: noticias.php");
                exit;
            } catch (PDOException $e) {
                $erro = "Erro ao inserir no banco: " . $e->getMessage();
            }
        }
    }
}

// Agora que todo o processamento e redirects (se houver) já foram feitos, incluímos o HTML visual:
require_once __DIR__ . '/includes/header.php';

$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll();

$autores = [];
if (isAdmin()) {
    $autores = $pdo->query("SELECT id, nome, tipo FROM usuarios WHERE tipo IN ('admin', 'jornalista', 'colunista') ORDER BY nome ASC")->fetchAll();
}

$autor_id_pre = $_GET['autor_id'] ?? ($noticia['autor_id'] ?? $_SESSION['user_id']);

$cat_slug_pre = $_GET['cat_slug'] ?? '';
$categoria_id_pre = $noticia['categoria_id'] ?? '';
if (!$categoria_id_pre && $cat_slug_pre) {
    $stmt = $pdo->prepare("SELECT id FROM categorias WHERE slug = ?");
    $stmt->execute([$cat_slug_pre]);
    $categoria_id_pre = $stmt->fetchColumn();
}
?>

    <?php if ($erro): ?>
        <div style="padding: 15px; background-color: #fee2e2; color: #991b1b; margin-bottom: 25px; border-radius: 6px; border-left: 4px solid #ef4444; font-weight: 500;">
            <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i> <?= escape($erro) ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" id="form-noticia">
        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h2 style="margin:0; font-size: 1.6rem; color: var(--admin-primary);"><i class="fas fa-pen-nib" style="margin-right: 10px;"></i> <?= $noticia ? 'Editar Notícia' : 'Nova Notícia' ?></h2>
            <a href="noticias.php" class="btn btn-outline" style="padding: 8px 16px;"><i class="fas fa-arrow-left" style="margin-right: 5px;"></i> Voltar para Lista</a>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            
            <!-- Coluna Esquerda: Conteúdo -->
            <div style="display: flex; flex-direction: column; gap: 25px;">
                <div class="card" style="margin-bottom: 0;">
                    <h3 style="font-size: 1.1rem; color: var(--admin-primary); border-bottom: 1px solid var(--admin-border); padding-bottom: 12px; margin-bottom: 20px;">
                        <i class="fas fa-align-left" style="margin-right: 8px;"></i> Conteúdo Editorial
                    </h3>
                    
                    <div class="form-group">
                        <label>Título da Notícia</label>
                        <input type="text" name="titulo" class="form-control" value="<?= $noticia ? escape($noticia['titulo']) : '' ?>" placeholder="Escreva um título forte e atrativo..." style="font-size: 1.2rem; font-weight: 600; padding: 15px;" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Subtítulo / Linha Fina (Opcional)</label>
                        <input type="text" name="subtitulo" class="form-control" value="<?= $noticia ? escape($noticia['subtitulo']) : '' ?>" placeholder="Resumo de uma frase para complementar o título principal...">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Corpo do Texto</label>
                        <input type="hidden" name="conteudo">
                        <div id="editor-container" style="height: 450px; background: #ffffff; font-size: 1.05rem; border-color: var(--admin-border);"><?= $noticia ? $noticia['conteudo'] : '' ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Coluna Direita: Metadados e Opções -->
            <div style="display: flex; flex-direction: column; gap: 25px;">
                
                <!-- Card Configurações -->
                <div class="card" style="margin-bottom: 0;">
                    <h3 style="font-size: 1.1rem; color: var(--admin-primary); border-bottom: 1px solid var(--admin-border); padding-bottom: 12px; margin-bottom: 20px;">
                        <i class="fas fa-sliders-h" style="margin-right: 8px;"></i> Publicação
                    </h3>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control" style="font-weight: bold;">
                            <option value="publicado" <?= (!$noticia || ($noticia && $noticia['status'] == 'publicado')) ? 'selected' : '' ?>>🟢 Publicado (Visível no site)</option>
                            <option value="rascunho" <?= ($noticia && $noticia['status'] == 'rascunho') ? 'selected' : '' ?>>🟡 Rascunho (Apenas no painel)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Agendar para (Opcional)</label>
                        <input type="datetime-local" name="data_agendamento" class="form-control" value="<?= $noticia && isset($noticia['data_agendamento']) ? escape(date('Y-m-d\TH:i', strtotime($noticia['data_agendamento']))) : '' ?>">
                        <small style="display: block; margin-top: 5px; color: var(--admin-text-light); font-size: 0.8rem; font-weight: normal;">Se preenchido e salvo como <b>Rascunho</b>, será publicado automaticamente nesta data.</small>
                    </div>

                    <div class="form-group">
                        <label>Categoria</label>
                        <select name="categoria_id" class="form-control" required>
                            <option value="">Selecione uma editoria...</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($categoria_id_pre == $cat['id']) ? 'selected' : '' ?>><?= escape($cat['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if (isAdmin()): ?>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Autor / Colunista</label>
                        <select name="autor_id" class="form-control" required>
                            <?php foreach ($autores as $autor): ?>
                                <option value="<?= $autor['id'] ?>" <?= ($autor_id_pre == $autor['id']) ? 'selected' : '' ?>><?= escape($autor['nome']) ?> (<?= ucfirst($autor['tipo']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Card Imagem -->
                <div class="card" style="margin-bottom: 0;">
                    <h3 style="font-size: 1.1rem; color: var(--admin-primary); border-bottom: 1px solid var(--admin-border); padding-bottom: 12px; margin-bottom: 20px;">
                        <i class="fas fa-camera" style="margin-right: 8px;"></i> Mídia Principal
                    </h3>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <?php if ($noticia && $noticia['imagem_destacada']): ?>
                            <div style="margin-bottom: 15px; border: 1px solid var(--admin-border); border-radius: 6px; padding: 4px; background: var(--admin-bg);">
                                <img src="<?= str_starts_with($noticia['imagem_destacada'], 'data:') ? $noticia['imagem_destacada'] : BASE_URL . '/uploads/noticias/' . escape($noticia['imagem_destacada']) ?>" style="width: 100%; height: auto; display: block; border-radius: 4px;">
                            </div>
                            <label>Trocar Imagem</label>
                        <?php else: ?>
                            <label>Enviar Imagem Destacada</label>
                        <?php endif; ?>
                        <input type="file" name="imagem" class="form-control" accept="image/*" <?= !$noticia ? 'required' : '' ?> style="padding: 10px;">
                        <small style="display: block; margin-top: 8px; color: var(--admin-text-light); font-size: 0.75rem; text-transform: none; font-weight: normal;">
                            Formatos aceitos: JPG, PNG, WEBP. Tamanho máximo: 5MB.<br>Proporção ideal: 16:9 (Horizontal).
                        </small>
                    </div>
                </div>
                
                <!-- Card Destaques -->
                <div class="card" style="margin-bottom: 0;">
                    <h3 style="font-size: 1.1rem; color: var(--admin-primary); border-bottom: 1px solid var(--admin-border); padding-bottom: 12px; margin-bottom: 20px;">
                        <i class="fas fa-star" style="margin-right: 8px;"></i> Visibilidade Especial
                    </h3>
                    
                    <div class="form-group" style="background: var(--admin-bg); padding: 15px; border-radius: 6px; border: 1px solid var(--admin-border); margin-bottom: 15px; transition: all 0.2s;">
                        <label style="display: flex; align-items: flex-start; gap: 12px; margin: 0; cursor: pointer;">
                            <input type="checkbox" name="destaque" <?= ($noticia && $noticia['destaque']) ? 'checked' : '' ?> style="width: 20px; height: 20px; margin-top: 2px;">
                            <div>
                                <span style="display: block; font-weight: bold; color: var(--admin-text);">Manchete Principal do Site</span>
                                <span style="display: block; font-size: 0.75rem; color: var(--admin-text-light); text-transform: none; font-weight: normal; margin-top: 4px; line-height: 1.4;">Ativar isto substituirá a grande manchete no topo da página inicial por esta notícia.</span>
                            </div>
                        </label>
                    </div>
                    
                    <div class="form-group" style="background: rgba(200, 16, 46, 0.05); padding: 15px; border-radius: 6px; border: 1px solid rgba(200, 16, 46, 0.2); margin-bottom: 0; transition: all 0.2s;">
                        <label style="display: flex; align-items: flex-start; gap: 12px; margin: 0; cursor: pointer;">
                            <input type="checkbox" name="urgente" <?= ($noticia && $noticia['urgente']) ? 'checked' : '' ?> style="width: 20px; height: 20px; margin-top: 2px;">
                            <div>
                                <span style="display: block; font-weight: bold; color: var(--admin-accent);">Alerta de Plantão (Urgente)</span>
                                <span style="display: block; font-size: 0.75rem; color: var(--admin-text-light); text-transform: none; font-weight: normal; margin-top: 4px; line-height: 1.4;">Cria uma barra vermelha "Plantão" altamente visível no topo de todas as páginas.</span>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Botão de Ação -->
                <button type="submit" class="btn" style="width: 100%; padding: 18px; font-size: 1.1rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; box-shadow: var(--shadow-md); margin-top: 10px;">
                    <i class="fas fa-save" style="margin-right: 8px;"></i> <?= $noticia ? 'Salvar Alterações' : 'Publicar Notícia' ?>
                </button>
            </div>
        </div>
    </form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
