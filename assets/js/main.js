document.addEventListener('DOMContentLoaded', () => {
    // Menu Mobile
    const mobileBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');

    if (mobileBtn && navLinks) {
        mobileBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            const isExpanded = navLinks.classList.contains('active');
            mobileBtn.setAttribute('aria-expanded', isExpanded);
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
        themeToggleBtn.addEventListener('click', () => {
            let theme = document.documentElement.getAttribute('data-theme');
            let newTheme = theme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });
    }

    function updateThemeIcon(theme) {
        if (!themeToggleBtn) return;
        if (theme === 'dark') {
            themeToggleBtn.innerHTML = '<i class="fas fa-sun"></i>';
        } else {
            themeToggleBtn.innerHTML = '<i class="fas fa-moon"></i>';
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

        acceptCookiesBtn.addEventListener('click', () => {
            // Salva a aceitação no navegador por tempo indeterminado
            localStorage.setItem('cookies_accepted', 'true');
            // Esconde o banner
            cookieBanner.classList.remove('show');
        });
    }

    // A lógica de sessionStorage foi removida conforme solicitado:
    // os banners voltarão a aparecer sempre que a página for recarregada.

    // Radio Player Logic
    const radioPlayer = document.getElementById('antena1-player');
    const radioPlayBtn = document.getElementById('antena1-play-btn');
    if (radioPlayer && radioPlayBtn) {
        radioPlayBtn.addEventListener('click', () => {
            const icon = radioPlayBtn.querySelector('i');
            if (radioPlayer.paused) {
                radioPlayer.play();
                icon.className = 'fas fa-pause';
                icon.style.marginLeft = '0';
            } else {
                radioPlayer.pause();
                icon.className = 'fas fa-play';
                icon.style.marginLeft = '1px';
            }
        });
    }
});


// Expor função de fechar globalmente para o onclick no HTML
window.closeBanner = function(bannerId) {
    const bannerElement = document.getElementById('banner-' + bannerId);
    if (bannerElement) {
        bannerElement.style.display = 'none';
        // Sem gravação na sessão
    }
};
