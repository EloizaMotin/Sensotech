# SensoTech — Versão PHP + MySQL

Sistema educacional para gestão de análises sensoriais (IFPR Campus Colombo),
convertido do protótipo original (artifact HTML/JS) para uma aplicação real
em **PHP + MySQL**.

## Requisitos

- PHP 8.0 ou superior, com extensões `pdo_mysql` e `curl` habilitadas
- MySQL 5.7+ ou MariaDB
- Servidor web (Apache, Nginx ou o servidor embutido do PHP)

## Instalação

1. **Crie o banco de dados** importando o schema:
   ```bash
   mysql -u root -p < schema.sql
   ```

2. **Configure a conexão** em `config.php`:
   ```php
   $DB_HOST = 'localhost';
   $DB_NAME = 'sensotech';
   $DB_USER = 'root';
   $DB_PASS = '';
   ```

3. **(Opcional) Configure a chave da API Anthropic**, usada na funcionalidade
   "Analisar com IA". Defina como variável de ambiente no servidor:
   ```bash
   export ANTHROPIC_API_KEY="sua-chave-aqui"
   ```
   Sem essa chave, a aplicação funciona normalmente — apenas a análise por
   IA exibirá um aviso informando que a chave não foi configurada.

4. **Configure o envio de e-mail** (usado na recuperação de senha). Por padrão
   o código usa a função nativa `mail()` do PHP, que depende de um servidor
   de e-mail (SMTP) configurado no `php.ini`. Em ambiente de desenvolvimento
   sem SMTP, o código de recuperação fica registrado no log de erros do PHP
   (`error_log`) para que você possa testar o fluxo. Em produção, recomenda-se
   usar uma biblioteca como PHPMailer com um serviço de e-mail transacional.

5. **Suba os arquivos** para o diretório público do seu servidor (ex:
   `/var/www/html/sensotech` ou `htdocs/sensotech`). Se a aplicação não
   estiver na raiz do domínio, ajuste `BASE_URL` em `config.php` (ex:
   `/sensotech`).

6. Para testar rapidamente com o servidor embutido do PHP:
   ```bash
   php -S localhost:8000
   ```
   e acesse `http://localhost:8000`.

## Estrutura de pastas

```
sensotech-php/
├── config.php              # conexão com o banco e configurações
├── schema.sql               # schema do banco de dados
├── index.php                 # roteador inicial
├── includes/
│   ├── functions.php         # regras de negócio (tipos de teste, cálculo de resultados, etc.)
│   ├── header.php / footer.php
├── css/style.css
├── images/logo.png
├── auth/                     # login, cadastro, recuperação de senha
│   ├── login.php
│   ├── cadastro.php
│   ├── logout.php
│   ├── esqueci.php
│   └── redefinir.php
├── pesquisador/               # área do pesquisador
│   ├── dashboard.php
│   ├── criar_sala.php
│   ├── sala.php               # resultados + análise com IA
│   └── export_csv.php
└── julgador/                  # área do julgador
    ├── entrar.php
    ├── teste.php
    ├── enviar.php
    └── obrigado.php
```

## Segurança

Diferente do protótipo original (que guardava senhas em texto puro em um
armazenamento simples do artifact), esta versão:

- Usa **MySQL com PDO e prepared statements** (proteção contra SQL injection)
- Usa **`password_hash()` / `password_verify()`** para armazenar senhas
- Usa **sessões PHP nativas** para autenticação
- Escapa toda saída HTML com `htmlspecialchars()`

## Tipos de teste suportados

Escala Hedônica, Teste Triangular, Teste Duo-Trio, Comparação Pareada e
Ordenação — com as mesmas regras de validação e cálculo de resultados do
protótipo original.
