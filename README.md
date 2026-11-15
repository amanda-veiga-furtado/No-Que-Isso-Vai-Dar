# No Que Isso Vai Dar?

Projeto de TCC em PHP, MySQL e JavaScript para compartilhar receitas culinárias de forma simples, prática e colaborativa. A plataforma permite que usuários cadastrem receitas, pesquisem por ingredientes, gerenciem perfil e participem de uma comunidade de culinária.

## Visão geral

O sistema foi pensado para facilitar a busca por receitas com base nos ingredientes disponíveis, reduzir desperdício e incentivar a criatividade na cozinha. Além disso, oferece áreas para:

- cadastro e login de usuários;
- publicação e edição de receitas;
- busca por nome, categoria e ingredientes;
- autenticação de administradores;
- recuperação de senha por e-mail;
- gestão de usuários e conteúdo.

## Stack

- Front-end: HTML, CSS e JavaScript
- Back-end: PHP
- Banco de dados: MySQL
- Dependência externa principal: PHPMailer
- Ambiente recomendado: WAMP, XAMPP ou Apache + MySQL local

## Funcionalidades principais

### Usuário

- Cadastro com nome de usuário, e-mail e senha
- Login com validação de credenciais
- Recuperação de senha por e-mail
- Edição de perfil e foto de usuário
- Publicação de receitas com ingredientes, preparo e imagem
- Edição e exclusão das próprias receitas
- Pesquisa por receitas por nome, categoria e ingredientes
- Sugestões de ingredientes ou categorias

### Administrador

- Visualização de área administrativa
- Banimento de usuários
- Exclusão de receitas
- Gestão do conteúdo do sistema

## Requisitos do sistema

- PHP com suporte a PDO e extensão OpenSSL/mbstring normalmente exigidas no ambiente local
- Composer
- MySQL
- Apache ou servidor web local compatível
- phpMyAdmin (opcional, mas recomendado para importar o banco)

## Estrutura do projeto

```text
TCC/
├── composer.json
├── composer.lock
├── README.md
├── database/
│   └── tcc_receitas.sql
├── docs/
├── installers/
├── src/
│   ├── admin/
│   ├── css/
│   ├── functions/
│   ├── home/
│   ├── receita/
│   ├── usuario/
│   ├── conexao.php
│   ├── include_once.php
│   ├── menu.php
│   └── ...
└── vendor/
```

## Configuração local

### 1) Clone o repositório

```bash
git clone https://github.com/amanda-veiga-furtado/TCC.git
cd TCC
```

### 2) Instale as dependências

```bash
composer install
```

### 3) Crie e importe o banco de dados

1. Inicie o MySQL no WAMP/XAMPP.
2. Crie um banco chamado `tcc_receitas`.
3. Importe o arquivo:

```text
database/tcc_receitas.sql
```

Se preferir, faça isso pelo phpMyAdmin e verifique se a estrutura do banco foi criada corretamente.

### 4) Ajuste a conexão com o banco

O arquivo de conexão está em `src/conexao.php` e usa a configuração padrão local:

```php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "tcc_receitas";
$port = 3306;
```

Se o seu ambiente usar outras credenciais, ajuste esses valores antes de iniciar a aplicação.

### 5) Configure o ambiente web

- Coloque a pasta do projeto em `www` do WAMP ou `htdocs` do XAMPP.
- Acesse no navegador por um endereço semelhante a:

```text
http://localhost/TCC
```

Ou, se o projeto estiver em uma pasta específica do servidor local, use o caminho equivalente.

## Acesso inicial

A aplicação possui fluxo principal com páginas de:

- home;
- login/cadastro;
- dashboard do usuário;
- dashboard do administrador;
- cadastro de receitas;
- listagem e busca de receitas.

A entrada de login/cadastro pode ser acessada em:

```text
src/usuario/login.php
```

## Recuperação de senha por e-mail

A recuperação de senha utiliza PHPMailer com SMTP autenticado. Para funcionar corretamente, as seguintes variáveis de ambiente devem ser configuradas no ambiente em que a aplicação está sendo executada:

- `TCC_SMTP_HOST`
- `TCC_SMTP_PORT` (normalmente `587` para TLS ou `465` para SSL)
- `TCC_SMTP_ENCRYPTION` (`tls` ou `ssl`)
- `TCC_SMTP_USER`
- `TCC_SMTP_PASSWORD`
- `TCC_EMAIL_FROM` (opcional; usa `TCC_SMTP_USER` se não for informado)
- `TCC_BASE_URL` (URL base do projeto, por exemplo `http://localhost/TCC`)

Observações:

- Nunca versionar senhas SMTP no repositório.
- Para contas do Gmail, normalmente é necessário usar uma senha de app.
- O link de recuperação expira em aproximadamente uma hora e só pode ser usado uma vez.

## Arquitetura do projeto

O sistema segue uma estrutura de camadas simples, alinhada ao estudo de arquitetura web:

- camada de apresentação: HTML, CSS e JavaScript;
- camada de aplicação: páginas PHP e regras de negócio;
- camada de dados: banco MySQL e consultas preparadas.

Essa separação ajuda a manter a aplicação organizada e a reduzir a chance de falhas de segurança e manutenção.

## Considerações de segurança

- Senhas armazenadas com `password_hash()`
- Operações com banco utilizando prepared statements
- Validação de entradas em PHP e no front-end
- Controle de acesso por perfil de usuário e administrador
- Mensagens em português para uma melhor usabilidade

## Estado atual

Este projeto já contempla um conjunto funcional de recursos para o fluxo principal de um sistema de receitas, com foco em:

- autenticação;
- gestão de receitas;
- busca e filtro;
- perfil do usuário;
- administração básica.

Algumas melhorias futuras podem incluir:

- favoritos;
- denúncias;
- comentários;
- visualização por categoria;
- cálculo automático de porções;
- expansão da base de dados e filtros dinâmicos.

## Contribuição

Contribuições são bem-vindas. Para colaborar:

```bash
git checkout -b minha-feature
# faça suas alterações
git add .
git commit -m "feat: adiciona melhoria no sistema"
git push origin minha-feature
```

Em seguida, abra um pull request para revisão.

## Licença

Este repositório foi publicado como projeto acadêmico e de estudo. Para uso em produção, redistribuição ou adaptação comercial, confirme os termos com a autora ou mantenedora do projeto.

## Autoria

Projeto desenvolvido por **Amanda Veiga Furtado** como Trabalho de Conclusão de Curso (TCC) para o curso de Análise e Desenvolvimento de Sistemas da Faculdade de Tecnologia do Ipiranga.

## Repositório

- GitHub: https://github.com/amanda-veiga-furtado/TCC

