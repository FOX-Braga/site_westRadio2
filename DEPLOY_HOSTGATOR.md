# 🚀 Guia Definitivo e Detalhado de Instalação na HostGator

Este é o manual completo feito **passo a passo para iniciantes** sobre como colocar o seu portal **96 News** no ar utilizando a hospedagem da HostGator (painel cPanel).

Não se preocupe se você não tem experiência técnica! Siga os passos exatamente como descritos abaixo e seu site estará funcionando perfeitamente.

---

## 📦 Passo 1: Preparando seus Arquivos (No seu Computador)

Antes de abrir a HostGator, precisamos empacotar seu site.

1. Abra a pasta no seu computador onde estão os arquivos do site (a pasta chamada `site_westRadio`).
2. Você verá arquivos como `index.php`, pastas como `admin`, `assets`, `config`, etc.
3. **Selecione TODOS** os arquivos e pastas que estão aí dentro.
4. Clique com o botão direito do mouse sobre os arquivos selecionados.
5. Escolha a opção de **Compactar (ZIP)** (Se usar Windows, é "Enviar para" > "Pasta Compactada").
6. Um arquivo `.zip` será gerado. Dê o nome de `site.zip`. Deixe-o aí por enquanto.

---

## 🗄️ Passo 2: Criando o Banco de Dados (No Painel HostGator)

O Banco de Dados é onde ficam salvas as suas notícias, senhas e categorias.

1. Acesse o site da HostGator e faça Login no seu **Portal do Cliente**.
2. Clique no botão amarelo **cPanel** (fica ao lado do nome do seu plano de hospedagem).
3. Dentro do cPanel, desça a tela até a seção chamada **Bancos de Dados**.
4. Clique no ícone **Assistente de Banco de Dados MySQL**.

**Siga o Assistente:**
1. **Passo 1:** Ele pedirá um nome para o banco. Digite `noticias` e clique em **Próxima Etapa**. *(Nota: O cPanel adiciona um pedaço do seu login antes, então o nome final ficará algo como `seulogin_noticias`. Anote esse nome inteiro num bloco de notas!)*
2. **Passo 2:** Ele pedirá um nome de usuário. Digite `usuario` e crie uma senha **muito forte** no campo de senha. Clique em **Criar Usuário**. *(Anote também o nome de usuário completo, ex: `seulogin_usuario`, e a senha que você criou!)*
3. **Passo 3:** Na tela seguinte, marque a caixinha lá em cima que diz **"TODOS OS PRIVILÉGIOS"**. Role a página para baixo e clique em **Fazer Alterações**.

Perfeito! O cofre onde os dados vão morar está criado.

---

## 💾 Passo 3: Colocando as Tabelas no Banco de Dados

Agora precisamos colocar as gavetas dentro do cofre.

1. Volte para a tela inicial do **cPanel** (clicando no logo laranja do cPanel no canto superior esquerdo).
2. Na seção **Bancos de Dados**, clique no ícone **phpMyAdmin**.
3. Uma nova guia vai abrir. No lado esquerdo, procure pelo nome do banco que você criou no Passo 2 (ex: `seulogin_noticias`) e **clique nele**.
4. A tela da direita ficará vazia. No menu superior da tela da direita, clique na aba **Importar**.
5. No meio da tela, clique no botão **Escolher Arquivo** (ou Browse).
6. Procure no seu computador a pasta do seu site, abra a pasta `config` e selecione o arquivo chamado **`database.sql`**.
7. Role a página até o fim e clique no botão **Importar** (ou Executar/Go).
8. Vai aparecer uma mensagem verde dizendo que a importação foi finalizada.

---

## 🔗 Passo 4: Conectando o Site com o Banco de Dados

Agora o seu site precisa saber a senha do cofre que você acabou de criar.

1. Volte lá no seu computador, na pasta do seu site, e abra a pasta `config`.
2. Clique com o botão direito no arquivo **`config.php`** e abra com o **Bloco de Notas** (ou qualquer editor de texto).
3. Encontre as seguintes linhas (lá pela linha 33):

