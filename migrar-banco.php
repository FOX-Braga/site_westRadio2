<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Apenas administradores podem rodar a migração em produção
requirePanelAccess();
if (!isAdmin()) {
    die("<h2 style='color:red;'>Acesso Negado. Você precisa ser um administrador.</h2>");
}

$pdo = Database::getInstance();
echo "<div style='font-family: sans-serif; padding: 20px;'>";
echo "<h1>Iniciando Verificação do Banco de Dados...</h1>";
echo "<p>Isso garantirá que todas as tabelas e colunas necessárias existam, sem apagar nenhum dado.</p>";

try {
    // Tabela de Usuarios
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        senha TEXT NOT NULL,
        tipo TEXT DEFAULT 'usuario',
        foto_perfil TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✔️ Tabela <b>usuarios</b> verificada.<br>";

    // Tabela de Categorias
    $pdo->exec("CREATE TABLE IF NOT EXISTS categorias (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✔️ Tabela <b>categorias</b> verificada.<br>";

    // Tabela de Noticias
    $pdo->exec("CREATE TABLE IF NOT EXISTS noticias (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titulo TEXT NOT NULL,
        subtitulo TEXT,
        slug TEXT NOT NULL UNIQUE,
        conteudo TEXT NOT NULL,
        imagem_destacada TEXT,
        autor_id INTEGER NOT NULL,
        categoria_id INTEGER NOT NULL,
        status TEXT DEFAULT 'publicado',
        destaque INTEGER DEFAULT 0,
        urgente INTEGER DEFAULT 0,
        data_agendamento DATETIME,
        views INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(autor_id) REFERENCES usuarios(id),
        FOREIGN KEY(categoria_id) REFERENCES categorias(id)
    )");
    echo "✔️ Tabela <b>noticias</b> verificada.<br>";
    
    // Tabela Banners
    $pdo->exec("CREATE TABLE IF NOT EXISTS banners (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titulo TEXT NOT NULL,
        imagem TEXT NOT NULL,
        link TEXT,
        posicao TEXT DEFAULT 'topo',
        ativo INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✔️ Tabela <b>banners</b> verificada.<br>";

    // Verificar e adicionar colunas faltantes em todas as tabelas
    $esquema = [
        'usuarios' => [
            'nome' => "TEXT NOT NULL DEFAULT ''",
            'email' => "TEXT NOT NULL DEFAULT ''",
            'senha' => "TEXT NOT NULL DEFAULT ''",
            'tipo' => "TEXT DEFAULT 'usuario'",
            'foto_perfil' => 'TEXT',
            'created_at' => 'DATETIME'
        ],
        'categorias' => [
            'nome' => "TEXT NOT NULL DEFAULT ''",
            'slug' => "TEXT NOT NULL DEFAULT ''",
            'created_at' => 'DATETIME'
        ],
        'noticias' => [
            'titulo' => "TEXT NOT NULL DEFAULT ''",
            'subtitulo' => 'TEXT',
            'slug' => "TEXT NOT NULL DEFAULT ''",
            'conteudo' => "TEXT NOT NULL DEFAULT ''",
            'imagem_destacada' => 'TEXT',
            'autor_id' => 'INTEGER NOT NULL DEFAULT 0',
            'categoria_id' => 'INTEGER NOT NULL DEFAULT 0',
            'status' => "TEXT DEFAULT 'publicado'",
            'destaque' => 'INTEGER DEFAULT 0',
            'urgente' => 'INTEGER DEFAULT 0',
            'data_agendamento' => 'DATETIME',
            'views' => 'INTEGER DEFAULT 0',
            'created_at' => 'DATETIME',
            'updated_at' => 'DATETIME'
        ],
        'banners' => [
            'titulo' => "TEXT NOT NULL DEFAULT ''",
            'imagem' => "TEXT NOT NULL DEFAULT ''",
            'link' => 'TEXT',
            'posicao' => "TEXT DEFAULT 'topo'",
            'ativo' => 'INTEGER DEFAULT 1',
            'created_at' => 'DATETIME'
        ]
    ];

    foreach ($esquema as $tabela => $colunas) {
        $q = $pdo->query("PRAGMA table_info($tabela)");
        $colunasAtuais = [];
        foreach($q->fetchAll(PDO::FETCH_ASSOC) as $col) {
            $colunasAtuais[] = $col['name'];
        }

        foreach($colunas as $coluna => $tipo) {
            if (!in_array($coluna, $colunasAtuais)) {
                $pdo->exec("ALTER TABLE $tabela ADD COLUMN $coluna $tipo");
                echo "➕ Coluna <b>$coluna</b> adicionada à tabela '$tabela'.<br>";
            }
        }
    }

    echo "<br><h2 style='color:green;'>✅ Migração concluída com sucesso! Nenhuma informação foi apagada.</h2>";
    echo "<a href='admin/noticia_form.php' style='display:inline-block; margin-top:20px; padding:10px 20px; background:#1a365d; color:white; text-decoration:none; border-radius:5px;'>Voltar para tentar cadastrar Notícia</a>";

} catch (Exception $e) {
    echo "<h2 style='color:red;'>Erro durante a migração:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}

echo "</div>";
?>
