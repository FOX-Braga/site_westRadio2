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
ini_set('error_log', ${quote(path.join(folder,'php-error.log').replaceAll('\\','/'))});
$pdo=new PDO('sqlite:'.${quote(fixture)},null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
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
const request=(route,user=2,options={})=>fetch('http://127.0.0.1:8002/admin/'+route,{redirect:'manual',...options,headers:{Cookie:`test_user=${user}`,...options.headers}});
const readNews=()=>JSON.parse(php(db+"echo json_encode($db->query('SELECT id,titulo,autor_id,destaque,urgente,status,data_agendamento FROM noticias ORDER BY id')->fetchAll(PDO::FETCH_ASSOC));"));
const formBody=extra=>new URLSearchParams({csrf_token:'permissions-test-token',titulo:'Minha materia editada',subtitulo:'Resumo',categoria_id:'1',conteudo:'Texto atualizado',status:'publicado',...extra});

(async()=>{try{
for(let i=0;i<50;i++){try{await request('noticias.php');break}catch{await new Promise(r=>setTimeout(r,100))}}
php(db+"$db->exec('ALTER TABLE noticias DROP COLUMN autor_nome');");
let r=await request('noticia_form.php',1,{method:'POST',body:formBody({titulo:'Nova sem coluna autor',autor_id:'2'})});
check(r.status===302,'Admin cria noticia sem autor_nome');
const latest=()=>JSON.parse(php(db+"echo json_encode($db->query('SELECT * FROM noticias ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC));"));
let saved=latest();check(saved.titulo==='Nova sem coluna autor'&&saved.autor_id===2&&saved.conteudo==='Texto atualizado','Conteudo e autor_id gravados no banco antigo');
const id=saved.id;
r=await request('noticia_form.php?id='+id,1,{method:'POST',body:formBody({titulo:'Editada sem coluna autor',autor_id:'3',urgente:'1'})});
check(r.status===302&&latest().titulo==='Editada sem coluna autor'&&latest().autor_id===3&&latest().urgente===1,'Admin edita noticia sem autor_nome e mantem campos existentes');
r=await request('noticia_form.php',2,{method:'POST',body:formBody({titulo:'Jornalista sem coluna autor',autor_id:'1',destaque:'1',urgente:'1'})});
check(r.status===302&&latest().autor_id===2&&latest().destaque===0&&latest().urgente===0,'Jornalista publica sem autor_nome com permissoes preservadas');
php(db+"$db->exec('ALTER TABLE noticias DROP COLUMN urgente');$db->exec('ALTER TABLE noticias DROP COLUMN data_agendamento');");
r=await request('noticia_form.php',1,{method:'POST',body:formBody({titulo:'Banco muito antigo',autor_id:'2'})});
check(r.status===302&&latest().titulo==='Banco muito antigo','Publicacao funciona sem os tres campos opcionais');
let previous=latest().id;
r=await request('noticia_form.php',1,{method:'POST',body:formBody({titulo:'Agendamento indisponivel',data_agendamento:'2099-01-01T10:00',autor_id:'2'})});
check(r.status===200&&(await r.text()).includes('Remova a data')&&latest().id===previous,'Agendamento ausente informa erro e nao publica indevidamente');
// A failed INSERT must not clear the previous headline.
php(db+"$db->exec(\"CREATE TRIGGER reject_test BEFORE INSERT ON noticias WHEN NEW.titulo='Falha simulada' BEGIN SELECT RAISE(ABORT, 'falha de teste'); END\");");
r=await request('noticia_form.php',1,{method:'POST',body:formBody({titulo:'Falha simulada',destaque:'1',autor_id:'2'})});
check(r.status===200&&!(await r.text()).includes('SQLSTATE'),'Erro de banco nao revela SQL ao usuario');
check(Number(php(db+"echo $db->query('SELECT destaque FROM noticias WHERE id=2')->fetchColumn();"))===1,'Falha ao salvar preserva o destaque anterior por rollback');
php(db+"$db->exec('ALTER TABLE noticias ADD COLUMN autor_nome TEXT');$db->exec('ALTER TABLE noticias ADD COLUMN urgente INTEGER DEFAULT 0');$db->exec('ALTER TABLE noticias ADD COLUMN data_agendamento DATETIME');");
r=await request('noticia_form.php',1,{method:'POST',body:formBody({titulo:'Schema atual',autor_id:'2',urgente:'1',data_agendamento:'2099-01-01T10:00',status:'rascunho'})});
saved=latest();check(r.status===302&&saved.autor_nome==='Jornalista A'&&saved.urgente===1&&saved.data_agendamento.startsWith('2099-01-01'),'Banco atual preserva assinatura, urgente e agendamento');
console.log('TESTES DE GRAVACAO APROVADOS. Banco real nao foi alterado.');
}finally{server.kill();await new Promise(r=>server.once('exit',r));if(fs.existsSync(path.join(folder,'php-error.log')))fs.unlinkSync(path.join(folder,'php-error.log'));fs.unlinkSync(routerPath);fs.unlinkSync(fixture);fs.rmdirSync(folder)}})().catch(e=>{console.error(e);process.exitCode=1});
