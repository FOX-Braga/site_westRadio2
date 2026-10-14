// Geometria real no Chrome, sem tocar no banco ou instalar dependencias.
const fs = require('node:fs'), os = require('node:os'), path = require('node:path');
const cp = require('node:child_process'), net = require('node:net'), assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
const folder = fs.mkdtempSync(path.join(os.tmpdir(), '96news-banner-scroll-'));
const wait = ms => new Promise(resolve => setTimeout(resolve, ms));
const port = () => new Promise(resolve => {
    const server = net.createServer();
    server.listen(0, '127.0.0.1', () => { const number = server.address().port; server.close(() => resolve(number)); });
});
const css = fs.readFileSync(path.join(root, 'assets/css/banners.css'), 'utf8');
const js = fs.readFileSync(path.join(root, 'assets/js/banners.js'), 'utf8');
const side = (name, band) => `<aside class="ad-slot ad-side" data-ad-slot="SIDE_${name}_${band}">
    <span class="ad-label">PUBLICIDADE</span><div class="ad-creative">${[300, 160].map((width, index) =>
        `<a class="ad-slide" ${index ? 'hidden' : ''} data-ad-display-width="${width}" data-ad-min-width="${width}" data-ad-image-width="${width}" data-ad-image-height="600"><picture><img alt="Teste"></picture></a>`).join('')}</div></aside>`;
const band = name => `<div class="ad-band" data-ad-band="${name}">${side('LEFT', name)}${side('RIGHT', name)}</div>`;
const fixture = `<html><head><style>body{margin:0}header{height:200px}.site-main{padding:16px 0}.news{width:1200px;max-width:100%;margin:auto}.hero-grid,.news-grid-4{height:800px}.space{height:1600px}.site-footer{height:1000px}</style><style>${css}</style></head><body>
    <header></header><main class="site-main">${band('TOP')}<section class="news hero-grid"></section><div class="space"></div>
    ${band('MIDDLE')}<section class="news news-grid-4"></section><div class="space"></div>
    <section class="news news-grid-4"></section>${band('BOTTOM')}</main><footer class="site-footer"></footer></body></html>`;
