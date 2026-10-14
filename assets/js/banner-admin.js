/* Envio exclusivo dos banners, sem depender dos limites de multipart do PHP. */
(() => {
    const init = () => {
        const form = document.getElementById('banner-campaign-form');
        if (!form || form.dataset.uploadReady) return;
        form.dataset.uploadReady = 'true';
        const status = document.getElementById('banner-upload-status');
        const csrf = form.querySelector('[name="csrf_token"]').value;
        const maxFileSize = Number(form.dataset.maxFileSize);
        const sizeError = `Cada criativo deve ter no máximo ${maxFileSize / 1048576} MB.`;
        const fields = ['imagem', 'imagem_mobile'];
        let submitting = false;
        const message = text => { status.hidden = false; status.textContent = text; };
        for (const name of fields) {
            form.elements[name].addEventListener('change', () => {
                form.elements[name + '_token'].value = '';
                form.elements[name].setCustomValidity('');
            });
        }
        const api = async (action, values, chunk = null) => {
            const url = new URL(form.dataset.uploadUrl, location.href);
            const headers = {'X-CSRF-Token': csrf};
            let body;
            if (chunk) {
                url.search = new URLSearchParams({action, ...values});
                body = chunk;
                headers['Content-Type'] = 'application/octet-stream';
            } else {
                body = new FormData();
                for (const [key, value] of Object.entries({action, ...values})) body.append(key, value);
            }
            const response = await fetch(url, {method: 'POST', headers, body});
            let data;
            try { data = await response.json(); } catch { throw new Error('O servidor interrompeu o envio. Confira sua conexão e tente novamente.'); }
            if (!response.ok) throw new Error(data.error || 'Não foi possível enviar o banner.');
            return data;
        };
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (submitting) return;
            for (const name of fields) {
                const input = form.elements[name];
                if (input.files[0]?.size > maxFileSize) {
                    input.setCustomValidity(sizeError);
                    input.reportValidity();
                    message(sizeError + ' Selecione uma imagem menor.');
                    return;
                }
            }
            submitting = true;
            fields.forEach(name => { form.elements[name].disabled = true; });
            const buttons = [...form.querySelectorAll('button[type="submit"]')];
            buttons.forEach(button => { button.disabled = true; });
            try {
                for (const name of fields) {
                    const input = form.elements[name];
                    const file = input.files[0];
                    if (!file || form.elements[name + '_token'].value) continue;
                    const {token} = await api('start', {kind: name, size: String(file.size)});
                    let offset = 0;
                    while (offset < file.size) {
                        const chunk = file.slice(offset, offset + 262144);
                        let result;
                        for (let attempt = 0; ; attempt++) {
                            try { result = await api('chunk', {token, offset: String(offset)}, chunk); break; }
                            catch (error) { if (attempt >= 2) throw error; }
                        }
                        if (result.received <= offset || result.received > file.size) throw new Error('O envio foi interrompido. Tente novamente.');
                        offset = result.received;
                        message(`Enviando ${name === 'imagem' ? 'banner desktop' : 'banner mobile'}: ${Math.round(offset / file.size * 100)}%`);
                    }
                    await api('finish', {token});
                    form.elements[name + '_token'].value = token;
                }
                fields.forEach(name => { form.elements[name].disabled = true; });
                message('Imagens recebidas. Salvando a campanha…');
                HTMLFormElement.prototype.submit.call(form);
            } catch (error) {
                submitting = false;
                buttons.forEach(button => { button.disabled = false; });
                fields.forEach(name => { form.elements[name].disabled = false; });
                message(error.message);
            }
        });
    };
    document.addEventListener('DOMContentLoaded', init);
    if (document.readyState !== 'loading') init();
})();
