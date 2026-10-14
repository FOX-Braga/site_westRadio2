<?php
/** Publicidade: armazenamento e renderização isolados do conteúdo editorial. */
function bannerMaxUploadSize() {
    // Limite exclusivo de publicidade; não altera uploads de notícias ou perfis.
    return 50 * 1024 * 1024;
}

function bannerUploadLimitLabel() {
    return (bannerMaxUploadSize() / 1048576) . ' MB';
}

function bannerAllowedSideImage($width, $height) {
    return in_array((int)$width, [120,160,300], true) && (int)$height === 600;
}

/** Agenda publicitária usa o fuso do painel, independente do conteúdo editorial. */
function bannerTimezone() {
    return new DateTimeZone('America/Sao_Paulo');
}

function bannerNow() {
    return (new DateTimeImmutable('now', bannerTimezone()))->format('Y-m-d H:i:s');
}

function bannerSlots() {
    return [
        'TOP'=>'Topo', 'MIDDLE'=>'Meio', 'BOTTOM'=>'Rodapé',
        'SIDE_LEFT_TOP'=>'Lateral esquerda superior', 'SIDE_RIGHT_TOP'=>'Lateral direita superior',
        'SIDE_LEFT_MIDDLE'=>'Lateral esquerda meio', 'SIDE_RIGHT_MIDDLE'=>'Lateral direita meio',
        'SIDE_LEFT_BOTTOM'=>'Lateral esquerda inferior', 'SIDE_RIGHT_BOTTOM'=>'Lateral direita inferior',
    ];
}

/** Executada somente na aba Banners; não altera tabelas editoriais nem banners antigos. */
function bannerInstall(PDO $pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $id = $driver === 'mysql' ? 'INT AUTO_INCREMENT PRIMARY KEY' : ($driver === 'pgsql' ? 'SERIAL PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT');
    $pdo->exec("CREATE TABLE IF NOT EXISTS banners (
        id $id, titulo VARCHAR(255) NOT NULL, imagem VARCHAR(255) NOT NULL,
        link VARCHAR(2048) NOT NULL, status VARCHAR(20) DEFAULT 'ativo',
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS banner_settings (
        banner_id INTEGER PRIMARY KEY, anunciante VARCHAR(255) NOT NULL,
        imagem_mobile VARCHAR(255), desktop_width INTEGER NOT NULL, desktop_height INTEGER NOT NULL,
        mobile_width INTEGER, mobile_height INTEGER, show_home INTEGER NOT NULL DEFAULT 0,
        all_categories INTEGER NOT NULL DEFAULT 0, categories TEXT NOT NULL, slots TEXT NOT NULL,
        starts_at VARCHAR(19), ends_at VARCHAR(19)
    )");
}

function bannerDirectory() {
    return defined('BANNER_UPLOAD_DIR') ? BANNER_UPLOAD_DIR : UPLOAD_DIR . 'banners/';
}

function bannerUrl($filename) {
    return BASE_URL . '/uploads/banners/' . rawurlencode($filename);
}

function bannerValidUrl($url) {
    return is_string($url) && strlen($url) <= 2048 && filter_var($url, FILTER_VALIDATE_URL)
        && in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['https', 'http'], true);
}

function bannerImageInfo($filename) {
    if (!$filename || basename($filename) !== $filename) return null;
    static $cache = [];
    if (array_key_exists($filename, $cache)) return $cache[$filename];
    $info = @getimagesize(rtrim(bannerDirectory(), '/\\') . '/' . $filename);
    return $cache[$filename] = $info && in_array($info['mime'], ['image/jpeg','image/png','image/webp'], true) ? [$info[0], $info[1]] : null;
}

function bannerUpload($file) {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) throw new RuntimeException('A imagem excedeu o limite de envio direto do servidor. Atualize a página e use o envio automático em partes.');
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('O envio da imagem falhou. Confira o limite do servidor.');
    if ($file['size'] > bannerMaxUploadSize()) throw new RuntimeException('Cada criativo deve ter no máximo ' . bannerUploadLimitLabel() . '.');
    return bannerStoreImage($file['tmp_name']);
}

function bannerValidateImage($path) {
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
    $formats = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'];
    $info = @getimagesize($path);
    if (!isset($formats[$mime]) || !$info || $info['mime'] !== $mime) throw new RuntimeException('Envie uma imagem WebP, JPG, JPEG ou PNG válida.');
    return [$formats[$mime], $info[0], $info[1]];
}

