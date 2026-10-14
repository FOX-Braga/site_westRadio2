// node scratch/test_banners_system.cjs
// Banco, uploads, perfil do Chrome e fontes de comparação exclusivamente temporários.
const fs = require('node:fs'), os = require('node:os'), path = require('node:path');
const cp = require('node:child_process'), net = require('node:net'), http = require('node:http'), assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..').replaceAll('\\', '/');
const folder = fs.mkdtempSync(path.join(os.tmpdir(), '96news-banners-')).replaceAll('\\', '/');
const database = folder + '/fixture.sqlite', media = folder + '/media';
fs.mkdirSync(media);
const quote = s => "'" + s.replaceAll("'", "\\'") + "'";
const php = code => {
    const r = cp.spawnSync('php', ['-r', code], {encoding: 'utf8', windowsHide: true});
    if (r.status !== 0) throw Error(r.stdout + r.stderr);
    return r.stdout;
};
const db = `$pdo=new PDO('sqlite:'.${quote(database)},null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);`;
const update = code => php(db + code);
const check = (value, label) => { assert.ok(value, label); console.log('OK:', label); };
const port = () => new Promise(resolve => { const s = net.createServer(); s.listen(0, '127.0.0.1', () => { const p = s.address().port; s.close(() => resolve(p)); }); });
const wait = ms => new Promise(r => setTimeout(r, ms));
let server, browser, cdp, proxy;
let checks = 0;

