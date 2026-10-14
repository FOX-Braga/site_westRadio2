<?php
/** php scratch/seed_demo_news.php — dados locais para visualizar o portal e os banners. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$root = dirname(__DIR__);
$database = realpath($root . '/database.sqlite');
if (!$database) throw new RuntimeException('Banco SQLite local não encontrado.');
$pdo = new PDO('sqlite:' . $database, null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$pdo->exec('PRAGMA foreign_keys = ON');
$pdo->exec('PRAGMA busy_timeout = 5000');
$author = $pdo->query("SELECT id, nome FROM usuarios WHERE tipo = 'admin' ORDER BY id LIMIT 1")->fetch();
if (!$author) throw new RuntimeException('É necessário um administrador já cadastrado.');

$sections = [
    'mundo'=>['Mundo','#0A2E73',[
        ['Cidades compartilham projetos de mobilidade em encontro internacional','mobilidade_urbana.png'],
        ['Mostra de fotografia reúne paisagens e histórias de diferentes países','pantanal_news_1779504048495.png'],
        ['Universidades apresentam ideias para cooperação em tecnologia','tech_news_1779493114233.png'],
        ['Feira aproxima pequenos negócios de novos mercados','feira_negocios.png'],
    ]],
    'politica'=>['Política','#CC0000',[
        ['Audiência pública abre espaço para propostas sobre a cidade','mobilidade_urbana.png'],
        ['Representantes de bairros discutem prioridades para os próximos meses','transito_news_1779504034246.png'],
        ['Seminário reúne projetos de inovação na gestão pública','polo_tecnologico.png'],
        ['Agenda de debates destaca desenvolvimento regional','feira_negocios.png'],
    ]],
    'economia'=>['Economia','#16a34a',[
        ['Pequenos negócios apresentam novidades em feira regional','feira_negocios.png'],
        ['Painel de especialistas discute cenários para o comércio','economy_news_1779493141277.png'],
        ['Empreendedores trocam experiências sobre planejamento','selic_mercado.png'],
        ['Projetos de tecnologia movimentam encontro de empresas','startup_news_1779504021083.png'],
    ]],
    'cultura'=>['Cultura','#9333ea',[
        ['Festival reúne música e apresentações em praça pública','entertainment_news_1779493155504.png'],
        ['Exposição destaca fotografias e memórias do Pantanal','pantanal_news_1779504048495.png'],
        ['Feira do livro abre espaço para autores da região','feira_negocios.png'],
        ['Mostra cultural aproxima arte e novas tecnologias','polo_tecnologico_1779494019264.png'],
    ]],
    'tecnologia'=>['Tecnologia','#2563eb',[
        ['Laboratório apresenta protótipos desenvolvidos por estudantes','tech_news_1779493114233.png'],
        ['Startups mostram soluções para desafios do campo','rastreamento_gado.png'],
        ['Encontro reúne projetos de inteligência artificial','startup_news_1779504021083.png'],
        ['Centro de inovação recebe programação aberta ao público','polo_tecnologico_1779494019264.png'],
    ]],
    'saude'=>['Saúde','#0ea5e9',[
        ['Feira de bem-estar ocupa espaço de convivência na cidade','feira_negocios.png'],
        ['Encontro apresenta projetos de tecnologia voltados ao cuidado','tech_news_1779493114233.png'],
        ['Atividades ao ar livre integram programação comunitária','mobilidade_urbana.png'],
        ['Seminário abre conversa sobre qualidade de vida','polo_tecnologico.png'],
    ]],
    'esportes'=>['Esportes','#ea580c',[
        ['Torneio de bairro reúne equipes e torcedores no fim de semana','sports_news_1779493128078.png'],
        ['Arena recebe programação de futebol de areia','rebeldes_pantanal.png'],
        ['Clubes apresentam calendário de competições locais','final_campeonato_1779494061068.png'],
        ['Projetos esportivos levam novas atividades à comunidade','final_campeonato.png'],
    ]],
    'opiniao'=>['Opinião','#475569',[
        ['O papel dos espaços públicos na convivência das cidades','mobilidade_urbana.png'],
        ['Inovação também começa nas pequenas iniciativas','startup_news_1779504021083.png'],
        ['Cultura e memória ajudam a contar a história de uma região','pantanal_news_1779504048495.png'],
        ['Esporte comunitário pode aproximar diferentes gerações','sports_news_1779493128078.png'],
    ]],
];
foreach ($sections as $section) foreach ($section[2] as $news) {
    if (!is_file($root . '/uploads/noticias/' . $news[1])) throw new RuntimeException('Imagem local não encontrada: ' . $news[1]);
}

// Snapshot fora da raiz pública, incluindo as alterações locais já existentes.
$backupDirectory = sys_get_temp_dir() . '/96news-demo-backup-' . bin2hex(random_bytes(6));
if (!mkdir($backupDirectory, 0700)) throw new RuntimeException('Não foi possível criar o backup.');
$backupPath = $backupDirectory . '/database.sqlite';
$source = new SQLite3($database, SQLITE3_OPEN_READONLY);
$backup = new SQLite3($backupPath);
if (!$source->backup($backup)) throw new RuntimeException('Não foi possível copiar o banco antes da inclusão.');
$backup->close(); $source->close();

$originalNews = $pdo->query('SELECT * FROM noticias ORDER BY id')->fetchAll();
$originalBanners = $pdo->query('SELECT * FROM banners ORDER BY id')->fetchAll();
$originalSettings = $pdo->query('SELECT * FROM banner_settings ORDER BY banner_id')->fetchAll();
$created = [];
$createdCategories = [];
$now = new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
$pdo->beginTransaction();
try {
    $findCategory = $pdo->prepare('SELECT id FROM categorias WHERE slug = ?');
    $insertCategory = $pdo->prepare('INSERT INTO categorias (nome, slug, cor) VALUES (?, ?, ?)');
    $findNews = $pdo->prepare('SELECT id FROM noticias WHERE slug = ?');
    $insertNews = $pdo->prepare('INSERT INTO noticias (titulo, subtitulo, slug, conteudo, imagem_destacada, legenda_imagem, autor_id, autor_nome, categoria_id, status, destaque, urgente, visualizacoes, criado_em, atualizado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($sections as $categorySlug => [$categoryName, $color, $newsList]) {
        $findCategory->execute([$categorySlug]);
        $categoryId = $findCategory->fetchColumn();
        if (!$categoryId) {
            $insertCategory->execute([$categoryName, $categorySlug, $color]);
            $categoryId = $pdo->lastInsertId();
            $createdCategories[] = $categorySlug;
        }
        foreach ($newsList as $index => [$title, $image]) {
            $slug = 'demo-visualizacao-' . $categorySlug . '-' . ($index + 1);
            $findNews->execute([$slug]);
            if ($findNews->fetchColumn()) continue;
            $time = $now->modify('-' . (count($created) * 3 + 1) . ' hours')->format('Y-m-d H:i:s');
            $content = '<p><strong>Conteúdo fictício de demonstração.</strong> Esta matéria foi criada para visualizar os cards, as editorias e as posições de publicidade do 96NEWS. Não corresponde a um acontecimento real.</p>'
                . '<p>O exemplo da editoria ' . htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') . ' apresenta o tema “' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '” em uma estrutura semelhante à utilizada pela redação. A imagem é ilustrativa e pertence ao acervo local de demonstração.</p>'
                . '<p>Os parágrafos permitem conferir a leitura da página da notícia, a assinatura do autor e as interações do portal. Este conteúdo pode ser removido pelo painel administrativo após a avaliação do layout.</p>';
            $insertNews->execute(['Demo: ' . $title, 'Matéria fictícia para demonstração do layout e dos banners publicitários.', $slug, $content, $image, 'Imagem ilustrativa do acervo de demonstração.', $author['id'], $author['nome'], $categoryId, 'publicado', 0, 0, 0, $time, $time]);
            $created[] = ['id'=>(int)$pdo->lastInsertId(), 'slug'=>$slug, 'category'=>$categorySlug];
        }
    }
    // A inclusão não altera registros anteriores nem campanhas de publicidade.
    $findOriginal = $pdo->prepare('SELECT * FROM noticias WHERE id = ?');
    foreach ($originalNews as $news) {
        $findOriginal->execute([$news['id']]);
        if ($findOriginal->fetch() !== $news) throw new RuntimeException('Uma notícia anterior foi alterada durante a inclusão.');
    }
    if ($pdo->query('SELECT * FROM banners ORDER BY id')->fetchAll() !== $originalBanners
        || $pdo->query('SELECT * FROM banner_settings ORDER BY banner_id')->fetchAll() !== $originalSettings) throw new RuntimeException('Uma campanha anterior foi alterada durante a inclusão.');
    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $error;
}
echo json_encode(['created'=>count($created), 'categories_created'=>$createdCategories, 'news'=>$created, 'backup'=>$backupPath], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
