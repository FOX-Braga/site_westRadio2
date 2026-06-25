-- Estrutura do Banco de Dados para West News (Versão Supabase PostgreSQL)

DROP TABLE IF EXISTS curtidas CASCADE;
DROP TABLE IF EXISTS comentarios CASCADE;
DROP TABLE IF EXISTS noticias CASCADE;
DROP TABLE IF EXISTS categorias CASCADE;
DROP TABLE IF EXISTS usuarios CASCADE;

CREATE TABLE usuarios (
  id SERIAL PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  tipo VARCHAR(50) DEFAULT 'user' CHECK (tipo IN ('admin', 'jornalista', 'user')),
  foto_perfil VARCHAR(255) DEFAULT NULL,
  bio TEXT DEFAULT NULL,
  verificado INTEGER DEFAULT 1,
  token_verificacao VARCHAR(100) DEFAULT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categorias (
  id SERIAL PRIMARY KEY,
  nome VARCHAR(50) NOT NULL,
  slug VARCHAR(60) NOT NULL UNIQUE,
  cor VARCHAR(7) DEFAULT '#1b5e20',
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE noticias (
  id SERIAL PRIMARY KEY,
  titulo VARCHAR(255) NOT NULL,
  subtitulo VARCHAR(255) DEFAULT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  conteudo TEXT NOT NULL,
  imagem_destacada VARCHAR(255) DEFAULT NULL,
  legenda_imagem VARCHAR(255) DEFAULT NULL,
  autor_id INTEGER NOT NULL REFERENCES usuarios (id) ON DELETE CASCADE,
  categoria_id INTEGER NOT NULL REFERENCES categorias (id) ON DELETE CASCADE,
  visualizacoes INTEGER DEFAULT 0,
  status VARCHAR(50) DEFAULT 'rascunho' CHECK (status IN ('rascunho', 'publicado')),
  destaque BOOLEAN DEFAULT FALSE,
  urgente BOOLEAN DEFAULT FALSE,
  data_agendamento TIMESTAMP DEFAULT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE comentarios (
  id SERIAL PRIMARY KEY,
  noticia_id INTEGER NOT NULL REFERENCES noticias (id) ON DELETE CASCADE,
  usuario_id INTEGER NOT NULL REFERENCES usuarios (id) ON DELETE CASCADE,
  conteudo TEXT NOT NULL,
  status VARCHAR(50) DEFAULT 'aprovado' CHECK (status IN ('pendente', 'aprovado', 'rejeitado')),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE curtidas (
  id SERIAL PRIMARY KEY,
  noticia_id INTEGER NOT NULL REFERENCES noticias (id) ON DELETE CASCADE,
  usuario_id INTEGER NOT NULL REFERENCES usuarios (id) ON DELETE CASCADE,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (noticia_id, usuario_id)
);

-- DADOS DE TESTE (DUMMY DATA)

INSERT INTO usuarios (id, nome, email, senha, tipo, bio, verificado) VALUES
(1, 'Administrador', 'admin@westnews.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Editor-chefe do portal West News.', 1),
(2, 'João Leitor', 'joao@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'Leitor assíduo de notícias de tecnologia.', 1),
(3, 'Admin Extra', 'admin@admin', '$2y$10$SqCK78N.6y.ZVeTxUeVjy.zE4Ef0vFfeFqPMhIe2hUIDNAZX28tT2', 'admin', 'Administrador adicional de teste.', 1);

SELECT setval('usuarios_id_seq', (SELECT MAX(id) FROM usuarios));

INSERT INTO categorias (id, nome, slug, cor) VALUES
(1, 'Política', 'politica', '#1e3a8a'),
(2, 'Economia', 'economia', '#065f46'),
(3, 'Tecnologia', 'tecnologia', '#3b82f6'),
(4, 'Esportes', 'esportes', '#ea580c'),
(5, 'Mato Grosso do Sul', 'mato-grosso-do-sul', '#1b5e20');

SELECT setval('categorias_id_seq', (SELECT MAX(id) FROM categorias));

INSERT INTO noticias (id, titulo, subtitulo, slug, conteudo, imagem_destacada, autor_id, categoria_id, status, destaque, urgente) VALUES
(1, 'Novo Polo Tecnológico é Inaugurado no MS', 'Governo estadual anuncia investimentos de R$ 50 milhões no setor de inovação e tecnologia', 'novo-polo-tecnologico-ms', '<p>O Governo de Mato Grosso do Sul inaugurou nesta manhã o maior polo de inovação do estado. O espaço contará com laboratórios de robótica, inteligência artificial e incubadoras para startups.</p><p>A cerimônia contou com a presença de diversas autoridades e líderes do setor privado.</p>', 'polo_tecnologico.png', 1, 3, 'publicado', TRUE, FALSE),
(2, 'Taxa Selic é mantida e mercado reage positivamente', 'Copom decide manter juros básicos, aliviando pressões no varejo e estimulando a bolsa', 'taxa-selic-mantida-mercado', '<p>Em decisão unânime, o Comitê de Política Monetária (Copom) do Banco Central optou por não alterar a taxa básica de juros. Especialistas afirmam que o cenário é de estabilidade para os próximos meses.</p>', 'selic_mercado.png', 1, 2, 'publicado', TRUE, FALSE),
(3, 'Alerta de Tempestade: Ventos fortes atingem Campo Grande', 'Defesa civil emite alerta vermelho para tempestades nas próximas 24 horas', 'alerta-tempestade-campo-grande', '<p>Previsão indica volumes de chuva acima de 50mm e ventos de até 80km/h. A população deve evitar áreas de risco e alagamento.</p>', 'tempestade_cg.png', 1, 5, 'publicado', FALSE, TRUE),
(4, 'Final do Campeonato Estadual terá casa cheia', 'Ingressos esgotados em apenas duas horas após o início das vendas', 'final-campeonato-estadual', '<p>A expectativa é de um jogo histórico no próximo domingo. Torcedores de ambos os times prometem uma grande festa nas arquibancadas.</p>', 'final_campeonato.png', 1, 4, 'publicado', FALSE, FALSE),
(5, 'Rebeldes do Pantanal vencem o clássico de futebol de areia', 'Com gol nos acréscimos, time local garante liderança do campeonato regional', 'rebeldes-pantanal-vencem-classico', '<p>Uma partida emocionante agitou a Arena Pantanal neste sábado. Os Rebeldes venceram por 3 a 2 em um jogo cheio de reviravoltas e gol salvador no último minuto do jogo.</p>', 'rebeldes_pantanal.png', 1, 4, 'publicado', TRUE, FALSE),
(6, 'Câmara Municipal aprova novo plano de mobilidade urbana', 'Projeto prevê ampliação de ciclovias e modernização do transporte coletivo', 'camara-aprova-novo-plano-mobilidade', '<p>A Câmara Municipal aprovou por unanimidade o novo plano estrutural para as vias da cidade. Os investimentos previstos ultrapassam R$ 15 milhões em infraestrutura viária.</p>', 'mobilidade_urbana.png', 1, 1, 'publicado', TRUE, FALSE),
(7, 'Startup do MS cria solução inovadora para rastreamento de gado', 'Tecnologia utiliza inteligência artificial e coleiras conectadas por satélite', 'startup-ms-rastreamento-gado', '<p>Uma startup baseada em Campo Grande desenvolveu um dispositivo inteligente que promete revolucionar a pecuária de corte, monitorando a saúde e a geolocalização dos animais em tempo real.</p>', 'rastreamento_gado.png', 1, 3, 'publicado', FALSE, FALSE),
(8, 'Feira de Negócios e Agropecuária projeta recorde de faturamento', 'Expectativa dos organizadores é movimentar mais de R$ 100 milhões em novos contratos', 'feira-negocios-agropecuaria-recorde', '<p>A tradicional feira agropecuária da região teve início nesta quarta-feira com estandes de tecnologia agrícola, palestras sobre sustentabilidade e leilões de alta linhagem.</p>', 'feira_negocios.png', 1, 2, 'publicado', FALSE, FALSE);

SELECT setval('noticias_id_seq', (SELECT MAX(id) FROM noticias));

INSERT INTO comentarios (id, noticia_id, usuario_id, conteudo, status) VALUES
(1, 1, 2, 'Excelente notícia para o nosso estado! Mais oportunidades de emprego na área.', 'aprovado'),
(2, 2, 2, 'Isso ajuda muito os pequenos empreendedores.', 'aprovado');

SELECT setval('comentarios_id_seq', (SELECT MAX(id) FROM comentarios));

INSERT INTO curtidas (id, noticia_id, usuario_id) VALUES
(1, 1, 2),
(2, 3, 2);

SELECT setval('curtidas_id_seq', (SELECT MAX(id) FROM curtidas));

-- Tabelas Adicionadas para Vídeos, Lives e Chat em Tempo Real

CREATE TABLE IF NOT EXISTS videos (
  id SERIAL PRIMARY KEY,
  titulo VARCHAR(255) NOT NULL,
  descricao TEXT DEFAULT NULL,
  youtube_id VARCHAR(50) NOT NULL UNIQUE,
  tipo VARCHAR(50) DEFAULT 'video' CHECK (tipo IN ('live', 'video')),
  duracao VARCHAR(10) DEFAULT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS chat_mensagens (
  id SERIAL PRIMARY KEY,
  video_id INTEGER NULL,
  usuario_id INTEGER NULL REFERENCES usuarios (id) ON DELETE SET NULL,
  nome_usuario VARCHAR(100) NOT NULL,
  mensagem VARCHAR(255) NOT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS aovivo_pings (
  session_id VARCHAR(100) PRIMARY KEY,
  ultimo_ping TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO videos (id, titulo, descricao, youtube_id, tipo, duracao) VALUES
(1, 'West News / West FM - Transmissão Oficial ao Vivo', 'Acompanhe nossa programação jornalística e musical direto do estúdio de Campo Grande - MS. Notícias que importam e a rádio que toca os EUA.', 'yP5S7V7g3p0', 'live', 'AO VIVO'),
(2, 'Bastidores West FM - Melhores Momentos do Estúdio', 'Veja o que acontece por trás das câmeras durante os programas da manhã da West FM com nossos apresentadores.', 'XqgG17dYqC8', 'video', '12:45'),
(3, 'Entrevista Especial: Projetos de Ecoturismo no Pantanal', 'Nossa equipe conversa com especialistas sobre a preservação e o turismo sustentável nas planícies do MS.', '5F7y80fWfVw', 'video', '18:20'),
(4, 'Polo Tecnológico do MS: Investimentos e Impacto Local', 'Entenda como o novo polo tecnológico inaugurado em Campo Grande vai impulsionar a economia e startups do estado.', 'J7hXq26HicE', 'video', '08:15')
ON CONFLICT (youtube_id) DO NOTHING;

SELECT setval('videos_id_seq', (SELECT MAX(id) FROM videos));

INSERT INTO chat_mensagens (id, usuario_id, nome_usuario, mensagem) VALUES
(1, 2, 'João Leitor', 'Melhor rádio do MS! Escuto todo dia de Três Lagoas.'),
(2, 3, 'Admin Extra', 'Abraço de Dourados! Excelente transmissão e sinal 100%.'),
(3, 1, 'Administrador', 'O estúdio novo ficou sensacional! Parabéns West News.'),
(4, 2, 'João Leitor', 'Que música massa, toca mais rock clássico aí!'),
(5, 3, 'Admin Extra', 'Parabéns pela qualidade da imagem!'),
(6, 1, 'Administrador', 'Excelente matéria de Campo Grande.')
ON CONFLICT (id) DO NOTHING;

SELECT setval('chat_mensagens_id_seq', (SELECT MAX(id) FROM chat_mensagens));

