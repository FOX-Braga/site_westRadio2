function initApp() {
    // Menu Mobile
    const mobileBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');

    if (mobileBtn && navLinks) {
        // Remover listener anterior caso exista (para evitar duplo clique com Turbo)
        const newMobileBtn = mobileBtn.cloneNode(true);
        mobileBtn.parentNode.replaceChild(newMobileBtn, mobileBtn);
        
        newMobileBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            const isExpanded = navLinks.classList.contains('active');
            newMobileBtn.setAttribute('aria-expanded', isExpanded);
        });
    }

    // Dark Mode Toggle
    const themeToggleBtn = document.querySelector('.theme-toggle');
    const currentTheme = localStorage.getItem('theme');

    // Inicializa o tema baseado no localStorage ou preferência do sistema
    if (currentTheme) {
        document.documentElement.setAttribute('data-theme', currentTheme);
        updateThemeIcon(currentTheme);
    } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.documentElement.setAttribute('data-theme', 'dark');
        updateThemeIcon('dark');
    }

    if (themeToggleBtn) {
        const newThemeToggleBtn = themeToggleBtn.cloneNode(true);
        themeToggleBtn.parentNode.replaceChild(newThemeToggleBtn, themeToggleBtn);
        
        newThemeToggleBtn.addEventListener('click', () => {
            let theme = document.documentElement.getAttribute('data-theme');
            let newTheme = theme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });
    }

    function updateThemeIcon(theme) {
        const btn = document.querySelector('.theme-toggle');
        if (!btn) return;
        if (theme === 'dark') {
            btn.innerHTML = '<i class="fas fa-sun"></i>';
        } else {
            btn.innerHTML = '<i class="fas fa-moon"></i>';
        }
    }

    // Cookie Banner Logic
    const cookieBanner = document.getElementById('cookie-banner');
    const acceptCookiesBtn = document.getElementById('accept-cookies');

    if (cookieBanner && acceptCookiesBtn) {
        // Se o usuário ainda não aceitou os cookies
        if (!localStorage.getItem('cookies_accepted')) {
            // Pequeno delay para aparecer com uma animação suave
            setTimeout(() => {
                cookieBanner.classList.add('show');
            }, 800);
        }

        const newAcceptBtn = acceptCookiesBtn.cloneNode(true);
        acceptCookiesBtn.parentNode.replaceChild(newAcceptBtn, acceptCookiesBtn);

        newAcceptBtn.addEventListener('click', () => {
            // Salva a aceitação no navegador por tempo indeterminado
            localStorage.setItem('cookies_accepted', 'true');
            // Esconde o banner
            cookieBanner.classList.remove('show');
        });
    }

    // A lógica de sessionStorage foi removida conforme solicitado:
    // os banners voltarão a aparecer sempre que a página for recarregada.

    // Radio Player Logic
    if (!window.globalRadioPlayer) {
        window.globalRadioPlayer = new Audio('https://server27.srvsh.com.br:6900/stream//');
        window.globalRadioPlayer.preload = 'none';
        
        window.globalRadioPlayer.addEventListener('play', () => {
            localStorage.setItem('radio_is_playing', 'true');
            const btnIcon = document.querySelector('#antena1-play-btn i');
            if (btnIcon) {
                btnIcon.className = 'fas fa-pause';
                btnIcon.style.marginLeft = '0';
            }
        });
        
        window.globalRadioPlayer.addEventListener('pause', () => {
            localStorage.setItem('radio_is_playing', 'false');
            const btnIcon = document.querySelector('#antena1-play-btn i');
            if (btnIcon) {
                btnIcon.className = 'fas fa-play';
                btnIcon.style.marginLeft = '3px'; // offset for play icon centering
            }
        });
    }

    const radioPlayer = window.globalRadioPlayer;
    const radioPlayBtn = document.getElementById('antena1-play-btn');

    if (radioPlayBtn) {
        // Clone to remove previous event listeners attached by turbo:load to avoid duplicate clicks
        const newRadioPlayBtn = radioPlayBtn.cloneNode(true);
        radioPlayBtn.parentNode.replaceChild(newRadioPlayBtn, radioPlayBtn);
        const newIcon = newRadioPlayBtn.querySelector('i');

        // Sincroniza o ícone imediatamente com o estado atual do player
        if (!radioPlayer.paused) {
            newIcon.className = 'fas fa-pause';
            newIcon.style.marginLeft = '0';
        } else {
            newIcon.className = 'fas fa-play';
            newIcon.style.marginLeft = '3px';
        }

        // Tenta retomar se for a primeira carga e estava tocando na sessão anterior
        if (radioPlayer.paused && localStorage.getItem('radio_is_playing') === 'true') {
            const playPromise = radioPlayer.play();
            if (playPromise !== undefined) {
                playPromise.catch(error => {
                    console.log('Autoplay prevented by browser');
                    localStorage.setItem('radio_is_playing', 'false');
                });
            }
        }

        newRadioPlayBtn.addEventListener('click', () => {
            if (radioPlayer.paused) {
                radioPlayer.play();
            } else {
                radioPlayer.pause();
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', initApp);
document.addEventListener('turbo:load', initApp);

// Expor função de fechar globalmente para o onclick no HTML
window.closeBanner = function(bannerId) {
    const bannerElement = document.getElementById('banner-' + bannerId);
    if (bannerElement) {
        bannerElement.style.display = 'none';
        // Sem gravação na sessão
    }
};
