<?php
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getInstance();

try {
    $stmt = $pdo->prepare("INSERT INTO noticias (titulo, subtitulo, slug, conteudo, imagem_destacada, autor_id, categoria_id, status, destaque, urgente, data_agendamento) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute(['Teste titulo', 'Subtitulo teste', 'teste-slug-' . time(), '<p>Conteudo</p>', '', 1, 1, 'publicado', 0, 0, null]);
    echo "Sucesso!";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
