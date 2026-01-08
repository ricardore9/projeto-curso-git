# Lista de Materiais e Compras

Este é um sistema web para gerenciamento de listas de compras (materiais escolares, despesas, etc.), desenvolvido com PHP, MySQL e Bootstrap 5.

## Instalação

### 1. Banco de Dados

1.  Acesse o painel de controle da sua hospedagem (phpMyAdmin ou similar).
2.  Selecione o banco de dados `covesa26_listaMateriais`.
3.  Importe o arquivo `database.sql` incluído neste pacote. Isso criará as tabelas necessárias:
    *   `users`
    *   `shopping_lists`
    *   `list_items`
    *   `list_shares`
    *   `password_resets`
    *   **Nota:** A tabela `users` foi atualizada para incluir o campo `email`. Se você já instalou a versão anterior, exclua as tabelas e importe o `database.sql` novamente.

### 2. Configuração

O arquivo `config.php` já está configurado com as credenciais fornecidas:
*   Banco: `covesa26_listaMateriais`
*   Usuário: `covesa26_listaMateriais`
*   Senha: `@1defrc30`

Se precisar alterar, edite o arquivo `config.php`.

### 3. Upload dos Arquivos

Faça o upload de **todos** os arquivos e pastas para a pasta pública do seu servidor (ex: `public_html/compras` ou onde desejar que o site fique acessível).

Estrutura de pastas:
*   `/api/` (Scripts PHP para as requisições AJAX)
*   `/css/` (Estilos CSS)
*   `*.php` (Arquivos principais do site)

### 4. Uso

1.  Acesse o endereço onde você instalou o site (ex: `igrejacese.com.br/compras/`).
2.  Crie uma conta em "Cadastre-se".
3.  Faça login.
4.  Crie suas listas, adicione itens e compartilhe com outros usuários cadastrados.
