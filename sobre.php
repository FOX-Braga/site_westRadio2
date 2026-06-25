<?php
$page_title = "Quem Somos - 96 News";
require_once __DIR__ . '/includes/header.php';
?>

<div style="background-color: var(--color-surface); padding: 60px 0; border-bottom: 1px solid var(--color-border); margin-bottom: 40px; text-align: center;">
    <div class="container">
        <h1 style="font-family: var(--font-title); font-size: 3rem; color: var(--color-primary); margin-bottom: 15px;">Quem Somos</h1>
        <p style="font-size: 1.2rem; color: var(--color-text-muted); max-width: 800px; margin: 0 auto; line-height: 1.6;">O <strong>96 News</strong> é um portal de jornalismo digital independente dedicado a levar informação com credibilidade, agilidade e profundidade para o Brasil e o mundo.</p>
    </div>
</div>

<div class="container" style="max-width: 1000px; margin-bottom: 80px;">
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center; margin-bottom: 60px;">
        <div>
            <h2 style="font-size: 2rem; color: var(--color-text); margin-bottom: 20px;">Nossa Missão</h2>
            <p style="font-size: 1.1rem; line-height: 1.8; color: var(--color-text-muted); margin-bottom: 15px;">Acreditamos que o acesso à informação de qualidade é o pilar fundamental de uma sociedade livre e democrática. Nosso objetivo é investigar, apurar e relatar os fatos que moldam o nosso dia a dia, sempre com imparcialidade e rigor jornalístico.</p>
            <p style="font-size: 1.1rem; line-height: 1.8; color: var(--color-text-muted);">Com redações conectadas 24 horas por dia, nós cobrimos os principais eventos da Política, Economia, Cultura e Tecnologia global, entregando não apenas a notícia, mas o contexto por trás dela.</p>
        </div>
        <div>
            <img src="<?= BASE_URL ?>/assets/logo-96news.png" alt="Redação 96 News" style="width: 100%; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 40px; background: #fff; border: 1px solid var(--color-border); object-fit: contain;">
        </div>
    </div>

    <hr style="border: none; border-top: 1px solid var(--color-border); margin: 60px 0;">

    <div style="text-align: center; margin-bottom: 50px;">
        <h2 style="font-size: 2rem; color: var(--color-text); margin-bottom: 15px;">Nossos Princípios Editoriais</h2>
    </div>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
        <div style="background: var(--color-surface); padding: 30px; border-radius: 8px; border: 1px solid var(--color-border); text-align: center;">
            <i class="fas fa-balance-scale" style="font-size: 2.5rem; color: var(--color-primary); margin-bottom: 20px;"></i>
            <h3 style="margin-bottom: 15px; font-size: 1.2rem;">Imparcialidade</h3>
            <p style="color: var(--color-text-muted); font-size: 0.95rem; line-height: 1.6;">Ouvimos todos os lados da história. Nossa cobertura é equilibrada e não assume lados partidários.</p>
        </div>
        <div style="background: var(--color-surface); padding: 30px; border-radius: 8px; border: 1px solid var(--color-border); text-align: center;">
            <i class="fas fa-search" style="font-size: 2.5rem; color: var(--color-primary); margin-bottom: 20px;"></i>
            <h3 style="margin-bottom: 15px; font-size: 1.2rem;">Rigor Investigativo</h3>
            <p style="color: var(--color-text-muted); font-size: 0.95rem; line-height: 1.6;">Fatos, não especulações. Toda notícia publicada passa por um rigoroso processo de checagem (Fact-Checking).</p>
        </div>
        <div style="background: var(--color-surface); padding: 30px; border-radius: 8px; border: 1px solid var(--color-border); text-align: center;">
            <i class="fas fa-bolt" style="font-size: 2.5rem; color: var(--color-primary); margin-bottom: 20px;"></i>
            <h3 style="margin-bottom: 15px; font-size: 1.2rem;">Agilidade</h3>
            <p style="color: var(--color-text-muted); font-size: 0.95rem; line-height: 1.6;">Velocidade com responsabilidade. Levamos a quebra de notícia (Breaking News) a você antes de todos.</p>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
