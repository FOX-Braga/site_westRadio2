<?php
$page_title = isset($_GET['id']) ? "Editar Vídeo/Live" : "Adicionar Vídeo/Live";
require_once __DIR__ . '/includes/header.php';

$pdo = Database::getInstance();

$id = $_GET['id'] ?? null;
$video = null;

if ($id && is_numeric($id)) {
    $stmt = $pdo->prepare("SELECT * FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    $video = $stmt->fetch();
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrf_token)) {
        die("Erro de segurança (CSRF). Ação não permitida.");
    }
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $youtube_id = trim($_POST['youtube_id']);
    $tipo = $_POST['tipo'];
    $duracao = trim($_POST['duracao']);
    
    // Validações básicas
    if (empty($titulo)) {
        $erro = "O título é obrigatório.";
    } elseif (empty($youtube_id)) {
        $erro = "O ID do vídeo do YouTube é obrigatório.";
    } else {
        // Se for live, a duração é forçada para 'AO VIVO'
        if ($tipo === 'live') {
            $duracao = 'AO VIVO';
        } elseif (empty($duracao)) {
            $duracao = '00:00';
        }
        
        try {
            if ($id) {
                // Atualizar
                $stmt = $pdo->prepare("UPDATE videos SET titulo = ?, descricao = ?, youtube_id = ?, tipo = ?, duracao = ? WHERE id = ?");
                $stmt->execute([$titulo, $descricao, $youtube_id, $tipo, $duracao, $id]);
                $sucesso = "Vídeo atualizado com sucesso!";
            } else {
                // Inserir
                $stmt = $pdo->prepare("INSERT INTO videos (titulo, descricao, youtube_id, tipo, duracao) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$titulo, $descricao, $youtube_id, $tipo, $duracao]);
                $sucesso = "Vídeo adicionado com sucesso!";
            }
            
            header("Location: videos.php");
            exit;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'unique') !== false || strpos($e->getMessage(), 'duplicate') !== false) {
                $erro = "Este ID do YouTube já está cadastrado no sistema.";
            } else {
                $erro = "Erro ao salvar no banco de dados: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <h3 style="margin-bottom: 20px;"><?= $video ? 'Editar Vídeo/Live' : 'Cadastrar Novo Vídeo ou Live' ?></h3>
    
    <?php if ($erro): ?>
        <div style="padding: 10px; background-color: #fee2e2; color: #991b1b; margin-bottom: 15px; border-radius: 4px;"><?= escape($erro) ?></div>
    <?php endif; ?>
    
    <form action="" method="POST">
        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
        <div class="form-group">
            <label>Título</label>
            <input type="text" name="titulo" class="form-control" value="<?= $video ? escape($video['titulo']) : '' ?>" placeholder="Ex: Bastidores da Transmissão" required>
        </div>
        
        <div class="form-group">
            <label>Descrição</label>
            <textarea name="descricao" class="form-control" rows="4" placeholder="Abraço especial aos ouvintes..." style="resize: vertical;"><?= $video ? escape($video['descricao']) : '' ?></textarea>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label>ID do Vídeo do YouTube</label>
                <input type="text" name="youtube_id" class="form-control" value="<?= $video ? escape($video['youtube_id']) : '' ?>" placeholder="Ex: yP5S7V7g3p0" required>
                <small style="color: var(--admin-text-light); font-size: 0.75rem; margin-top: 4px; display: block;">Copie os 11 caracteres após o 'v=' na URL do vídeo.</small>
            </div>
            
            <div class="form-group">
                <label>Tipo</label>
                <select name="tipo" class="form-control" id="video-type-select" onchange="toggleDurationField()" required>
                    <option value="video" <?= ($video && $video['tipo'] === 'video') ? 'selected' : '' ?>>Vídeo Gravado</option>
                    <option value="live" <?= ($video && $video['tipo'] === 'live') ? 'selected' : '' ?>>Transmissão Ao Vivo (Live)</option>
                </select>
            </div>
        </div>
        
        <div class="form-group" id="duration-field-container">
            <label>Duração (MM:SS)</label>
            <input type="text" name="duracao" class="form-control" id="duration-input" value="<?= $video ? escape($video['duracao']) : '' ?>" placeholder="Ex: 12:45">
        </div>
        
        <div style="display: flex; gap: 15px; margin-top: 30px;">
            <button type="submit" class="btn" style="flex-grow: 1;">Salvar</button>
            <a href="videos.php" class="btn btn-outline" style="text-align: center; width: 120px;">Voltar</a>
        </div>
    </form>
</div>

<script>
function toggleDurationField() {
    const select = document.getElementById('video-type-select');
    const container = document.getElementById('duration-field-container');
    const input = document.getElementById('duration-input');
    
    if (select.value === 'live') {
        container.style.opacity = '0.5';
        input.value = 'AO VIVO';
        input.readOnly = true;
    } else {
        container.style.opacity = '1';
        if (input.value === 'AO VIVO') {
            input.value = '';
        }
        input.readOnly = false;
    }
}

// Rodar na inicialização
document.addEventListener('DOMContentLoaded', toggleDurationField);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
