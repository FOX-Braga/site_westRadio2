<?php
$pdo = new PDO('sqlite:database.sqlite');
$cats = $pdo->query("SELECT * FROM categorias")->fetchAll(PDO::FETCH_ASSOC);

$corrections = [
    'ultimas-noticias' => 'Últimas Notícias',
    'politica' => 'Política',
    'policia' => 'Polícia',
    'economia' => 'Economia',
    'saude' => 'Saúde',
    'esportes' => 'Esportes',
    'mundo' => 'Mundo',
    'brasil' => 'Brasil',
    'entretenimento' => 'Entretenimento',
    'cultura' => 'Cultura',
    'tecnologia' => 'Tecnologia',
    'ciencia' => 'Ciência',
    'turismo' => 'Turismo',
    'opiniao' => 'Opinião',
    'podcasts' => 'Podcasts'
];

foreach ($cats as $cat) {
    if (isset($corrections[$cat['slug']])) {
        $novoNome = mb_strtoupper($corrections[$cat['slug']], 'UTF-8');
        $stmt = $pdo->prepare("UPDATE categorias SET nome = ? WHERE id = ?");
        $stmt->execute([$novoNome, $cat['id']]);
        echo "Updated {$cat['slug']} to {$novoNome}\n";
    }
}
echo "Done.";
?>
