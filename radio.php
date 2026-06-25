<?php
$page_title = "Ouvir a Rádio Ao Vivo";
$page_desc = "Ouça a programação da nossa rádio ao vivo com a melhor qualidade de som.";
require_once __DIR__ . '/includes/header.php';
?>

<style>
.radio-page-container {
    max-width: 800px;
    margin: 50px auto;
    background: var(--color-surface);
    border-radius: 16px;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
    overflow: hidden;
    text-align: center;
    border: 1px solid var(--color-border);
}
.radio-page-header {
    background: linear-gradient(135deg, var(--color-primary), #0f2544);
    padding: 60px 20px;
    color: #ffffff;
    position: relative;
}
.radio-page-header::after {
    content: '';
    position: absolute;
    bottom: -20px;
    left: 50%;
    transform: translateX(-50%);
    border-width: 20px 20px 0;
    border-style: solid;
    border-color: #0f2544 transparent transparent transparent;
}
.radio-page-header h1 {
    margin: 0;
    font-size: 2.8rem;
    font-family: var(--font-title);
    color: #ffffff !important;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}
.radio-page-header p {
    font-size: 1.15rem;
    color: rgba(255, 255, 255, 0.95) !important;
    margin-top: 15px;
    font-weight: 500;
}
.radio-player-body {
    padding: 100px 20px 60px; /* Aumenta bastante a distância da seta vermelha */
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 35px;
}
.radio-icon-large {
    width: 120px;
    height: 120px;
    background: rgba(26, 54, 93, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3.5rem;
    color: var(--color-primary); /* Volta a ser Azul */
    box-shadow: 0 0 30px rgba(26, 54, 93, 0.2);
    animation: pulse-ring 2s infinite;
}
@keyframes pulse-ring {
    0% { box-shadow: 0 0 0 0 rgba(26,54,93,0.3); }
    70% { box-shadow: 0 0 0 25px rgba(26,54,93,0); }
    100% { box-shadow: 0 0 0 0 rgba(26,54,93,0); }
}
.radio-controls {
    display: flex;
    align-items: center;
    gap: 20px;
}
.btn-play-large {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: none;
    background: var(--color-primary); /* Volta a ser Azul */
    color: white;
    font-size: 2rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s, background 0.3s;
    box-shadow: 0 10px 20px rgba(26, 54, 93, 0.3);
}
.btn-play-large:hover {
    transform: scale(1.05);
    background: var(--color-primary-dark, #0f2544);
}
.volume-control {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--color-text-muted);
}
.volume-slider {
    -webkit-appearance: none;
    width: 150px;
    height: 6px;
    background: #e2e8f0;
    border-radius: 3px;
    outline: none;
}
.volume-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: var(--color-primary); /* Volta a ser Azul */
    cursor: pointer;
}
.radio-status {
    font-weight: bold;
    color: var(--color-text);
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 10px;
}
.status-indicator {
    width: 12px;
    height: 12px;
    background: var(--color-primary);
    border-radius: 50%;
    display: inline-block;
}
.status-indicator.live {
    animation: blink-live 1.5s infinite;
    background: #10b981; /* Verde quando está tocando */
}
</style>

<div class="container">
    <div class="radio-page-container">
        <div class="radio-page-header">
            <h1>Antena 1 - Campo Grande</h1>
            <p>A melhor programação, 24 horas por dia com você.</p>
        </div>
        
        <div class="radio-player-body">
            <div class="radio-icon-large">
                <i class="fas fa-broadcast-tower"></i>
            </div>
            
            <div class="radio-status">
                <span class="status-indicator" id="page-status-dot"></span>
                <span id="page-status-text">Pronto para tocar</span>
            </div>
            
            <audio id="page-radio-player" src="https://server27.srvsh.com.br:6900/stream//" preload="none"></audio>
            
            <div class="radio-controls">
                <button class="btn-play-large" id="page-play-btn">
                    <i class="fas fa-play" style="margin-left: 5px;"></i>
                </button>
            </div>
            
            <div class="volume-control">
                <i class="fas fa-volume-down"></i>
                <input type="range" id="page-volume-slider" class="volume-slider" min="0" max="1" step="0.01" value="0.8">
                <i class="fas fa-volume-up"></i>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const player = document.getElementById('page-radio-player');
    const playBtn = document.getElementById('page-play-btn');
    const playIcon = playBtn.querySelector('i');
    const volumeSlider = document.getElementById('page-volume-slider');
    const statusText = document.getElementById('page-status-text');
    const statusDot = document.getElementById('page-status-dot');
    
    // Sincroniza e impede o player flutuante de tocar ao mesmo tempo
    const floatingPlayer = document.getElementById('antena1-player');
    const floatingBtn = document.getElementById('antena1-play-btn');
    
    if (floatingPlayer && !floatingPlayer.paused) {
        floatingPlayer.pause();
        if (floatingBtn) {
            floatingBtn.innerHTML = '<i class="fas fa-play" style="margin-left: 3px;"></i>';
        }
    }

    function togglePlay() {
        if (player.paused) {
            statusText.textContent = "Conectando...";
            statusDot.style.background = "#f59e0b"; // orange
            
            player.play().then(() => {
                playIcon.className = 'fas fa-stop';
                playIcon.style.marginLeft = '0';
                statusText.textContent = "No Ar - Transmissão Ao Vivo";
                statusDot.classList.add('live');
                statusDot.style.background = ""; // limpa para pegar a classe live
            }).catch(e => {
                statusText.textContent = "Erro ao conectar";
                statusDot.style.background = "#ef4444"; // red
                console.error("Erro no áudio:", e);
            });
        } else {
            player.pause();
            player.src = player.src; // Reseta stream
            playIcon.className = 'fas fa-play';
            playIcon.style.marginLeft = '5px';
            statusText.textContent = "Pronto para tocar";
            statusDot.classList.remove('live');
            statusDot.style.background = "var(--color-primary)"; // default blue
        }
    }

    playBtn.addEventListener('click', togglePlay);

    volumeSlider.addEventListener('input', function(e) {
        player.volume = e.target.value;
    });
    
    player.volume = volumeSlider.value;
});
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
