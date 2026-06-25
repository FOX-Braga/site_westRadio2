<?php
$page_title = "Termos de Uso - West News";
require_once __DIR__ . '/includes/header.php';
?>

<div style="background-color: var(--color-surface); padding: 50px 0; border-bottom: 1px solid var(--color-border); margin-bottom: 40px;">
    <div class="container" style="max-width: 800px;">
        <h1 style="font-family: var(--font-title); font-size: 2.5rem; color: var(--color-primary); margin-bottom: 10px;">Termos de Uso</h1>
        <p style="color: var(--color-text-muted); font-size: 1.1rem;">Última atualização: <?= date('d/m/Y') ?></p>
    </div>
</div>

<div class="container" style="max-width: 800px; margin-bottom: 80px; min-height: 50vh;">
    
    <div style="font-size: 1.1rem; line-height: 1.8; color: var(--color-text); font-family: var(--font-family);">
        <p style="margin-bottom: 25px;">Ao acessar o portal <strong>West News</strong>, você concorda em cumprir e estar vinculado aos seguintes Termos de Uso. Leia atentamente as condições abaixo antes de utilizar os nossos serviços.</p>
        
        <h2 style="font-size: 1.6rem; margin-top: 40px; margin-bottom: 15px; color: var(--color-primary);">1. Propriedade Intelectual e Direitos Autorais</h2>
        <p style="margin-bottom: 25px;">Todo o conteúdo jornalístico, fotográfico, em vídeo ou em áudio (podcasts) publicado neste site é propriedade exclusiva do West News ou de agências parceiras licenciadas. <strong>A reprodução, cópia, distribuição ou modificação de qualquer material sem a citação da fonte e o devido link (URL) para a publicação original é expressamente proibida.</strong></p>

        <h2 style="font-size: 1.6rem; margin-top: 40px; margin-bottom: 15px; color: var(--color-primary);">2. Responsabilidade do Usuário</h2>
        <p style="margin-bottom: 15px;">Ao interagir com o portal (como, por exemplo, na área de comentários, fóruns ou contato), o usuário se compromete a:</p>
        <ul style="margin-bottom: 25px; padding-left: 20px;">
            <li style="margin-bottom: 10px;">Não publicar conteúdos ofensivos, racistas, homofóbicos, ameaçadores ou que violem os direitos humanos.</li>
            <li style="margin-bottom: 10px;">Não utilizar nossa plataforma para propagação de <em>Fake News</em> (notícias falsas) ou spam.</li>
            <li style="margin-bottom: 10px;">Manter em sigilo as credenciais de sua conta (login e senha).</li>
        </ul>
        <p style="margin-bottom: 25px;">O West News reserva-se o direito de excluir comentários e suspender ou banir contas de usuários que descumprirem estas regras, sem aviso prévio.</p>

        <h2 style="font-size: 1.6rem; margin-top: 40px; margin-bottom: 15px; color: var(--color-primary);">3. Isenção de Responsabilidade</h2>
        <p style="margin-bottom: 25px;">Os artigos assinados por colunistas externos na editoria de "Opinião" refletem única e exclusivamente o pensamento de seus respectivos autores, não representando necessariamente a visão editorial do West News. O portal não se responsabiliza por danos oriundos de falhas de conexão de internet do usuário durante a navegação.</p>

        <h2 style="font-size: 1.6rem; margin-top: 40px; margin-bottom: 15px; color: var(--color-primary);">4. Assinaturas e Acesso Restrito</h2>
        <p style="margin-bottom: 25px;">Determinados conteúdos investigativos podem ser restritos a assinantes pagos. As regras de renovação, cancelamento e reembolso de assinaturas seguem estritamente as determinações do Código de Defesa do Consumidor (CDC).</p>

        <h2 style="font-size: 1.6rem; margin-top: 40px; margin-bottom: 15px; color: var(--color-primary);">5. Alterações nos Termos</h2>
        <p style="margin-bottom: 25px;">O West News poderá revisar ou atualizar estes Termos de Uso a qualquer momento, visando se adequar a novas legislações ou mudanças estruturais no portal. Recomendamos que os usuários visitem esta página periodicamente.</p>
        
        <div style="background: var(--color-surface); padding: 30px; border-radius: 8px; border: 1px solid var(--color-border); margin-top: 50px;">
            <p style="margin-bottom: 0; font-size: 1rem;">Ao continuar utilizando nosso portal, você concorda formalmente com os Termos expostos acima. Para falar conosco sobre questões legais, envie um e-mail para <a href="mailto:juridico@westnews.com" style="color: var(--color-primary); font-weight: bold;">juridico@westnews.com</a>.</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
