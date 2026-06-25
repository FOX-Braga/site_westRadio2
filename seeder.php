<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getInstance();
    
    // 1. Garantir que as categorias existam
    $categorias = [
        ['nome' => 'Mundo', 'slug' => 'mundo', 'cor' => '#0A2E73'],
        ['nome' => 'Política', 'slug' => 'politica', 'cor' => '#CC0000'],
        ['nome' => 'Economia', 'slug' => 'economia', 'cor' => '#16a34a'],
        ['nome' => 'Cultura', 'slug' => 'cultura', 'cor' => '#9333ea'],
        ['nome' => 'Tecnologia', 'slug' => 'tecnologia', 'cor' => '#2563eb'],
        ['nome' => 'Saúde', 'slug' => 'saude', 'cor' => '#0ea5e9'],
        ['nome' => 'Esportes', 'slug' => 'esportes', 'cor' => '#ea580c'],
        ['nome' => 'Opinião', 'slug' => 'opiniao', 'cor' => '#475569'],
    ];

    $cat_ids = [];
    foreach ($categorias as $cat) {
        $stmt = $pdo->prepare("SELECT id FROM categorias WHERE slug = ?");
        $stmt->execute([$cat['slug']]);
        $row = $stmt->fetch();
        
        if ($row) {
            $cat_ids[$cat['slug']] = $row['id'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO categorias (nome, slug, cor) VALUES (?, ?, ?)");
            $stmt->execute([$cat['nome'], $cat['slug'], $cat['cor']]);
            $cat_ids[$cat['slug']] = $pdo->lastInsertId();
        }
    }

    // 2. Garantir que exista um autor
    $stmt = $pdo->query("SELECT id FROM usuarios WHERE tipo = 'admin' LIMIT 1");
    $autor_row = $stmt->fetch();
    
    if ($autor_row) {
        $autor_id = $autor_row['id'];
    } else {
        $senha = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo, bio) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Editor Chefe', 'editor@96news.com', $senha, 'admin', 'Jornalista chefe da redação 96News.']);
        $autor_id = $pdo->lastInsertId();
    }

    // 3. Limpar notícias antigas (opcional, mas bom para reset)
    // $pdo->query("DELETE FROM noticias");

    // 4. Gerar 3 notícias por categoria
    $lorem_ipsum = "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus imperdiet, nulla et dictum interdum, nisi lorem egestas vitae scel<erisque enim ligula venenatis dolor. Maecenas nisl est, ultrices nec congue eget, auctor vitae massa.</p><p>Fusce luctus vestibulum augue ut aliquet. Nunc sagittis dictum nisi, sed ullamcorper ipsum dignissim ac. In at libero sed nunc venenatis imperdiet sed ornare turpis. Donec vitae dui eget tellus gravida venenatis. Integer fringilla congue eros non fermentum.</p>";

    $titulos_exemplo = [
        'mundo' => [
            "Conferência do Clima termina com acordo histórico entre as 50 maiores nações",
            "Crise na Europa: Novos protestos marcam o fim de semana em Paris",
            "Terremoto atinge região central da Ásia e deixa milhares desabrigados"
        ],
        'politica' => [
            "Câmara aprova novo pacote fiscal após longa madrugada de debates",
            "Presidente discursa no Senado e promete reformas estruturais este ano",
            "Eleições municipais: Pesquisa mostra empate técnico entre os líderes"
        ],
        'economia' => [
            "Dólar atinge menor cotação em 12 meses após anúncio do Banco Central",
            "Bolsa de Valores bate recorde histórico puxada por setor de energia",
            "Inflação desacelera em agosto, mas setor de serviços ainda preocupa"
        ],
        'cultura' => [
            "Nova exposição no MASP reúne obras inéditas do período modernista",
            "Filme nacional é premiado no Festival de Cannes sob aplausos do júri",
            "Bienal do Livro atrai público recorde de jovens no fim de semana"
        ],
        'tecnologia' => [
            "Inteligência Artificial revoluciona diagnóstico precoce na medicina",
            "Empresa lança novo smartphone com bateria que dura uma semana inteira",
            "Falha global em servidores afeta principais redes sociais por horas"
        ],
        'saude' => [
            "Novo estudo revela os reais benefícios do jejum intermitente",
            "Campanha de vacinação atinge 90% da meta nacional antes do prazo",
            "Hospitais registram queda significativa nos casos de doenças respiratórias"
        ],
        'esportes' => [
            "Seleção vence nos pênaltis e garante vaga na grande final do campeonato",
            "Atleta quebra recorde mundial nos 100m rasos nas Olimpíadas",
            "Clube anuncia contratação milionária do principal atacante europeu"
        ],
        'opiniao' => [
            "Por que precisamos repensar o modelo de trabalho híbrido nas capitais",
            "Os desafios da nova geração frente ao aquecimento global",
            "O impacto silencioso das redes sociais na política moderna"
        ]
    ];

    $count = 0;
    foreach ($categorias as $cat) {
        $slug = $cat['slug'];
        $cat_id = $cat_ids[$slug];
        
        $titulos = $titulos_exemplo[$slug];
        
        foreach ($titulos as $index => $titulo) {
            $subtitulo = "Subtítulo da matéria explicando os principais pontos abordados nesta notícia exclusiva do portal 96News.";
            $news_slug = $slug . '-noticia-teste-' . ($index + 1) . '-' . uniqid();
            
            // Variar as datas para parecer mais real (entre hoje e 5 dias atrás)
            $dias_atras = rand(0, 5);
            $hora = rand(8, 20);
            $minuto = rand(0, 59);
            $data_criacao = date('Y-m-d H:i:s', strtotime("-$dias_atras days $hora:$minuto:00"));
            
            // Marcar a primeira de cada como destaque para testar o Hero
            $destaque = ($index === 0) ? 1 : 0;
            
            // Deixar a imagem NULL. Como a index tem placeholder inteligente, vai funcionar perfeitamente.
            $imagem_destacada = null; 

            $stmt = $pdo->prepare("INSERT INTO noticias 
                (titulo, subtitulo, conteudo, imagem_destacada, slug, categoria_id, autor_id, destaque, status, visualizacoes, criado_em) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'publicado', ?, ?)");
            
            $stmt->execute([
                $titulo, 
                $subtitulo, 
                $lorem_ipsum, 
                $imagem_destacada, 
                $news_slug, 
                $cat_id, 
                $autor_id, 
                $destaque,
                rand(10, 500),
                $data_criacao
            ]);
            $count++;
        }
    }
    
    echo "Seed finalizado com sucesso! $count noticias criadas.\n";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
