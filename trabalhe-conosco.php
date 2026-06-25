<?php
$page_title = "Trabalhe Conosco";
$page_desc = "Envie seu currículo e faça parte da equipe da Antena 1 Campo Grande.";
require_once __DIR__ . '/includes/header.php';

$mensagem_retorno = '';
$tipo_alerta = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = htmlspecialchars(trim($_POST['nome'] ?? ''));
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $telefone = htmlspecialchars(trim($_POST['telefone'] ?? ''));
    $vaga = htmlspecialchars(trim($_POST['vaga'] ?? ''));
    $mensagem_texto = htmlspecialchars(trim($_POST['mensagem'] ?? ''));

    // Validação básica
    if (empty($nome) || empty($email) || empty($vaga)) {
        $mensagem_retorno = "Por favor, preencha todos os campos obrigatórios.";
        $tipo_alerta = "error";
    } else {
        $to = "time@antena1cg.com.br";
        $subject = "Novo Currículo: " . $nome . " - Vaga: " . $vaga;
        
        $message = "Você recebeu um novo currículo através do site.\n\n";
        $message .= "Nome: $nome\n";
        $message .= "E-mail: $email\n";
        $message .= "Telefone/WhatsApp: $telefone\n";
        $message .= "Vaga Desejada: $vaga\n";
        $message .= "Mensagem Adicional:\n$mensagem_texto\n";

        $boundary = md5(time());
        $headers = "From: site@antena1cg.com.br\r\n"; 
        $headers .= "Reply-To: $email\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"" . $boundary . "\"\r\n\r\n";

        // Corpo do email (Texto)
        $body = "--" . $boundary . "\r\n";
        $body .= "Content-Type: text/plain; charset=utf-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $message . "\r\n\r\n";

        // Anexo (Currículo)
        $anexo_ok = false;
        if (isset($_FILES['curriculo']) && $_FILES['curriculo']['error'] === UPLOAD_ERR_OK) {
            $file_name = $_FILES['curriculo']['name'];
            $file_size = $_FILES['curriculo']['size'];
            $file_tmp = $_FILES['curriculo']['tmp_name'];
            $file_type = $_FILES['curriculo']['type'];

            // Limite de tamanho (5MB)
            if ($file_size <= 5242880) {
                $handle = fopen($file_tmp, "r");
                $content = fread($handle, $file_size);
                fclose($handle);
                $encoded_content = chunk_split(base64_encode($content));
                
                $body .= "--" . $boundary . "\r\n";
                $body .= "Content-Type: $file_type; name=\"" . basename($file_name) . "\"\r\n";
                $body .= "Content-Disposition: attachment; filename=\"" . basename($file_name) . "\"\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $body .= $encoded_content . "\r\n\r\n";
                $anexo_ok = true;
            } else {
                $mensagem_retorno = "O arquivo do currículo deve ter no máximo 5MB.";
                $tipo_alerta = "error";
            }
        } else {
            $mensagem_retorno = "É obrigatório anexar o seu currículo.";
            $tipo_alerta = "error";
        }

        $body .= "--" . $boundary . "--";

        // Enviar o email se não houver erro com o anexo
        if (empty($mensagem_retorno)) {
            if (mail($to, $subject, $body, $headers)) {
                $mensagem_retorno = "Currículo enviado com sucesso! Entraremos em contato em breve.";
                $tipo_alerta = "success";
            } else {
                $mensagem_retorno = "Ocorreu um erro ao enviar o currículo. Tente novamente mais tarde.";
                $tipo_alerta = "error";
            }
        }
    }
}
?>

