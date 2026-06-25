<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/../database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
try {
    $stmt = $pdo->prepare("INSERT INTO noticias (titulo, subtitulo, slug, conteudo, imagem_destacada, autor_id, categoria_id, status, destaque, urgente) VALUES ('teste', 'teste', 'teste-slug', 'conteudo', null, 1, 1, 'agendado', 0, 0)");
    $stmt->execute();
    echo "Sucesso!";
    $pdo->exec("DELETE FROM noticias WHERE slug = 'teste-slug'");
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
