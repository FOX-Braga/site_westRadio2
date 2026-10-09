<?php
require_once __DIR__ . '/news_queries.php';
class EditorialDeleteException extends RuntimeException {
    public $stage;
    public function __construct($stage, Throwable $previous) {
        $this->stage = $stage;
        parent::__construct($stage . ': ' . $previous->getMessage(), 0, $previous);
    }
}
/** Busca somente vinculos existentes; bancos antigos usam nomes diferentes. */
function editorialDeleteTables(PDO $pdo, $news = false) {
    $tables = [];
    foreach ($news ? ['comentarios', 'curtidas'] : ['comentarios', 'curtidas', 'chat_mensagens'] as $table) {
        foreach ($news ? ['noticia_id', 'news_id'] : ['usuario_id', 'user_id', 'autor_id'] as $column) {
            try { $pdo->query("SELECT $column FROM $table WHERE 1 = 0"); $tables[$table] = $column; break; }
            catch (PDOException $e) {
                if (in_array((string) $e->getCode(), ['42S02', '42P01'], true) || stripos($e->getMessage(), 'no such table') !== false) break;
                if (in_array((string) $e->getCode(), ['42S22', '42703'], true) || stripos($e->getMessage(), 'no such column') !== false) continue;
                throw $e;
            }
        }
    }
    return $tables;
}
function editorialColumnNullable(PDO $pdo, $table, $column) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        foreach ($pdo->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($row['name'] === $column) return !(bool) $row['notnull'];
        }
    } elseif ($driver === 'mysql') {
        foreach ($pdo->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($row['Field'] === $column) return $row['Null'] === 'YES';
        }
    } else {
        $stmt = $pdo->prepare('SELECT is_nullable FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ? AND column_name = ?');
        $stmt->execute([$table, $column]);
        return $stmt->fetchColumn() === 'YES';
    }
    throw new RuntimeException('Coluna nao encontrada.');
}
function editorialDeleteError(Throwable $error) {
    $stage = $error instanceof EditorialDeleteException ? $error->stage : 'verificar estrutura do banco';
    $cause = $error->getPrevious() ?: $error;
    $code = $cause instanceof PDOException ? ($cause->errorInfo[1] ?? $cause->getCode()) : '';
    $detail = '';
    if (in_array((string) $code, ['1048', '23502'], true) || stripos($cause->getMessage(), 'NOT NULL constraint') !== false) $detail = ' O banco exige um autor vinculado; autor_id precisa aceitar NULL para preservar as noticias.';
    elseif (in_array((string) $code, ['1451', '23503'], true) || stripos($cause->getMessage(), 'FOREIGN KEY constraint') !== false) $detail = ' Existe outro vinculo no banco impedindo a exclusao.';
    return 'Nao foi possivel excluir o usuario. Nenhuma materia foi apagada. Etapa: ' . $stage . '. Codigo: ' . preg_replace('/[^a-zA-Z0-9]/', '', (string) $code) . '.' . $detail;
}
function editorialDeleteUser(PDO $pdo, $id) {
    $hasAuthor = newsHasColumn($pdo, 'autor_nome');
    $tables = editorialDeleteTables($pdo);
    $chatNullable = !isset($tables['chat_mensagens']) || editorialColumnNullable($pdo, 'chat_mensagens', $tables['chat_mensagens']);
    $stage = 'iniciar exclusao';
    $pdo->beginTransaction();
    try {
        $stage = 'buscar usuario';
        $stmt = $pdo->prepare('SELECT nome FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        $name = $stmt->fetchColumn();
        if ($name === false) throw new RuntimeException('Usuario nao encontrado.');
        $stage = 'preservar assinatura';
        if ($hasAuthor) $pdo->prepare("UPDATE noticias SET autor_nome = ? WHERE autor_id = ? AND (autor_nome IS NULL OR autor_nome = '')")->execute([$name, $id]);
        // Desvincular ANTES do DELETE protege inclusive bancos antigos com ON DELETE CASCADE.
        $stage = 'desvincular noticias';
        $pdo->prepare('UPDATE noticias SET autor_id = NULL WHERE autor_id = ?')->execute([$id]);
        foreach ($tables as $table => $column) {
            $stage = 'desvincular ' . $table;
            $sql = $table === 'chat_mensagens' && $chatNullable ? "UPDATE $table SET $column = NULL WHERE $column = ?" : "DELETE FROM $table WHERE $column = ?";
            $pdo->prepare($sql)->execute([$id]);
        }
        $stage = 'excluir cadastro';
        $pdo->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw new EditorialDeleteException($stage, $e); }
}
function editorialDeleteNews(PDO $pdo, $id) {
    $tables = editorialDeleteTables($pdo, true);
    $pdo->beginTransaction();
    try {
        foreach (['comentarios', 'curtidas'] as $table) {
            if (isset($tables[$table])) $pdo->prepare("DELETE FROM $table WHERE {$tables[$table]} = ?")->execute([$id]);
        }
        $stmt = $pdo->prepare('DELETE FROM noticias WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->rowCount()) throw new RuntimeException('Noticia nao encontrada.');
        $pdo->commit();
        // Arquivos podem ser compartilhados por materias: nao apaga imagens do disco aqui.
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
