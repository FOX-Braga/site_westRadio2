-- Estrutura SQLite do portal 96 News. Sem dados de demonstracao.
PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `nome` TEXT NOT NULL,
  `email` TEXT NOT NULL UNIQUE,
  `senha` TEXT NOT NULL,
  `tipo` TEXT DEFAULT 'user',
  `foto_perfil` TEXT DEFAULT NULL,
  `bio` TEXT,
  `verificado` INTEGER DEFAULT 0,
  `token_verificacao` TEXT DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `categorias` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `nome` TEXT NOT NULL,
  `slug` TEXT NOT NULL UNIQUE,
  `cor` TEXT DEFAULT '#1b5e20',
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `noticias` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `titulo` TEXT NOT NULL,
  `subtitulo` TEXT DEFAULT NULL,
  `slug` TEXT NOT NULL UNIQUE,
  `conteudo` TEXT NOT NULL,
  `imagem_destacada` TEXT DEFAULT NULL,
  `legenda_imagem` TEXT DEFAULT NULL,
  `autor_id` INTEGER DEFAULT NULL,
  `autor_nome` TEXT DEFAULT NULL,
  `categoria_id` INTEGER NOT NULL,
  `visualizacoes` INTEGER DEFAULT 0,
  `status` TEXT DEFAULT 'rascunho',
  `destaque` INTEGER DEFAULT 0,
  `urgente` INTEGER DEFAULT 0,
  `data_agendamento` DATETIME DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`autor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `comentarios` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `noticia_id` INTEGER NOT NULL,
  `usuario_id` INTEGER NOT NULL,
  `conteudo` TEXT NOT NULL,
  `status` TEXT DEFAULT 'aprovado',
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`noticia_id`) REFERENCES `noticias` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `curtidas` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `noticia_id` INTEGER NOT NULL,
  `usuario_id` INTEGER NOT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (`noticia_id`, `usuario_id`),
  FOREIGN KEY (`noticia_id`) REFERENCES `noticias` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `videos` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `titulo` TEXT NOT NULL,
  `descricao` TEXT DEFAULT NULL,
  `youtube_id` TEXT NOT NULL UNIQUE,
  `tipo` TEXT DEFAULT 'video',
  `duracao` TEXT DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `chat_mensagens` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `video_id` INTEGER DEFAULT NULL,
  `usuario_id` INTEGER DEFAULT NULL,
  `nome_usuario` TEXT NOT NULL,
  `mensagem` TEXT NOT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS `aovivo_pings` (
  `session_id` TEXT PRIMARY KEY,
  `ultimo_ping` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS banners (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  titulo TEXT NOT NULL,
  imagem TEXT NOT NULL,
  link TEXT NOT NULL,
  status TEXT DEFAULT 'ativo',
  criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_noticias_status_data ON noticias(status, criado_em);
CREATE INDEX IF NOT EXISTS idx_noticias_categoria ON noticias(categoria_id);
CREATE INDEX IF NOT EXISTS idx_noticias_autor ON noticias(autor_id);
CREATE INDEX IF NOT EXISTS idx_comentarios_noticia ON comentarios(noticia_id);
CREATE INDEX IF NOT EXISTS idx_chat_criado_em ON chat_mensagens(criado_em);
CREATE INDEX IF NOT EXISTS idx_pings_ultimo_ping ON aovivo_pings(ultimo_ping);
