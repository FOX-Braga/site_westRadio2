<?php
$page_title = "Transmissão Ao Vivo";
$page_desc = "Assista a transmissão ao vivo da 96 FM / 96 News e confira os últimos vídeos do nosso canal.";
require_once __DIR__ . '/includes/header.php';

if (!isset($_SESSION['user_nome']) && !isset($_SESSION['guest_name'])) {
    $_SESSION['guest_name'] = 'Visitante #' . rand(1000, 9999);
}

$pdo = Database::getInstance();

// Buscamos todos os vídeos cadastrados ordenados: primeiro lives, depois vídeos por data de criação decrescente
$stmt = $pdo->query("SELECT * FROM videos ORDER BY tipo DESC, criado_em DESC");
$videos = $stmt->fetchAll();

// Encontrar o vídeo inicial (destaque)
// Procuramos a primeira live cadastrada. Se não houver, pegamos o primeiro vídeo.
$video_inicial = null;
foreach ($videos as $v) {
    if ($v['tipo'] === 'live') {
        $video_inicial = $v;
        break;
    }
}
if (!$video_inicial && !empty($videos)) {
    $video_inicial = $videos[0];
}

$video_inicial_id = $video_inicial ? $video_inicial['youtube_id'] : 'yP5S7V7g3p0';
$video_inicial_titulo = $video_inicial ? $video_inicial['titulo'] : '96 News / 96 FM - Transmissão Oficial ao Vivo';
$video_inicial_desc = $video_inicial ? $video_inicial['descricao'] : 'Acompanhe nossa programação jornalística e musical direto do estúdio de Campo Grande - MS. Notícias que importam e a rádio que toca os EUA.';
$video_inicial_tipo = $video_inicial ? $video_inicial['tipo'] : 'live';
?>

<style>
/* Estilos da Página Ao Vivo */
.aovivo-layout {
    display: grid;
    grid-template-columns: 2.2fr 1fr;
    gap: 30px;
    margin: 30px 0;
}

/* Container do Player */
.player-container {
    background-color: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: var(--shadow-md);
}

.iframe-wrapper {
    position: relative;
    width: 100%;
    padding-top: 56.25%; /* Aspect Ratio 16:9 */
}

.iframe-wrapper iframe {
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    border: none;
}

.player-info {
    padding: 24px;
}

.player-status-bar {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 12px;
}

.badge-live {
    background-color: var(--color-ao-vivo-bg);
    color: #ffffff;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.badge-live i {
    font-size: 0.6rem;
    animation: blink-live 1.5s infinite;
}

.viewer-count {
    font-size: 0.85rem;
    color: var(--color-text-muted);
    font-weight: 600;
}

.player-title {
    font-family: var(--font-title);
    font-size: 2rem;
    font-weight: 800;
    color: var(--color-text);
    margin: 0 0 10px 0;
    line-height: 1.3;
}

.player-desc {
    font-size: 0.95rem;
    color: var(--color-text-muted);
    line-height: 1.6;
    margin: 0;
}

/* Live Chat Widget */
.chat-container {
    background-color: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 480px;
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.chat-header {
    background-color: rgba(0, 0, 0, 0.2);
    padding: 15px 20px;
    border-bottom: 1px solid var(--color-border);
    font-weight: 700;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--color-text);
    display: flex;
    align-items: center;
    gap: 8px;
}

.chat-messages {
    flex-grow: 1;
    padding: 20px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-height: 380px;
    background-color: rgba(0, 0, 0, 0.05);
}

.chat-msg {
    font-size: 0.85rem;
    line-height: 1.4;
    word-break: break-word;
}

.chat-author {
    font-weight: 700;
    color: var(--color-primary);
    margin-right: 5px;
}

.chat-text {
    color: var(--color-text);
}

.chat-input-bar {
    padding: 15px 20px;
    border-top: 1px solid var(--color-border);
    display: flex;
    gap: 10px;
}

