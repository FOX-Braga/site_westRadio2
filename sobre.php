<?php
$page_title = "Quem Somos - 96 News";
require_once __DIR__ . '/includes/header.php';
?>

<div style="background-color: var(--color-surface); padding: 60px 0; border-bottom: 1px solid var(--color-border); margin-bottom: 40px; text-align: center;">
    <div class="container">
        <h1 style="font-family: var(--font-title); font-size: 3rem; color: var(--color-primary); margin-bottom: 15px;">Quem Somos Nós</h1>
        <p style="font-size: 1.2rem; color: var(--color-text-muted); max-width: 800px; margin: 0 auto; line-height: 1.6;">Com as raízes cravadas na credibilidade e na força da 96.7 FM em Campo Grande, MS, o portal 96news é o seu canal definitivo para um jornalismo digital independente e de altíssimo nível. Expandimos nossas ondas para o ambiente digital com um único objetivo: ser a ponte entre os acontecimentos de Mato Grosso do Sul, do Brasil e do mundo. Entregamos não apenas fatos, mas uma curadoria precisa onde a agilidade e a profundidade se encontram.</p>
    </div>
</div>

<div class="container" style="max-width: 1000px; margin-bottom: 80px;">
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center; margin-bottom: 60px;">
        <div>
            <h2 style="font-size: 2rem; color: var(--color-text); margin-bottom: 20px;">Nossa Missão</h2>
            <p style="font-size: 1.1rem; line-height: 1.8; color: var(--color-text-muted); margin-bottom: 15px;">Sabemos que, para mentes exigentes, o acesso à informação qualificada é uma ferramenta estratégica indispensável. Nosso propósito é investigar e relatar os movimentos que ditam os rumos da sociedade, sempre com imparcialidade e sofisticação analítica.</p>
            <p style="font-size: 1.1rem; line-height: 1.8; color: var(--color-text-muted);">Nosso compromisso é entregar a notícia exata, acompanhada do contexto que você exige para compreender os bastidores de cada acontecimento do cenário regional ao impacto global.</p>
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
            <p style="color: var(--color-text-muted); font-size: 0.95rem; line-height: 1.6;">Cobertura equilibrada e sem amarras partidárias. Apresentamos todos os ângulos da notícia para que você forme suas próprias conclusões.</p>
        </div>
        <div style="background: var(--color-surface); padding: 30px; border-radius: 8px; border: 1px solid var(--color-border); text-align: center;">
            <i class="fas fa-search" style="font-size: 2.5rem; color: var(--color-primary); margin-bottom: 20px;"></i>
            <h3 style="margin-bottom: 15px; font-size: 1.2rem;">Rigor Investigativo</h3>
            <p style="color: var(--color-text-muted); font-size: 0.95rem; line-height: 1.6;">Fatos, não suposições. Toda informação passa por um rigoroso processo de checagem (fact-checking) para garantir precisão absoluta.</p>
        </div>
        <div style="background: var(--color-surface); padding: 30px; border-radius: 8px; border: 1px solid var(--color-border); text-align: center;">
            <i class="fas fa-bolt" style="font-size: 2.5rem; color: var(--color-primary); margin-bottom: 20px;"></i>
            <h3 style="margin-bottom: 15px; font-size: 1.2rem;">Agilidade</h3>
            <p style="color: var(--color-text-muted); font-size: 0.95rem; line-height: 1.6;">O timing exato entre o acontecimento e você. Entregamos a notícia em primeira mão (breaking news), unindo velocidade e responsabilidade editorial.</p>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
