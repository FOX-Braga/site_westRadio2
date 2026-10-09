<?php
// Teste de renderizacao com fixtures transacionais; nao persiste dados.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$_SERVER['HTTP_HOST'] = '127.0.0.1:8000';
$_SERVER['REQUEST_METHOD'] = 'GET';
require __DIR__ . '/../config/database.php';
$pdo = Database::getInstance();
$role = $argv[1] ?? 'jornalista';
$mode = $argv[2] ?? 'dashboard';
$_SERVER['SCRIPT_NAME'] = '/admin/' . ($mode === 'dashboard' ? 'index.php' : 'noticias.php');
$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
set_error_handler(function($severity, $message, $file, $line) { if ($severity & (E_WARNING | E_NOTICE | E_USER_ERROR)) throw new ErrorException($message, 0, $severity, $file, $line); return false; });
$pdo->beginTransaction();
try {
    $tag = 'ux-fixture-' . bin2hex(random_bytes(5));
    $stmt = $pdo->prepare('INSERT INTO usuarios (nome,email,senha,tipo,verificado) VALUES (?,?,?,?,1)');
    $stmt->execute(['Teste UX', $tag . '@example.invalid', password_hash('teste', PASSWORD_DEFAULT), $role]);
    $userId = (int) $pdo->lastInsertId();
    $_SESSION['user_id'] = $userId; $_SESSION['user_nome'] = 'Teste UX'; $_SESSION['user_tipo'] = $role;
    $category = (int) $pdo->query('SELECT id FROM categorias LIMIT 1')->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO noticias (titulo,slug,conteudo,categoria_id,autor_id,status,data_agendamento) VALUES (?,?,?,?,?,?,?)');
    foreach ([['publicado',null],['rascunho',null],['rascunho',date('Y-m-d H:i:s',time()+86400)]] as $index => [$status,$date]) {
        $stmt->execute([$tag.' '.$index,$tag.'-'.$index,'<p>Teste</p>',$category,$userId,$status,$date]);
    }
    $_GET = [];
    if ($mode !== 'dashboard') $_GET = ['q'=>$tag, 'minhas'=>'1'];
    if (in_array($mode,['publicado','rascunho','agendado'],true)) $_GET['status']=$mode;
    if ($mode === 'empty') $_GET['q']='nenhum-resultado-'.$tag;
    ob_start();
    require __DIR__ . '/../admin/' . ($mode === 'dashboard' ? 'index.php' : 'noticias.php');
    $html = ob_get_clean();
    $check = function($condition,$message) { if (!$condition) throw new RuntimeException($message); };
    $check(str_contains($html,'</html>'),'HTML incompleto');
    $check(substr_count($html,'aria-current="page"') >= 1,'Navegacao sem pagina ativa');
    $check(str_contains($html,'Equipe e usuários') === ($role === 'admin'),'Menu incorreto para o perfil');
    if ($mode === 'dashboard') {
        $check(str_contains($html,$role === 'admin' ? 'Últimas notícias da redação' : 'Suas últimas notícias'),'Dashboard incorreto');
        $check(str_contains($html,'Moderar comentários') === ($role === 'admin'),'Acao administrativa indevida');
    } elseif ($mode === 'empty') $check(str_contains($html,'Nenhuma notícia encontrada'),'Estado vazio ausente');
    else {
        $count = $mode === 'search' ? 3 : 1;
        $check(str_contains($html,'de '.$count.' notícias'),'Filtro retornou quantidade incorreta');
        $check(substr_count($html,'data-confirm=') === $count,'Quantidade incorreta de noticias na lista');
        $check(!str_contains($html,'?delete='),'Exclusao ainda usa GET');
    }
    $dom = new DOMDocument(); libxml_use_internal_errors(true); $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
    $xpath = new DOMXPath($dom);
    $check($xpath->query('//div[@id="panel-content"]//table')->length > 0 || $mode === 'empty','Conteudo fora do layout');
    $pdo->rollBack();
    echo "OK: $role / $mode; fixtures removidas por rollback.\n";
} catch (Throwable $e) {
    if (ob_get_level()) ob_end_clean();
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR,$e->getMessage().PHP_EOL); exit(1);
}