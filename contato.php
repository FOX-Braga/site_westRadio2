<?php
$page_title = "Fale Conosco - West News";
require_once __DIR__ . '/includes/header.php';

$mensagem_enviada = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simulação de envio de email
    $mensagem_enviada = true;
}
?>

<div style="background-color: var(--color-primary); color: white; padding: 60px 0; margin-bottom: 40px;">
    <div class="container" style="text-align: center;">
        <h1 style="font-family: var(--font-title); font-size: 3rem; margin-bottom: 15px; color: white;">Fale Conosco</h1>
        <p style="font-size: 1.2rem; opacity: 0.9; max-width: 800px; margin: 0 auto; line-height: 1.6;">Nossa redação, equipe de assinaturas e departamento comercial estão prontos para ouvir você.</p>
    </div>
</div>

<div class="container" style="max-width: 1100px; margin-bottom: 80px; min-height: 50vh;">
    
    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 50px;">
        <!-- Coluna Esquerda: Informações -->
        <div>
            <h3 style="font-size: 1.5rem; margin-bottom: 25px; border-bottom: 2px solid var(--color-primary); padding-bottom: 10px; display: inline-block;">Canais de Atendimento</h3>
            
            <div style="margin-bottom: 30px;">
                <h4 style="font-size: 1.1rem; margin-bottom: 5px;"><i class="fas fa-newspaper" style="color: var(--color-primary); margin-right: 8px;"></i> Redação</h4>
                <p style="color: var(--color-text-muted); font-size: 0.95rem; margin-bottom: 5px;">Sugestões de pautas, denúncias e correções.</p>
                <a href="mailto:redacao@westnews.com" style="color: var(--color-primary); font-weight: 600;">redacao@westnews.com</a>
            </div>
            
            <div style="margin-bottom: 30px;">
                <h4 style="font-size: 1.1rem; margin-bottom: 5px;"><i class="fas fa-bullhorn" style="color: var(--color-primary); margin-right: 8px;"></i> Comercial / Anuncie</h4>
                <p style="color: var(--color-text-muted); font-size: 0.95rem; margin-bottom: 5px;">Mídia kit, publicidade e parcerias institucionais.</p>
                <a href="mailto:comercial@westnews.com" style="color: var(--color-primary); font-weight: 600;">comercial@westnews.com</a>
            </div>
            
            <div style="margin-bottom: 30px;">
                <h4 style="font-size: 1.1rem; margin-bottom: 5px;"><i class="fas fa-headset" style="color: var(--color-primary); margin-right: 8px;"></i> Central do Assinante</h4>
                <p style="color: var(--color-text-muted); font-size: 0.95rem; margin-bottom: 5px;">Dúvidas sobre sua assinatura ou acesso.</p>
                <a href="mailto:assinante@westnews.com" style="color: var(--color-primary); font-weight: 600;">assinante@westnews.com</a><br>
                <span style="font-size: 0.9rem; color: var(--color-text-muted);"><i class="fab fa-whatsapp"></i> (67) 99999-0000</span>
            </div>
            
            <div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid var(--color-border);">
                <h4 style="font-size: 1.1rem; margin-bottom: 10px;">Sede Administrativa</h4>
                <p style="color: var(--color-text-muted); font-size: 0.95rem; line-height: 1.6;">Av. Afonso Pena, 1234 - Centro<br>Campo Grande - MS, 79000-000<br>Brasil</p>
            </div>
        </div>
        
        <!-- Coluna Direita: Formulário -->
        <div>
            <?php if ($mensagem_enviada): ?>
                <div style="padding: 25px; background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; border-radius: 8px; text-align: center;">
                    <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 15px; color: #22c55e;"></i>
                    <h3 style="margin-bottom: 10px;">Mensagem Enviada!</h3>
                    <p style="font-size: 1.05rem;">Sua mensagem foi recebida pela nossa equipe. Retornaremos o mais breve possível.</p>
                    <a href="contato.php" class="btn" style="margin-top: 20px;">Enviar nova mensagem</a>
                </div>
            <?php else: ?>
                <div style="background-color: var(--color-bg); padding: 40px; border-radius: 8px; border: 1px solid var(--color-border); box-shadow: var(--shadow-sm);">
                    <h3 style="font-size: 1.5rem; margin-bottom: 25px;">Envie uma Mensagem</h3>
                    
                    <form action="contato.php" method="POST">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="nome" style="font-weight: 600; margin-bottom: 8px; display: block;">Nome Completo</label>
                                <input type="text" id="nome" name="nome" class="form-control" style="background: var(--color-surface);" required>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="email" style="font-weight: 600; margin-bottom: 8px; display: block;">E-mail</label>
                                <input type="email" id="email" name="email" class="form-control" style="background: var(--color-surface);" required>
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="departamento" style="font-weight: 600; margin-bottom: 8px; display: block;">Direcionar para:</label>
                            <select id="departamento" name="departamento" class="form-control" style="background: var(--color-surface);" required>
                                <option value="redacao">Redação (Pautas/Notícias)</option>
                                <option value="comercial">Comercial (Anúncios)</option>
                                <option value="assinaturas">Assinaturas</option>
                                <option value="trabalhe_conosco">Trabalhe Conosco (RH)</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="assunto" style="font-weight: 600; margin-bottom: 8px; display: block;">Assunto</label>
                            <input type="text" id="assunto" name="assunto" class="form-control" style="background: var(--color-surface);" required>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 25px;">
                            <label for="mensagem" style="font-weight: 600; margin-bottom: 8px; display: block;">Mensagem</label>
                            <textarea id="mensagem" name="mensagem" class="form-control" rows="6" style="background: var(--color-surface); resize: vertical;" required></textarea>
                        </div>
                        
                        <button type="submit" class="btn" style="width: 100%; padding: 15px; font-size: 1.1rem;"><i class="far fa-paper-plane" style="margin-right: 8px;"></i> Enviar Mensagem</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
