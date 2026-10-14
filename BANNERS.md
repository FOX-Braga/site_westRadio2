# Publicidade no 96NEWS

O módulo está restrito à aba **Banners**, aos pontos publicitários da Home e das
editorias e aos arquivos `includes/banner*.php`, `assets/css/banner*.css` e
`assets/js/banners.js`. Notícias individuais, menu, footer, usuários e editor
não recebem alterações. No cabeçalho foi removido somente o carrossel antigo
que compartilhava todos os banners entre todas as páginas.

## Uso

1. Abra **Admin → Banners**. A primeira visita cria as tabelas de publicidade,
   se necessário, sem modificar tabelas editoriais. A conta do banco precisa
   permitir `CREATE TABLE` nessa etapa; as páginas públicas apenas consultam.
2. Informe campanha, anunciante, URL HTTP/HTTPS e arte Desktop / Tablet.
3. Escolha explicitamente as páginas e as posições. Não há escolhas automáticas.
4. Envie uma arte Mobile opcional, defina o período e escolha Ativo ou Pausado.

**Todas as editorias** inclui editorias futuras e a página Últimas Notícias.
Essa opção não inclui a Home. Ao desmarcá-la, voltam a valer apenas as editorias
selecionadas. A Home é sempre uma escolha independente.

Os banners antigos continuam cadastrados e editáveis, com seus arquivos
preservados, mas só são exibidos depois de configurar páginas e posições.
Não há migração automática para a Home nem para todas as editorias.

Datas usam `America/Sao_Paulo`, o horário do painel de publicidade, independente
do fuso editorial de Campo Grande. O painel mostra a hora atual e explica se
a campanha está agendada, encerrada, pausada, sem configuração ou disponível
somente nas laterais. Sem início, a campanha
começa imediatamente; sem final, não expira. O minuto final é inclusivo.
Pausar sempre interrompe a exibição, independentemente das datas.

## Formatos e exibição

| Posição | Desktop / Tablet | Mobile |
| --- | --- | --- |
| TOP | 970 × 90 | 320 × 100 |
| MIDDLE / BOTTOM | 970 × 250 | 320 × 100 ou 300 × 250 |
| Laterais | 120 × 600, 160 × 600 ou 300 × 600 | Ocultas |

Arquivos WebP, JPG/JPEG e PNG são validados por MIME e dimensões, com limite de
50 MB por arquivo, em um limite exclusivo de publicidade que não altera os
uploads de notícias ou perfis. O painel envia cada imagem em partes de até 256 KB para uma
área temporária privada e envia apenas os identificadores no cadastro final.
Isso permite cadastrar desktop e mobile mesmo com PHP limitado a 8 MB por POST
e 2 MB por upload, sem mudar a configuração do servidor. A autorização de
administrador e o CSRF continuam obrigatórios; cada envio pertence ao usuário
que o iniciou. Temporários são consumidos após salvar, ou limpos depois de
24 horas quando o usuário inicia outro envio. Sem arte mobile, a arte desktop é exibida proporcionalmente,
sem recorte. Os tamanhos centrais da tabela são recomendações. Novos criativos
laterais devem ter exatamente 120 × 600, 160 × 600 ou 300 × 600 px. Imagens
16:9 e outras proporções podem ocupar posições centrais. Artes laterais
cadastradas durante o período de formatos livres continuam sendo exibidas e
podem ter seus dados editados sem reenviar o arquivo. Ao substituir a imagem,
ou acrescentar posições laterais a uma campanha antes apenas central, os
formatos laterais permitidos passam a ser obrigatórios.

Campanhas elegíveis no mesmo slot alternam a cada 5 segundos. A seleção não
mistura páginas ou posições. Os links usam nova aba e
`noopener noreferrer sponsored`. Não há tracking externo.

