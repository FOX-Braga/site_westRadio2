</main> <!-- Fechamento da main do header -->

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Coluna 1: Logo e Social -->
            <div class="footer-col">
                <div class="logo-container" style="display: flex; align-items: center; justify-content: flex-start; margin-bottom: 20px;">
                    <a href="<?= BASE_URL ?>/" class="logo" style="background-color: #ffffff; padding: 10px 20px; border-radius: 4px; display: inline-block;">
                        <img src="<?= BASE_URL ?>/assets/logo-96news.png" alt="96News Logo" style="height: 45px; width: auto; mix-blend-mode: multiply;">
                    </a>
                </div>
                <p style="color: rgba(255, 255, 255, 0.7); line-height: 1.6; font-size: 0.9rem; margin-bottom: 25px;">
                    O seu portal de notícias. Informação com credibilidade, agilidade e profundidade, acompanhando o Brasil e o Mundo em tempo real.
                </p>
                <div style="display: flex; gap: 15px; font-size: 1.2rem;">
                    <a href="#" style="color: #ffffff; background: rgba(255,255,255,0.1); width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%;"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" style="color: #ffffff; background: rgba(255,255,255,0.1); width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%;"><i class="fab fa-twitter"></i></a>
                    <a href="#" style="color: #ffffff; background: rgba(255,255,255,0.1); width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%;"><i class="fab fa-instagram"></i></a>
                    <a href="#" style="color: #ffffff; background: rgba(255,255,255,0.1); width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%;"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <!-- Coluna 2: Institucional -->
            <div class="footer-col">
                <h3>Institucional</h3>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>/sobre.php">Quem Somos</a></li>
                    <li><a href="<?= BASE_URL ?>/contato.php">Fale Conosco</a></li>
                    <li><a href="<?= BASE_URL ?>/trabalhe-conosco.php">Trabalhe Conosco</a></li>
                    <li><a href="<?= BASE_URL ?>/contato.php">Anuncie</a></li>
                </ul>
            </div>
            
            <!-- Coluna 3: Categorias -->
            <div class="footer-col">
                <h3>Editorias</h3>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>/categoria/brasil">Brasil</a></li>
                    <li><a href="<?= BASE_URL ?>/categoria/mundo">Mundo</a></li>
                    <li><a href="<?= BASE_URL ?>/categoria/economia">Economia</a></li>
                    <li><a href="<?= BASE_URL ?>/categoria/politica">Política</a></li>
                    <li><a href="<?= BASE_URL ?>/categoria/cultura">Cultura</a></li>
                </ul>
            </div>
            
            <!-- Coluna 4: Legal & Mapa -->
            <div class="footer-col">
                <h3>Legal</h3>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>/termos.php">Termos de Uso</a></li>
                    <li><a href="<?= BASE_URL ?>/privacidade.php">Política de Privacidade</a></li>
                    <li><a href="<?= BASE_URL ?>/cookies.php">Política de Cookies</a></li>
                    <li><a href="<?= BASE_URL ?>/mapa.php">Mapa do Site</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> 96News - O Portal de Notícias. Todos os direitos reservados.</p>
            <div style="margin-top: 10px; font-size: 0.8rem; color: #666; letter-spacing: 0.5px;">
                Desenvolvido por <strong style="color: #aaa; font-weight: 500;">João Gabriel Braga</strong> &bull; <span style="text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px; color: var(--color-accent); font-weight: 700;">HannyaTech</span>
            </div>
        </div>
    </div>
</footer>

<!-- Floating Radio Player -->
<div id="floating-radio" data-turbo-permanent="true" style="position: fixed; bottom: 30px; right: 30px; background: var(--color-surface); padding: 15px 20px; border-radius: 50px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); display: flex; align-items: center; gap: 15px; z-index: 9999; border: 2px solid var(--color-primary); transition: transform 0.3s ease;">
    <div style="width: 55px; height: 55px; display: flex; align-items: center; justify-content: center; border-radius: 50%; overflow: hidden; flex-shrink: 0; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
        <img src="<?= BASE_URL ?>/assets/logo-antena1.jpg" alt="Antena 1" style="width: 100%; height: 100%; object-fit: cover;">
    </div>
    <div style="display: flex; flex-direction: column;">
        <strong style="font-size: 0.95rem; color: var(--color-text);">Antena 1</strong>
        <span style="font-size: 0.75rem; color: var(--color-text-muted);">Campo Grande</span>
    </div>
    <button id="antena1-play-btn" style="background: var(--color-primary); color: white; border: none; border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 1.2rem; box-shadow: 0 4px 10px rgba(204, 0, 0, 0.3); transition: transform 0.2s, background 0.3s;" aria-label="Play/Pause">
        <i class="fas fa-play" style="margin-left: 3px;"></i>
    </button>
</div>

<!-- Aviso de Cookies -->
<div id="cookie-banner" class="cookie-banner" style="position: fixed; bottom: 0; left: 0; width: 100%; background: #ffffff; box-shadow: 0 -4px 20px rgba(0,0,0,0.1); z-index: 9999; transform: translateY(110%); transition: transform 0.3s; color: #333;">
    <div class="container" style="display: flex; justify-content: space-between; align-items: center; padding: 20px; gap: 20px;">
        <p style="margin: 0; font-size: 0.9rem;">Utilizamos cookies e tecnologias semelhantes para melhorar a sua experiência, de acordo com a nossa <a href="<?= BASE_URL ?>/privacidade.php" style="color: var(--color-primary); font-weight: 700; text-decoration: underline;">Política de Privacidade</a>.</p>
        <button id="accept-cookies" class="btn" style="white-space: nowrap;">Prosseguir</button>
    </div>
</div>

<?php
// Banners foram movidos para o header (Top Carousel)
?>

<!-- 7. Integração WhatsApp -->
<a href="https://wa.me/5567998732692" target="_blank" title="Fale conosco" style="position: fixed; bottom: 30px; left: 30px; background-color: #25D366; color: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; box-shadow: 0 4px 10px rgba(0,0,0,0.3); z-index: 9999; text-decoration: none; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
    <i class="fab fa-whatsapp"></i>
</a>

<script src="<?= BASE_URL ?>/assets/js/main.js?v=<?= filemtime(__DIR__ . '/../assets/js/main.js') ?>"></script>
</body>
</html>

