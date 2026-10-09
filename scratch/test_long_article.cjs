// Executar com node scratch/test_long_article.cjs. Testa compatibilidade do formulario antigo.
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
const server=cp.spawn('php',['-d','post_max_size=30M','-d','upload_max_filesize=25M','-d','memory_limit=256M','-d','display_errors=0','-S','127.0.0.1:8002','-t',root,routerPath],{cwd:root,stdio:'ignore',windowsHide:true});
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
const sentence='A reportagem reúne informações sobre educação, saúde e infraestrutura em Campo Grande. Moradores, especialistas e representantes da comunidade apresentam propostas, com atenção à transparência e à participação pública. ';
const blocks=['<h2>INÍCIO DA REPORTAGEM LONGA</h2>'];
let size=0,index=0;
while(size<8*1048576){const block=`<p><strong>Parágrafo ${++index}.</strong> ${sentence.repeat(4)}<em>Informação verificada, ação e opinião.</em></p>`;blocks.push(block);size+=Buffer.byteLength(block)}
blocks.push('<blockquote>FIM DA REPORTAGEM LONGA — conteúdo completo e acentuação preservada.</blockquote>');
const article=blocks.join('\n');const form=new FormData();
for(const [key,value] of Object.entries({csrf_token:'permissions-test-token',titulo:'Reportagem longa de teste com acentuação',subtitulo:'Teste integral do editor e publicação',categoria_id:'1',status:'publicado',conteudo:'',conteudo_b64:Buffer.from(article,'utf8').toString('base64')}))form.append(key,value);
const png=Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=','base64');
form.append('imagem',new Blob([png],{type:'image/png'}),'capa-teste.png');
const encoded=new Request('http://localhost',{method:'POST',body:form});const wireBytes=(await encoded.arrayBuffer()).byteLength;
check(wireBytes>8*1048576&&wireBytes<30*1048576,'Formulario multipart ultrapassa o limite antigo de 8 MB e respeita 30 MB');
let response=await request('noticia_form.php?id=1',2,{method:'POST',body:form});check(response.status===302,'Materia longa salva com capa e Base64 pelo fluxo do editor');
const stored=JSON.parse(php(db+"$row=$db->query('SELECT conteudo,slug,autor_id,status,imagem_destacada FROM noticias WHERE id=1')->fetch(PDO::FETCH_ASSOC);echo json_encode(['bytes'=>strlen($row['conteudo']),'hash'=>hash('sha256',$row['conteudo']),'slug'=>$row['slug'],'autor'=>$row['autor_id'],'status'=>$row['status'],'image'=>substr($row['imagem_destacada'],0,22)]);"));
check(stored.bytes===Buffer.byteLength(article)&&stored.hash===crypto.createHash('sha256').update(article).digest('hex'),'Banco preservou todo o HTML, byte a byte (SHA-256)');
check(stored.autor===2&&stored.status==='publicado'&&stored.image.startsWith('img_'),'Autoria, publicacao e capa preservadas');
response=await fetch('http://127.0.0.1:8002/noticia.php?slug='+stored.slug,{headers:{Cookie:'test_user=2'}});let html=await response.text();
check(response.status===200&&html.includes('</html>')&&!/Fatal error|Warning:/.test(html),'Pagina publica completa sem erros PHP');
check(html.includes(article),'Pagina publica contem o texto completo com todos os paragrafos e formatacao');
check(html.includes('INÍCIO DA REPORTAGEM LONGA')&&html.includes('FIM DA REPORTAGEM LONGA')&&html.includes('participação pública'),'Inicio, final e acentuacao exibidos');
response=await request('noticia_form.php?id=1');html=await response.text();check(response.status===200&&html.includes('FIM DA REPORTAGEM LONGA')&&html.includes('</html>'),'Materia pode ser reaberta para edicao sem truncamento');
console.log(JSON.stringify({paragrafos:index,palavras:article.replace(/<[^>]*>/g,' ').trim().split(/\s+/u).length,conteudo_mb:(Buffer.byteLength(article)/1048576).toFixed(2),formulario_mb:(wireBytes/1048576).toFixed(2),resultado:'APROVADO',banco_real_alterado:false},null,2));
}finally{server.kill();await new Promise(r=>server.once('exit',r));removeTestFolder()}})().catch(e=>{console.error(e);process.exitCode=1});