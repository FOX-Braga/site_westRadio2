// Executar com node scratch/test_permissions.cjs. Usa somente banco temporario.
const fs=require('node:fs'),os=require('node:os'),path=require('node:path'),cp=require('node:child_process');
const root=path.resolve(__dirname,'..').replaceAll('\\','/');
const folder=fs.mkdtempSync(path.join(os.tmpdir(),'96news-permissions-'));
const fixture=path.join(folder,'fixture.sqlite').replaceAll('\\','/');
const quote=s=>"'"+s.replaceAll("'","\\'")+"'";
function php(code){const r=cp.spawnSync('php',['-r',code],{encoding:'utf8'});if(r.status!==0)throw Error(r.stdout+r.stderr);return r.stdout}
function check(value,message){if(!value)throw Error(message);console.log('OK:',message)}
const db=`$db=new PDO('sqlite:'.${quote(fixture)},null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);`;
php(db+`preg_match_all('/CREATE TABLE IF NOT EXISTS.*?;/s',file_get_contents(${quote(root+'/config/database_sqlite.sql')}),$m);foreach($m[0] as $sql)$db->exec(str_replace('ON DELETE SET NULL','ON DELETE CASCADE',$sql));$db->exec("ALTER TABLE noticias ADD COLUMN data_agendamento DATETIME DEFAULT NULL");$db->exec("CREATE TABLE banners (id INTEGER PRIMARY KEY, titulo TEXT,imagem TEXT,link TEXT,status TEXT,criado_em TEXT)");$db->exec("INSERT INTO usuarios (nome,email,senha,tipo) VALUES ('Admin teste','a@example.invalid','teste','admin'),('Jornalista A','b@example.invalid','teste','jornalista'),('Jornalista B','c@example.invalid','teste','jornalista'),('Leitor','d@example.invalid','teste','user')");$db->exec("INSERT INTO categorias (nome,slug) VALUES ('Teste','teste')");$db->exec("INSERT INTO noticias (titulo,slug,conteudo,autor_id,categoria_id,status,destaque,urgente) VALUES ('Minha materia protegida','minha-materia','Texto A',2,1,'rascunho',0,0),('Materia de outro jornalista','outra-materia','Texto B',3,1,'publicado',1,1)");`);
const router=`<?php
require ${quote(root+'/config/database.php')};
ini_set('error_log', ${quote(path.join(folder,'test-error.log').replaceAll('\\','/'))});
$pdo=new PDO('sqlite:'.${quote(fixture)},null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$pdo->exec('PRAGMA foreign_keys=ON');
$r=new ReflectionClass(Database::class);$instance=$r->newInstanceWithoutConstructor();$r->getProperty('conn')->setValue($instance,$pdo);$r->getProperty('instance')->setValue(null,$instance);
$id=(int)($_COOKIE['test_user']??0);$_SESSION=[];
if($id)$_SESSION=['user_id'=>$id,'user_tipo'=>'admin','user_nome'=>'Teste','csrf_token'=>'permissions-test-token'];
$uri=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(!preg_match('~\\A/admin/(index|noticias|noticia_form|usuarios|usuario_form|categorias|banners|comentarios|videos|video_form)\\.php\\z~',$uri)){http_response_code(404);exit;}
$_SERVER['SCRIPT_NAME']=$uri;$_SERVER['PHP_SELF']=$uri;
require ${quote(root)}.$uri;
`;
const routerPath=path.join(folder,'router.php');fs.writeFileSync(routerPath,router);
const server=cp.spawn('php',['-d','post_max_size=30M','-d','upload_max_filesize=25M','-d','memory_limit=256M','-d','display_errors=0','-S','127.0.0.1:8002',routerPath],{cwd:root,stdio:'ignore',windowsHide:true});
const request=(route,user=2,options={})=>new Promise((resolve,reject)=>{
const http=require('node:http'),body=options.body?options.body.toString():'';
const headers={Cookie:'test_user='+user,...options.headers};
if(body){headers['Content-Type']='application/x-www-form-urlencoded';headers['Content-Length']=Buffer.byteLength(body)}
const req=http.request('http://127.0.0.1:8002/admin/'+route,{method:options.method||'GET',headers,agent:false},res=>{
const parts=[];res.on('data',c=>parts.push(c));res.on('end',()=>resolve(new Response(Buffer.concat(parts),{status:res.statusCode,headers:res.headers})));res.on('error',reject)});
req.on('error',reject);req.end(body);
});
const readNews=()=>JSON.parse(php(db+"echo json_encode($db->query('SELECT id,titulo,autor_id,destaque,urgente,status,data_agendamento FROM noticias ORDER BY id')->fetchAll(PDO::FETCH_ASSOC));"));
const formBody=extra=>new URLSearchParams({csrf_token:'permissions-test-token',titulo:'Minha materia editada',subtitulo:'Resumo',categoria_id:'1',conteudo:'Texto atualizado',status:'publicado',...extra});

