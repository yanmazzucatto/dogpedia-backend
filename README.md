# Dogpedia Backend API (PHP + Supabase)

Este é o backend RESTful em PHP para o projeto Dogpedia. Ele atua como um intermediário seguro (middleware) entre o frontend React e o banco de dados Supabase, lidando com todas as operações CRUD.

## 1. Requisitos

| Requisito | Detalhe |
| :--- | :--- |
| **Servidor Web** | Apache, Nginx, ou servidor embutido do PHP. |
| **Versão do PHP** | PHP 7.4 ou superior (Recomendado PHP 8.x). |
| **Extensão cURL** | `php-curl` instalada e habilitada. |
| **Projeto Supabase** | Projeto ativo com RLS configurada. (Veja `supabase_schema.md`) |

## 2. Configuração

1.  **Clone este repositório.**
2.  **Crie o `config.php`:** Na pasta `src/`, crie o arquivo `config.php`.
3.  **Adicione suas credenciais:** Edite `src/config.php` e insira sua `SUPABASE_URL` e `SUPABASE_ANON_KEY`.

    ```php
    <?php
    // src/config.php
    define('SUPABASE_URL', 'SUA_URL_DO_SUPABASE_AQUI');
    define('SUPABASE_ANON_KEY', 'SUA_CHAVE_ANON_DO_SUPABASE_AQUI');
    
    // Cabeçalhos comuns
    define('SUPABASE_HEADERS', [
        'Content-Type: application/json',
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY
    ]);
    ```
4.  **Configure o Supabase:** Certifique-se de que suas tabelas (veja `supabase_schema.md`) tenham as **Políticas de RLS (Row Level Security)** corretas para permitir que a chave `anon` execute as operações necessárias.

## 3. Executando Localmente

A forma mais simples de testar é usando o servidor embutido do PHP.

1.  Navegue até a pasta `src/` do projeto.
2.  Execute o comando:
    ```bash
    php -S localhost:8000
    ```
3.  Sua API estará disponível em `http://localhost:8000`.

## 4. Endpoints da API

A API usa o último segmento da URL como o nome da tabela (`breeds`, `categories`, `posts`, `comments`).

| Operação | Método | Endpoint (Exemplo) | Descrição |
| :--- | :--- | :--- | :--- |
| **Read (All)** | `GET` | `/categories` | Retorna todos os registros. |
| **Read (Filter)** | `GET` | `/breeds?id=eq.1` | Retorna registros com `id = 1`. |
| **Create** | `POST` | `/posts` | Cria um novo registro (dados no corpo JSON). |
| **Update** | `PATCH` | `/posts?id=eq.1` | Atualiza o registro com `id = 1` (dados no corpo JSON). |
| **Delete** | `DELETE` | `/comments?id=eq.1` | Exclui o registro com `id = 1`. |