O container editorial continua com 1.200 px. Cada lateral precisa da própria
largura mais 24 px livres na margem; portanto, as larguras de 120, 160 e 300 px
exigem aproximadamente 1.488, 1.568 e 1.848 px de área útil, respectivamente.
O JavaScript mede o espaço real, incluindo a largura perdida com scrollbar.
Quando várias larguras ocupam o mesmo slot, só as que cabem participam da
rotação. Artes laterais antigas fora dos formatos atuais são ajustadas à largura livre, até 300 px,
com altura proporcional e largura mínima de 120 px (ou a largura original,
quando menor). Os formatos verticais antigos mantêm suas larguras originais.
As laterais também precisam caber verticalmente dentro do conteúdo; essas artes
antigas são reduzidas proporcionalmente pela altura disponível,
o que permite exibi-las também em editorias com poucas notícias. A margem
mínima e o espaço do rótulo são respeitados, sem cobrir o footer;
posições inferiores são ocultadas quando não houver altura para evitar
sobreposição. Durante a rolagem, cada lateral acompanha a janela até o próximo
banner do mesmo lado, mantendo 16 px de separação. O último para antes do rodapé.
Artes mais altas que a janela passam por inteiro antes de acompanhar pela base,
para que toda a imagem possa ser vista. Ao voltar a página para cima, os banners
retornam às suas posições iniciais.

O alinhamento vertical usa o centro das notícias, excluindo o anúncio central:
na Home, as laterais superiores acompanham o mosaico de manchetes; as do meio
acompanham o próximo bloco editorial; as inferiores acompanham o último bloco.
Nas editorias, topo e rodapé seguem a primeira e a última linha de cards,
enquanto o meio acompanha o centro da grade. A posição é recalculada ao
redimensionar a janela ou mudar o tamanho dos blocos de notícias.

Sem campanhas elegíveis, o renderizador não emite caixas, rótulos, margens nem
assets de publicidade. As páginas públicas não criam tabelas.

## Banco e manutenção

`banners` preserva os registros existentes. A tabela adicional `banner_settings`
armazena anunciante, criativo mobile, dimensões, páginas, posições e período.
As duas tabelas são atualizadas em transações; imagens compartilhadas não são
apagadas enquanto houver outra campanha referenciando o arquivo.

O armazenamento usa PDO e SQL compatível com SQLite, MySQL/MariaDB e PostgreSQL.
Os testes automatizados usam SQLite temporário; MySQL/PostgreSQL exigem
validação no respectivo ambiente antes da publicação.

## Verificação local

```text
node scratch/test_banners_system.cjs
node scratch/test_banner_uploads.cjs
node scratch/test_banner_scroll.cjs
```

Requer PHP com PDO SQLite, GD e mbstring, Node.js 22+ e Chrome/Edge instalado.
O teste não instala dependências. Cria banco, uploads, fontes de comparação e
perfil do navegador em uma pasta temporária, cujo caminho é informado ao final.
Não utiliza o `database.sqlite` real. Requisições externas do navegador são
bloqueadas; o HTML real gerado pelo PHP e os assets locais são incorporados ao
documento para a verificação de geometria. O agendamento de frames é simulado
porque o compositor headless do ambiente não entrega frames. A comparação usa
coordenadas e dimensões do DOM; não inclui comparação de screenshots.

São verificadas permissões, CSRF, CRUD, isolamento, datas, fallback mobile,
criativos responsivos, posições individuais e simultâneas. Home e uma editoria
são comparadas com o código de `HEAD`, com todos os banners pausados, nas onze
resoluções solicitadas. Por isso, essa comparação deve ser executada antes de
commitar a implementação; depois do commit, `HEAD` passa a conter a versão nova.

O teste de uploads reproduz os limites PHP de 8M/2M e executa o JavaScript real
do formulário com um banner de 12 MB e um mobile de 4,2 MB. Confere os bytes armazenados, os
limites por requisição, os erros de tamanho, a autorização, o CSRF e a retomada
após falhas, usando apenas arquivos e banco temporários.

O teste de rolagem verifica o acompanhamento das duas laterais, a passagem ao
próximo banner, o limite do rodapé, a volta ao topo, janelas baixas, resize,
celular e navegação Turbo. Não altera o banco. Para validar também o HTML real
do servidor local, execute `node scratch/test_banner_scroll.cjs --live=http://localhost:8080`.

Para verificar também a campanha lateral ativa no servidor local:

```text
node scratch/test_banners_system.cjs --live=http://localhost:8080
```

A opção aceita somente localhost/127.0.0.1. Consulta a Home e Economia por GET
e verifica as laterais no HTML real em 1920, 1366 e 390 px. A campanha lateral
deve aparecer na tela larga e ficar oculta nas telas sem espaço. Nenhuma
posição central é adicionada automaticamente para compensar a falta de margem.
