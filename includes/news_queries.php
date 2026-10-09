<?php
/** Compatibilidade de leitura com bancos anteriores, sem alterar o schema. */
function newsHasColumn(PDO $pdo, $column) {
    if (!in_array($column, ['autor_nome', 'urgente', 'data_agendamento'], true)) throw new InvalidArgumentException('Coluna invalida.');
    static $cache;
    if (!$cache) $cache = new SplObjectStorage();
    $columns = $cache->contains($pdo) ? $cache[$pdo] : [];
    if (!array_key_exists($column, $columns)) {
        try {
            $pdo->query("SELECT $column FROM noticias WHERE 1 = 0");
            $columns[$column] = true;
        } catch (PDOException $e) {
            // Apenas ausencia de coluna tem fallback; outros erros continuam visiveis no log.
            if (!in_array((string) $e->getCode(), ['42S22', '42703'], true) && stripos($e->getMessage(), 'no such column') === false) throw $e;
            $columns[$column] = false;
        }
        $cache[$pdo] = $columns;
    }
    return $columns[$column];
}
function newsAuthorSql(PDO $pdo) {
    $saved = newsHasColumn($pdo, 'autor_nome') ? "NULLIF(n.autor_nome, '')" : 'NULL';
    return "COALESCE(NULLIF(u.nome, ''), $saved, 'Redação')";
}
/** Imagens antigas em Base64 sao servidas separadamente, nunca repetidas no HTML. */
function newsImageSql(PDO $pdo) {
    $marker = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql'
        ? "CONCAT('__news_image__', n.id)" : "'__news_image__' || n.id";
    return "CASE WHEN n.imagem_destacada LIKE 'data:%' THEN $marker ELSE n.imagem_destacada END";
}
function newsImageUrl($image) {
    if (!$image) return 'https://placehold.co/600x400/eeeeee/999999?text=Sem+Imagem';
    if (strpos($image, '__news_image__') === 0) return BASE_URL . '/imagem_noticia.php?id=' . (int) substr($image, 14);
    if (strpos($image, 'data:') === 0 || preg_match('~^https?://~i', $image)) return $image;
    return BASE_URL . '/uploads/noticias/' . $image;
}