function bannerStoreImage($temporary, $uploaded = true) {
    [$extension, $width, $height] = bannerValidateImage($temporary);
    $directory = rtrim(bannerDirectory(), '/\\');
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) throw new RuntimeException('Não foi possível preparar a pasta de banners.');
    $name = 'ad_' . bin2hex(random_bytes(20)) . '.' . $extension;
    $stored = $uploaded ? move_uploaded_file($temporary, $directory . '/' . $name) : copy($temporary, $directory . '/' . $name);
    if (!$stored) { if (is_file($directory . '/' . $name)) unlink($directory . '/' . $name); throw new RuntimeException('Não foi possível armazenar a imagem.'); }
    return [$name, $width, $height];
}

function bannerRemoveFile($filename) {
    // Nunca aceita caminhos vindos do formulário; apenas nomes persistidos pelo módulo.
    if ($filename && basename($filename) === $filename) {
        $path = rtrim(bannerDirectory(), '/\\') . '/' . $filename;
        if (is_file($path)) unlink($path);
    }
}

function bannerCategories(PDO $pdo) {
    $categories = $pdo->query('SELECT slug, nome FROM categorias ORDER BY nome')->fetchAll(PDO::FETCH_ASSOC);
    if (!in_array('ultimas-noticias', array_column($categories, 'slug'), true)) $categories[] = ['slug'=>'ultimas-noticias', 'nome'=>'Últimas Notícias'];
    return $categories;
}

function bannerDate($value) {
    if ($value === '') return null;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, bannerTimezone());
    if (!$date || $date->format('Y-m-d\TH:i') !== $value) throw new RuntimeException('Data de campanha inválida.');
    return $date->format('Y-m-d H:i:s');
}

/** Seleção exata de página, posição, status e período. Sem fallback entre editorias. */
function bannerMatches(array $banner, $page, $slot, $category = null, $now = null) {
    $now = $now ?? bannerNow();
    if ($banner['status'] !== 'ativo' || !bannerValidUrl($banner['link'])) return false;
    if ((!empty($banner['starts_at']) && $now < $banner['starts_at']) || (!empty($banner['ends_at']) && $now > $banner['ends_at'])) return false;
    $slots = json_decode($banner['slots'] ?? '[]', true);
    if (!is_array($slots) || !in_array($slot, $slots, true)) return false;
    if ($page === 'home') return !empty($banner['show_home']);
    $categories = json_decode($banner['categories'] ?? '[]', true);
    return $page === 'category' && $category !== null && (!empty($banner['all_categories']) || (is_array($categories) && in_array($category, $categories, true)));
}

/** Explica no painel a disponibilidade real, além do status escolhido. */
function bannerVisibilityMessage(array $banner) {
    $slots = json_decode($banner['slots'] ?? '[]', true) ?: [];
    $categories = json_decode($banner['categories'] ?? '[]', true) ?: [];
    if (!$slots || (empty($banner['show_home']) && empty($banner['all_categories']) && !$categories)) return 'Aguardando configuração de páginas e posições. Esta campanha não é exibida.';
    if ($banner['status'] !== 'ativo') return 'Campanha pausada.';
    $now = bannerNow();
    if (!empty($banner['starts_at']) && $now < $banner['starts_at']) return 'Agendada: começa em ' . $banner['starts_at'] . ' (horário de São Paulo).';
    if (!empty($banner['ends_at']) && $now > $banner['ends_at']) return 'Campanha encerrada em ' . $banner['ends_at'] . ' (horário de São Paulo).';
    if (!bannerValidUrl($banner['link'])) return 'O link de destino precisa ser corrigido.';
    if (!bannerImageInfo($banner['imagem'])) return 'A imagem da campanha não foi encontrada. Edite e envie novamente.';
    if (!array_intersect($slots, ['TOP','MIDDLE','BOTTOM'])) return 'Em veiculação somente nas laterais. São exibidas quando existe espaço fora do conteúdo; ficam ocultas no celular e em janelas estreitas, inclusive com o DevTools aberto. Para aparecer nessas telas, selecione também Topo, Meio ou Rodapé.';
    return 'Em veiculação nas páginas e posições selecionadas.';
}

function bannerCandidates(PDO $pdo, $page, $slot, $category = null) {
    static $cache;
    if (!$cache) $cache = new SplObjectStorage();
    if (!$cache->contains($pdo)) {
        try {
            $rows = $pdo->query("SELECT b.*, s.* FROM banners b JOIN banner_settings s ON s.banner_id = b.id WHERE b.status = 'ativo' ORDER BY b.id")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // A primeira visita pública não precisa criar tabelas nem de privilégios DDL.
            if (!in_array((string)$e->getCode(), ['42S02','42P01'], true) && stripos($e->getMessage(), 'no such table') === false) error_log('Banners: ' . $e->getMessage());
            $rows = [];
        }
        $cache[$pdo] = $rows;
    }
    return array_values(array_filter($cache[$pdo], function ($row) use ($page, $slot, $category) {
        return bannerMatches($row, $page, $slot, $category) && bannerImageInfo($row['imagem']);
    }));
}

