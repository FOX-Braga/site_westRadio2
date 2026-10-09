<?php
/** Cria o banco SQLite sem apagar ou popular dados existentes. */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Execute este script pelo terminal.');
}

$path = $argv[1] ?? dirname(__DIR__) . '/database.sqlite';
$pdo = null;
try {
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $schema = file_get_contents(__DIR__ . '/schema_sqlite.sql');
    if ($schema === false) {
        throw new RuntimeException('Nao foi possivel ler o esquema SQLite.');
    }
    $pdo->beginTransaction();
    $pdo->exec($schema);
    $columns = array_column($pdo->query('PRAGMA table_info(noticias)')->fetchAll(), 'name');
    if (!in_array('data_agendamento', $columns, true)) {
        $pdo->exec('ALTER TABLE noticias ADD COLUMN data_agendamento DATETIME DEFAULT NULL');
    }
    $pdo->commit();
    echo 'Banco pronto: ' . $path . PHP_EOL;
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $quoted = '"' . str_replace('"', '""', $table) . '"';
        echo $table . ': ' . $pdo->query('SELECT COUNT(*) FROM ' . $quoted)->fetchColumn() . ' registros' . PHP_EOL;
    }
    echo 'Integridade: ' . $pdo->query('PRAGMA integrity_check')->fetchColumn() . PHP_EOL;
    $violations = $pdo->query('PRAGMA foreign_key_check')->fetchAll();
    if ($violations) {
        throw new RuntimeException('Foram encontradas referencias invalidas: ' . count($violations));
    }
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}