```php
define('DB_DRIVER', 'mysql'); 
define('DB_HOST', 'localhost');
define('DB_USER', '96user');  <-- VAMOS MUDAR ISSO
define('DB_PASS', 'west123');   <-- VAMOS MUDAR ISSO
define('DB_NAME', '96_news'); <-- VAMOS MUDAR ISSO
```

4. **Altere cuidadosamente** o texto que está entre as aspas simples, colocando os dados que você anotou no Passo 2. Vai ficar assim (substitua pelos seus dados reais):

```php
define('DB_USER', 'seulogin_usuario');  // O usuário completo que o cPanel gerou
define('DB_PASS', 'SuaSenhaForte123!'); // A senha que você inventou
define('DB_NAME', 'seulogin_noticias'); // O nome do banco completo
```

5. Clique em **Arquivo > Salvar** e feche o Bloco de Notas.
6. **IMPORTANTE:** Como você alterou um arquivo do seu computador, você precisa **refazer o Passo 1** (deletar o `site.zip` antigo e criar um `.zip` novo com todos os arquivos, agora atualizados!).

---

## 🚀 Passo 5: Enviando o Site para a HostGator

1. Volte à página inicial do **cPanel**.
2. Procure a seção **Arquivos** e clique em **Gerenciador de Arquivos**.
3. No lado esquerdo, procure uma pastinha com o ícone de um globo chamada **`public_html`** e clique nela. Esta é a pasta raiz do seu site na internet!
4. *(Opcional)* Se houver arquivos padrão da HostGator aí dentro como `default.html`, selecione e apague.
5. No menu lá no topo, clique em **Carregar** (Upload). Uma nova guia vai abrir.
6. Arraste o seu novo arquivo `site.zip` para dentro dessa tela (ou clique em Selecionar Arquivo).
7. Aguarde a barra carregar e ficar **100% Verde**.
8. Feche essa guia e volte para a guia do Gerenciador de Arquivos.
9. Clique no botão **Atualizar** (Recarregar) no menu superior. O `site.zip` vai aparecer aí.
10. Clique com o botão direito sobre o `site.zip` e escolha **Extrair** (Extract). Clique em *Extract Files* na caixinha que abrir.
11. Pode deletar o arquivo `site.zip` do Gerenciador para não ocupar espaço.

---

## 🔒 Passo 6: Exibindo os Arquivos Ocultos e Checando Permissões

A HostGator esconde arquivos muito sensíveis, precisamos ter certeza de que eles vieram junto.

1. Ainda no Gerenciador de Arquivos, dentro do `public_html`, olhe para o canto superior direito e clique no botão **Configurações**.
2. Marque a caixinha **Mostrar Arquivos Ocultos (dotfiles)** e clique em Save.
3. Agora você deve conseguir ver arquivos que começam com um ponto, como `.htaccess` e `.user.ini`. Se eles estiverem aí, excelente! A segurança avançada está ativada.
4. Agora, localize a pasta chamada **`uploads`**.
5. Clique nela e certifique-se que ela existe.

---

## 🎉 Pronto! O Site está no Ar

Seu portal de notícias 96 News já deve estar funcionando perfeitamente.

1. Abra uma nova aba no seu navegador e digite o endereço do seu site: `www.seusite.com.br`
2. Você verá a página inicial do portal.
3. Para acessar o painel de controle e começar a postar notícias, digite na barra de endereços:
   `www.seusite.com.br/admin`

### 🔑 Acesso do Administrador:
- **Email:** `admin@96news.com`
- **Senha:** `senha123` *(Recomendamos fortemente trocar a senha no painel o mais rápido possível!)*

---

### 🚨 Dúvidas Frequentes

* **O site diz "Erro ao Conectar ao Banco de Dados":** Volte no **Passo 4**. Você provavelmente digitou o nome do banco de dados ou a senha errada. Lembre-se que o usuário e o banco devem incluir o prefixo do cPanel (ex: `seulogin_...`).
* **Meu site não está com cadeado verde (HTTPS):** A HostGator instala o cadeado verde automaticamente. Pode levar de 1 a 4 horas após você apontar o domínio. Nossos arquivos de segurança vão forçar o cadeado aparecer assim que ele estiver pronto!
