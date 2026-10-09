/* Midias fora do HTML/Base64 e envio em partes para evitar o limite por requisicao. */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('#form-noticia[data-upload-url]');
    if (!form) return;
    const container = document.querySelector('#editor-container');
    const status = document.querySelector('#editor-upload-status');
    const cover = form.querySelector('input[name="imagem"]');
    const token = form.querySelector('[name="csrf_token"]').value;
    const pending = new Set();
    let submitting = false, dirty = false, allowLeave = false, mediaFailure = null;
    const message = (text, failed = false) => {
        status.hidden = false;
        status.textContent = text;
        status.style.color = failed ? 'var(--adm-danger-text, #b42335)' : 'var(--adm-text-main, #24324a)';
    };
    const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
    async function api(fields, retry = false) {
        for (let attempt = 0; ; attempt++) {
            try {
                const body = new FormData();
                for (const [key, value] of Object.entries(fields)) body.append(key, value);
                const response = await fetch(form.dataset.uploadUrl, {method:'POST', headers:{'X-CSRF-Token':token}, body});
                const raw = await response.text();
                let data;
                try { data = JSON.parse(raw); } catch { throw new Error('O servidor interrompeu o envio. Confira a conexão e tente novamente.'); }
                if (!response.ok) {
                    const error = new Error(data.error || 'Não foi possível concluir o envio.');
                    error.permanent = response.status < 500;
                    throw error;
                }
                return data;
            } catch (error) {
                if (!retry || error.permanent || attempt >= 2) throw error;
                await pause(500 * (attempt + 1));
            }
        }
    }
    async function upload(file, kind, label = file.name || 'conteúdo') {
        if (!file.size) throw new Error('O arquivo está vazio.');
        const start = await api({action:'start',kind,size:String(file.size)});
        let offset = 0;
        while (offset < file.size) {
            const chunk = file.slice(offset, offset + 1048576);
            const result = await api({action:'chunk',token:start.token,offset:String(offset),chunk}, true);
            if (result.received <= offset || result.received > file.size) throw new Error('Progresso de envio inválido. Tente novamente.');
            offset = result.received;
            message(`Enviando ${label}: ${Math.round(offset / file.size * 100)}%`);
        }
        return api({action:'finish',token:start.token}, true);
    }
    if (!window.Quill) { message('O editor não carregou. Atualize a página antes de enviar a matéria.', true); return; }
    const BlockEmbed = Quill.import('blots/block/embed');
    class MediaVideo extends BlockEmbed {
        static create(value) {
            const node = super.create();
            const safe = Quill.import('formats/link').sanitize(value);
            node.setAttribute('src', safe);
            node.setAttribute('controls', '');
            node.setAttribute('preload', 'metadata');
            node.setAttribute('playsinline', '');
            node.style.maxWidth = '100%';
            return node;
        }
        static value(node) { return node.getAttribute('src'); }
    }
    MediaVideo.blotName = 'mediaVideo';
    MediaVideo.tagName = 'VIDEO';
    Quill.register(MediaVideo, true);
    Quill.import('ui/icons').mediaVideo = '<svg viewBox="0 0 18 18"><rect class="ql-stroke" x="2" y="6" width="10" height="9" rx="1"/><path class="ql-stroke" d="M12 9l4-2v7l-4-2M7 11V1M4 4l3-3 3 3"/></svg>';
    const quill = new Quill(container, {
        theme:'snow', placeholder:'Escreva sua matéria e inclua imagens ou vídeos.',
        modules:{toolbar:[[{header:[1,2,3,false]}],['bold','italic','underline','strike'],[{color:[]},{background:[]}],['blockquote','code-block'],[{list:'ordered'},{list:'bullet'}],[{align:[]}],['link','image','video','mediaVideo'],['clean']]}
    });
    const toolbar = quill.getModule('toolbar');
    const track = task => {
        pending.add(task);
        task.then(() => { mediaFailure = null; }, error => { mediaFailure = error; message(error.message, true); }).finally(() => pending.delete(task));
    };
    async function insertMedia(file, kind) {
        const position = (quill.getSelection(true) || {index:quill.getLength() - 1}).index;
        const result = await upload(file, kind);
        quill.insertEmbed(position, kind === 'video' ? 'mediaVideo' : 'image', result.url, 'user');
        quill.setSelection(position + 1, 0, 'silent');
        message(`${file.name || 'Arquivo'} adicionado à matéria.`);
    }
    const choose = kind => {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = kind === 'video' ? 'video/mp4,video/webm,video/ogg' : 'image/jpeg,image/png,image/webp,image/gif';
        input.onchange = () => { if (input.files[0]) track(insertMedia(input.files[0], kind)); };
        input.click();
    };
    toolbar.addHandler('image', () => choose('image'));
    toolbar.addHandler('mediaVideo', () => choose('video'));
    for (const [selector,title] of [['.ql-image','Enviar imagem do computador'],['.ql-video','Incorporar vídeo por link'],['.ql-mediaVideo','Enviar vídeo do computador (MP4, WEBM ou OGV)']]) {
        const button = form.querySelector(selector);
        button?.setAttribute('title',title); button?.setAttribute('aria-label',title);
    }
    const hint = document.createElement('p');
    hint.textContent = 'Envie imagens e vídeos do computador ou incorpore um vídeo por link.';
    hint.style.cssText = 'font-size:12px;color:var(--adm-text-muted);margin-top:10px';
    container.after(hint);
    const readFiles = event => {
        const files = Array.from(event.clipboardData?.files || event.dataTransfer?.files || []);
        const media = files.filter(file => /^(image|video)\//.test(file.type));
        if (!media.length) return;
        event.preventDefault(); event.stopImmediatePropagation();
        for (const file of media) track(insertMedia(file, file.type.startsWith('video/') ? 'video' : 'image'));
    };
    quill.root.addEventListener('paste', readFiles, true);
    quill.root.addEventListener('drop', readFiles, true);
    quill.root.addEventListener('dragover', event => { if (event.dataTransfer?.types.includes('Files')) event.preventDefault(); });
    quill.on('text-change', (_delta,_old,source) => { if (source === 'user') dirty = true; });
    cover?.addEventListener('change', () => { dirty = true; form.querySelector('[name="imagem_token"]').value = ''; });
    form.addEventListener('input', () => { dirty = true; });
    window.addEventListener('beforeunload', event => {
        if (allowLeave || (!dirty && !submitting && !pending.size)) return;
        event.preventDefault(); event.returnValue = '';
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (submitting) return;
        submitting = true;
        const buttons = Array.from(form.querySelectorAll('button[type="submit"]'));
        buttons.forEach(button => { button.disabled = true; });
        quill.enable(false);
        try {
            await Promise.allSettled(Array.from(pending));
            if (mediaFailure) throw new Error('Uma mídia não terminou de enviar. Tente adicioná-la novamente antes de salvar.');
            // Imagens coladas de outros editores tambem saem do HTML/Base64.
            for (const image of quill.root.querySelectorAll('img[src^="data:"]')) {
                const blob = await (await fetch(image.src)).blob();
                const result = await upload(blob, 'image', 'imagem da matéria');
                image.setAttribute('src', result.url);
            }
            if (cover?.files[0] && !form.querySelector('[name="imagem_token"]').value) {
                const result = await upload(cover.files[0], 'cover', 'foto de capa');
                form.querySelector('[name="imagem_token"]').value = result.token;
            }
            const html = new Blob([quill.root.innerHTML], {type:'text/html;charset=utf-8'});
            const content = await upload(html, 'content', 'texto da matéria');
            form.querySelector('[name="conteudo_token"]').value = content.token;
            form.querySelector('[name="conteudo"]').value = '';
            form.querySelector('[name="conteudo_b64"]').value = '';
            if (cover) cover.disabled = true;
            message('Envio concluído. Salvando a matéria…');
            allowLeave = true;
            HTMLFormElement.prototype.submit.call(form);
        } catch (error) {
            submitting = false;
            quill.enable(true);
            buttons.forEach(button => { button.disabled = false; });
            if (cover) cover.disabled = false;
            message(error.message, true);
        }
    });
});