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

/* Responsividade Mobile para a Página da Rádio */
@media (max-width: 600px) {
    .radio-page-container {
        margin: 20px auto;
        border-radius: 12px;
    }
    .radio-page-header {
        padding: 40px 15px;
    }
    .radio-page-header h1 {
        font-size: 2rem;
    }
    .radio-page-header p {
        font-size: 1rem;
    }
    .radio-player-body {
        padding: 60px 15px 40px;
        gap: 25px;
    }
    .radio-icon-large {
        width: 90px;
        height: 90px;
    }
    .btn-play-large {
        width: 65px;
        height: 65px;
        font-size: 1.5rem;
    }
    .radio-status {
        font-size: 1rem;
    }
    .volume-slider {
        width: 100px;
    }
}
</style>

<div class="container">
    <div class="radio-page-container">
        <div class="radio-page-header">
            <h1>Antena 1 - Campo Grande</h1>
            <p>A melhor programação, 24 horas por dia com você.</p>
        </div>
        
        <div class="radio-player-body">
            <div class="radio-icon-large" style="background: transparent; box-shadow: 0 4px 15px rgba(0,0,0,0.15); animation: none; overflow: hidden; border-radius: 50%; padding: 0;">
                <img src="<?= BASE_URL ?>/assets/logo-antena1.jpg" alt="Antena 1" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            
            <div class="radio-status">
                <span class="status-indicator" id="page-status-dot"></span>
                <span id="page-status-text">Pronto para tocar</span>
            </div>
            
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
(function() {
    if (!window.globalRadioPlayer) {
        window.globalRadioPlayer = new Audio('https://server27.srvsh.com.br:6900/stream//');
        window.globalRadioPlayer.preload = 'none';
        window.globalRadioPlayer.addEventListener('play', () => localStorage.setItem('radio_is_playing', 'true'));
        window.globalRadioPlayer.addEventListener('pause', () => localStorage.setItem('radio_is_playing', 'false'));
    }
    const player = window.globalRadioPlayer;
    const playBtn = document.getElementById('page-play-btn');
    if (!playBtn) return;
    
    const playIcon = playBtn.querySelector('i');
    const volumeSlider = document.getElementById('page-volume-slider');
    const statusText = document.getElementById('page-status-text');
    const statusDot = document.getElementById('page-status-dot');
    
    // Sync UI if already playing
    if (!player.paused) {
        playIcon.className = 'fas fa-stop';
        playIcon.style.marginLeft = '0';
        statusText.textContent = "No Ar - Transmissão Ao Vivo";
        statusDot.classList.add('live');
        statusDot.style.background = "";
    }
    
    const syncFloatingBtn = () => {
        const floatingIcon = document.querySelector('#antena1-play-btn i');
        if (floatingIcon) {
            if (!player.paused) {
                floatingIcon.className = 'fas fa-pause';
                floatingIcon.style.marginLeft = '0';
            } else {
                floatingIcon.className = 'fas fa-play';
                floatingIcon.style.marginLeft = '1px';
            }
        }
    };

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
                syncFloatingBtn();
            }).catch(e => {
                statusText.textContent = "Erro ao conectar";
                statusDot.style.background = "#ef4444"; // red
                console.error("Erro no áudio:", e);
            });
        } else {
            player.pause();
            playIcon.className = 'fas fa-play';
            playIcon.style.marginLeft = '5px';
            statusText.textContent = "Pronto para tocar";
            statusDot.classList.remove('live');
            statusDot.style.background = "var(--color-primary)"; // default blue
            syncFloatingBtn();
        }
    }

    playBtn.addEventListener('click', togglePlay);

    if (volumeSlider) {
        volumeSlider.addEventListener('input', function(e) {
            player.volume = e.target.value;
        });
        player.volume = volumeSlider.value;
    }
})();
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