.chat-input {
    flex-grow: 1;
    padding: 10px 15px;
    background-color: var(--color-bg);
    border: 1px solid var(--color-border);
    color: var(--color-text);
    border-radius: 4px;
    font-size: 0.85rem;
    outline: none;
    font-family: var(--font-family);
}

.chat-input:focus {
    border-color: var(--color-primary);
}

.chat-send-btn {
    padding: 10px 18px;
    background-color: var(--color-primary);
    color: #ffffff;
    border: none;
    border-radius: 4px;
    font-weight: 700;
    cursor: pointer;
    font-size: 0.85rem;
    text-transform: uppercase;
    transition: var(--transition);
}

.chat-send-btn:hover {
    background-color: var(--color-primary-dark);
}

/* Video Gallery Section */
.gallery-section {
    margin: 50px 0;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 20px;
    margin-top: 25px;
}

.gallery-card {
    background-color: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    flex-direction: column;
}

.gallery-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
}

.gallery-thumb-container {
    position: relative;
    width: 100%;
    padding-top: 56.25%; /* 16:9 */
    background-color: #000;
}

.gallery-thumb {
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    object-fit: cover;
    opacity: 0.85;
    transition: opacity 0.2s;
}

.gallery-card:hover .gallery-thumb {
    opacity: 1;
}

.play-overlay {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 50px;
    height: 50px;
    background-color: rgba(46, 125, 50, 0.9);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 1.3rem;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    transition: all 0.2s ease-in-out;
}

.gallery-card:hover .play-overlay {
    background-color: var(--color-primary);
    transform: translate(-50%, -50%) scale(1.1);
}

.play-overlay i {
    margin-left: 4px;
}

.duration-tag {
    position: absolute;
    bottom: 10px; right: 10px;
    background-color: rgba(0, 0, 0, 0.8);
    color: #ffffff;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 4px;
}

