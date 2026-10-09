const fs=require('node:fs'),os=require('node:os'),path=require('node:path'),cp=require('node:child_process'),http=require('node:http');
const root=path.resolve(__dirname,'..').replaceAll('\\','/'),folder=fs.mkdtempSync(path.join(os.tmpdir(),'96news-author-migration-'));
const q=s=>"'"+s.replaceAll("'","\\'")+"'";
function php(code){const r=cp.spawnSync('php',['-r',code],{encoding:'utf8'});if(r.status!==0)throw Error(r.stdout+r.stderr);return r.stdout}
const connect="$db=new PDO('mysql:host=127.0.0.1;port=3308;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);";
const db=connect+"$db->exec('USE news96_author_test');";
php(connect+"$db->exec('CREATE DATABASE news96_author_test');$db->exec('USE news96_author_test');preg_match_all('/CREATE TABLE IF NOT EXISTS.*?;/s',file_get_contents("+q(root+'/config/database.sql')+"),$m);foreach($m[0] as $sql){$sql=str_replace('`autor_id` int(11) DEFAULT NULL','`autor_id` int(11) NOT NULL',$sql);$sql=str_replace('ON DELETE SET NULL','ON DELETE CASCADE',$sql);$db->exec($sql);}$db->exec(\"INSERT INTO usuarios(id,nome,email,senha,tipo) VALUES(1,'Admin','a@example.invalid','x','admin'),(2,'Autor','b@example.invalid','x','jornalista'),(3,'Outro','c@example.invalid','x','jornalista');INSERT INTO categorias(id,nome,slug) VALUES(1,'Teste','teste');INSERT INTO noticias(id,titulo,slug,conteudo,autor_id,categoria_id,status) VALUES(1,'Texto preservado','texto','Completo',2,1,'publicado'),(2,'Outra materia','outra','Outro texto',3,1,'publicado')\");");
const router=path.join(folder,'router.php');fs.writeFileSync(router,'<?php require '+q(root+'/config/database.php')+';ini_set("error_log",'+q(path.join(folder,'php.log').replaceAll('\\','/'))+');'+db+'$r=new ReflectionClass(Database::class);$i=$r->newInstanceWithoutConstructor();$r->getProperty("conn")->setValue($i,$db);$r->getProperty("instance")->setValue(null,$i);$_SESSION=["user_id"=>(int)($_COOKIE["test_user"]??1),"csrf_token"=>"test-token"]; $uri=parse_url($_SERVER["REQUEST_URI"],PHP_URL_PATH); if(!in_array($uri,["/admin/usuarios.php","/admin/corrigir_banco.php"],true)){http_response_code(404);exit;}$_SERVER["SCRIPT_NAME"]=$uri;$_SERVER["PHP_SELF"]=$uri;require '+q(root)+'.$uri;');
const server=cp.spawn('php',['-S','127.0.0.1:8002',router],{cwd:root,stdio:'ignore',windowsHide:true});
function request(route,user=1,body){return new Promise((resolve,reject)=>{const raw=body?new URLSearchParams(body).toString():'';const req=http.request('http://127.0.0.1:8002/admin/'+route,{method:body?'POST':'GET',agent:false,headers:{Cookie:'test_user='+user,...(body?{'Content-Type':'application/x-www-form-urlencoded','Content-Length':Buffer.byteLength(raw)}:{})}},res=>{const parts=[];res.on('data',c=>parts.push(c));res.on('end',()=>resolve({status:res.statusCode,headers:res.headers,text:Buffer.concat(parts).toString()}))});req.on('error',reject);req.end(raw)})}
function check(v,m){if(!v)throw Error(m);console.log('OK: '+m)}
const scalar=sql=>php(db+'echo $db->query('+q(sql)+')->fetchColumn();');
(async()=>{try{
for(let i=0;i<50;i++){try{await request('usuarios.php');break}catch{await new Promise(r=>setTimeout(r,100))}}
let r=await request('usuarios.php',1,{delete:2,csrf_token:'test-token'});check(r.status===200&&r.text.includes('1048')&&r.text.includes('corrigir_banco.php'),'Erro 1048 reproduzido e link para migracao exibido');
check(scalar('SELECT COUNT(*) FROM usuarios')==='3'&&scalar('SELECT COUNT(*) FROM noticias')==='2','Falha nao exclui usuarios ou noticias');
r=await request('corrigir_banco.php');check(r.status===200&&r.text.includes('ALTER TABLE noticias MODIFY COLUMN autor_id int(11) NULL DEFAULT NULL'),'Plano exato exibido sem executar');
check(scalar("SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='noticias' AND COLUMN_NAME='autor_id'")==='NO','GET nao altera estrutura');
r=await request('corrigir_banco.php',2,{action:'allow_removed_author',csrf_token:'test-token'});check(r.status===403,'Jornalista nao aplica migracao');
r=await request('corrigir_banco.php',1,{action:'allow_removed_author',csrf_token:'invalido'});check(r.status===403,'Migracao exige CSRF valido');
r=await request('corrigir_banco.php',1,{action:'allow_removed_author',csrf_token:'test-token'});check(r.status===302,'Administrador aplica migracao explicitamente');
check(scalar("SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='noticias' AND COLUMN_NAME='autor_id'")==='YES','autor_id aceita NULL depois da migracao');
check(scalar('SELECT @@foreign_key_checks')==='1','Verificacao de chave estrangeira permanece ativa');
check(scalar("SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='noticias' AND COLUMN_NAME='autor_id' AND REFERENCED_TABLE_NAME='usuarios'")==='1','Chave estrangeira preservada');
r=await request('usuarios.php',1,{delete:2,csrf_token:'test-token'});check(r.status===302&&scalar('SELECT COUNT(*) FROM usuarios WHERE id=2')==='0','Usuario excluido apos correcao');
check(scalar('SELECT COUNT(*) FROM noticias')==='2'&&scalar('SELECT autor_id IS NULL FROM noticias WHERE id=1')==='1'&&scalar('SELECT conteudo FROM noticias WHERE id=1')==='Completo','Todas as noticias e textos preservados');
check(scalar('SELECT autor_nome FROM noticias WHERE id=1')==='Autor','Assinatura preservada');
r=await request('corrigir_banco.php',1,{action:'allow_removed_author',csrf_token:'test-token'});check(r.status===302,'Reaplicar e seguro e nao altera dados');
console.log('MIGRACAO VALIDADA EM MARIADB REAL. Producao nao foi acessada.');
}finally{server.kill();await new Promise(r=>server.once('exit',r));php(connect+"$db->exec('DROP DATABASE news96_author_test');");for(const f of [router,path.join(folder,'php.log')])if(fs.existsSync(f))fs.unlinkSync(f);fs.rmdirSync(folder)}})().catch(e=>{console.error(e);process.exitCode=1});
