// Executar com node scratch/test_permissions.cjs. Usa somente banco temporario.
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
require ${quote(root+'/config/database.php')};
$pdo=new PDO('sqlite:'.${quote(fixture)},null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$r=new ReflectionClass(Database::class);$instance=$r->newInstanceWithoutConstructor();$r->getProperty('conn')->setValue($instance,$pdo);$r->getProperty('instance')->setValue(null,$instance);
$id=(int)($_COOKIE['test_user']??0);$_SESSION=[];
if($id)$_SESSION=['user_id'=>$id,'user_tipo'=>'admin','user_nome'=>'Teste','csrf_token'=>'permissions-test-token'];
$uri=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(!in_array($uri, ['/index.php','/categoria.php','/busca.php','/api_busca.php','/noticia.php','/imagem_noticia.php','/admin/index.php','/admin/noticias.php'], true)){http_response_code(404);exit;}
$_SERVER['SCRIPT_NAME']=$uri;$_SERVER['PHP_SELF']=$uri;
require ${quote(root)}.$uri;
`;
const routerPath=path.join(folder,'router.php');fs.writeFileSync(routerPath,router);
const server=cp.spawn('php',['-d','post_max_size=30M','-d','upload_max_filesize=25M','-d','memory_limit=128M','-d','display_errors=0','-S','127.0.0.1:8002',routerPath],{cwd:root,stdio:'ignore',windowsHide:true});
const request=(route,user=2,options={})=>fetch('http://127.0.0.1:8002/admin/'+route,{redirect:'manual',...options,headers:{Cookie:`test_user=${user}`,...options.headers}});
const readNews=()=>JSON.parse(php(db+"echo json_encode($db->query('SELECT id,titulo,autor_id,destaque,urgente,status,data_agendamento FROM noticias ORDER BY id')->fetchAll(PDO::FETCH_ASSOC));"));
const formBody=extra=>new URLSearchParams({csrf_token:'permissions-test-token',titulo:'Minha materia editada',subtitulo:'Resumo',categoria_id:'1',conteudo:'Texto atualizado',status:'publicado',...extra});

(async()=>{try{
for(let i=0;i<50;i++){try{await request('noticias.php');break}catch{await new Promise(r=>setTimeout(r,100))}}
// Reproduce the old production schema without modifying the real database.
php(db+"$db->exec('ALTER TABLE noticias DROP COLUMN autor_nome');$db->exec('ALTER TABLE noticias DROP COLUMN urgente');$db->exec('ALTER TABLE noticias DROP COLUMN data_agendamento');$db->exec(\"UPDATE categorias SET slug='politica' WHERE id=1\");$db->beginTransaction();$s=$db->prepare('INSERT INTO noticias (titulo,slug,conteudo,autor_id,categoria_id,status,criado_em) VALUES (?,?,?,?,?,?,?)');for($i=3;$i<=900;$i++)$s->execute(['Acervo '.$i,'acervo-'.$i,'Texto do acervo',2,1,'publicado','2026-10-09 10:00:00']);$db->commit();$image='data:image/png;base64,'.base64_encode(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWioAAAAASUVORK5CYII=').str_repeat(chr(0),3*1048576));$db->prepare('UPDATE noticias SET conteudo=?,imagem_destacada=?,destaque=1,criado_em=? WHERE id=900')->execute([str_repeat('TEXTO_PESADO_',2700000),$image,'2098-01-01 10:00:00']);");
let legacyError=php(db+"try{$db->query(\"SELECT COALESCE(u.nome,n.autor_nome,'Redacao') FROM noticias n LEFT JOIN usuarios u ON n.autor_id=u.id\");}catch(PDOException $e){echo $e->getMessage();}");
check(legacyError.includes('no such column'),'Reproduzido erro SQL da consulta antiga sem autor_nome');
const publicRequest=(p,opts={})=>fetch('http://127.0.0.1:8002/'+p,opts);
for(const p of ['index.php','categoria.php?slug=politica','categoria.php?slug=ultimas-noticias','busca.php?q=Acervo']){
 const r=await publicRequest(p),html=await r.text();check(r.status===200&&html.includes('</html>')&&html.includes('Acervo 900'),'Pagina completa e noticias visiveis: '+p);
 check(!html.includes('data:image')&&!html.includes('TEXTO_PESADO_')&&Buffer.byteLength(html)<150000,'HTML leve sem corpo integral ou Base64: '+p);
 if(p==='index.php')check(html.includes('imagem_noticia.php?id=900'),'Capa antiga mantida por URL separada');
}
for(const p of ['noticias.php','noticias.php?p=2','noticias.php?q=Acervo','noticias.php?status=publicado','index.php']){
 const r=await request(p,1),html=await r.text();check(r.status===200&&html.includes('</html>'),'Painel completo com banco antigo: '+p);
}
let r=await request('noticias.php',2),html=await r.text();check(r.status===200&&html.includes('</html>')&&!html.includes('Materia de outro jornalista'),'Escopo do jornalista preservado com banco antigo');
r=await publicRequest('api_busca.php?q=Acervo');let data=await r.json();check(r.status===200&&data.length===5&&data.some(i=>i.imagem.includes('imagem_noticia.php?id=900')),'API retorna noticias e imagens leves');
r=await publicRequest('imagem_noticia.php?id=900');const image=Buffer.from(await r.arrayBuffer());check(r.status===200&&r.headers.get('content-type')==='image/png'&&image.length>3*1048576,'Capa Base64 antiga continua disponivel sem alterar banco');
const etag=r.headers.get('etag');r=await publicRequest('imagem_noticia.php?id=900',{headers:{'If-None-Match':etag}});check(r.status===304,'Cache condicional da capa funciona');
r=await publicRequest('imagem_noticia.php?id=1');check(r.status===404,'Imagem de rascunho nao e exposta');
r=await publicRequest('imagem_noticia.php?id=1%20OR%201=1');check(r.status===404,'Identificador malformado rejeitado');
// Modern schema: preserve saved attribution and keep drafts unpublished.
php(db+"$db->exec('ALTER TABLE noticias ADD COLUMN autor_nome TEXT');$db->exec('ALTER TABLE noticias ADD COLUMN urgente INTEGER DEFAULT 0');$db->exec('ALTER TABLE noticias ADD COLUMN data_agendamento DATETIME');$db->exec(\"UPDATE noticias SET autor_id=NULL,autor_nome='Autor preservado' WHERE id=900\");");
for(const p of ['index.php','noticia.php?slug=acervo-900']){r=await publicRequest(p);html=await r.text();check(r.status===200&&html.includes('</html>')&&html.includes('Autor preservado'),'Autoria preservada no schema atual: '+p)}
check(Number(php(db+"echo $db->query('SELECT COUNT(*) FROM noticias')->fetchColumn();"))===900,'Todas as 900 noticias preservadas');
check(php(db+"echo $db->query('SELECT status FROM noticias WHERE id=1')->fetchColumn();")==='rascunho','Rascunho permanece privado');
console.log('TESTES APROVADOS: 900 noticias, texto de 33 MB, capa de 3 MB, PHP com 128 MB; banco real intacto.');
}finally{server.kill();await new Promise(r=>server.once('exit',r));fs.unlinkSync(routerPath);fs.unlinkSync(fixture);fs.rmdirSync(folder)}})().catch(e=>{console.error(e);process.exitCode=1});