async function main() {
    php(db + `preg_match_all('/CREATE TABLE IF NOT EXISTS.*?;/s',file_get_contents(${quote(root + '/config/database_sqlite.sql')}),$m);foreach($m[0] as $sql)$pdo->exec($sql);
    $pdo->exec("INSERT INTO usuarios (nome,email,senha,tipo) VALUES ('Admin','admin@example.invalid','teste','admin'),('Jornalista','j@example.invalid','teste','jornalista'),('Leitor','l@example.invalid','teste','user')");
    $names=['mundo','economia','politica','saude','esportes','cultura','agronegocio','tecnologia','opiniao'];
    foreach($names as $i=>$slug){$pdo->prepare('INSERT INTO categorias(id,nome,slug) VALUES(?,?,?)')->execute([$i+1,ucfirst($slug),$slug]);for($j=0;$j<16;$j++)$pdo->prepare('INSERT INTO noticias(titulo,slug,subtitulo,conteudo,autor_id,categoria_id,status,criado_em) VALUES(?,?,?,?,?,?,?,?)')->execute(['Noticia '.$slug.' '.$j,$slug.'-'.$j,'Resumo','<p>Texto de teste</p>',1,$i+1,'publicado','2026-10-01 10:00:00']);}
    require ${quote(root + '/includes/banners.php')};bannerInstall($pdo);
    foreach([[970,90],[970,250],[320,100],[300,250],[120,600],[160,600],[300,600],[640,360],[1280,720],[1920,1080],[3840,2160]] as [$w,$h]){$im=imagecreatetruecolor($w,$h);imagefill($im,0,0,imagecolorallocate($im,35,90,140));imagestring($im,4,10,10,$w.' x '.$h,imagecolorallocate($im,255,255,255));imagepng($im,${quote(media)}.'/'.$w.'x'.$h.'.png');imagedestroy($im);}`);

    // Asserções de seleção também cobrem os limites exatos do período.
    const matchResults = JSON.parse(php(`require ${quote(root + '/includes/banners.php')};
    $b=['status'=>'ativo','link'=>'https://example.invalid','slots'=>'["TOP"]','categories'=>'["mundo"]','show_home'=>0,'all_categories'=>0,'starts_at'=>'2026-10-01 10:00:00','ends_at'=>'2026-10-31 10:00:00'];
    $r=[bannerMatches($b,'category','TOP','mundo','2026-10-01 10:00:00'),!bannerMatches($b,'home','TOP',null,'2026-10-02 10:00:00'),!bannerMatches($b,'category','TOP','economia','2026-10-02 10:00:00'),!bannerMatches($b,'category','BOTTOM','mundo','2026-10-02 10:00:00'),!bannerMatches($b,'category','TOP','mundo','2026-10-01 09:59:59'),!bannerMatches($b,'category','TOP','mundo','2026-10-31 10:00:01'),bannerMatches($b,'category','TOP','mundo','2026-10-31 10:00:00')];
    $b['all_categories']=1;$r[]=bannerMatches($b,'category','TOP','economia','2026-10-02 10:00:00');$r[]=!bannerMatches($b,'home','TOP',null,'2026-10-02 10:00:00');$b['status']='pausado';$r[]=!bannerMatches($b,'category','TOP','mundo','2026-10-02 10:00:00');echo json_encode($r);`));
    check(matchResults.every(Boolean), 'Isolamento de páginas/slots, todas as editorias, pausa e limites de datas');
    const timezoneResult=JSON.parse(php(`require ${quote(root+'/includes/banners.php')};
    date_default_timezone_set('America/Campo_Grande');$before=bannerNow();$local=(new DateTimeImmutable('now',new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d H:i:s');
    $b=['status'=>'ativo','link'=>'https://example.invalid','slots'=>'["SIDE_LEFT_TOP"]','categories'=>'[]','show_home'=>1,'all_categories'=>0,'starts_at'=>$before,'ends_at'=>null];
    $r=[$before===$local,bannerMatches($b,'home','SIDE_LEFT_TOP'),date_default_timezone_get()==='America/Campo_Grande'];date_default_timezone_set('UTC');$r[]=bannerNow()===$before;echo json_encode($r);`));
    check(timezoneResult.every(Boolean),'Agenda usa São Paulo e não depende nem altera o fuso editorial');

    for (const [name, relative] of [['baseline-index.php','index.php'], ['baseline-category.php','categoria.php'], ['baseline-header.php','includes/header.php']]) {
        let source = cp.execFileSync('git', ['show', 'HEAD:' + relative], {cwd: root, encoding: 'utf8'});
        if (relative !== 'includes/header.php') source = source.replaceAll("__DIR__ . '/includes/header.php'", quote(folder + '/baseline-header.php'));
        source = source.replaceAll('__DIR__', quote(relative === 'includes/header.php' ? root + '/includes' : root));
        fs.writeFileSync(folder + '/' + name, source);
    }
    const httpPort = await port(), phpPort = await port(), chromePort = await port();
    const origin = 'http://127.0.0.1:' + httpPort;
    const router = `<?php
    define('BANNER_UPLOAD_DIR', ${quote(media + '/')});
    require ${quote(root + '/config/database.php')};
    ini_set('error_log', ${quote(folder + '/php-errors.log')});
    ${db}
    $r=new ReflectionClass(Database::class);$instance=$r->newInstanceWithoutConstructor();$r->getProperty('conn')->setValue($instance,$pdo);$r->getProperty('instance')->setValue(null,$instance);
    $_SESSION=[];$user=(int)($_COOKIE['test_user']??0);if($user)$_SESSION=['user_id'=>$user,'user_tipo'=>'admin','user_nome'=>'Teste','csrf_token'=>'banners-test-token'];
    $uri=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
    if(preg_match('~^/uploads/banners/([a-zA-Z0-9_.-]+)$~',$uri,$m)){header('Content-Type: image/png');readfile(${quote(media + '/')}.$m[1]);exit;}
    if(strpos($uri,'/assets/')===0 && strpos($uri,'..')===false){$file=${quote(root)}.$uri;if(is_file($file)){header('Content-Type: '.(str_ends_with($file,'.css')?'text/css':(str_ends_with($file,'.js')?'application/javascript':'image/png')));readfile($file);exit;}}
    if($uri==='/admin/banners.php'){$_SERVER['SCRIPT_NAME']=$_SERVER['PHP_SELF']=$uri;require ${quote(root + '/admin/banners.php')};exit;}
    $baseline=strpos($uri,'/baseline')===0;if($baseline)$uri=substr($uri,9)?:'/';
    $_SERVER['REQUEST_URI']=$uri.(!empty($_SERVER['QUERY_STRING'])?'?'.$_SERVER['QUERY_STRING']:'');
    if(preg_match('~^/categoria/([a-z0-9-]+)$~',$uri,$m)){$_GET['slug']=$m[1];$_SERVER['SCRIPT_NAME']=$_SERVER['PHP_SELF']='/categoria.php';require $baseline?${quote(folder + '/baseline-category.php')}:${quote(root + '/categoria.php')};exit;}
    if($uri==='/'){$_SERVER['SCRIPT_NAME']=$_SERVER['PHP_SELF']='/index.php';require $baseline?${quote(folder + '/baseline-index.php')}:${quote(root + '/index.php')};exit;}
    http_response_code(404);`;
    fs.writeFileSync(folder + '/router.php', router);
    const log=fs.openSync(folder+'/server.log','w');
    server = cp.spawn('php', ['-d','display_errors=1','-d','post_max_size=30M','-d','upload_max_filesize=25M','-S','127.0.0.1:' + phpPort,folder + '/router.php'], {cwd:root,stdio:['ignore',log,log],windowsHide:true});
    // O proxy impede conexões especulativas do Chrome de bloquear o PHP monothread no Windows.
    proxy=http.createServer((incoming,outgoing)=>{const upstream=http.request({hostname:'127.0.0.1',port:phpPort,path:incoming.url,method:incoming.method,headers:{...incoming.headers,connection:'close'}},response=>{outgoing.writeHead(response.statusCode,response.headers);response.pipe(outgoing);});upstream.on('error',()=>{outgoing.writeHead(503);outgoing.end();});incoming.pipe(upstream);});
    await new Promise(resolve=>proxy.listen(httpPort,'127.0.0.1',resolve));
    const request = async (route, user = 1, options = {}) => {
        const req=new Request(origin+route,{...options,headers:{Cookie:'test_user='+user,...options.headers}});
        const body=Buffer.from(await req.arrayBuffer());
        return new Promise((resolve,reject)=>{const outgoing=http.request(req.url,{method:req.method,headers:{...Object.fromEntries(req.headers),Connection:'close','Content-Length':String(body.length)}},incoming=>{const chunks=[];incoming.on('data',c=>chunks.push(c));incoming.on('end',()=>{const raw=Buffer.concat(chunks).toString();resolve({status:incoming.statusCode,text:async()=>raw});});});outgoing.on('error',reject);outgoing.end(body);});
    };
    for(let i=0;i<50;i++){try{const response=await request('/');await response.text();if(response.status===200)break;}catch{}await wait(100);}

    for(const user of [0,2,3]) { const r = await request('/admin/banners.php',user); check(r.status === (user ? 403 : 302), 'Acesso à aba protegido: usuário ' + user); }
    let r = await request('/admin/banners.php');
    let html = await r.text();
    check(r.status === 200 && !/name="(?:show_home|all_categories|slots\[\]|categories\[\])"[^>]* checked/.test(html), 'Cadastro sem páginas/posições preselecionadas');
    r = await request('/admin/banners.php',1,{method:'POST',body:new URLSearchParams({action:'toggle',id:'1',csrf_token:'incorreto'})});
    check(r.status === 403, 'CSRF obrigatório');
    const makeForm = ({slots=['TOP'], categories=[], home=false, all=false, mobile=null, desktop='970x250.png', ...extra}={}) => {
        const f=new FormData();
        for(const [key,value] of Object.entries({csrf_token:'banners-test-token',action:'save',titulo:'Campanha teste',anunciante:'Anunciante',link:'https://example.invalid/destino',status:'ativo',...extra}))f.set(key,value);
        for(const slot of slots)f.append('slots[]',slot);
        for(const category of categories)f.append('categories[]',category);
        if(home)f.set('show_home','1');if(all)f.set('all_categories','1');
        if(desktop)f.set('imagem',new Blob([fs.readFileSync(media+'/'+desktop)],{type:'image/png'}),desktop);
        if(mobile)f.set('imagem_mobile',new Blob([fs.readFileSync(media+'/'+mobile)],{type:'image/png'}),mobile);
        return f;
    };
    const save = async options => {const result=await request('/admin/banners.php',1,{method:'POST',body:makeForm(options)});assert.equal(result.status,302,await result.text());return Number(update("echo $pdo->query('SELECT MAX(id) FROM banners')->fetchColumn();"));};
    const postAction = (id, action) => request('/admin/banners.php',1,{method:'POST',body:new URLSearchParams({id:String(id),action,csrf_token:'banners-test-token'})});
    const homeId = await save({home:true,mobile:'320x100.png'});
    const worldId = await save({categories:['mundo'],titulo:'Banner Mundo'});
    const economyId = await save({categories:['economia'],titulo:'Banner Economia'});
    check(!(await (await request('/categoria/mundo')).text()).includes('Banner Economia') && !(await (await request('/categoria/economia')).text()).includes('Banner Mundo'), 'Mundo e Economia não compartilham campanhas');
    check(!(await (await request('/categoria/mundo')).text()).includes('data-campaign="'+homeId+'"'), 'Campanha da Home não vaza para editoria');
    const allId = await save({all:true,categories:['mundo'],slots:['BOTTOM'],titulo:'Todas'});
    for(const category of ['mundo','economia','politica','saude','esportes','cultura','agronegocio','tecnologia','opiniao','ultimas-noticias'])check((await (await request('/categoria/'+category)).text()).includes('data-campaign="'+allId+'"'), 'Todas as editorias: '+category);
    check(!(await (await request('/')).text()).includes('data-campaign="'+allId+'"'), 'Todas as editorias não inclui Home');
    await save({id:String(allId),categories:['mundo'],slots:['BOTTOM'],desktop:null,titulo:'Somente Mundo'});
    check(!(await (await request('/categoria/economia')).text()).includes('data-campaign="'+allId+'"'), 'Desmarcar todas respeita editorias escolhidas');
    await postAction(homeId,'toggle');
    check(!(await (await request('/')).text()).includes('data-campaign="'+homeId+'"'), 'Pausa remove a campanha');
    await postAction(homeId,'toggle');
    for(const options of [{home:true,starts_at:'2099-01-01T00:00'},{home:true,ends_at:'2000-01-01T00:00'}]) {const id=await save(options);check(!(await(await request('/')).text()).includes('data-campaign="'+id+'"'),'Período controla exibição');}
    const countBefore = Number(update("echo $pdo->query('SELECT COUNT(*) FROM banners')->fetchColumn();"));
    for (const options of [{home:true,slots:[]},{slots:['TOP']},{home:true,link:'javascript:alert(1)'},{home:true,starts_at:'2026-02-30T00:00'},{home:true,starts_at:'2026-11-01T00:00',ends_at:'2026-10-01T00:00'},{categories:['inexistente']}]) {
        r=await request('/admin/banners.php',1,{method:'POST',body:makeForm(options)});check(r.status!==302,'Cadastro inválido rejeitado: '+JSON.stringify(options));
    }
    check(Number(update("echo $pdo->query('SELECT COUNT(*) FROM banners')->fetchColumn();"))===countBefore,'Falhas não criam campanhas');
    const deleteId = await save({home:true,mobile:'320x100.png'});
    const deleted = JSON.parse(update('$s=$pdo->query("SELECT b.imagem,s.imagem_mobile FROM banners b JOIN banner_settings s ON b.id=s.banner_id WHERE b.id='+deleteId+'");echo json_encode($s->fetch());'));
    await postAction(deleteId,'delete');
    check(!fs.existsSync(media+'/'+deleted.imagem)&&!fs.existsSync(media+'/'+deleted.imagem_mobile),'Exclusão remove ambos os criativos sem órfãos');
    const baselineResponse=await request('/baseline/');
    const baselineHtml=await baselineResponse.text();
    check(baselineResponse.status===200&&baselineHtml.includes('site-header'),'Página original disponível para comparação');

    const chrome = ['C:/Program Files/Google/Chrome/Application/chrome.exe','C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe'].find(fs.existsSync);
    if(!chrome) throw Error('Chrome/Edge não encontrado para validação visual obrigatória.');
    const browserLog=fs.openSync(folder+'/chrome.log','w');
    browser=cp.spawn(chrome,['--headless=new','--disable-gpu','--no-sandbox','--disable-gpu-sandbox','--in-process-gpu','--disable-features=NetworkServiceSandbox','--no-first-run','--no-default-browser-check','--remote-allow-origins=*','--remote-debugging-port='+chromePort,'--user-data-dir='+folder+'/chrome','about:blank'],{stdio:['ignore',browserLog,browserLog],windowsHide:true});
    let targets;
    for(let i=0;i<80;i++){try{targets=await(await fetch('http://127.0.0.1:'+chromePort+'/json/list')).json();break;}catch{await wait(100);}}
    assert.ok(targets?.length,'CDP disponível');
    const ws=new WebSocket(targets[0].webSocketDebuggerUrl);
    await new Promise((resolve,reject)=>{ws.onopen=resolve;ws.onerror=event=>reject(Error(event.message || event.error?.message || 'WebSocket CDP indisponível: '+targets[0].webSocketDebuggerUrl));});
    let serial=0;const requests=new Map();
    cdp=(method,params={})=>new Promise((resolve,reject)=>{const id=++serial;const timeout=setTimeout(()=>reject(Error('CDP timeout: '+method)),15000);requests.set(id,{resolve:value=>{clearTimeout(timeout);resolve(value)},reject:error=>{clearTimeout(timeout);reject(error)}});ws.send(JSON.stringify({id,method,params}));});
    ws.onclose=()=>{for(const p of requests.values())p.reject(Error('Chrome desconectou'));requests.clear();};
    ws.onmessage=async event=>{const data=JSON.parse(event.data);if(data.id){const p=requests.get(data.id);requests.delete(data.id);data.error?p.reject(Error(JSON.stringify(data.error))):p.resolve(data.result);}
    };
    await cdp('Page.enable');await cdp('Runtime.enable');await cdp('Network.enable');await cdp('Network.setBlockedURLs',{urls:['https://*']});
    const evaluate=async expression=>{const result=await cdp('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true});if(result.exceptionDetails)throw Error(JSON.stringify(result.exceptionDetails));return result.result.value;};
    // HTML real do PHP com os mesmos assets incorporados para tornar a captura independente
    // de rede e das restrições de navegação local do Chrome neste ambiente.
    const navigate=async (route,live=null)=>{
        const assetOrigin=live?.origin??origin;
        let html=live?.html??await(await request(route,0)).text();
        html=html.replace(/<link\b[^>]*href="([^"]+)"[^>]*>/g,(tag,url)=>url.startsWith(assetOrigin+'/assets/css/')?'<style>'+fs.readFileSync(root+new URL(url).pathname,'utf8')+'</style>':tag);
        html=html.replace(/<script\b[^>]*src="([^"]+)"[^>]*><\/script>/g,(tag,url)=>url.startsWith(assetOrigin+'/assets/js/')?'<script>'+fs.readFileSync(root+new URL(url).pathname,'utf8')+'</script>':tag);
        html=html.replace(/\b(src|srcset)="([^"]+)"/g,(tag,attribute,url)=>{
            if(!url.startsWith(assetOrigin+'/'))return tag;
            const pathname=new URL(url).pathname;
            const file=!live&&pathname.startsWith('/uploads/banners/')?media+'/'+path.basename(pathname):root+pathname;
            return fs.existsSync(file)&&/\.(png|jpe?g|webp)$/.test(file)?attribute+'="data:image/'+(file.endsWith('.png')?'png':file.endsWith('.webp')?'webp':'jpeg')+';base64,'+fs.readFileSync(file).toString('base64')+'"':tag;
        });
        // O compositor headless deste host não entrega frames; simula apenas o agendamento.
        html=html.replace('<head>','<head><script>window.requestAnimationFrame=cb=>setTimeout(()=>cb(performance.now()),0);window.cancelAnimationFrame=clearTimeout;</script>');
        await cdp('Page.navigate',{url:'about:blank'});await wait(50);
        const {frameTree}=await cdp('Page.getFrameTree');await cdp('Page.setDocumentContent',{frameId:frameTree.frame.id,html});
        await wait(180);
        await evaluate('Promise.race([Promise.all([...document.querySelectorAll(".ad-slot img")].map(img=>{img.loading="eager";return img.decode().catch(()=>{})})),new Promise(r=>setTimeout(r,500))])');
        await evaluate('window.newsBanners?.init()');await wait(50);
    };
    const sizes=[[1920,1080],[1600,900],[1440,900],[1366,768],[1280,720],[1024,768],[768,1024],[430,932],[390,844],[375,812],[360,800]];
    const viewport=([width,height])=>cdp('Emulation.setDeviceMetricsOverride',{width,height,deviceScaleFactor:1,mobile:false});
    const metrics=()=>evaluate(`(()=>{const box=e=>{const r=e.getBoundingClientRect();return [r.x,r.y,r.width,r.height].map(x=>Math.round(x*100)/100)};return {header:box(document.querySelector('.site-header')),footer:box(document.querySelector('.site-footer')),containers:[...document.querySelectorAll('.site-main .container')].map(box),grids:[...document.querySelectorAll('.site-main .news-grid-4,.hero-grid,.cultura-grid,.opiniao-grid')].map(box),cards:[...document.querySelectorAll('.news-card-v')].map(box),scroll:document.documentElement.scrollWidth,adCount:document.querySelectorAll('.ad-band').length}})()`);

    // Todos pausados: geometria e estilos editoriais iguais ao HEAD anterior.
    update("$pdo->exec(\"UPDATE banners SET status='pausado'\");");
    for(const size of sizes){await viewport(size);for(const route of ['/','/categoria/mundo']){
        await navigate('/baseline'+route);const before=await metrics();
        await navigate(route);const after=await metrics();
        assert.deepEqual(after,before,'Layout sem campanhas '+size+' '+route);checks++;
    }console.log('OK: Sem campanhas, comparação '+size.join('x'));}
    console.log('OK: Comparação antes/depois sem campanhas: '+checks+' páginas/resoluções, geometria idêntica');

    // Todos os slots, dimensões laterais e todos os dispositivos. Sem dependências npm.
    const centralIds=[];for(const slot of ['TOP','MIDDLE','BOTTOM'])centralIds.push(await save({home:true,all:true,slots:[slot],desktop:slot==='TOP'?'970x90.png':'970x250.png',mobile:'320x100.png'}));
    const sides=[];for(const width of [120,160,300])for(const band of ['TOP','MIDDLE','BOTTOM'])for(const side of ['LEFT','RIGHT'])sides.push(await save({home:true,all:true,slots:['SIDE_'+side+'_'+band],desktop:width+'x600.png'}));
    for(const size of sizes){await viewport(size);for(const route of ['/','/categoria/mundo']){await navigate(route);
        const results=await evaluate(`(()=>{const main=document.querySelector('.site-main').getBoundingClientRect(),footer=document.querySelector('.site-footer').getBoundingClientRect();const content=[...document.querySelectorAll('.site-main .container')].map(e=>e.getBoundingClientRect());const ads=[...document.querySelectorAll('.ad-slot')].filter(e=>e.getClientRects().length).map(e=>{const r=e.getBoundingClientRect();return {slot:e.dataset.adSlot,width:+e.dataset.adWidth,left:r.left,right:r.right,top:r.top,bottom:r.bottom,side:e.classList.contains('ad-side'),fit:[...e.querySelectorAll('img')].every(img=>getComputedStyle(img).objectFit==='contain'),mobile:e.querySelector('img').naturalWidth===320};});return {ads,mainTop:main.top,footerTop:footer.top,viewport:document.documentElement.clientWidth,scroll:document.documentElement.scrollWidth,content:content.map(r=>({left:r.left,right:r.right})),slots:[...document.querySelectorAll('.ad-slot')].map(e=>e.dataset.adSlot)}})()`);
        assert.equal(results.ads.filter(a=>!a.side).length,3,'Três slots centrais visíveis');
        if(size[0]===1920&&route==='/')assert.equal(results.ads.filter(a=>a.side).length,6,'Seis laterais visíveis quando há espaço');
        for(const ad of results.ads){assert.ok(ad.left>=0&&ad.right<=results.viewport+.1,'Anúncio dentro da tela '+size+' '+ad.slot);assert.ok(ad.top>=results.mainTop&&ad.bottom<=results.footerTop+.1,'Anúncio fora de header/footer');assert.ok(ad.fit,'Proporção preservada');if(ad.side){assert.ok(size[0]>768,'Laterais ocultas mobile');assert.ok(results.content.every(c=>ad.right<=c.left||ad.left>=c.right),'Lateral fora de containers');}else assert.equal(ad.mobile,size[0]<=768,'Criativo correto');}
        const visibleSides=results.ads.filter(a=>a.side);for(let i=0;i<visibleSides.length;i++)for(let j=i+1;j<visibleSides.length;j++){const a=visibleSides[i],b=visibleSides[j];assert.ok(a.right<=b.left||a.left>=b.right||a.bottom<=b.top||a.top>=b.bottom,'Laterais sem sobreposição');}
        // A página já possui overflow próprio em algumas telas; banners não podem aumentá-lo.
        const scrollWith=results.scroll;await evaluate("document.querySelectorAll('.ad-band').forEach(e=>e.remove())");const scrollWithout=await evaluate('document.documentElement.scrollWidth');assert.ok(scrollWith<=scrollWithout,'Sem overflow causado por anúncios '+size+' '+route);checks++;
    }}
    console.log('OK: Slots simultâneos, criativos e geometria nas 11 resoluções da Home e editoria');

    // Cada slot individualmente; o MIDDLE/BOTTOM lateral pode se ocultar em páginas curtas.
    for(const slot of ['TOP','MIDDLE','BOTTOM',...['TOP','MIDDLE','BOTTOM'].flatMap(b=>['SIDE_LEFT_'+b,'SIDE_RIGHT_'+b])]){
        update("$pdo->exec(\"UPDATE banners SET status='pausado'\");");
        await save({home:true,slots:[slot],desktop:slot.startsWith('SIDE_')?'160x600.png':'970x250.png'});
        await viewport([1920,1080]);await navigate('/');
        check(await evaluate(`document.querySelectorAll('.ad-slot').length===1 && document.querySelector('.ad-slot').dataset.adSlot===${JSON.stringify(slot)} && document.querySelector('.ad-slot').getClientRects().length>0`),'Home: posição individual '+slot);
    }
    // A publicidade central não pode puxar as laterais para cima das manchetes.
    update("$pdo->exec(\"UPDATE banners SET status='pausado'\");");
    await save({home:true,slots:['TOP'],desktop:'970x250.png'});
    await save({home:true,slots:['SIDE_LEFT_TOP','SIDE_RIGHT_TOP'],desktop:'300x600.png'});
    await save({home:true,slots:['SIDE_LEFT_TOP','SIDE_RIGHT_TOP'],desktop:'160x600.png'});
    for(const size of [[1920,1080],[1600,900]]){
        await viewport(size);await navigate('/');
        const alignment=await evaluate(`(()=>{const news=document.querySelector('.hero-grid').getBoundingClientRect();return [...document.querySelectorAll('.ad-side.is-visible')].map(ad=>{const r=ad.querySelector('.ad-creative').getBoundingClientRect();return Math.abs((r.top+r.bottom)/2-(news.top+news.bottom)/2)})})()`);
        assert.equal(alignment.length,2,'Duas laterais junto às manchetes');
        check(alignment.every(delta=>delta<1),'Laterais centralizadas nas manchetes com anúncio central: '+size.join('x'));
        // O host headless não entrega callbacks do compositor; dispara o evento real de resize.
        await evaluate("document.querySelector('.hero-grid').style.minHeight='1200px';window.dispatchEvent(new Event('resize'))");await wait(120);
        check(await evaluate(`(()=>{const news=document.querySelector('.hero-grid').getBoundingClientRect();return [...document.querySelectorAll('.ad-side.is-visible')].every(ad=>{const r=ad.querySelector('.ad-creative').getBoundingClientRect();return Math.abs((r.top+r.bottom)/2-(news.top+news.bottom)/2)<1})})()`),'Centralização recalculada após mudança de altura e resize');
    }
    update("$pdo->exec(\"UPDATE banners SET status='pausado'\");");
    await save({home:true,slots:['TOP','MIDDLE','BOTTOM'],desktop:'970x250.png'});
    await viewport([360,800]);await navigate('/');
    check(await evaluate("[...document.querySelectorAll('.ad-central img')].every(img=>img.naturalWidth===970 && img.getBoundingClientRect().width<=360)"),'Fallback mobile sem criativo específico');
    update("$pdo->exec(\"UPDATE banners SET status='pausado'\");");
    await save({home:true,slots:['MIDDLE','BOTTOM'],mobile:'300x250.png'});
    await navigate('/');
    check(await evaluate("[...document.querySelectorAll('.ad-central img')].every(img=>img.naturalWidth===300 && img.naturalHeight===250 && Math.abs(img.getBoundingClientRect().width/img.getBoundingClientRect().height-1.2)<.01)"),'Criativo mobile 300 × 250 mantém proporção');
    update("$pdo->exec(\"UPDATE banners SET status='pausado'\");");
    await save({home:true,slots:['TOP'],titulo:'Rotacao A'});
    await save({home:true,slots:['TOP'],titulo:'Rotacao B'});
    await viewport([1920,1080]);await navigate('/');
    await evaluate("Object.defineProperty(document,'hidden',{get:()=>false,configurable:true})");
    const currentCampaign=()=>evaluate("document.querySelector('.ad-slide:not([hidden])').dataset.campaign");
    const first=await currentCampaign();await wait(5200);const second=await currentCampaign();
    check(first!==second,'Rotação preservada a cada 5 segundos');
    await evaluate("document.dispatchEvent(new Event('turbo:before-cache'))");await wait(5200);
    check(await currentCampaign()===second,'Turbo interrompe rotação antes de guardar a página');
    const centralSlots=['TOP','MIDDLE','BOTTOM'];
    for(const image of ['640x360.png','1280x720.png','1920x1080.png','3840x2160.png']){
        update("$pdo->exec(\"UPDATE banners SET status='pausado'\");");
        const rejected=await request('/admin/banners.php',1,{method:'POST',body:makeForm({home:true,slots:['SIDE_LEFT_TOP'],desktop:image})});
        check(rejected.status===200&&(await rejected.text()).includes('Formatos permitidos nas laterais'),'Formato lateral não permitido rejeitado: '+image);
        const id=await save({home:true,all:true,slots:centralSlots,desktop:image});
        for(const route of ['/','/categoria/mundo']){
            const html=await(await request(route)).text();
            check((html.match(new RegExp('data-campaign="'+id+'"','g'))||[]).length===3,'16:9 nas posições centrais: '+image+' '+route);
        }
    }
    for(const size of sizes){await viewport(size);for(const route of ['/','/categoria/mundo']){await navigate(route);
        const layout=await evaluate(`(()=>{const main=document.querySelector('.site-main').getBoundingClientRect(),footer=document.querySelector('.site-footer').getBoundingClientRect();return {width:document.documentElement.clientWidth,top:main.top,bottom:footer.top,ads:[...document.querySelectorAll('.ad-slot')].filter(e=>e.getClientRects().length).map(e=>{const r=e.getBoundingClientRect(),image=e.querySelector('.ad-slide:not([hidden]) img'),c=e.querySelector('.ad-creative').getBoundingClientRect();return {side:e.classList.contains('ad-side'),left:r.left,right:r.right,top:r.top,bottom:r.bottom,ratio:c.width/c.height,natural:image.naturalWidth/image.naturalHeight,fit:getComputedStyle(image).objectFit}})}})()`);
        assert.equal(layout.ads.filter(a=>!a.side).length,3,'16:9 mantém três posições centrais');
        assert.equal(layout.ads.filter(a=>a.side).length,0,'16:9 não recebe laterais automaticamente');
        for(const ad of layout.ads){assert.ok(ad.left>=0&&ad.right<=layout.width+.1&&ad.top>=layout.top&&ad.bottom<=layout.bottom+.1,'16:9 dentro dos limites');assert.ok(Math.abs(ad.ratio-16/9)<.01&&Math.abs(ad.natural-16/9)<.01&&ad.fit==='contain','16:9 proporcional sem recorte');if(ad.side)assert.ok(size[0]>768,'16:9 lateral oculta no mobile');}
        const before=await evaluate('document.documentElement.scrollWidth');await evaluate("document.querySelectorAll('.ad-band').forEach(e=>e.remove())");assert.ok(before<=await evaluate('document.documentElement.scrollWidth'),'16:9 sem overflow');
    }}
    console.log('OK: Formatos laterais originais e posições centrais nas onze resoluções');
    const grandfatherId=Number(update("echo $pdo->query('SELECT MAX(id) FROM banners')->fetchColumn();"));
    update(`$pdo->prepare('UPDATE banner_settings SET slots = ? WHERE banner_id = ?')->execute(['["SIDE_LEFT_TOP"]',${grandfatherId}]);`);
    r=await request('/admin/banners.php',1,{method:'POST',body:makeForm({id:String(grandfatherId),home:true,slots:['SIDE_LEFT_TOP'],desktop:null})});
    check(r.status===302,'Arte lateral já cadastrada pode ser editada sem substituição');
    check((await(await request('/')).text()).includes('data-campaign="'+grandfatherId+'"'),'Arte lateral já cadastrada permanece em exibição');
    r=await request('/admin/banners.php',1,{method:'POST',body:makeForm({id:String(grandfatherId),home:true,slots:['SIDE_LEFT_TOP'],desktop:'1920x1080.png'})});
    check(r.status===200&&(await r.text()).includes('Formatos permitidos nas laterais'),'Substituição exige um formato lateral permitido');
    r=await request('/admin/banners.php',1,{method:'POST',body:makeForm({id:String(grandfatherId),home:true,slots:['SIDE_LEFT_TOP'],desktop:'160x600.png'})});
    check(r.status===302,'Substituição por 160 × 600 aceita');
    const liveArg=process.argv.find(arg=>arg.startsWith('--live='));
    if(liveArg){
        const liveOrigin=new URL(liveArg.slice(7)).origin;
        assert.ok(['localhost','127.0.0.1'].includes(new URL(liveOrigin).hostname),'Somente servidor local');
        for(const route of ['/','/categoria/economia']){
            const html=await new Promise((resolve,reject)=>{http.get(liveOrigin+route,res=>{let body='';res.on('data',chunk=>body+=chunk);res.on('end',()=>resolve(body));}).on('error',reject);});
            for(const size of [[1920,1080],[1366,768],[390,844]]){
                await viewport(size);await navigate(route,{origin:liveOrigin,html});
                const count=await evaluate("[...document.querySelectorAll('.ad-side')].filter(e=>e.getClientRects().length).length");
                check(size[0]===1920?count>=2:count===0,'Campanha real visível somente com espaço: '+route+' '+size.join('x')+' ('+count+' laterais)');
            }
        }
    }
    console.log('PASS: testes funcionais, HTTP, uploads e navegador concluídos.');
    ws.close();
}
main().catch(error=>{console.error(error);process.exitCode=1;}).finally(()=>{server?.kill();browser?.kill();proxy?.closeAllConnections();proxy?.close();console.log('Artefatos temporários:',folder);});
