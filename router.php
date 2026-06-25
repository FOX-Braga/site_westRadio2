<?php
/**
 * Roteador para o servidor interno do PHP (desenvolvimento local)
 * Simula as regras de reescrita do Apache (.htaccess)
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// Remove a barra final se não for a raiz
if ($uri !== '/' && str_ends_with($uri, '/')) {
    $uri = rtrim($uri, '/');
}

// Se o arquivo ou diretório físico existir, serve diretamente
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// noticia/([a-zA-Z0-9_-]+)
if (preg_match('/^\/noticia\/([a-zA-Z0-9_-]+)$/', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/noticia.php';
    exit;
}

// categoria/([a-zA-Z0-9_-]+)
if (preg_match('/^\/categoria\/([a-zA-Z0-9_-]+)$/', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/categoria.php';
    exit;
}

// busca
if ($uri === '/busca') {
    require __DIR__ . '/busca.php';
    exit;
}

// páginas simples
$paginas_simples = [
    '/ao-vivo' => 'aovivo.php',
    '/sobre' => 'sobre.php',
    '/contato' => 'contato.php',
    '/login' => 'login.php',
    '/cadastro' => 'cadastro.php',
    '/perfil' => 'perfil.php'
];

if (isset($paginas_simples[$uri])) {
    require __DIR__ . '/' . $paginas_simples[$uri];
    exit;
}

// Retorna false para que arquivos estáticos sejam servidos pelo servidor interno do PHP
return false;
