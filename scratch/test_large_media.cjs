// Executar com node scratch/test_large_media.cjs. Banco e arquivos temporarios.
const crypto=require('node:crypto');
const fs=require('node:fs'),os=require('node:os'),path=require('node:path'),cp=require('node:child_process');
const root=path.resolve(__dirname,'..').replaceAll('\\','/');
const folder=fs.mkdtempSync(path.join(os.tmpdir(),'96news-permissions-'));
const fixture=path.join(folder,'fixture.sqlite').replaceAll('\\','/');
const quote=s=>"'"+s.replaceAll("'","\\'")+"'";
function php(code){const r=cp.spawnSync('php',['-r',code],{encoding:'utf8'});if(r.status!==0)throw Error(r.stdout+r.stderr);return r.stdout}
function check(value,message){if(!value)throw Error(message);console.log('OK:',message)}
const db=`$db=new PDO('sqlite:'.${quote(fixture)},null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);`;
php(db+`preg_match_all('/CREATE TABLE IF NOT EXISTS.*?;/s',file_get_contents(${quote(root+'/config/database_sqlite.sql')}),$m);foreach($m[0] as $sql)$db->exec($sql);$db->exec("ALTER TABLE noticias ADD COLUMN data_agendamento DATETIME DEFAULT NULL");$db->exec("CREATE TABLE banners (id INTEGER PRIMARY KEY, titulo TEXT,imagem TEXT,link TEXT,status TEXT,criado_em TEXT)");$db->exec("INSERT INTO usuarios (nome,email,senha,tipo) VALUES ('Admin teste','a@example.invalid','teste','admin'),('Jornalista A','b@example.invalid','teste','jornalista'),('Jornalista B','c@example.invalid','teste','jornalista'),('Leitor','d@example.invalid','teste','user')");$db->exec("INSERT INTO categorias (nome,slug) VALUES ('Teste','teste')");$db->exec("INSERT INTO noticias (titulo,slug,conteudo,autor_id,categoria_id,status,destaque,urgente) VALUES ('Minha materia protegida','minha-materia','Texto A',2,1,'rascunho',0,0),('Materia de outro jornalista','outra-materia','Texto B',3,1,'publicado',1,1)");`);
const router=`<?php
define('EDITORIAL_MEDIA_DIR', ${quote(folder.replaceAll('\\','/')+'/media')});
define('EDITORIAL_TEMP_DIR', ${quote(folder.replaceAll('\\','/')+'/chunks')});
require ${quote(root+'/config/database.php')};
$pdo=new PDO('sqlite:'.${quote(fixture)},null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$r=new ReflectionClass(Database::class);$instance=$r->newInstanceWithoutConstructor();$r->getProperty('conn')->setValue($instance,$pdo);$r->getProperty('instance')->setValue(null,$instance);
$id=(int)($_COOKIE['test_user']??0);$_SESSION=[];
if($id)$_SESSION=['user_id'=>$id,'user_tipo'=>'admin','user_nome'=>'Teste','csrf_token'=>'permissions-test-token'];
$uri=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(str_starts_with($uri,'/assets/'))return false;
if(preg_match('~\\A/uploads/(noticias|midias)/([a-z0-9_.-]+)\\z~',$uri,$match)){$file=EDITORIAL_MEDIA_DIR.'/'.$match[1].'/'.$match[2];if(!is_file($file)){http_response_code(404);exit;}header('Content-Type: '.(new finfo(FILEINFO_MIME_TYPE))->file($file));readfile($file);exit;}
if($uri==='/noticia.php'){require ${quote(root+'/noticia.php')};exit;}
if(!preg_match('~\\A/admin/(index|noticias|noticia_form|usuarios|usuario_form|categorias|banners|comentarios|videos|video_form|upload)\\.php\\z~',$uri)){http_response_code(404);exit;}
$_SERVER['SCRIPT_NAME']=$uri;$_SERVER['PHP_SELF']=$uri;
require ${quote(root)}.$uri;
`;
const routerPath=path.join(folder,'router.php');fs.writeFileSync(routerPath,router);
const server=cp.spawn('php',['-d','post_max_size=2M','-d','upload_max_filesize=2M','-d','memory_limit=256M','-d','display_errors=0','-S','127.0.0.1:8002','-t',root,routerPath],{cwd:root,stdio:'ignore',windowsHide:true});
const request=(route,user=2,options={})=>fetch('http://127.0.0.1:8002/admin/'+route,{redirect:'manual',...options,headers:{Cookie:`test_user=${user}`,...options.headers}});
const readNews=()=>JSON.parse(php(db+"echo json_encode($db->query('SELECT id,titulo,autor_id,destaque,urgente,status,data_agendamento FROM noticias ORDER BY id')->fetchAll(PDO::FETCH_ASSOC));"));
const formBody=extra=>new URLSearchParams({csrf_token:'permissions-test-token',titulo:'Minha materia editada',subtitulo:'Resumo',categoria_id:'1',conteudo:'Texto atualizado',status:'publicado',...extra});
const startedTokens=[];
const mediaPath=path.join(folder,'media');
async function api(fields,user=2,csrf='permissions-test-token'){
    const body=new FormData();for(const [key,value]of Object.entries(fields))body.append(key,value);
    const r=await request('upload.php',user,{method:'POST',headers:{'X-CSRF-Token':csrf},body});const raw=await r.text();let data;try{data=JSON.parse(raw)}catch{throw Error('Resposta invalida: '+raw.slice(0,250))}return {r,data};
}
async function upload(bytes,kind){
    let result=await api({action:'start',kind,size:String(bytes.length)});check(result.r.status===200,'Inicio de '+kind);const token=result.data.token;startedTokens.push(token);
    for(let offset=0;offset<bytes.length;offset+=1048576){const chunk=new Blob([bytes.subarray(offset,offset+1048576)]);result=await api({action:'chunk',token,offset:String(offset),chunk});if(result.r.status!==200)throw Error(JSON.stringify(result.data));if(offset===0){const again=await api({action:'chunk',token,offset:'0',chunk});check(again.r.status===200&&again.data.received===result.data.received,'Repetir parte nao duplica bytes: '+kind)}}
    result=await api({action:'finish',token});check(result.r.status===200,'Conclusao de '+kind);return result.data;
}
function removeTestFolder(){const actual=fs.realpathSync(folder);const temp=fs.realpathSync(os.tmpdir());const relative=path.relative(temp,actual);if(!relative||relative.startsWith('..')||path.isAbsolute(relative)||!path.basename(actual).startsWith('96news-permissions-'))throw Error('Pasta temporaria inesperada');fs.rmSync(actual,{recursive:true,force:true})}
let browser=null;
(async()=>{try{
for(let i=0;i<50;i++){try{await request('noticias.php');break}catch{await new Promise(r=>setTimeout(r,100))}}
let bad=await api({action:'start',kind:'cover',size:'10'},2,'invalido');check(bad.r.status===403,'Upload exige CSRF valido');
let r=await request('upload.php',4,{method:'POST'});check(r.status===403,'Leitor nao envia arquivos');
const png=Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=','base64');
const picture=Buffer.concat([png,Buffer.alloc(7*1048576)]);
const smallVideo=fs.readFileSync(path.join(os.tmpdir(),'96news-test-video.mp4'));
const freeBox=Buffer.alloc(40*1048576);freeBox.writeUInt32BE(freeBox.length,0);freeBox.write('free',4,'ascii');
const video=Buffer.concat([smallVideo,freeBox]);
const cover=await upload(picture,'cover');const inline=await upload(picture,'image');const movie=await upload(video,'video');
for(const [uploaded,expected,subdir]of [[cover,picture,'noticias'],[inline,picture,'noticias'],[movie,video,'midias']]){const response=await fetch(uploaded.url);check(response.status===200,'URL publica da midia responde');const remote=Buffer.from(await response.arrayBuffer());check(remote.equals(expected),'Download da midia mantem todos os bytes');const file=fs.readFileSync(path.join(mediaPath,subdir,uploaded.filename));check(crypto.createHash('sha256').update(file).digest('hex')===crypto.createHash('sha256').update(expected).digest('hex'),'Arquivo grande identico ao original: '+subdir)}
const wrongOwner=await api({action:'finish',token:cover.token},3);check(wrongOwner.r.status===400,'Outro jornalista nao usa token alheio');
const text='<h2>INICIO LONGO</h2><p>'+ 'Texto longo com acentos e informação. '.repeat(850000)+`</p><img src="${inline.url}"><video controls preload="metadata" src="${movie.url}"></video><p>FIM LONGO</p>`;
check(Buffer.byteLength(text)>30*1048576,'Texto de teste maior que 30 MB');
const content=await upload(Buffer.from(text),'content');
const body=formBody({titulo:'Materia grande com midias',conteudo:'',conteudo_token:content.token,imagem_token:cover.token});
check(Buffer.byteLength(body.toString())<1500,'Formulario final pequeno, sem arquivos ou corpo pesado');
r=await request('noticia_form.php?id=1',2,{method:'POST',body});check(r.status===302,'Texto acima de 30 MB salvo com limite PHP de 2 MB por requisicao');
const stored=JSON.parse(php(db+"$row=$db->query('SELECT conteudo,imagem_destacada,slug,autor_id FROM noticias WHERE id=1')->fetch(PDO::FETCH_ASSOC);echo json_encode(['hash'=>hash('sha256',$row['conteudo']),'bytes'=>strlen($row['conteudo']),'image'=>$row['imagem_destacada'],'slug'=>$row['slug'],'autor'=>$row['autor_id']]);"));
check(stored.hash===crypto.createHash('sha256').update(text).digest('hex')&&stored.image===cover.filename&&stored.autor===2,'Texto inteiro, capa em arquivo e autoria preservados');
r=await fetch('http://127.0.0.1:8002/noticia.php?slug='+stored.slug,{headers:{Cookie:'test_user=2'}});const publicHtml=await r.text();check(r.status===200&&publicHtml.includes('FIM LONGO')&&publicHtml.includes(movie.url)&&publicHtml.includes('</html>'),'Noticia publica completa com imagens e video');
const replay=await request('noticia_form.php?id=1',2,{method:'POST',body});check(replay.status===200&&(await replay.text()).includes('envio não foi concluído'),'Token de texto consumido nao pode ser reutilizado');
const malicious=await upload(Buffer.from('<?php echo "perigoso"; ?>'),'content');
let invalid=await api({action:'start',kind:'cover',size:'23'});startedTokens.push(invalid.data.token);await api({action:'chunk',token:invalid.data.token,offset:'0',chunk:new Blob([Buffer.from('<?php echo "teste"; ?>  ')])});invalid=await api({action:'finish',token:invalid.data.token});check(invalid.r.status===400,'Arquivo que nao e imagem nao e publicado');
const before=readNews();r=await request('noticia_form.php?id=2',2,{method:'POST',body:formBody({conteudo_token:malicious.token})});check(r.status===403&&JSON.stringify(readNews())===JSON.stringify(before),'Upload nao libera edicao de noticia alheia');
console.log(JSON.stringify({capa_mb:(picture.length/1048576).toFixed(2),video_mb:(video.length/1048576).toFixed(2),texto_mb:(Buffer.byteLength(text)/1048576).toFixed(2),limite_php_por_requisicao_mb:2,banco_real_alterado:false}));
// Confere tambem o editor real no Chrome, se disponivel nesta maquina.
const chrome=['C:/Program Files/Google/Chrome/Application/chrome.exe','C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe'].find(fs.existsSync);
if(chrome){
const profile=path.join(folder,'browser');browser=cp.spawn(chrome,['--headless=new','--disable-gpu','--remote-debugging-port=9224','--user-data-dir='+profile,'--no-first-run','about:blank'],{stdio:'ignore',windowsHide:true});
let tab;for(let i=0;i<50;i++){try{tab=await(await fetch('http://127.0.0.1:9224/json/new?about:blank',{method:'PUT'})).json();break}catch{await new Promise(r=>setTimeout(r,100))}}
const ws=new WebSocket(tab.webSocketDebuggerUrl);await new Promise(r=>ws.addEventListener('open',r,{once:true}));let n=0;const pending=new Map(),errors=[];let chooser;
ws.addEventListener('message',({data})=>{const m=JSON.parse(data);if(m.id){const p=pending.get(m.id);pending.delete(m.id);m.error?p.reject(m.error):p.resolve(m.result)}else if(m.method==='Runtime.exceptionThrown')errors.push(m.params.exceptionDetails.exception?.description||m.params.exceptionDetails.text);else if(m.method==='Page.fileChooserOpened'&&chooser){chooser(m.params);chooser=null}});
const send=(method,params={})=>new Promise((resolve,reject)=>{const id=++n;pending.set(id,{resolve,reject});ws.send(JSON.stringify({id,method,params}))});
const evaluate=async expression=>{const result=await send('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true,userGesture:true});if(result.exceptionDetails)throw Error(result.exceptionDetails.exception?.description||result.exceptionDetails.text);return result.result.value};
const waitFor=async expression=>{for(let i=0;i<150;i++){if(await evaluate(expression))return;await new Promise(r=>setTimeout(r,100))}throw Error('Timeout: '+expression)};
await send('Page.enable');await send('Runtime.enable');await send('Page.setInterceptFileChooserDialog',{enabled:true});await send('Network.setCookie',{name:'test_user',value:'2',url:'http://127.0.0.1:8002',httpOnly:true});
await send('Page.navigate',{url:'http://127.0.0.1:8002/admin/noticia_form.php'});await waitFor("!!document.querySelector('.ql-editor')");
const picturePath=path.join(folder,'grande.png'),videoPath=path.join(folder,'grande.mp4');fs.writeFileSync(picturePath,picture);fs.writeFileSync(videoPath,video);
async function choose(selector,file){const opened=new Promise(resolve=>{chooser=resolve});await evaluate(`document.querySelector(${JSON.stringify(selector)}).click()`);const params=await Promise.race([opened,new Promise((_,reject)=>setTimeout(()=>reject(Error('Seletor de arquivo nao abriu')),10000))]);await send('DOM.setFileInputFiles',{files:[file],backendNodeId:params.backendNodeId})}
await choose('.ql-image',picturePath);await waitFor("!!document.querySelector('.ql-editor img[src^=\"http\"]')");check(true,'Botao de imagem envia imagem de 7 MB sem Base64');
await choose('.ql-mediaVideo',videoPath);await waitFor("!!document.querySelector('.ql-editor video')");check(true,'Botao de video envia video maior que 40 MB');
console.log('Estado do video',await evaluate("JSON.stringify({src:document.querySelector('.ql-editor video').src,error:document.querySelector('.ql-editor video').error?.message,ready:document.querySelector('.ql-editor video').readyState})"));
await evaluate("(async()=>{const video=document.querySelector('.ql-editor video');video.muted=true;video.preload='auto';video.load();await video.play();return true})()");
await waitFor("document.querySelector('.ql-editor video').readyState>=2");check(await evaluate("document.querySelector('.ql-editor video').videoWidth>0"),'Video enviado carrega frames no navegador');
const doc=await send('DOM.getDocument');const node=await send('DOM.querySelector',{nodeId:doc.root.nodeId,selector:'input[name=imagem]'});await send('DOM.setFileInputFiles',{nodeId:node.nodeId,files:[picturePath]});
await evaluate("document.querySelector('[name=titulo]').value='Publicacao pelo editor com midias grandes';document.querySelector('[name=categoria_id]').value='1';Quill.find(document.querySelector('#editor-container')).insertText(0,'Texto enviado pelo editor. ');document.querySelector('#form-noticia').requestSubmit()");
await waitFor("location.pathname==='/admin/noticias.php'");
const latest=JSON.parse(php(db+"echo json_encode($db->query('SELECT titulo,conteudo,imagem_destacada FROM noticias ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC));"));check(latest.titulo==='Publicacao pelo editor com midias grandes'&&latest.conteudo.includes('<video')&&!latest.conteudo.includes('data:image')&&latest.imagem_destacada.startsWith('img_'),'Editor completo publica com capa, imagem e video grandes');
check(errors.length===0,'Editor sem excecoes JavaScript');await send('Browser.close');browser=null;
}
console.log('TODOS OS TESTES DE MIDIAS GRANDES PASSARAM.');
}finally{
if(browser)browser.kill();server.kill();await new Promise(r=>server.once('exit',r));
await new Promise(r=>setTimeout(r,300));removeTestFolder();
}})().catch(e=>{console.error(e);process.exitCode=1});