function bannerAssets() {
    static $loaded = false;
    if ($loaded) return;
    $loaded = true;
    echo '<link rel="stylesheet" href="' . escape(BASE_URL) . '/assets/css/banners.css?v=' . filemtime(__DIR__ . '/../assets/css/banners.css') . '">';
    echo '<script src="' . escape(BASE_URL) . '/assets/js/banners.js?v=' . filemtime(__DIR__ . '/../assets/js/banners.js') . '" defer></script>';
}

function renderBannerSlot(PDO $pdo, $page, $slot, $category = null) {
    $rows = bannerCandidates($pdo, $page, $slot, $category);
    if (!$rows) return;
    $side = strpos($slot, 'SIDE_') === 0;
    $groups = [];
    foreach ($rows as $row) {
        $groups[$side ? 0 : 970][] = $row;
    }
    foreach ($groups as $width => $items) {
        if ($side) $width = min(300, max(array_column($items, 'desktop_width')));
        $desktopRatio = $mobileRatio = 0;
        $mobileWidth = 0;
        foreach ($items as $row) {
            $desktopRatio = max($desktopRatio, $row['desktop_height'] / max(1, $row['desktop_width']));
            $hasMobile = !$side && !empty($row['imagem_mobile']) && bannerImageInfo($row['imagem_mobile']);
            $mw = $hasMobile ? $row['mobile_width'] : $row['desktop_width'];
            $mh = $hasMobile ? $row['mobile_height'] : $row['desktop_height'];
            $mobileRatio = max($mobileRatio, $mh / max(1, $mw));
            $mobileWidth = max($mobileWidth, min(970, $mw));
        }
        if ($side) {
            $height = max(array_map(function ($row) { return min(300, $row['desktop_width']) * $row['desktop_height'] / max(1, $row['desktop_width']); }, $items));
            $desktopRatio = $height / $width;
        }
        $class = $side ? 'ad-slot ad-side ad-' . strtolower($slot) : 'ad-slot ad-central ad-' . strtolower($slot);
        echo '<aside class="' . $class . '" data-ad-slot="' . $slot . '" data-ad-width="' . $width . '" aria-label="Publicidade" style="--ad-width:' . $width . 'px;--ad-mobile-width:' . $mobileWidth . 'px;--ad-ratio:' . (1 / max(.001, $desktopRatio)) . ';--ad-mobile-ratio:' . (1 / max(.001, $mobileRatio)) . '">';
        echo '<span class="ad-label">PUBLICIDADE</span><div class="ad-creative">';
        foreach ($items as $i => $row) {
            $hasMobile = !$side && !empty($row['imagem_mobile']) && bannerImageInfo($row['imagem_mobile']);
            $displayWidth = min(300, (int)$row['desktop_width']);
            $legacySide = in_array((int)$row['desktop_width'], [120,160,300], true) && (int)$row['desktop_height'] === 600;
            $minWidth = $legacySide ? $displayWidth : min(120, $displayWidth);
            echo '<a class="ad-slide" href="' . escape($row['link']) . '" target="_blank" rel="noopener noreferrer sponsored"' . ($i ? ' hidden' : '') . ' data-ad-image-width="' . (int)$row['desktop_width'] . '" data-ad-image-height="' . (int)$row['desktop_height'] . '" data-ad-display-width="' . $displayWidth . '" data-ad-min-width="' . $minWidth . '" data-campaign="' . (int)$row['id'] . '"><picture>';
            if ($hasMobile) echo '<source media="(max-width: 768px)" srcset="' . escape(bannerUrl($row['imagem_mobile'])) . '" width="' . (int)$row['mobile_width'] . '" height="' . (int)$row['mobile_height'] . '">';
            echo '<img src="' . escape(bannerUrl($row['imagem'])) . '" alt="' . escape($row['titulo']) . '" width="' . (int)$row['desktop_width'] . '" height="' . (int)$row['desktop_height'] . '" loading="' . ($slot === 'TOP' || substr($slot, -4) === '_TOP' ? 'eager' : 'lazy') . '" decoding="async"></picture></a>';
        }
        echo '</div></aside>';
    }
}

/** Um ponto de inserção natural reúne centro e laterais, sem envolver o grid editorial. */
function renderBannerBand(PDO $pdo, $page, $band, $category = null) {
    $slots = [$band, 'SIDE_LEFT_' . $band, 'SIDE_RIGHT_' . $band];
    $exists = false;
    foreach ($slots as $slot) if (bannerCandidates($pdo, $page, $slot, $category)) $exists = true;
    if (!$exists) return;
    bannerAssets();
    echo '<div class="ad-band" data-ad-band="' . $band . '">';
    foreach ($slots as $slot) renderBannerSlot($pdo, $page, $slot, $category);
    echo '</div>';
}
