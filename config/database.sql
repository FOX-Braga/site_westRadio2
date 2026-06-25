-- Estrutura do Banco de Dados para 96 News
-- Compatível com MySQL / MariaDB

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo` enum('admin','jornalista','user') DEFAULT 'user',
  `foto_perfil` varchar(255) DEFAULT NULL,
  `bio` text,
  `verificado` tinyint(1) NOT NULL DEFAULT '0',
  `token_verificacao` varchar(100) DEFAULT NULL,
  `criado_em` timestamp DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `slug` varchar(60) NOT NULL,
  `cor` varchar(7) DEFAULT '#1b5e20',
  `criado_em` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `noticias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `subtitulo` varchar(255) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `conteudo` longtext NOT NULL,
  `imagem_destacada` varchar(255) DEFAULT NULL,
  `legenda_imagem` varchar(255) DEFAULT NULL,
  `autor_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `visualizacoes` int(11) DEFAULT 0,
  `status` enum('rascunho','publicado') DEFAULT 'rascunho',
  `destaque` boolean DEFAULT false,
  `urgente` boolean DEFAULT false,
  `criado_em` timestamp DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `autor_id` (`autor_id`),
  KEY `categoria_id` (`categoria_id`),
  CONSTRAINT `fk_noticias_autor` FOREIGN KEY (`autor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_noticias_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `comentarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `noticia_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `conteudo` text NOT NULL,
  `status` enum('pendente','aprovado','rejeitado') DEFAULT 'aprovado',
  `criado_em` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `noticia_id` (`noticia_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_comentarios_noticia` FOREIGN KEY (`noticia_id`) REFERENCES `noticias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comentarios_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `curtidas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `noticia_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `criado_em` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unica_curtida` (`noticia_id`,`usuario_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_curtidas_noticia` FOREIGN KEY (`noticia_id`) REFERENCES `noticias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_curtidas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DADOS DE TESTE (DUMMY DATA)

-- Senha padrão para os usuários de teste é: 'senha123'
-- Hash gerado com password_hash('senha123', PASSWORD_DEFAULT)
INSERT INTO `usuarios` (`nome`, `email`, `senha`, `tipo`, `bio`, `verificado`) VALUES
('Administrador', 'admin@96news.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Editor-chefe do portal 96 News.', 1),
('João Leitor', 'joao@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'Leitor assíduo de notícias de tecnologia.', 1),
('Admin Extra', 'admin@admin', '$2y$10$SqCK78N.6y.ZVeTxUeVjy.zE4Ef0vFfeFqPMhIe2hUIDNAZX28tT2', 'admin', 'Administrador adicional de teste.', 1);

INSERT INTO `categorias` (`nome`, `slug`, `cor`) VALUES
('Política', 'politica', '#1e3a8a'),
('Economia', 'economia', '#065f46'),
('Tecnologia', 'tecnologia', '#3b82f6'),
('Esportes', 'esportes', '#ea580c'),
('Mato Grosso do Sul', 'mato-grosso-do-sul', '#1b5e20');

INSERT INTO `noticias` (`titulo`, `subtitulo`, `slug`, `conteudo`, `imagem_destacada`, `autor_id`, `categoria_id`, `status`, `destaque`, `urgente`) VALUES
('Novo Polo Tecnológico é Inaugurado no MS', 'Governo estadual anuncia investimentos de R$ 50 milhões no setor de inovação e tecnologia', 'novo-polo-tecnologico-ms', '<p>O Governo de Mato Grosso do Sul inaugurou nesta manhã o maior polo de inovação do estado. O espaço contará com laboratórios de robótica, inteligência artificial e incubadoras para startups.</p><p>A cerimônia contou com a presença de diversas autoridades e líderes do setor privado.</p>', 'polo_tecnologico.png', 1, 3, 'publicado', 1, 0),
('Taxa Selic é mantida e mercado reage positivamente', 'Copom decide manter juros básicos, aliviando pressões no varejo e estimulando a bolsa', 'taxa-selic-mantida-mercado', '<p>Em decisão unânime, o Comitê de Política Monetária (Copom) do Banco Central optou por não alterar a taxa básica de juros. Especialistas afirmam que o cenário é de estabilidade para os próximos meses.</p>', 'selic_mercado.png', 1, 2, 'publicado', 1, 0),
('Alerta de Tempestade: Ventos fortes atingem Campo Grande', 'Defesa civil emite alerta vermelho para tempestades nas próximas 24 horas', 'alerta-tempestade-campo-grande', '<p>Previsão indica volumes de chuva acima de 50mm e ventos de até 80km/h. A população deve evitar áreas de risco e alagamento.</p>', 'tempestade_cg.png', 1, 5, 'publicado', 0, 1),
('Final do Campeonato Estadual terá casa cheia', 'Ingressos esgotados em apenas duas horas após o início das vendas', 'final-campeonato-estadual', '<p>A expectativa é de um jogo histórico no próximo domingo. Torcedores de ambos os times prometem uma grande festa nas arquibancadas.</p>', NULL, 1, 4, 'publicado', 0, 0),
('Rebeldes do Pantanal vencem o clássico de futebol de areia', 'Com gol nos acréscimos, time local garante liderança do campeonato regional', 'rebeldes-pantanal-vencem-classico', '<p>Uma partida emocionante agitou a Arena Pantanal neste sábado. Os Rebeldes venceram por 3 a 2 em um jogo cheio de reviravoltas e gol salvador no último minuto do jogo.</p>', 'rebeldes_pantanal.png', 1, 4, 'publicado', 1, 0),
('Câmara Municipal aprova novo plano de mobilidade urbana', 'Projeto prevê ampliação de ciclovias e modernização do transporte coletivo', 'camara-aprova-novo-plano-mobilidade', '<p>A Câmara Municipal aprovou por unanimidade o novo plano estrutural para as vias da cidade. Os investimentos previstos ultrapassam R$ 15 milhões em infraestrutura viária.</p>', NULL, 1, 1, 'publicado', 1, 0),
('Startup do MS cria solução inovadora para rastreamento de gado', 'Tecnologia utiliza inteligência artificial e coleiras conectadas por satélite', 'startup-ms-rastreamento-gado', '<p>Uma startup baseada em Campo Grande desenvolveu um dispositivo inteligente que promete revolucionar a pecuária de corte, monitorando a saúde e a geolocalização dos animais em tempo real.</p>', NULL, 1, 3, 'publicado', 0, 0),
('Feira de Negócios e Agropecuária projeta recorde de faturamento', 'Expectativa dos organizadores é movimentar mais de R$ 100 milhões em novos contratos', 'feira-negocios-agropecuaria-recorde', '<p>A tradicional feira agropecuária da região teve início nesta quarta-feira com estandes de tecnologia agrícola, palestras sobre sustentabilidade e leilões de alta linhagem.</p>', NULL, 1, 2, 'publicado', 0, 0);

INSERT INTO `comentarios` (`noticia_id`, `usuario_id`, `conteudo`, `status`) VALUES
(1, 2, 'Excelente notícia para o nosso estado! Mais oportunidades de emprego na área.', 'aprovado'),
(2, 2, 'Isso ajuda muito os pequenos empreendedores.', 'aprovado');

INSERT INTO `curtidas` (`noticia_id`, `usuario_id`) VALUES
(1, 2),
(3, 2);

-- Tabelas Adicionadas para Vídeos, Lives e Chat em Tempo Real

CREATE TABLE IF NOT EXISTS `videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `youtube_id` varchar(50) NOT NULL,
  `tipo` enum('live','video') DEFAULT 'video',
  `duracao` varchar(10) DEFAULT NULL,
  `criado_em` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `youtube_id` (`youtube_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_mensagens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `video_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `nome_usuario` varchar(100) NOT NULL,
  `mensagem` varchar(255) NOT NULL,
  `criado_em` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_chat_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `aovivo_pings` (
  `session_id` varchar(100) NOT NULL,
  `ultimo_ping` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `videos` (`id`, `titulo`, `descricao`, `youtube_id`, `tipo`, `duracao`) VALUES
(1, '96 News / 96 FM - Transmissão Oficial ao Vivo', 'Acompanhe nossa programação jornalística e musical direto do estúdio de Campo Grande - MS. Notícias que importam e a rádio que toca os EUA.', 'yP5S7V7g3p0', 'live', 'AO VIVO'),
(2, 'Bastidores 96 FM - Melhores Momentos do Estúdio', 'Veja o que acontece por trás das câmeras durante os programas da manhã da 96 FM com nossos apresentadores.', 'XqgG17dYqC8', 'video', '12:45'),
(3, 'Entrevista Especial: Projetos de Ecoturismo no Pantanal', 'Nossa equipe conversa com especialistas sobre a preservação e o turismo sustentável nas planícies do MS.', '5F7y80fWfVw', 'video', '18:20'),
(4, 'Polo Tecnológico do MS: Investimentos e Impacto Local', 'Entenda como o novo polo tecnológico inaugurado em Campo Grande vai impulsionar a economia e startups do estado.', 'J7hXq26HicE', 'video', '08:15')
ON DUPLICATE KEY UPDATE `youtube_id`=`youtube_id`;

INSERT INTO `chat_mensagens` (`id`, `usuario_id`, `nome_usuario`, `mensagem`) VALUES
(1, 2, 'João Leitor', 'Melhor rádio do MS! Escuto todo dia de Três Lagoas.'),
(2, 3, 'Admin Extra', 'Abraço de Dourados! Excelente transmissão e sinal 100%.'),
(3, 1, 'Administrador', 'O estúdio novo ficou sensacional! Parabéns 96 News.'),
(4, 2, 'João Leitor', 'Que música massa, toca mais rock clássico aí!'),
(5, 3, 'Admin Extra', 'Parabéns pela qualidade da imagem!'),
(6, 1, 'Administrador', 'Excelente matéria de Campo Grande.')
ON DUPLICATE KEY UPDATE `id`=`id`;

