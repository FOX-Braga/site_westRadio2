document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('.menu-toggle');
    const backdrop = document.querySelector('.nav-backdrop');
    const sidebar = document.querySelector('.admin-sidebar');
    const mobileViewport = window.matchMedia('(max-width: 760px)');
    if (sidebar) sidebar.inert = mobileViewport.matches;
    const setMenu = (open) => {
        if (sidebar) sidebar.inert = mobileViewport.matches && !open;
        document.body.classList.toggle('mobile-nav-open', open);
        toggle?.setAttribute('aria-expanded', String(open));
        if (backdrop) backdrop.hidden = !open;
        if (open) sidebar?.querySelector('a')?.focus();
        else toggle?.focus();
    };
    toggle?.addEventListener('click', () => setMenu(toggle.getAttribute('aria-expanded') !== 'true'));
    backdrop?.addEventListener('click', () => setMenu(false));
    document.addEventListener('keydown', (event) => {
        if (!document.body.classList.contains('mobile-nav-open')) return;
        if (event.key === 'Escape') setMenu(false);
        if (event.key === 'Tab') {
            const links = sidebar.querySelectorAll('a, button');
            const first = links[0], last = links[links.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
    mobileViewport.addEventListener('change', () => {
        if (document.body.classList.contains('mobile-nav-open')) setMenu(false);
        if (sidebar) sidebar.inert = mobileViewport.matches;
    });
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', event => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });
    const form = document.querySelector('#form-noticia');
    if (!form) return;
    let dirty = false;
    form.addEventListener('input', () => { dirty = true; });
    form.addEventListener('change', () => { dirty = true; });
    form.addEventListener('submit', () => { dirty = false; });
    window.addEventListener('beforeunload', event => {
        if (!dirty) return;
        event.preventDefault(); event.returnValue = '';
    });
    const editor = document.querySelector('#editor-container .ql-editor');
    if (editor) {
        const feedback = document.createElement('div');
        feedback.className = 'editor-feedback';
        document.querySelector('#editor-container').after(feedback);
        const update = () => {
            const words = editor.textContent.trim().split(/\s+/u).filter(Boolean).length;
            feedback.textContent = `${words} palavras · ${Math.max(1, Math.ceil(words / 200))} min de leitura`;
        };
        new MutationObserver(() => { dirty = true; update(); }).observe(editor, {subtree: true, childList: true, characterData: true});
        update();
    }
    const status = form.querySelector('[name="status"]');
    const date = form.querySelector('[name="data_agendamento"]');
    const submit = form.querySelector('button[type="submit"]');
    const updateAction = () => {
        if (!submit || !status) return;
        const scheduled = date?.value && new Date(date.value) > new Date();
        submit.textContent = scheduled ? 'Salvar agendamento' : status.value === 'rascunho' ? 'Salvar rascunho' : 'Salvar e publicar';
    };
    status?.addEventListener('change', updateAction);
    date?.addEventListener('change', updateAction);
    updateAction();
    form.querySelectorAll('input, select, textarea').forEach((field, index) => {
        if (field.type === 'hidden' || field.id) return;
        const label = field.closest('.form-group')?.querySelector('label');
        if (label && !label.querySelector('input')) { field.id = `editor-field-${index}`; label.htmlFor = field.id; }
    });
});