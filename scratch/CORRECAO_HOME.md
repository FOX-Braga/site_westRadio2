# Correcao da home e consumo de memoria

A home publica de https://96news.com.br/ foi observada respondendo HTTP 200 com HTML interrompido antes das noticias. O log atual de producao nao esta disponivel nesta sessao, portanto a causa no servidor precisa ser confirmada depois do envio.

A falha de memoria foi reproduzida localmente com 920 noticias publicadas, 48 delas com texto de aproximadamente 1 MB e capas Base64 de aproximadamente 1 MB, mais rascunho e agendamento futuro. A home antiga falhou por falta de memoria com limite de 64 MB. A versao corrigida terminou com pico de 2 MB de memoria PHP, cerca de 56 KB de HTML e 0,65 s no teste local. Esses numeros nao sao uma promessa de tempo no servidor de producao.

## Mudancas pontuais

- Listagens selecionam apenas os campos usados pelos cards, sem o corpo completo e sem trazer capas Base64 para o PHP.
- Removida a consulta de 20 noticias que nao era utilizada pela home.
- Faixas de manha/tarde carregam apenas titulo, slug e data.
- Capas Base64 existentes continuam intactas no banco. O novo imagem.php entrega os bytes originais com cache HTTP, sem inserir a capa no HTML de cada listagem. Capas em arquivo continuam usando uploads/noticias.
- Esquema existente e verificado antes de tentar ALTER/CREATE. Visitas sem agendamento vencido nao executam UPDATE.
- O carregador Skypack do Turbo falhava no navegador. Foi substituido pela distribuicao local da mesma versao 8.0.23, com sua licenca MIT. Fonte: https://github.com/hotwired/turbo/releases/tag/v8.0.23 . Distribuicao: https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0.23/dist/turbo.es2017-esm.js .

## Arquivos de producao no ZIP

- index.php
- categoria.php
- busca.php
- api_busca.php
- imagem.php (novo)
- includes/functions.php
- includes/header.php
- assets/js/turbo.es2017-esm.js (novo)
- assets/js/turbo.LICENSE.txt (novo)

## Aplicar na hospedagem

1. Salve uma copia dos arquivos atuais listados acima para permitir retorno a versao anterior.
2. Extraia o ZIP na raiz do site, preservando a estrutura das pastas. Envie includes/functions.php e imagem.php antes de index.php/categoria.php/busca.php; envie o Turbo local antes de includes/header.php. Pelo gerenciador cPanel, o pacote pode ser extraido junto na raiz.
3. Nao substitua database.sqlite, config/config.php, uploads nem importe SQL. O pacote nao inclui esses arquivos e nao exige migracao dos dados.
4. Abra a home em aba anonima e confira uma capa, uma noticia, categorias e busca. Se houver cache de pagina na hospedagem/CDN, limpe-o. Se o servidor conservar PHP antigo em OPcache, use o recurso da hospedagem para reiniciar/revalidar o PHP.
5. Confira os logs recentes da hospedagem para confirmar que nao ha novos erros de memoria/SQL. Caso o HTML ainda termine antes das noticias, o erro atual do servidor e necessario para diagnosticar uma eventual diferenca de esquema/configuracao.

O deploy automatico existente aponta para /home1/por46724/westnews.com.br/. Confirme se essa e tambem a raiz que atende 96news.com.br antes de usar esse fluxo. Esta correcao nao foi publicada pela sessao.

## Validacao executada

- Sintaxe PHP dos arquivos alterados.
- Reproducao da falha antiga e testes da home/categoria/busca com memory_limit=64M em SQLite temporario.
- HTTP: home, categoria, pagina 2, busca, autocomplete de cinco resultados e noticia individual.
- Capas: comparacao SHA-256 com bytes originais, MIME, resposta 304, IDs invalidos e bloqueio de rascunhos/agendamento futuro.
- Seis requisicoes de capa completas.
- Agendamento vencido publicado e futuro preservado.
- Exclusao de autor preservou as 923 noticias ficticias.
- Chrome em 1440px e 390px: manchete, capas, links e rodape; sem excecoes JavaScript depois da correcao do Turbo.
- Regressao do painel para administrador e jornalista.

Os testes nao usaram o banco de producao. MySQL/PostgreSQL de producao nao estao acessiveis para teste nesta sessao. O SQL novo usa CASE/SUBSTR, comuns aos drivers suportados, e mantem os filtros e a ordenacao existentes.

Para repetir o teste de memoria (em um caminho temporario novo):

```powershell
php scratch/test_home_performance.php setup "$env:TEMP/96news-fixture-nova.sqlite"
php -d memory_limit=64M scratch/test_home_performance.php home "$env:TEMP/96news-fixture-nova.sqlite"
php -d memory_limit=64M scratch/test_home_performance.php category "$env:TEMP/96news-fixture-nova.sqlite"
php -d memory_limit=64M scratch/test_home_performance.php search "$env:TEMP/96news-fixture-nova.sqlite"
```