# Documentação Oficial - Projeto West News

Bem-vindo à documentação oficial do portal de notícias **West News**. 
Este documento foi estruturado em duas partes: uma voltada para desenvolvedores e proprietários (Human-Readable) e outra estruturada para fornecer contexto técnico exato a assistentes de Inteligência Artificial que venham a dar manutenção futura neste código (AI-Readable).

---

## 👩‍💻 PARTE 1: PARA HUMANOS (Manual do Desenvolvedor)

### 1. Visão Geral
O **West News** é um portal de jornalismo moderno, responsivo e focado em alta performance. O objetivo primário é oferecer um sistema de publicação de notícias limpo que pode ser facilmente hospedado em servidores compartilhados com cPanel (como HostGator), sem a dependência de frameworks pesados ou processos de build complexos (Node.js/Composer não são requeridos).

### 2. Tecnologias Utilizadas
- **Linguagem Principal:** PHP 8+ (Puro)
- **Banco de Dados:** MySQL / MariaDB (Via PDO)
- **Frontend:** HTML5, CSS3 Vanilla (sem Tailwind/Bootstrap), JavaScript Vanilla.
- **Integrações Externas:** FontAwesome (Ícones), Google Fonts (Poppins), QuillJS (Editor de texto rico no Admin).

### 3. Principais Funcionalidades
- **Autenticação:** Sistema completo de Login e Cadastro para usuários e administradores, protegidos contra força-bruta e injeções.
- **Painel Administrativo:** Permite gerenciar Notícias (CRUD), Categorias (CRUD), moderação de Comentários e permissões de Usuários.
- **Frontend Interativo:** 
  - *Mosaico de Destaques:* Grid responsivo na Home.
  - *Dark Mode:* Modo noturno que guarda a preferência do usuário via LocalStorage.
  - *Interações Sociais:* Usuários podem curtir, comentar em notícias e alterar avatares de perfil.
- **SEO Ready:** URLs amigáveis (slugs dinâmicos configurados via `.htaccess`).

### 4. Instalação e Deploy (cPanel / Local)

**Passo a passo básico para colocar o site no ar:**
1. Importe o arquivo `config/database.sql` para o seu banco de dados MySQL (via phpMyAdmin ou terminal).
2. Acesse o arquivo `config/config.php` e atualize as variáveis de ambiente:
   - `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` com os dados criados no cPanel.
3. Suba todos os arquivos para a pasta `public_html` da sua hospedagem.
4. **Credenciais Padrão:**
   - **Painel Admin:** `admin@westnews.com` / Senha: `senha123`

### 5. Estrutura de Diretórios
- `/admin` - Painel de controle restrito (CRUDs).
- `/assets` - CSS, JS e imagens estáticas (logo).
- `/config` - Classes e conexões (`database.php`, `config.php`, `database.sql`).
- `/includes` - Componentes visuais (`header.php`, `footer.php`) e lógicas globais (`auth.php`, `functions.php`).
- `/uploads` - Arquivos físicos dinâmicos (avatares, imagens de notícias). Protegidos com `chmod 0755`.

---

## 🤖 PART 2: FOR AI ASSISTANTS (Technical Context & Rules)

**[AI_CONTEXT]**
Hello AI. If you are reading this, you are tasked with modifying or extending the "West News" codebase. Follow these strict architectural guidelines and rules to maintain the integrity of the project.

### 1. Architecture & Design Patterns
- **No Heavy Frameworks:** Do NOT attempt to install composer packages, Laravel, or npm modules. This project relies entirely on Vanilla PHP 8+ and Vanilla JS/CSS.
- **Database Connection:** Use the Singleton pattern via `Database::getInstance()`. This returns a standard PDO object.
- **Routing:** Handled via Apache `.htaccess` rewriting requests to `index.php`, `noticia.php`, `categoria.php`, etc. Do not create a complex PHP router. Rely on query string parameters (`$_GET['slug']`) fed by URL rewriting.
- **Component Inclusion:** Always use `require_once __DIR__ . '/includes/...'` to prevent path traversal errors.

### 2. Security Protocols (MANDATORY)
- **SQL Injection Prevention:** ALL database queries involving variables MUST use PDO prepared statements (`$pdo->prepare()->execute()`). String concatenation in SQL is strictly forbidden.
- **XSS Prevention:** ALL user-generated content rendered in HTML MUST be wrapped in the `escape()` utility function (which is a wrapper for `htmlspecialchars()`).
- **CSRF Protection:** Forms mutating data must include `csrf_field()` and be validated via `verify_csrf()` in the POST logic.
- **Authentication:** Passwords are hashed using `password_hash()` (Argon2/Bcrypt). Session control relies on `$_SESSION['user_id']`. Use middlewares `requireLogin()` and `requireAdmin()` at the very top of restricted files.

### 3. Frontend & Styling Guidelines
- **CSS Architecture:** The project uses a global `assets/css/style.css`.
- **CSS Variables & Dark Mode:** The color palette is defined in `:root` and `[data-theme="dark"]`. If you add new colors, you MUST define them as variables (e.g., `--color-new`) and provide a dark mode counterpart.
- **Grid Layouts:** The homepage uses CSS Grid. The main highlights area (`.hero-grid`) relies on a 4-column mosaic layout (`grid-template-columns: repeat(4, 1fr)`). Do not break this layout when adding elements.
- **Responsiveness:** All custom CSS must be mobile-first or heavily rely on `@media (max-width: 768px)` blocks. 

### 4. Database Schema Relationships
- `usuarios` (id, nome, email, senha, tipo, avatar)
- `categorias` (id, nome, slug, cor)
- `noticias` (id, titulo, slug, conteudo, categoria_id, autor_id, destaque, urgente)
  - `categoria_id` -> `categorias(id)` (ON DELETE RESTRICT)
  - `autor_id` -> `usuarios(id)`
- `comentarios` (id, noticia_id, usuario_id, conteudo, status)
- `curtidas` (usuario_id, noticia_id) - Composite Primary Key.

### 5. How to Extend (Standard Operating Procedures)
- **Adding a New Page:** Create `page_name.php` in root. `require 'includes/header.php'` at the top and `footer.php` at the bottom.
- **Image Uploads:** Always use the `uploadImage()` function from `functions.php`. It validates MIME types and handles random naming to prevent conflicts.

**[/AI_CONTEXT]**
