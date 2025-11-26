

## 🚀 Guia de Configuração e Inicialização do PedeAI (Final)

Este guia detalha o processo de clonagem e configuração inicial do projeto PedeAI (PHP Puro com Router e Supabase).

### 1\. Pré-requisitos ⚙️

Certifique-se de que os seguintes softwares estão instalados em sua máquina:

  * **Servidor Web Local:** Apache (XAMPP, WAMP, Laragon, etc.)
  * **Linguagem de Programação:** **PHP 8.x**
  * **Gerenciador de Dependências:** **Composer**
  * **Git:** Para clonagem do repositório.

### 2\. Clonagem e Dependências 📦

1.  **Navegue até o diretório do seu servidor web** (ex: `htdocs`):

    ```bash
    cd /caminho/do/seu/servidor/htdocs
    ```

2.  **Clone o projeto:**

    ```bash
    git clone https://github.com/esterarraiz/pedeai.git
    ```

3.  **Entre no diretório do projeto** (ex: `PedeAI`):

    ```bash
    cd PedeAI
    ```

4.  **Instale as Dependências do PHP:**
    O projeto utiliza o Composer para gerenciar bibliotecas, incluindo a possível integração com o Supabase.

    ```bash
    composer install
    ```

### 3\. Configuração do Banco de Dados (Supabase) ✨

O PedeAI utiliza o Supabase como backend principal.

1.  **Criação e Schema no Supabase:**

      * Crie um projeto no Supabase.
      * Importe o *schema* SQL para configurar as tabelas. O arquivo deve ser encontrado em `database/` ou seguir as instruções de *seed* do projeto.

2.  **Configuração da Conexão:**

      * **Crie ou localize o arquivo de configuração de ambiente** na raiz do projeto (como o `.env` que aparece na estrutura) e configure as variáveis:

    <!-- end list -->

    ```bash
    # Exemplo do arquivo .env
    DB_CONNECTION=pgsql

    # Host disponível em "Project Settings → Database → Connection Info"
    DB_HOST=[SEU_DB_HOST]

    DB_PORT=5432

    # Nome padrão do banco: postgres
    DB_DATABASE=postgres

    # Usuário padrão configurado no Supabase
    DB_USERNAME=[SEU_DB_USER]

    # Senha definida na criação do projeto
    DB_PASSWORD=[SUA_DB_PASSWORD]

    ```

      * **Se o projeto usa o `config/Database.php`** (como sugerido pela estrutura) para gerenciar credenciais, certifique-se de que este arquivo está lendo as variáveis do `.env` ou está configurado diretamente com os *placeholders* do Supabase.

### 4\. Configuração do Servidor Web e Rotas 🛣️

O projeto usa o **Router** em `App/Core/Router.php`, exigindo o redirecionamento de todas as requisições para o `index.php`.

1.  **Habilite o Modulo Rewrite** (Apache) e verifique se o arquivo `.htaccess` na raiz do projeto está presente e configurado:

    ```htaccess
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
    ```

### 5\. Inicialização do Projeto

1.  **Inicie o Servidor Web e o Banco de Dados (se for usar o Docker, inicie-o).**

2.  **Acesse o Projeto no Navegador:**

      * URL: `http://localhost/PedeAI/` (ou a URL do seu host virtual).

3.  **✔️ Tudo pronto!!**


---