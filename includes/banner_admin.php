<?php
require_once __DIR__ . '/banners.php';
require_once __DIR__ . '/banner_uploads.php';

function bannerFind(PDO $pdo, $id) {
    $stmt = $pdo->prepare('SELECT b.*, s.* FROM banners b LEFT JOIN banner_settings s ON s.banner_id = b.id WHERE b.id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function bannerDeleteUnusedImages(PDO $pdo, array $files) {
    foreach (array_unique(array_filter($files)) as $file) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM banners WHERE imagem = ?');
        $stmt->execute([$file]);
        $mobile = $pdo->prepare('SELECT COUNT(*) FROM banner_settings WHERE imagem_mobile = ?');
        $mobile->execute([$file]);
        if (!$stmt->fetchColumn() && !$mobile->fetchColumn()) bannerRemoveFile($file);
    }
}

function bannerSave(PDO $pdo, array $input, array $files, $existing = null) {
    $title = trim($input['titulo'] ?? '');
    $advertiser = trim($input['anunciante'] ?? '');
    $url = trim($input['link'] ?? '');
    if ($title === '' || $advertiser === '' || mb_strlen($title) > 255 || mb_strlen($advertiser) > 255) throw new RuntimeException('Informe campanha e anunciante com até 255 caracteres.');
    if (!bannerValidUrl($url) || strlen($url) > 255) throw new RuntimeException('Informe um link HTTP ou HTTPS válido com até 255 caracteres.');
    $status = $input['status'] ?? '';
    if (!in_array($status, ['ativo', 'pausado'], true)) throw new RuntimeException('Escolha Ativo ou Pausado.');
    $slots = $input['slots'] ?? [];
    $categories = $input['categories'] ?? [];
    if (!is_array($slots) || !$slots || array_diff($slots, array_keys(bannerSlots()))) throw new RuntimeException('Escolha pelo menos uma posição válida para a campanha.');
    if (!is_array($categories) || array_diff($categories, array_column(bannerCategories($pdo), 'slug'))) throw new RuntimeException('Escolha editorias válidas.');
    $home = isset($input['show_home']) ? 1 : 0;
    $all = isset($input['all_categories']) ? 1 : 0;
    if (!$home && !$all && !$categories) throw new RuntimeException('Escolha a Home, editorias específicas ou todas as editorias.');
    $start = bannerDate($input['starts_at'] ?? '');
    $end = bannerDate($input['ends_at'] ?? '');
    if ($end !== null) $end = substr($end, 0, 17) . '59';
    if ($start !== null && $end !== null && $end < $start) throw new RuntimeException('A data final deve ser igual ou posterior à data inicial.');
    $created = [];
    $tokens = [];
    try {
        if (!empty($input['imagem_token'])) {
            $desktop = bannerStoreImage(bannerReadyUpload($input['imagem_token'], 'imagem'), false);
            $tokens['imagem'] = $input['imagem_token'];
        } else $desktop = bannerUpload($files['imagem'] ?? []);
        $newDesktop = $desktop !== null;
        if ($desktop) $created[] = $desktop[0];
        else {
            $info = bannerImageInfo($existing['imagem'] ?? null);
            if (!$info) throw new RuntimeException('Envie o banner Desktop / Tablet.');
            $desktop = [$existing['imagem'], $info[0], $info[1]];
        }
        $isSide = function ($slot) { return strpos($slot, 'SIDE_') === 0; };
        $previousSlots = json_decode($existing['slots'] ?? '[]', true) ?: [];
        // Compatibilidade com criativos cadastrados antes da restauração dos formatos.
        $existingSide = $existing && array_filter($previousSlots, $isSide);
        if (array_filter($slots, $isSide) && !bannerAllowedSideImage($desktop[1], $desktop[2]) && ($newDesktop || !$existingSide)) {
            throw new RuntimeException('Formatos permitidos nas laterais: 120 × 600, 160 × 600 ou 300 × 600 px. Para outros tamanhos, selecione uma posição central ou envie uma arte lateral no formato permitido.');
        }
        if (!empty($input['imagem_mobile_token'])) {
            $mobile = bannerStoreImage(bannerReadyUpload($input['imagem_mobile_token'], 'imagem_mobile'), false);
            $tokens['imagem_mobile'] = $input['imagem_mobile_token'];
        } else $mobile = bannerUpload($files['imagem_mobile'] ?? []);
        if ($mobile) $created[] = $mobile[0];
        elseif (empty($input['remove_mobile']) && !empty($existing['imagem_mobile'])) {
            $info = bannerImageInfo($existing['imagem_mobile']);
            if ($info) $mobile = [$existing['imagem_mobile'], $info[0], $info[1]];
        }
        $pdo->beginTransaction();
        if ($existing) {
            $id = (int)$existing['id'];
            $pdo->prepare('UPDATE banners SET titulo = ?, imagem = ?, link = ?, status = ? WHERE id = ?')->execute([$title, $desktop[0], $url, $status, $id]);
        } else {
            $sql = 'INSERT INTO banners (titulo, imagem, link, status) VALUES (?, ?, ?, ?)';
            if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') $sql .= ' RETURNING id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $desktop[0], $url, $status]);
            $id = (int)($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql' ? $stmt->fetchColumn() : $pdo->lastInsertId());
        }
        $pdo->prepare('DELETE FROM banner_settings WHERE banner_id = ?')->execute([$id]);
        $pdo->prepare('INSERT INTO banner_settings (banner_id, anunciante, imagem_mobile, desktop_width, desktop_height, mobile_width, mobile_height, show_home, all_categories, categories, slots, starts_at, ends_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$id, $advertiser, $mobile[0] ?? null, $desktop[1], $desktop[2], $mobile[1] ?? null, $mobile[2] ?? null, $home, $all, json_encode(array_values(array_unique($categories))), json_encode(array_values(array_unique($slots))), $start, $end]);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        foreach ($created as $file) bannerRemoveFile($file);
        throw $error;
    }
    if ($existing) bannerDeleteUnusedImages($pdo, [$existing['imagem'], $existing['imagem_mobile'] ?? null]);
    foreach ($tokens as $kind => $token) bannerConsumeUpload($token, $kind);
    return $id;
}

function bannerDelete(PDO $pdo, array $existing) {
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM banner_settings WHERE banner_id = ?')->execute([$existing['id']]);
        $pdo->prepare('DELETE FROM banners WHERE id = ?')->execute([$existing['id']]);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
    bannerDeleteUnusedImages($pdo, [$existing['imagem'], $existing['imagem_mobile'] ?? null]);
}
