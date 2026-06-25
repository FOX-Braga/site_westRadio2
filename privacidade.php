<?php
$page_title = "Política de Privacidade - West News";
require_once __DIR__ . '/includes/header.php';
?>

<div style="background-color: var(--color-surface); padding: 50px 0; border-bottom: 1px solid var(--color-border); margin-bottom: 40px;">
    <div class="container" style="max-width: 800px;">
        <h1 style="font-family: var(--font-title); font-size: 2.5rem; color: var(--color-primary); margin-bottom: 10px;">Política de Privacidade</h1>
        <p style="color: var(--color-text-muted); font-size: 1.1rem;">Última atualização: <?= date('d/m/Y') ?></p>
    </div>
</div>

<div class="container" style="max-width: 800px; margin-bottom: 80px; min-height: 50vh;">
    
    <div style="font-size: 1.1rem; line-height: 1.8; color: var(--color-text); font-family: var(--font-family);">
        <p style="margin-bottom: 25px;">A privacidade dos nossos leitores é de extrema importância para o <strong>West News</strong>. Esta Política de Privacidade descreve de que forma coletamos, usamos, armazenamos e protegemos as suas informações pessoais ao acessar nosso portal, em conformidade com a Lei Geral de Proteção de Dados (LGPD - Lei nº 13.709/2018).</p>
        
        <h2 style="font-size: 1.6rem; margin-top: 40px; margin-bottom: 15px; color: var(--color-primary);">1. Coleta de Informações</h2>
        <p style="margin-bottom: 15px;">Ao navegar no West News, podemos coletar os seguintes tipos de informações:</p>
        <ul style="margin-bottom: 25px; padding-left: 20px;">
            <li style="margin-bottom: 10px;"><strong>Dados fornecidos por você:</strong> Nome, e-mail e outras informações inseridas ao assinar nossa newsletter, criar uma conta de acesso ou entrar em contato conosco.</li>
            <li style="margin-bottom: 10px;"><strong>Dados de navegação automáticos:</strong> Endereço IP, tipo de navegador, tempo de permanência nas páginas, páginas visitadas e dados de cookies.</li>
        </ul>

        <h2 style="font-size: 1.6rem; margin-top: 40px; margin-bottom: 15px; color: var(--color-primary);">2. Uso das Informações</h2>
        <p style="margin-bottom: 15px;">As informações coletadas são utilizadas com o propósito exclusivo de:</p>
        <ul style="margin-bottom: 25px; padding-left: 20px;">
            <li style="margin-bottom: 10px;">Personalizar a sua experiência no portal, oferecendo conteúdos relevantes ao seu perfil de navegação.</li>
            <li style="margin-bottom: 10px;">Melhorar continuamente o desempenho, a segurança e a usabilidade do nosso site.</li>
            <li style="margin-bottom: 10px;">Enviar comunicações, newsletters e alertas de notícias urgentes (apenas quando expressamente autorizado pelo usuário).</li>
        </ul>

        <h2 style="font-size: 1.6rem; margin-top: 40px; margin-bottom: 15px; color: var(--color-primary);">3. Compartilhamento de Dados</h2>
        <p style="margin-bottom: 25px;">O West News <strong>não vende, não aluga e não compartilha</strong> suas informações pessoais com terceiros para fins publicitários sem o seu consentimento explícito. O compartilhamento pode ocorrer apenas com provedores de serviços de infraestrutura (como servidores de hospedagem) que atuam sob rigorosos contratos de confidencialidade, ou quando exigido por lei mediante ordem judicial.</p>

        <h2 style="font-size: 1.6rem; margin-top: 40px; margin-bottom: 15px; color: var(--color-primary);">4. Segurança da Informação</h2>
        <p style="margin-bottom: 25px;">Implementamos as melhores práticas de segurança da informação do mercado (como criptografia SSL/TLS e armazenamento protegido) para garantir que seus dados não sejam acessados, alterados ou destruídos por pessoas não autorizadas.</p>

        <h2 style="font-size: 1.6rem; margin-top: 40px; margin-bottom: 15px; color: var(--color-primary);">5. Seus Direitos (LGPD)</h2>
        <p style="margin-bottom: 15px;">Você, como titular dos dados, tem o direito de:</p>
        <ul style="margin-bottom: 25px; padding-left: 20px;">
            <li style="margin-bottom: 10px;">Solicitar a exclusão total dos seus dados dos nossos bancos.</li>
            <li style="margin-bottom: 10px;">Revogar o consentimento para o recebimento de e-mails e newsletters a qualquer momento.</li>
            <li style="margin-bottom: 10px;">Solicitar acesso e correção de informações incorretas.</li>
        </ul>

        <div style="background: var(--color-surface); padding: 30px; border-radius: 8px; border: 1px solid var(--color-border); margin-top: 50px;">
            <h3 style="font-size: 1.3rem; margin-bottom: 10px;">Ficou com alguma dúvida?</h3>
            <p style="margin-bottom: 15px; font-size: 1rem;">Se você tiver perguntas sobre nossa Política de Privacidade ou sobre como tratamos seus dados, entre em contato com o nosso <strong>DPO (Data Protection Officer)</strong>:</p>
            <a href="mailto:privacidade@westnews.com" style="color: var(--color-primary); font-weight: bold; font-size: 1.1rem;"><i class="fas fa-envelope"></i> privacidade@westnews.com</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
