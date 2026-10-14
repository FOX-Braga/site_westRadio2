/* Rotação de 5 s, como no módulo anterior; laterais restritas aos espaços livres. */
(() => {
    if (window.newsBanners) { window.newsBanners.init(); return; }
    let timer, observer, resizeFrame, scrollFrame, active = false;
    let sideLayouts = [];
    const followScroll = () => {
        scrollFrame = null;
        const scrollTop = window.scrollY;
        sideLayouts.forEach(({ad, start, end, height}) => {
            // Artes maiores que a janela passam por inteiro antes de acompanhar pelo rodapé.
            const inset = Math.min(16, window.innerHeight - height - 16);
            const top = Math.max(start, Math.min(scrollTop + inset, end));
            ad.style.transform = `translateY(${top - start}px)`;
        });
    };
    const scheduleScroll = () => {
        if (active && sideLayouts.length && scrollFrame == null) scrollFrame = requestAnimationFrame(followScroll);
    };
    const newsSelector = '.hero-grid, .news-grid-4, .cultura-grid, .opiniao-grid';
    const newsReference = (main, anchor, band) => {
        const blocks = Array.from(main.querySelectorAll(newsSelector)).filter(block => block.getBoundingClientRect().height > 0);
        if (!blocks.length) return main.getBoundingClientRect();
        // Nas editorias, topo e rodapé acompanham a primeira e a última linha de notícias.
        if (blocks.length === 1 && blocks[0].matches('.news-grid-4') && band !== 'MIDDLE') {
            const cards = Array.from(blocks[0].querySelectorAll('.news-card-v')).map(card => card.getBoundingClientRect());
            if (cards.length) {
                const rowTop = band === 'TOP' ? Math.min(...cards.map(card => card.top)) : Math.max(...cards.map(card => card.top));
                const row = cards.filter(card => Math.abs(card.top - rowTop) < 2);
                return {top: rowTop, bottom: Math.max(...row.map(card => card.bottom))};
            }
        }
        if (band === 'TOP') return blocks[0].getBoundingClientRect();
        if (band === 'BOTTOM') return blocks[blocks.length - 1].getBoundingClientRect();
        const following = blocks.find(block => anchor.compareDocumentPosition(block) & Node.DOCUMENT_POSITION_FOLLOWING);
        return (following || blocks[blocks.length - 1]).getBoundingClientRect();
    };
    const placeSides = () => {
        sideLayouts = [];
        const main = document.querySelector('.site-main');
        if (!main) return;
        const bounds = main.getBoundingClientRect();
        const footer = document.querySelector('.site-footer')?.getBoundingClientRect();
        const limit = Math.min(bounds.bottom, footer?.top ?? bounds.bottom) - 16;
        const occupied = {LEFT: [], RIGHT: []};
        document.querySelectorAll('.ad-side').forEach(ad => {
            ad.classList.remove('is-visible');
            ad.style.removeProperty('transform');
        });
        for (const band of ['TOP', 'MIDDLE', 'BOTTOM']) {
            document.querySelectorAll(`.ad-side[data-ad-slot$="_${band}"]`).forEach(ad => {
                const anchor = ad.parentElement.getBoundingClientRect();
                const side = ad.dataset.adSlot.includes('_LEFT_') ? 'LEFT' : 'RIGHT';
                const free = side === 'LEFT' ? anchor.left : document.documentElement.clientWidth - anchor.right;
                const slides = Array.from(ad.querySelectorAll('.ad-slide'));
                const available = Math.floor(free - 24);
                // Ajusta também pela altura do conteúdo, descontando o rótulo e os respiros.
                const availableImageHeight = Math.max(0, limit - bounds.top - 32);
                const displayWidth = slide => Math.min(Number(slide.dataset.adDisplayWidth), available,
                    Math.floor(availableImageHeight * Number(slide.dataset.adImageWidth) / Number(slide.dataset.adImageHeight)));
                const eligible = slides.filter(slide => Number(slide.dataset.adMinWidth) <= displayWidth(slide));
                slides.forEach(slide => { slide.dataset.adEligible = eligible.includes(slide) ? '1' : '0'; });
                if (window.innerWidth <= 768 || !eligible.length) return;
                const width = Math.max(...eligible.map(displayWidth));
                const imageHeight = Math.max(...eligible.map(slide => displayWidth(slide) * Number(slide.dataset.adImageHeight) / Number(slide.dataset.adImageWidth)));
                ad.dataset.adWidth = String(width);
                ad.style.setProperty('--ad-width', `${width}px`);
                ad.style.setProperty('--ad-ratio', String(width / imageHeight));
                const current = eligible.find(slide => !slide.hidden) || eligible[0];
                slides.forEach(slide => { slide.hidden = slide !== current; });
                ad.classList.add('is-visible');
                const height = ad.getBoundingClientRect().height;
                const reference = newsReference(main, ad.parentElement, band);
                const labelHeight = ad.querySelector('.ad-label')?.getBoundingClientRect().height || 0;
                const preferred = (reference.top + reference.bottom - height - labelHeight) / 2;
                const top = Math.max(bounds.top + 16, Math.min(preferred, limit - height));
                if (top + height > limit || occupied[side].some(rect => top < rect.bottom + 16 && top + height > rect.top - 16)) {
                    ad.classList.remove('is-visible');
                    return;
                }
                ad.style.top = `${top - anchor.top}px`;
                occupied[side].push({top, bottom: top + height});
                sideLayouts.push({ad, side, start: top + window.scrollY, height, end: limit + window.scrollY - height});
            });
        }
        // Cada anúncio termina antes da posição inicial do próximo na mesma lateral.
        for (const side of ['LEFT', 'RIGHT']) {
            const layouts = sideLayouts.filter(layout => layout.side === side).sort((a, b) => a.start - b.start);
            layouts.forEach((layout, index) => {
                const next = layouts[index + 1];
                if (next) layout.end = Math.min(layout.end, next.start - layout.height - 16);
            });
        }
        followScroll();
    };
    const schedule = () => {
        if (!active) return;
        cancelAnimationFrame(resizeFrame);
        resizeFrame = requestAnimationFrame(placeSides);
    };
    const stop = () => {
        active = false;
        clearInterval(timer);
        observer?.disconnect();
        cancelAnimationFrame(resizeFrame);
        cancelAnimationFrame(scrollFrame);
        scrollFrame = null;
        sideLayouts.forEach(({ad}) => ad.style.removeProperty('transform'));
        sideLayouts = [];
    };
    const init = () => {
        stop();
        active = true;
        schedule();
        const main = document.querySelector('.site-main');
        if (main && window.ResizeObserver) {
            observer = new ResizeObserver(schedule);
            observer.observe(main);
            main.querySelectorAll(newsSelector).forEach(block => observer.observe(block));
        }
        const slots = Array.from(document.querySelectorAll('.ad-slot'));
        timer = setInterval(() => {
            if (document.hidden) return;
            slots.forEach(slot => {
                if (!slot.isConnected || !slot.getClientRects().length) return;
                const slides = Array.from(slot.querySelectorAll('.ad-slide')).filter(slide => !slot.classList.contains('ad-side') || slide.dataset.adEligible === '1');
                if (slides.length < 2) return;
                const current = Math.max(0, slides.findIndex(slide => !slide.hidden));
                slides[current].hidden = true;
                slides[(current + 1) % slides.length].hidden = false;
            });
        }, 5000);
    };
    window.newsBanners = {init};
    document.addEventListener('DOMContentLoaded', init);
    document.addEventListener('turbo:load', init);
    document.addEventListener('turbo:before-cache', stop);
    window.addEventListener('resize', schedule);
    window.addEventListener('scroll', scheduleScroll, {passive: true});
    window.addEventListener('load', schedule);
    if (document.readyState !== 'loading') init();
})();