let browser, ws, serial = 0;
const pending = new Map();
async function main() {
    const chrome = ['C:/Program Files/Google/Chrome/Application/chrome.exe', 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe'].find(fs.existsSync);
    assert(chrome, 'Chrome/Edge instalado');
    const number = await port();
    const log = fs.openSync(path.join(folder, 'chrome.log'), 'w');
    browser = cp.spawn(chrome, ['--headless=new', '--disable-gpu', '--no-sandbox', '--disable-gpu-sandbox', '--in-process-gpu', '--disable-features=NetworkServiceSandbox', '--no-first-run', '--no-default-browser-check', '--remote-allow-origins=*', '--remote-debugging-port=' + number, '--user-data-dir=' + path.join(folder, 'profile'), 'about:blank'], {stdio: ['ignore', log, log], windowsHide: true});
    let targets;
    for (let i = 0; i < 80; i++) { try { targets = await (await fetch('http://127.0.0.1:' + number + '/json/list')).json(); break; } catch { await wait(100); } }
    assert(targets?.length, 'CDP disponivel');
    ws = new WebSocket(targets[0].webSocketDebuggerUrl);
    await new Promise((resolve, reject) => { ws.onopen = resolve; ws.onerror = reject; });
    const cdp = (method, params = {}) => new Promise((resolve, reject) => {
        const id = ++serial;
        const timeout = setTimeout(() => { pending.delete(id); reject(Error('Timeout: ' + method)); }, 15000);
        pending.set(id, {resolve: result => { clearTimeout(timeout); resolve(result); }, reject: error => { clearTimeout(timeout); reject(error); }});
        ws.send(JSON.stringify({id, method, params}));
    });
    ws.onmessage = event => { const data = JSON.parse(event.data); if (data.id) { const request = pending.get(data.id); pending.delete(data.id); if (request) data.error ? request.reject(Error(JSON.stringify(data.error))) : request.resolve(data.result); } };
    ws.onclose = () => { for (const request of pending.values()) request.reject(Error('Chrome desconectou')); pending.clear(); };
    await cdp('Page.enable'); await cdp('Runtime.enable'); await cdp('Network.enable');
    await cdp('Network.setBlockedURLs', {urls: ['http://*', 'https://*']});
    const evaluate = async expression => {
        const result = await cdp('Runtime.evaluate', {expression, returnByValue: true, awaitPromise: true});
        if (result.exceptionDetails) throw Error(JSON.stringify(result.exceptionDetails));
        return result.result.value;
    };
    const viewport = async (width, height) => {
        await cdp('Emulation.setDeviceMetricsOverride', {width, height, deviceScaleFactor: 1, mobile: false});
        await evaluate("window.dispatchEvent(new Event('resize'))"); await wait(40);
    };
    const load = async html => {
        await cdp('Page.navigate', {url: 'about:blank'}); await wait(40);
        const {frameTree} = await cdp('Page.getFrameTree');
        await cdp('Page.setDocumentContent', {frameId: frameTree.frame.id, html});
        // O compositor deste host nao entrega RAF; apenas o agendamento e simulado.
        await evaluate('window.requestAnimationFrame=cb=>setTimeout(()=>cb(performance.now()),0);window.cancelAnimationFrame=clearTimeout');
        await evaluate(js); await evaluate('window.newsBanners.init()'); await wait(50);
    };
    const scroll = async y => {
        await evaluate(`window.scrollTo(0,${y});window.dispatchEvent(new Event('scroll'))`); await wait(30);
    };
    const measure = () => evaluate(`(()=>{const r=document.querySelector('.site-footer').getBoundingClientRect();return {y:scrollY,footer:r.top,overflow:document.documentElement.scrollWidth>document.documentElement.clientWidth,ads:[...document.querySelectorAll('.ad-side.is-visible')].map(ad=>{const b=ad.getBoundingClientRect(),a=ad.parentElement.getBoundingClientRect();return {slot:ad.dataset.adSlot,top:b.top,bottom:b.bottom,height:b.height,left:b.left,right:b.right,anchorLeft:a.left,anchorRight:a.right,width:b.width}})}})()`);
    const safe = state => {
        assert(!state.overflow, 'Sem overflow horizontal');
        for (const ad of state.ads) {
            assert(ad.bottom <= state.footer - 15.9, 'Sem cobrir o rodape');
            assert(ad.right <= ad.anchorLeft || ad.left >= ad.anchorRight, 'Sem cobrir as noticias');
        }
        for (const side of ['LEFT', 'RIGHT']) {
            const ads = state.ads.filter(ad => ad.slot.includes('_' + side + '_'));
            for (let i = 1; i < ads.length; i++) assert(ads[i - 1].bottom + 15.9 <= ads[i].top, 'Separacao entre banners da mesma lateral');
        }
    };
    await viewport(1920, 1080); await load(fixture);
    const initial = await measure(); assert.equal(initial.ads.length, 6); safe(initial);
    const first = initial.ads[0], middle = initial.ads[2];
    await scroll(first.top + 100);
    let state = await measure(); assert(Math.abs(state.ads[0].top - 16) < 1, 'Primeiro acompanha a janela'); safe(state);
    await scroll(middle.top - 316);
    state = await measure(); assert(Math.abs(state.ads[0].bottom + 16 - state.ads[2].top) < 1, 'Primeiro para ao chegar no proximo'); safe(state);
    await scroll(middle.top + 100);
    state = await measure(); assert(Math.abs(state.ads[2].top - 16) < 1, 'Proximo passa a acompanhar'); assert(state.ads[0].bottom < 0); safe(state);
    for (let y = 0; y < initial.footer + 300; y += 137) { await scroll(y); safe(await measure()); }
    state = await measure(); assert(Math.abs(state.ads.at(-1).bottom + 16 - state.footer) < 1, 'Ultimo respeita limite do rodape');
    await scroll(0); state = await measure();
    assert.deepEqual(state.ads.map(ad => ad.top), initial.ads.map(ad => ad.top), 'Retorno as posicoes iniciais ao subir');
    console.log('OK: duas laterais, tres posicoes, acompanhamento, passagem e rodape em toda a rolagem');
    await evaluate("document.querySelector('[data-ad-slot=SIDE_LEFT_MIDDLE]').remove();window.dispatchEvent(new Event('resize'))"); await wait(50);
    state = await measure(); const leftBottom = state.ads.find(ad => ad.slot === 'SIDE_LEFT_BOTTOM');
    await scroll(middle.top + 100); state = await measure();
    assert(Math.abs(state.ads.find(ad => ad.slot === 'SIDE_LEFT_TOP').top - 16) < 1, 'Lateral sem anuncio intermediario segue ate seu proprio proximo'); safe(state);
    await scroll(leftBottom.top + 100); safe(await measure());
    await viewport(1600, 900); state = await measure(); assert.equal(state.ads.length, 5); assert(state.ads.every(ad => ad.width === 160)); safe(state);
    await viewport(390, 844); assert.equal((await measure()).ads.length, 0, 'Ocultos no celular');
    await viewport(1920, 450); await scroll(0); state = await measure();
    await scroll(state.ads[0].top + 300); state = await measure();
    assert(Math.abs(state.ads[0].bottom - 434) < 1, 'Arte alta acompanha pela base sem impedir leitura completa'); safe(state);
    console.log('OK: lados independentes, redimensionamento, celular e janela mais baixa que a arte');
    await evaluate("document.dispatchEvent(new Event('turbo:before-cache'))");
    assert(await evaluate("[...document.querySelectorAll('.ad-side')].every(ad=>!ad.style.transform)"), 'Cache sem deslocamento antigo');
    await evaluate("document.dispatchEvent(new Event('turbo:load'));document.dispatchEvent(new Event('turbo:load'))"); await wait(50);
    safe(await measure()); await viewport(1920, 1080); await scroll(0);
    await evaluate("document.querySelector('.hero-grid').style.height='1200px';window.dispatchEvent(new Event('resize'))"); await wait(50);
    assert((await measure()).ads[0].top > first.top, 'Recalcula apos mudanca de altura das noticias');
    await load(fixture.replaceAll('height:800px', 'height:220px').replaceAll('height:1600px', 'height:0px'));
    state = await measure(); assert(state.ads.length > 0 && state.ads.length < 6, 'Oculta posicoes que nao cabem em pagina curta'); safe(state);
    console.log('OK: navegacao Turbo, mudanca de conteudo e pagina curta');
    const live = process.argv.find(arg => arg.startsWith('--live='))?.slice(7);
    if (live) {
        const origin = new URL(live); assert(['localhost', '127.0.0.1'].includes(origin.hostname));
        for (const route of ['/', '/categoria/economia']) {
            const response = await fetch(new URL(route, origin)); assert.equal(response.status, 200);
            let html = await response.text();
            html = html.replace(/<script\b[^>]*>[\s\S]*?<\/script>/g, '');
            html = html.replace(/<link\b[^>]*href="([^"]+)"[^>]*>/g, (tag, url) => {
                const pathname = new URL(url, origin).pathname;
                return pathname.startsWith('/assets/css/') ? '<style>' + fs.readFileSync(path.join(root, pathname), 'utf8') + '</style>' : '';
            });
            await load(html); const start = await measure(); assert(start.ads.length > 0, 'Laterais locais visiveis');
            for (let y = 0; y < start.footer; y += 300) { await scroll(y); safe(await measure()); }
            await scroll(0); safe(await measure());
            console.log('OK: HTML real local ' + route + ' durante toda a rolagem');
        }
    }
    console.log('Validacao concluida. Artefatos temporarios: ' + folder);
}
main().catch(error => { console.error(error); process.exitCode = 1; }).finally(() => { ws?.close(); browser?.kill(); });