<style>
.form-container {
    max-width: 700px;
    margin: 50px auto;
    background: var(--color-surface);
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: 1px solid var(--color-border);
}
.form-header {
    text-align: center;
    margin-bottom: 30px;
}
.form-header h1 {
    font-size: 2rem;
    color: var(--color-primary);
    margin-bottom: 10px;
    font-family: var(--font-title);
}
.form-header p {
    color: var(--color-text-muted);
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: var(--color-text);
}
.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 1rem;
    font-family: var(--font-family);
    background-color: var(--color-bg);
}
.form-control:focus {
    border-color: var(--color-primary);
    outline: none;
    box-shadow: 0 0 0 3px rgba(26, 54, 93, 0.1);
}
.btn-submit {
    background-color: var(--color-primary);
    color: #ffffff;
    border: none;
    padding: 15px 30px;
    font-size: 1.1rem;
    font-weight: bold;
    border-radius: 6px;
    cursor: pointer;
    width: 100%;
    transition: background-color 0.3s;
}
.btn-submit:hover {
    background-color: var(--color-primary-dark, #0f2544);
}
.alert {
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: bold;
}
.alert-success {
    background-color: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}
.alert-error {
    background-color: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}
.file-upload-wrapper {
    position: relative;
    border: 2px dashed #cbd5e1;
    border-radius: 6px;
    padding: 30px;
    text-align: center;
    background-color: var(--color-bg);
    cursor: pointer;
    transition: border-color 0.3s;
}
.file-upload-wrapper:hover {
    border-color: var(--color-primary);
}
.file-upload-wrapper input[type="file"] {
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    opacity: 0;
    cursor: pointer;
}
.file-upload-wrapper i {
    font-size: 2rem;
    color: var(--color-primary);
    margin-bottom: 10px;
}
</style>

<div class="container">
    <div class="form-container">
        <div class="form-header">
            <h1>FAÇA PARTE DO TIME</h1>
            <p>Envie seu currículo para <strong>time@antena1cg.com.br</strong> preenchendo o formulário abaixo.</p>
        </div>

        <?php if (!empty($mensagem_retorno)): ?>
            <div class="alert alert-<?= $tipo_alerta ?>">
                <?= $mensagem_retorno ?>
            </div>
        <?php endif; ?>

        <form action="trabalhe-conosco.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nome">Nome Completo *</label>
                <input type="text" id="nome" name="nome" class="form-control" required placeholder="Digite seu nome completo">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="email">E-mail *</label>
                    <input type="email" id="email" name="email" class="form-control" required placeholder="seu@email.com">
                </div>
                <div class="form-group">
                    <label for="telefone">WhatsApp / Telefone *</label>
                    <input type="text" id="telefone" name="telefone" class="form-control" required placeholder="(00) 00000-0000">
                </div>
            </div>

            <div class="form-group">
                <label for="vaga">Vaga Desejada *</label>
                <select id="vaga" name="vaga" class="form-control" required>
                    <option value="">Selecione uma área de interesse...</option>
                    <option value="Jornalismo / Redação">Jornalismo / Redação</option>
                    <option value="Locução / Apresentação">Locução / Apresentação</option>
                    <option value="Comercial / Vendas">Comercial / Vendas</option>
                    <option value="Técnico de Áudio / TI">Técnico de Áudio / TI</option>
                    <option value="Administrativo">Administrativo</option>
                    <option value="Outros">Outros</option>
                </select>
            </div>

            <div class="form-group">
                <label for="curriculo">Anexar Currículo (PDF ou DOC, máx 5MB) *</label>
                <div class="file-upload-wrapper">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <div id="file-name-display" style="font-weight: 500; color: var(--color-text);">Clique aqui para escolher um arquivo ou arraste e solte.</div>
                    <input type="file" id="curriculo" name="curriculo" accept=".pdf,.doc,.docx" required onchange="document.getElementById('file-name-display').textContent = this.files[0] ? this.files[0].name : 'Clique aqui para escolher um arquivo ou arraste e solte.'">
                </div>
            </div>

            <div class="form-group">
                <label for="mensagem">Mensagem Adicional (Opcional)</label>
                <textarea id="mensagem" name="mensagem" class="form-control" rows="4" placeholder="Fale um pouco sobre você e sua experiência..."></textarea>
            </div>

            <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Enviar Currículo</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