.gallery-info {
    padding: 15px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.gallery-title {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--color-text);
    line-height: 1.4;
    margin: 0 0 10px 0;
    /* Limita a 2 linhas de texto */
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.gallery-meta {
    font-size: 0.75rem;
    color: var(--color-text-muted);
    display: flex;
    justify-content: space-between;
}

@media (max-width: 992px) {
    .aovivo-layout {
        grid-template-columns: 1fr;
        gap: 30px;
    }
}
</style>

<div class="container" style="padding-top: 15px;">
    <div style="margin: 30px 0 15px 0;">
        <h1 class="section-title" style="margin: 0; font-size: 2.5rem; text-transform: none;">Ao Vivo</h1>
        <p style="margin-top: 8px; font-size: 1.05rem; color: var(--color-text-muted); font-weight: 400; font-family: var(--font-family);">Assista a nossa programação em tempo real e reveja grandes momentos</p>
    </div>

    <!-- Layout Principal -->
    <div class="aovivo-layout">
        <!-- Player de Vídeo -->
        <div class="player-wrapper">
            <div class="player-container">
                <div class="iframe-wrapper">
                    <iframe id="main-player" src="https://www.youtube.com/embed/<?= escape($video_inicial_id) ?>?autoplay=1" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
                <div class="player-info">
                    <div class="player-status-bar">
                        <span class="badge-live" style="<?= $video_inicial_tipo !== 'live' ? 'background-color: var(--color-primary);' : '' ?>">
                            <?php if ($video_inicial_tipo === 'live'): ?>
                                <i class="fas fa-circle"></i> No Ar
                            <?php else: ?>
                                <i class="fas fa-play-circle"></i> Gravado
                            <?php endif; ?>
                        </span>
                        <span class="viewer-count" style="<?= $video_inicial_tipo !== 'live' ? 'display: none;' : '' ?>">
                            <i class="far fa-user"></i> <span id="chat-viewers">1.248</span> assistindo agora
                        </span>
                    </div>
                    <h2 class="player-title" id="player-current-title"><?= escape($video_inicial_titulo) ?></h2>
                    <p class="player-desc" id="player-current-desc"><?= escape($video_inicial_desc) ?></p>
                </div>
            </div>
        </div>

        <!-- Chat ao Vivo -->
        <div class="chat-wrapper">
            <div class="chat-container">
                <div class="chat-header">
                    <i class="far fa-comments"></i> Chat da Transmissão
                </div>
                <div class="chat-messages" id="chat-messages-box">
                    <div class="chat-msg"><span class="chat-author">Marcos Souza</span><span class="chat-text">Melhor rádio do MS! Escuto todo dia de Três Lagoas.</span></div>
                    <div class="chat-msg"><span class="chat-author">Sandra Helena</span><span class="chat-text">Abraço de Dourados! Excelente transmissão e sinal 100%.</span></div>
                    <div class="chat-msg"><span class="chat-author">Gabriel Lima</span><span class="chat-text">O estúdio novo ficou sensacional! Parabéns 96 News.</span></div>
                    <div class="chat-msg"><span class="chat-author">Aline Duarte</span><span class="chat-text">Que música massa, toca mais rock clássico aí!</span></div>
                </div>
                <form class="chat-input-bar" id="chat-form" onsubmit="sendChatMessage(event)">
                    <input type="text" placeholder="Diga algo no chat..." class="chat-input" id="chat-input-field" required maxlength="100">
                    <button type="submit" class="chat-send-btn">Enviar</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Seção de Galeria de Vídeos -->
    <section class="gallery-section">
        <h2 class="section-title">Últimos Vídeos do Canal</h2>
        
        <div class="gallery-grid">
            <?php foreach ($videos as $v): ?>
            <?php 
            $tempo = '';
            $diff = time() - strtotime($v['criado_em']);
            if ($diff < 60) {
                $tempo = 'agora';
            } elseif ($diff < 3600) {
                $m = floor($diff / 60);
                $tempo = 'há ' . $m . ' min';
            } elseif ($diff < 86400) {
                $h = floor($diff / 3600);
                $tempo = 'há ' . $h . ' h';
            } else {
                $d = floor($diff / 86400);
                if ($d < 7) {
                    $tempo = 'há ' . $d . ' dia' . ($d > 1 ? 's' : '');
                } else {
                    $tempo = date('d/m/Y', strtotime($v['criado_em']));
                }
            }
            ?>
            <div class="gallery-card" 
                 data-id="<?= escape($v['youtube_id']) ?>" 
                 data-titulo="<?= escape($v['titulo']) ?>" 
                 data-descricao="<?= escape($v['descricao']) ?>" 
                 data-tipo="<?= escape($v['tipo']) ?>">
                <div class="gallery-thumb-container">
                    <img src="https://img.youtube.com/vi/<?= escape($v['youtube_id']) ?>/0.jpg" alt="<?= escape($v['titulo']) ?>" class="gallery-thumb">
                    <div class="play-overlay"><i class="fas fa-play"></i></div>
                    <span class="duration-tag"><?= escape(strtoupper($v['duracao'])) ?></span>
                </div>
                <div class="gallery-info">
                    <h3 class="gallery-title"><?= escape($v['titulo']) ?></h3>
                    <div class="gallery-meta">
                        <span><?= $v['tipo'] === 'live' ? 'Ao Vivo' : 'Vídeo' ?></span>
                        <span><?= $tempo ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (count($videos) === 0): ?>
                <p style="color: var(--color-text-muted); grid-column: 1 / -1; text-align: center; padding: 20px;">Nenhum vídeo disponível no momento.</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<script>
// JS Interativo para Carregamento de Vídeos
function loadVideo(id, title, desc, tipo) {
    const player = document.getElementById('main-player');
    const playerTitle = document.getElementById('player-current-title');
    const playerDesc = document.getElementById('player-current-desc');
    const badgeLive = document.querySelector('.badge-live');
    const viewerCount = document.querySelector('.viewer-count');
    
    // Atualiza a URL do iframe
    player.src = `https://www.youtube.com/embed/${id}?autoplay=1`;
    
    // Atualiza título e descrição na página
    playerTitle.textContent = title;
    playerDesc.textContent = desc;
    
    // Atualiza o status de live ou vídeo gravado
    if (tipo === 'live') {
        badgeLive.innerHTML = '<i class="fas fa-circle"></i> No Ar';
        badgeLive.style.backgroundColor = 'var(--color-ao-vivo-bg)';
        if (viewerCount) viewerCount.style.display = 'inline-flex';
    } else {
        badgeLive.innerHTML = '<i class="fas fa-play-circle"></i> Gravado';
        badgeLive.style.backgroundColor = 'var(--color-primary)';
        if (viewerCount) viewerCount.style.display = 'none';
    }
    
    // Rola suavemente até o topo do player
    window.scrollTo({
        top: document.querySelector('.player-wrapper').offsetTop - 100,
        behavior: 'smooth'
    });
}

// Configura eventos de clique dinamicamente
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.gallery-card').forEach(card => {
        card.addEventListener('click', () => {
            const id = card.getAttribute('data-id');
            const title = card.getAttribute('data-titulo');
            const desc = card.getAttribute('data-descricao');
            const tipo = card.getAttribute('data-tipo');
            loadVideo(id, title, desc, tipo);
        });
    });
});

