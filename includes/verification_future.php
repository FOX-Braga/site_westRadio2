<?php
/**
 * SISTEMA DE VERIFICAÇÃO DE E-MAIL (ISOLADO PARA IMPLEMENTAÇÃO FUTURA)
 * 
 * Para ativar a verificação de e-mail no futuro:
 * 1. Mova esta função de volta para o arquivo 'includes/functions.php'.
 * 2. Restaure a lógica de verificação no formulário 'cadastro.php' e no 'login.php'
 *    (consulte o histórico do Git ou o arquivo de plano de implementação).
 * 3. Habilite a constante 'REQUIRE_EMAIL_VERIFICATION' no config.php.
 * 4. Ative a rota `/verificar` no router.php e as regras correspondentes no .htaccess.
 */

function sendVerificationEmail($email, $nome, $token) {
    $link = BASE_URL . "/verificar?token=" . $token;
    
    $subject = "Confirme seu cadastro no " . SITE_NAME;
    
    $message = "
    <html>
    <head>
      <title>Confirme seu cadastro</title>
      <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .header { background-color: #1b5e20; color: #fff; padding: 15px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { padding: 20px; }
        .btn { display: inline-block; padding: 12px 24px; background-color: #1b5e20; color: #fff !important; text-decoration: none; font-weight: bold; border-radius: 4px; margin-top: 15px; }
        .footer { margin-top: 20px; font-size: 0.8rem; color: #777; text-align: center; }
      </style>
    </head>
    <body>
      <div class='container'>
        <div class='header'>
          <h2>" . SITE_NAME . "</h2>
        </div>
        <div class='content'>
          <p>Olá, <strong>" . escape($nome) . "</strong>!</p>
          <p>Obrigado por se cadastrar no " . SITE_NAME . ". Para ativar sua conta e ter acesso completo ao portal (como comentar e curtir notícias), por favor clique no botão abaixo:</p>
          <p style='text-align: center;'>
            <a href='" . $link . "' class='btn'>Confirmar Meu E-mail</a>
          </p>
          <p style='font-size: 0.9rem; color: #555; margin-top: 20px;'>Se o botão acima não funcionar, copie e cole o seguinte link no seu navegador:</p>
          <p style='word-break: break-all; font-size: 0.9rem;'><a href='" . $link . "'>" . $link . "</a></p>
        </div>
        <div class='footer'>
          <p>&copy; " . date('Y') . " " . SITE_NAME . ". Todos os direitos reservados.</p>
        </div>
      </div>
    </body>
    </html>
    ";

    // Se estiver rodando em desenvolvimento local, salva em um arquivo local para testes práticos
    if (defined('DB_DRIVER') && DB_DRIVER === 'sqlite') {
        $emailDir = __DIR__ . '/../uploads/emails/';
        if (!is_dir($emailDir)) {
            mkdir($emailDir, 0755, true);
        }
        $filename = $emailDir . strtolower(preg_replace('/[^a-z0-9@.-]/', '_', $email)) . '_' . substr($token, 0, 8) . '.html';
        file_put_contents($filename, $message);
        return true;
    }

    // Envio real em ambiente de produção (HostGator mail)
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n";
    
    return @mail($email, $subject, $message, $headers);
}
