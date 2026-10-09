<?php
/** Plano de migracao separado: nunca executado automaticamente ao excluir usuarios. */
function authorNullableMigrationPlan(PDO $pdo) {
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') throw new RuntimeException('Esta correcao e destinada ao MySQL/MariaDB da hospedagem.');
    foreach ($pdo->query('SHOW COLUMNS FROM noticias')->fetchAll(PDO::FETCH_ASSOC) as $column) {
        if ($column['Field'] !== 'autor_id') continue;
        if ($column['Null'] === 'YES') return null;
        $type = $column['Type'];
        if (!preg_match('/\A(?:tinyint|smallint|mediumint|int|bigint)(?:\([0-9]+\))?(?: unsigned)?(?: zerofill)?\z/i', $type)) throw new RuntimeException('O tipo de autor_id exige revisao manual.');
        return 'ALTER TABLE noticias MODIFY COLUMN autor_id ' . $type . ' NULL DEFAULT NULL';
    }
    throw new RuntimeException('Coluna autor_id nao encontrada.');
}
