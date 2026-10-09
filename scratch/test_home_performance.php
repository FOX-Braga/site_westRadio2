<?php
/** Regressao da home com 920 noticias e capas/textos grandes. Apenas banco temporario. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$mode = $argv[1] ?? 'setup';
$fixture = $argv[2] ?? sys_get_temp_dir() . '/96news-home-fixture.sqlite';
if ($mode === 'setup') {
    if (file_exists($fixture)) throw new RuntimeException('Use um caminho novo para a fixture.');
    $pdo = new PDO('sqlite:' . $fixture, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec(file_get_contents(__DIR__ . '/../config/schema_sqlite.sql'));
    $pdo->exec("INSERT INTO usuarios (nome,email,senha,tipo) VALUES ('Autor de teste','home-fixture@example.invalid','teste','jornalista')");
    $slugs = ['mundo','politica','economia','cultura','tecnologia','saude','esportes','opiniao'];
    $cat = $pdo->prepare('INSERT INTO categorias (nome,slug) VALUES (?,?)');
    foreach ($slugs as $slug) $cat->execute([$slug,$slug]);
    // PNG valido com padding: simula o tamanho de capas legadas sem depender da rede.
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
    $image = 'data:image/png;base64,' . base64_encode($png . str_repeat("\0", 786432));
    $body = '<p>' . str_repeat('Conteudo editorial de teste. ', 40000) . '</p>';
    $stmt = $pdo->prepare('INSERT INTO noticias (titulo,slug,subtitulo,conteudo,imagem_destacada,autor_id,categoria_id,status,criado_em,destaque,data_agendamento) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    $pdo->beginTransaction();
    for ($i=1;$i<=920;$i++) {
        $heavy = $i > 872;
        $date = date('Y-m-d',strtotime('-' . (920-$i) . ' days')) . ($i % 2 ? ' 09:00:00' : ' 15:00:00');
        $stmt->execute(['Fixture noticia '.$i,'fixture-noticia-'.$i,'Resumo '.$i,$heavy?$body:'<p>Noticia antiga.</p>',$heavy?$image:'capa-legada.jpg',1,($i%8)+1,'publicado',$date,$i===920?1:0,null]);
    }
    // Nao deve aparecer na home nem fornecer imagem publica.
    $stmt->execute(['Rascunho secreto','fixture-rascunho','Resumo','Corpo',$image,1,1,'rascunho',date('Y-m-d H:i:s'),0,null]);
    $stmt->execute(['Agendamento futuro','fixture-futura','Resumo','Corpo',$image,1,1,'rascunho',date('Y-m-d H:i:s'),0,date('Y-m-d H:i:s',time()+86400)]);
    $pdo->commit();
    echo $fixture,PHP_EOL; exit;
}
$_SERVER['HTTP_HOST']='127.0.0.1:8000';
$_SERVER['SCRIPT_NAME']='/index.php'; $_SERVER['REQUEST_URI']='/'; $_SERVER['REQUEST_METHOD']='GET';
require __DIR__ . '/../config/database.php';
ini_set('display_errors','1'); ini_set('error_log',sys_get_temp_dir().'/96news-home-test-errors.log');
error_reporting(E_ALL & ~E_DEPRECATED);
$pdo = new PDO('sqlite:'.$fixture,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$reflection = new ReflectionClass(Database::class);
$instance = $reflection->newInstanceWithoutConstructor();
$reflection->getProperty('conn')->setValue($instance,$pdo);
$reflection->getProperty('instance')->setValue(null,$instance);
if ($mode === 'category') { $_GET=['slug'=>'ultimas-noticias']; $target=__DIR__.'/../categoria.php'; }
elseif ($mode === 'search') { $_GET=['q'=>'Fixture noticia']; $target=__DIR__.'/../busca.php'; }
else $target=$argv[3] ?? __DIR__.'/../index.php';
$start=microtime(true); ob_start(); require $target; $html=ob_get_clean();
if (!str_contains($html,'</html>') || !str_contains($html,'fixture-noticia-920')) throw new RuntimeException('Noticias ou rodape ausentes');
if (str_contains($html,'data:image/png;base64,') || str_contains($html,'Rascunho secreto') || str_contains($html,'Agendamento futuro')) throw new RuntimeException('Payload pesado ou noticia privada na listagem');
if (strlen($html)>150000) throw new RuntimeException('HTML maior que o esperado');
echo json_encode(['mode'=>$mode,'seconds'=>round(microtime(true)-$start,4),'peak_mb'=>round(memory_get_peak_usage(true)/1048576,2),'html_bytes'=>strlen($html),'articles'=>substr_count($html,'/noticia/fixture-'),'complete'=>true]),PHP_EOL;