(async()=>{try{
for(let i=0;i<50;i++){try{await request('noticias.php');break}catch{await new Promise(r=>setTimeout(r,100))}}
php(db+"$db->exec('DROP TABLE comentarios');$db->exec('DROP TABLE curtidas');$db->exec('CREATE TABLE comentarios(id INTEGER PRIMARY KEY,noticia_id INTEGER,usuario_id INTEGER,conteudo TEXT,status TEXT,FOREIGN KEY(noticia_id) REFERENCES noticias(id) ON DELETE RESTRICT,FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT)');$db->exec('CREATE TABLE curtidas(id INTEGER PRIMARY KEY,noticia_id INTEGER,usuario_id INTEGER,FOREIGN KEY(noticia_id) REFERENCES noticias(id) ON DELETE RESTRICT,FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT)');$db->exec(\"INSERT INTO comentarios VALUES (1,2,2,'Comentario','pendente'),(2,2,1,'Admin','pendente');INSERT INTO curtidas VALUES (1,2,2),(2,2,1)\");$db->exec('ALTER TABLE noticias DROP COLUMN autor_nome');");
const scalar=sql=>php(db+'echo $db->query('+quote(sql)+')->fetchColumn();');
const postDelete=(route,id,user=1,token='permissions-test-token')=>request(route,user,{method:'POST',body:new URLSearchParams({delete:String(id),csrf_token:token})});
let r=await postDelete('usuarios.php',2,1,'invalido');check(r.status===403&&scalar('SELECT COUNT(*) FROM usuarios WHERE id=2')==='1','CSRF invalido nao exclui usuario');
r=await postDelete('noticias.php',2,1,'invalido');check(r.status===403,'CSRF invalido nao exclui noticia');
r=await postDelete('usuarios.php',3,2);check(r.status===403,'Jornalista nao exclui usuarios');
r=await postDelete('noticias.php',1,2);check(r.status===403,'Jornalista nao exclui noticias');
r=await postDelete('usuarios.php',1);check(r.status===200&&scalar('SELECT COUNT(*) FROM usuarios WHERE id=1')==='1','Nao permite excluir a propria conta');
r=await postDelete('usuarios.php?filter=colunista',2);check(r.status===302&&r.headers.get('location').includes('filter=colunista'),'Exclusao de usuario redireciona antes de enviar HTML');
check(scalar('SELECT COUNT(*) FROM usuarios WHERE id=2')==='0','Usuario excluido no banco antigo sem autor_nome');
check(scalar('SELECT COUNT(*) FROM noticias')==='2'&&scalar('SELECT autor_id IS NULL FROM noticias WHERE id=1')==='1','Todas as noticias preservadas e autor desvinculado');
check(scalar('SELECT COUNT(*) FROM comentarios WHERE usuario_id=2')==='0'&&scalar('SELECT COUNT(*) FROM curtidas WHERE usuario_id=2')==='0','Dependencias RESTRICT do usuario removidas');
php(db+"$db->exec('ALTER TABLE noticias ADD COLUMN autor_nome TEXT');$db->exec(\"CREATE TRIGGER reject_user BEFORE DELETE ON usuarios WHEN OLD.id=3 BEGIN SELECT RAISE(ABORT,'falha usuario teste');END\");");
r=await postDelete('usuarios.php',3);check(r.status===200&&scalar('SELECT autor_id FROM noticias WHERE id=2')==='3'&&scalar('SELECT COUNT(*) FROM usuarios WHERE id=3')==='1','Falha de exclusao faz rollback e mantem usuario e noticia');
php(db+"$db->exec('DROP TRIGGER reject_user');");
r=await postDelete('usuarios.php',3);check(r.status===302&&scalar('SELECT COUNT(*) FROM noticias')==='2'&&scalar('SELECT autor_nome FROM noticias WHERE id=2')==='Jornalista B','Exclusao no banco atual preserva noticias e nome do autor');
php(db+"$db->exec(\"CREATE TRIGGER reject_news BEFORE DELETE ON noticias WHEN OLD.id=2 BEGIN SELECT RAISE(ABORT,'falha noticia teste');END\");");
r=await postDelete('noticias.php',2);check(r.status===200&&scalar('SELECT COUNT(*) FROM comentarios WHERE noticia_id=2')==='1'&&scalar('SELECT COUNT(*) FROM curtidas WHERE noticia_id=2')==='1','Falha ao excluir noticia restaura comentarios e curtidas');
php(db+"$db->exec('DROP TRIGGER reject_news');");
r=await postDelete('noticias.php',2);check(r.status===302&&scalar('SELECT COUNT(*) FROM noticias WHERE id=2')==='0','Noticia com dependencias RESTRICT excluida');
check(scalar('SELECT COUNT(*) FROM comentarios WHERE noticia_id=2')==='0'&&scalar('SELECT COUNT(*) FROM curtidas WHERE noticia_id=2')==='0','Dependencias da noticia excluidas');
check(scalar('SELECT COUNT(*) FROM usuarios WHERE id=1')==='1'&&scalar('SELECT COUNT(*) FROM noticias WHERE id=1')==='1','Excluir noticia preserva usuarios e outras noticias');
php(db+"$db->exec('DROP TABLE curtidas');$db->exec('DROP TABLE chat_mensagens');");
r=await postDelete('noticias.php',1);check(r.status===302,'Exclusao funciona com tabelas opcionais ausentes');
console.log('TESTES DE EXCLUSAO APROVADOS. Banco real nao foi alterado.');
}finally{server.kill();await new Promise(r=>server.once('exit',r));for(const f of [routerPath,fixture,path.join(folder,'test-error.log')])if(fs.existsSync(f))fs.unlinkSync(f);fs.rmdirSync(folder)}})().catch(e=>{console.error(e);process.exitCode=1});
