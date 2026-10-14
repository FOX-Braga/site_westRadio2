// node scratch/test_banner_uploads.cjs — limites reais de 8M/2M; somente dados temporários.
const fs=require('node:fs'),os=require('node:os'),path=require('node:path'),cp=require('node:child_process'),http=require('node:http'),net=require('node:net'),vm=require('node:vm'),assert=require('node:assert/strict');
const root=path.resolve(__dirname,'..').replaceAll('\\','/');
const folder=fs.mkdtempSync(path.join(os.tmpdir(),'96news-banner-upload-')).replaceAll('\\','/');
const media=folder+'/media',staged=folder+'/staged',database=folder+'/fixture.sqlite';fs.mkdirSync(media);
const quote=s=>"'"+s.replaceAll("'","\\'")+"'";
const php=code=>{const r=cp.spawnSync('php',['-r',code],{encoding:'utf8',windowsHide:true});if(r.status!==0)throw Error(r.stdout+r.stderr);return r.stdout;};
const db=`$pdo=new PDO('sqlite:'.${quote(database)},null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);`;
const check=(value,label)=>{assert.ok(value,label);console.log('OK:',label);};
const sleep=ms=>new Promise(r=>setTimeout(r,ms));
const port=()=>new Promise(resolve=>{const s=net.createServer();s.listen(0,'127.0.0.1',()=>{const p=s.address().port;s.close(()=>resolve(p));});});
let server;
async function main(){
    php(db+`$pdo->exec("CREATE TABLE usuarios(id INTEGER PRIMARY KEY,nome TEXT,tipo TEXT);INSERT INTO usuarios VALUES(1,'Admin A','admin'),(2,'Admin B','admin'),(3,'Jornalista','jornalista');CREATE TABLE categorias(id INTEGER PRIMARY KEY,nome TEXT,slug TEXT);INSERT INTO categorias VALUES(1,'Economia','economia');");require ${quote(root+'/includes/banners.php')};bannerInstall($pdo);$im=imagecreatetruecolor(970,250);imagepng($im,${quote(folder+'/small.png')});`);
    const small=fs.readFileSync(folder+'/small.png');
    // PNG válido, com preenchimento para reproduzir um arquivo maior que upload_max_filesize.
    const large=Buffer.concat([small,Buffer.alloc(Math.floor(4.2*1024*1024)-small.length)]);
    const largeBanner=Buffer.concat([small,Buffer.alloc(12*1024*1024-small.length)]);
    const bannerLimit=50*1024*1024;
    const address='http://127.0.0.1:'+await port();
    fs.writeFileSync(folder+'/router.php',`<?php
    define('BANNER_UPLOAD_DIR',${quote(media+'/')});define('BANNER_TEMP_DIR',${quote(staged)});
    require ${quote(root+'/config/database.php')};ini_set('error_log',${quote(folder+'/php-errors.log')});${db}
    $r=new ReflectionClass(Database::class);$instance=$r->newInstanceWithoutConstructor();$r->getProperty('conn')->setValue($instance,$pdo);$r->getProperty('instance')->setValue(null,$instance);
    $_SESSION=[];$id=(int)($_COOKIE['test_user']??0);if($id)$_SESSION=['user_id'=>$id,'user_tipo'=>'admin','user_nome'=>'Teste','csrf_token'=>'upload-test'];
    $uri=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);$_SERVER['SCRIPT_NAME']=$_SERVER['PHP_SELF']=$uri;
    if($uri==='/admin/banners.php')require ${quote(root+'/admin/banners.php')};elseif($uri==='/admin/banner_upload.php')require ${quote(root+'/admin/banner_upload.php')};else http_response_code(404);`);
    const log=fs.openSync(folder+'/server.log','w');
    server=cp.spawn('php',['-d','post_max_size=8M','-d','upload_max_filesize=2M','-d','display_errors=0','-S',address.slice(7),folder+'/router.php'],{cwd:root,stdio:['ignore',log,log],windowsHide:true});
    const request=async(route,{user=1,body,headers={},method='POST'}={})=>{
        const req=new Request(address+route,{method,body,headers:{Cookie:'test_user='+user,...headers}});const bytes=Buffer.from(await req.arrayBuffer());
        return new Promise((resolve,reject)=>{const out=http.request(req.url,{method:req.method,headers:{...Object.fromEntries(req.headers),Connection:'close','Content-Length':String(bytes.length)}},response=>{const chunks=[];response.on('data',c=>chunks.push(c));response.on('end',()=>{const raw=Buffer.concat(chunks).toString();resolve({status:response.statusCode,ok:response.statusCode<400,json:async()=>JSON.parse(raw),text:async()=>raw});});});out.on('error',reject);out.end(bytes);});
    };
    for(let i=0;i<50;i++){try{if((await request('/admin/banners.php',{method:'GET'})).status===200)break;}catch{}await sleep(100);}
    const defaults={action:'save',csrf_token:'upload-test',titulo:'Campanha grande',anunciante:'Teste',link:'https://example.invalid',status:'ativo',show_home:'1','slots[]':'TOP'};
    const form=files=>{const f=new FormData();for(const [k,v]of Object.entries(defaults))f.set(k,v);for(const [k,v]of Object.entries(files))f.set(k,new Blob([v],{type:'image/png'}),k+'.png');return f;};
    let r=await request('/admin/banners.php',{body:form({imagem:large,imagem_mobile:large})});let html=await r.text();
    check(r.status===413&&html.includes('limite total')&&!html.includes('Acesso negado (CSRF)'),'POST acima de 8 MB recebe mensagem de tamanho, sem falso CSRF');
    r=await request('/admin/banners.php',{body:form({imagem:large})});html=await r.text();
    check(html.includes('limite de envio direto')&&!html.includes('Acesso negado (CSRF)'),'Arquivo acima de 2 MB recebe orientação correta no envio direto');
    const api=async(action,values={},options={})=>{
        const body=options.chunk??new URLSearchParams({action,...values});
        return request('/admin/banner_upload.php'+(options.chunk?'?'+new URLSearchParams({action,...values}):''),{...options,body,headers:{'X-CSRF-Token':'upload-test',...(options.chunk?{'Content-Type':'application/octet-stream'}:{}),...options.headers}});
    };
    check((await api('start',{size:large.length,kind:'imagem'},{user:3})).status===403,'Jornalista não acessa upload de publicidade');
    check((await api('start',{size:large.length,kind:'imagem'},{headers:{'X-CSRF-Token':'errado'}})).status===403,'CSRF permanece obrigatório no upload');
    check((await api('start',{size:bannerLimit+1,kind:'imagem'})).status===400,'Arquivo acima de 50 MB é rejeitado no servidor');
    check((await api('start',{size:bannerLimit,kind:'imagem'})).status===200,'Limite de 50 MB é aceito no servidor');
    let started=await(await api('start',{size:small.length,kind:'imagem'})).json();
    check((await api('finish',{token:started.token})).status===400,'Arquivo incompleto não é liberado');
    check((await api('chunk',{token:started.token,offset:1},{chunk:small.subarray(0,10)})).status===400,'Partes fora de ordem são rejeitadas');
    const piece=small.subarray(0,10);
    await api('chunk',{token:started.token,offset:0},{chunk:piece});
    check((await(await api('chunk',{token:started.token,offset:0},{chunk:piece})).json()).received===10,'Repetição de parte não duplica bytes');
    check((await api('chunk',{token:started.token,offset:0},{chunk:Buffer.alloc(10,65)})).status===400,'Parte repetida diferente é rejeitada');
    check((await api('finish',{token:started.token},{user:2})).status===400,'Token de outro administrador é rejeitado');
    check((await api('finish',{token:'../fixture.sqlite'})).status===400,'Token com caminho é rejeitado');
    const junk=Buffer.from('<?php echo "invalid";');started=await(await api('start',{size:junk.length,kind:'imagem'})).json();
    await api('chunk',{token:started.token,offset:0},{chunk:junk});
    check((await api('finish',{token:started.token})).status===400,'Arquivo não imagem é rejeitado antes de salvar');

    // Executa o JavaScript real do formulário; fetch usa o servidor PHP com limites baixos.
    async function client(files,csrf='upload-test'){
        const controls={csrf_token:{value:csrf},imagem_token:{value:''},imagem_mobile_token:{value:''}};
        for(const name of ['imagem','imagem_mobile'])controls[name]={files:files[name]?[new Blob([files[name]],{type:'image/png'})]:[],disabled:false,addEventListener(){},setCustomValidity(text){this.error=text;},reportValidity(){}};
        let submit,finalResponse,requests=0,maxBody=0;
        const status={hidden:true,textContent:''},button={disabled:false};
        const form={dataset:{uploadUrl:address+'/admin/banner_upload.php',maxFileSize:String(bannerLimit)},elements:controls,querySelector:()=>controls.csrf_token,querySelectorAll:()=>[button],addEventListener:(name,fn)=>{if(name==='submit')submit=fn;}};
        const doc={readyState:'complete',getElementById:id=>id==='banner-campaign-form'?form:status,addEventListener(){}};
        const context={document:doc,location:{href:address+'/admin/banners.php'},URL,URLSearchParams,FormData,Blob,HTMLFormElement:{prototype:{submit(){const body=new URLSearchParams(defaults);for(const key of ['imagem_token','imagem_mobile_token'])body.set(key,controls[key].value);assert.ok(controls.imagem.disabled&&controls.imagem_mobile.disabled,'Arquivos não são reenviados no POST final');finalResponse=request('/admin/banners.php',{body});}}},fetch:async(url,options)=>{requests++;const size=options.body instanceof Blob?options.body.size:Buffer.byteLength(await new Request(url,options).text());maxBody=Math.max(maxBody,size);return request(new URL(url).pathname+new URL(url).search,options);}};
        vm.runInNewContext(fs.readFileSync(root+'/assets/js/banner-admin.js','utf8'),context);
        await submit({preventDefault(){}});
        return {response:finalResponse?await finalResponse:null,status,controls,button,requests,maxBody};
    }
    const success=await client({imagem:largeBanner,imagem_mobile:large});
    check(success.response?.status===302,'Banner de 12 MB e mobile de 4,2 MB são salvos com PHP em 8M/2M');
    check(success.maxBody<=262144,'Cada requisição de imagem tem no máximo 256 KB');
    const saved=JSON.parse(php(db+"echo json_encode($pdo->query('SELECT b.imagem,s.imagem_mobile FROM banners b JOIN banner_settings s ON s.banner_id=b.id')->fetchAll());"));
    check(saved.length===1&&fs.readFileSync(media+'/'+saved[0].imagem).equals(largeBanner)&&fs.readFileSync(media+'/'+saved[0].imagem_mobile).equals(large),'Ambos os arquivos preservam exatamente os bytes originais');
    check(!fs.readdirSync(staged+'/1').some(file=>file.startsWith(success.controls.imagem_token.value)||file.startsWith(success.controls.imagem_mobile_token.value)),'Temporários consumidos após salvar');
    const invalid=await client({imagem:Buffer.alloc(bannerLimit+1)});
    check(invalid.requests===0&&invalid.status.textContent.includes('50 MB'),'Arquivo maior que 50 MB é bloqueado antes do envio');
    const failure=await client({imagem:small},'errado');
    check(!failure.response&&!failure.button.disabled&&!failure.controls.imagem.disabled&&failure.status.textContent.includes('sessão expirou'),'Falha preserva seleção e permite tentar novamente');
    console.log('PASS: regressão de limites, segurança e JavaScript do formulário.');
}
main().catch(error=>{console.error(error);process.exitCode=1;}).finally(()=>{server?.kill();console.log('Artefatos temporários:',folder);});