const chatBox = document.getElementById('chat-messages-box');
const chatViewers = document.getElementById('chat-viewers');
let lastMessageId = 0;

// Função para atualizar o chat e visualizações a partir do servidor
function fetchRealtimeData(isFirstLoad = false) {
    fetch('<?= BASE_URL ?>/aovivo_realtime.php?action=ping')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 1. Atualiza visualizações
                if (chatViewers) {
                    chatViewers.textContent = data.viewers.toLocaleString('pt-BR');
                }
                
                // 2. Atualiza mensagens do chat
                if (data.messages && data.messages.length > 0) {
                    const latestMessage = data.messages[data.messages.length - 1];
                    
                    // Só atualiza o DOM se houver mensagens novas
                    if (latestMessage.id !== lastMessageId) {
                        chatBox.innerHTML = '';
                        data.messages.forEach(msg => {
                            const msgDiv = document.createElement('div');
                            msgDiv.className = 'chat-msg';
                            
                            let authorHtml = '';
                            if (msg.tipo === 'admin') {
                                authorHtml = `<span class="chat-author" style="color: var(--color-accent); font-weight: 700; margin-right: 5px;">🛡️ ${msg.nome_usuario}</span>`;
                            } else {
                                authorHtml = `<span class="chat-author" style="color: var(--color-primary); font-weight: 700; margin-right: 5px;">${msg.nome_usuario}</span>`;
                            }
                            
                            msgDiv.innerHTML = `${authorHtml}<span class="chat-text">${msg.mensagem}</span>`;
                            chatBox.appendChild(msgDiv);
                        });
                        
                        // Atualiza o último ID
                        lastMessageId = latestMessage.id;
                        
                        // Rola para o fim
                        chatBox.scrollTop = chatBox.scrollHeight;
                    }
                } else if (data.messages && data.messages.length === 0) {
                    chatBox.innerHTML = '<div style="color: var(--color-text-muted); font-size: 0.85rem; text-align: center; padding: 10px;">Nenhuma mensagem enviada ainda.</div>';
                }
            }
        })
        .catch(err => console.error("Erro na comunicação em tempo real:", err));
}

// Inicia polling (a cada 3 segundos)
fetchRealtimeData(true);
setInterval(fetchRealtimeData, 3000);

// Função para envio de mensagem no chat pelo usuário
function sendChatMessage(e) {
    e.preventDefault();
    const input = document.getElementById('chat-input-field');
    const text = input.value.trim();
    if (!text) return;
    
    // Desabilita input temporariamente
    input.disabled = true;
    
    fetch('<?= BASE_URL ?>/aovivo_realtime.php?action=send', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ mensagem: text })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Limpa o campo de texto
            input.value = '';
            // Atualiza o chat imediatamente
            fetchRealtimeData();
        }
    })
    .catch(err => console.error("Erro ao enviar mensagem:", err))
    .finally(() => {
        input.disabled = false;
        input.focus();
    });
}
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

