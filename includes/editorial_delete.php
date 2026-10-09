<?php
require_once __DIR__ . '/news_queries.php';
/** Dependencias opcionais existem em algumas versoes do banco. */
function editorialDeleteTables(PDO $pdo) {
    $tables = [];
    foreach (['comentarios', 'curtidas', 'chat_mensagens'] as $table) {
        try { $pdo->query("SELECT usuario_id FROM $table WHERE 1 = 0"); $tables[] = $table; }
        catch (PDOException $e) {
            if (!in_array((string) $e->getCode(), ['42S02', '42P01'], true) && stripos($e->getMessage(), 'no such table') === false) throw $e;
        }
    }
    return $tables;
}
function editorialDeleteUser(PDO $pdo, $id) {
    $hasAuthor = newsHasColumn($pdo, 'autor_nome');
    $tables = editorialDeleteTables($pdo);
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT nome FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        $name = $stmt->fetchColumn();
        if ($name === false) throw new RuntimeException('Usuario nao encontrado.');
        if ($hasAuthor) $pdo->prepare("UPDATE noticias SET autor_nome = ? WHERE autor_id = ? AND (autor_nome IS NULL OR autor_nome = '')")->execute([$name, $id]);
        // Desvincular ANTES do DELETE protege inclusive bancos antigos com ON DELETE CASCADE.
        $pdo->prepare('UPDATE noticias SET autor_id = NULL WHERE autor_id = ?')->execute([$id]);
        foreach ($tables as $table) {
            $sql = $table === 'chat_mensagens' ? 'UPDATE chat_mensagens SET usuario_id = NULL WHERE usuario_id = ?' : "DELETE FROM $table WHERE usuario_id = ?";
            $pdo->prepare($sql)->execute([$id]);
        }
        $pdo->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
function editorialDeleteNews(PDO $pdo, $id) {
    $tables = editorialDeleteTables($pdo);
    $pdo->beginTransaction();
    try {
        foreach (['comentarios', 'curtidas'] as $table) {
            if (in_array($table, $tables, true)) $pdo->prepare("DELETE FROM $table WHERE noticia_id = ?")->execute([$id]);
        }
        $stmt = $pdo->prepare('DELETE FROM noticias WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->rowCount()) throw new RuntimeException('Noticia nao encontrada.');
        $pdo->commit();
        // Arquivos podem ser compartilhados por materias: nao apaga imagens do disco aqui.
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
