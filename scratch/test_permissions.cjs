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
let r=await request('noticias.php');let html=await r.text();check(r.status===200&&html.includes('Minha materia protegida')&&!html.includes('Materia de outro jornalista'),'Jornalista lista apenas suas materias');
for(const link of ['usuarios.php','usuario_form.php','videos.php','categorias.php','banners.php','comentarios.php','?delete='])check(!html.includes(link),'Menu/acoes sem '+link);
for(const route of ['usuarios.php','usuarios.php?filter=colunista','usuario_form.php?id=1','categorias.php','banners.php','comentarios.php','videos.php','video_form.php?id=1']){
for(const method of ['GET','POST']){r=await request(route,2,{method,...(method==='POST'?{body:formBody({tipo:'admin'})}:{})});check(r.status===403,method+' bloqueado: '+route)}
}
const original=readNews();
for(const method of ['GET','POST']){r=await request('noticia_form.php?id=2',2,{method,...(method==='POST'?{body:formBody()}:{})});check(r.status===403,method+' de materia alheia bloqueado')}
for(const id of [1,2]){r=await request('noticias.php?delete='+id+'&token=permissions-test-token');check(r.status===403,'Exclusao bloqueada: '+id)}
check(JSON.stringify(readNews())===JSON.stringify(original),'Tentativas proibidas nao alteraram noticias');
r=await request('noticia_form.php?id=1');html=await r.text();check(r.status===200&&!html.includes('name="autor_id"')&&!html.includes('name="destaque"')&&!html.includes('name="urgente"'),'Editor proprio sem controles administrativos');
r=await request('noticia_form.php?id=1',2,{method:'POST',body:formBody({autor_id:'1',destaque:'1',urgente:'1'})});check(r.status===302,'Jornalista publica sua materia');
let news=readNews();check(news[0].autor_id===2&&news[0].destaque===0&&news[0].urgente===0&&news[0].status==='publicado','Campos forjados nao alteram autoria ou destaques');check(JSON.stringify(news[1])===JSON.stringify(original[1]),'Materia alheia e manchete permanecem intactas');
r=await request('noticia_form.php',2,{method:'POST',body:formBody({titulo:'Nova materia agendada',status:'rascunho',data_agendamento:'2099-01-01T10:00',autor_id:'1'})});check(r.status===302,'Criacao e agendamento permitidos');news=readNews();check(news[2].autor_id===2&&news[2].status==='rascunho'&&news[2].data_agendamento.startsWith('2099-01-01'),'Nova materia vinculada somente ao jornalista autenticado');
const largeText='a'.repeat(12818825);
r=await request('noticia_form.php?id=1',2,{method:'POST',body:formBody({conteudo:largeText})});check(r.status===302,'Formulario com 12,8 MB salvo sem erro de CSRF');
check(Number(php(db+"echo $db->query('SELECT LENGTH(conteudo) FROM noticias WHERE id=1')->fetchColumn();"))===largeText.length,'Conteudo grande salvo integralmente');
r=await request('noticia_form.php?id=1',2,{method:'POST',body:formBody({conteudo:largeText,csrf_token:'invalido'})});check(r.status===403,'CSRF continua bloqueando formulario grande invalido');
r=await request('noticia_form.php?id=1',2,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'conteudo='+ 'a'.repeat(31*1048576)});html=await r.text();check(r.status===413&&html.includes('limite de 30M')&&!html.includes('CSRF')&&!html.includes('Warning:'),'Acima de 30 MB retorna erro 413 claro, sem falso CSRF');
for(const route of ['index.php','usuarios.php','usuario_form.php','categorias.php','banners.php','comentarios.php','videos.php','video_form.php','noticia_form.php?id=2']){r=await request(route,1);check(r.status===200&&(await r.text()).includes('</html>'),'Administrador mantem acesso: '+route)}
r=await request('noticia_form.php?id=2',1,{method:'POST',body:formBody({titulo:'Editada pelo admin',autor_id:'3',destaque:'1'})});check(r.status===302&&readNews()[1].titulo==='Editada pelo admin','Administrador edita materia de outro autor');
r=await request('noticias.php?delete=3&token=permissions-test-token',1);check(r.status===302&&readNews().length===2,'Administrador pode excluir noticia');
r=await request('noticias.php',4);check(r.status===403,'Leitor nao acessa painel');r=await request('noticias.php',0);check(r.status===302&&r.headers.get('location').includes('login'),'Visitante precisa fazer login');r=await request('noticias.php',999);check(r.status===302,'Usuario removido nao conserva permissao antiga na sessao');
console.log('TODOS OS TESTES DE PERMISSAO PASSARAM. Banco real nao foi alterado.');
}finally{server.kill();await new Promise(r=>server.once('exit',r));fs.unlinkSync(routerPath);fs.unlinkSync(fixture);fs.rmdirSync(folder)}})().catch(e=>{console.error(e);process.exitCode=1});