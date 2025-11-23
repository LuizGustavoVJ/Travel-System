# Travel System - Microsserviço de Gerenciamento de Pedidos de Viagem

## 1. Visão Geral

Este projeto é um microsserviço completo para gerenciamento de pedidos de viagem corporativa, desenvolvido com uma arquitetura robusta e moderna. Ele inclui um backend em **Laravel** que expõe uma API REST e um frontend em **Vue.js 3** para interação do usuário.

### Tecnologias Principais

- **Backend**: Laravel 11
- **Frontend**: Vue.js 3 (Composition API)
- **Banco de Dados**: MySQL 8
- **Cache**: Redis
- **Filas**: RabbitMQ
- **Autenticação**: JWT (JSON Web Tokens)
- **Containerização**: Docker e Docker Compose

## 2. Funcionalidades

- **Autenticação de Usuários**: Registro, login e gerenciamento de sessão com JWT.
- **Controle de Acesso**: Perfis de `usuário` e `administrador` com permissões distintas.
- **CRUD de Pedidos**: Usuários podem criar, listar, visualizar, atualizar e deletar seus próprios pedidos.
- **Ações de Admin**: Administradores podem aprovar ou cancelar pedidos.
- **Filtros Avançados**: Listagem de pedidos com filtros por status, destino e período.
- **Notificações Assíncronas**: Envio de e-mails para aprovação e cancelamento de pedidos, processados em fila com RabbitMQ.
- **Validação de Regras de Negócio**: Um pedido não pode ser cancelado se já foi aprovado.

## 3. Instalação e Execução (com Docker)

### Pré-requisitos

- Docker
- Docker Compose

### Passos para Instalação

1.  **Clone o repositório:**

    ```bash
    git clone https://github.com/LuizGustavoVJ/Travel-System.git
    cd Travel-System
    ```

2.  **Configure as Variáveis de Ambiente:**

    Copie o arquivo de exemplo do backend e configure-o:

    ```bash
    cp backend/.env.example backend/.env
    ```

    **Atenção**: Edite o arquivo `backend/.env` e configure as variáveis de banco de dados e e-mail. Para testes locais, você pode usar `MAIL_MAILER=log`.

3.  **Suba os Contêineres:**

    ```bash
    docker-compose up -d --build
    ```

4.  **Instale as Dependências do Backend:**

    ```bash
    docker-compose exec app composer install
    ```

5.  **Gere a Chave da Aplicação e do JWT:**

    ```bash
    docker-compose exec app php artisan key:generate
    docker-compose exec app php artisan jwt:secret
    ```

6.  **Execute as Migrations e Seeders:**

    ```bash
    docker-compose exec app php artisan migrate --seed
    ```

7.  **Instale as Dependências do Frontend:**

    ```bash
    docker-compose exec app npm --prefix /var/www/html/frontend install
    ```

8.  **Acesse a Aplicação:**

    - **Frontend**: [http://localhost:8080](http://localhost:8080)
    - **Backend API**: [http://localhost:8000/api](http://localhost:8000/api)

## 4. Execução dos Testes

### Testes do Backend (PHPUnit)

Para executar todos os testes do backend, use o seguinte comando:

```bash
docker-compose exec app php artisan test
```

### Testes do Frontend (Vitest)

Para executar os testes do frontend, use o seguinte comando:

```bash
docker-compose exec app npm --prefix /var/www/html/frontend test
```

## 5. Documentação da API (Postman)

Uma coleção completa do Postman está disponível na raiz do projeto:

- `Travel-System-API.postman_collection.json`

Importe este arquivo no seu Postman para ter acesso a todos os endpoints da API, com exemplos de requisições e respostas.

## 6. Informações Adicionais

### Credenciais de Teste

O `DatabaseSeeder` cria os seguintes usuários para teste:

- **Administrador**:
  - **E-mail**: `admin@example.com`
  - **Senha**: `password`
- **Usuário Comum**:
  - **E-mail**: `user@example.com`
  - **Senha**: `password`

### Processamento de Filas

O serviço `php-worker` é responsável por processar as filas (envio de e-mails). Você pode monitorar os logs do worker com:

```bash
docker-compose logs -f php-worker
```

### Arquitetura do Projeto

O projeto segue uma arquitetura robusta e escalável, separando as responsabilidades em camadas:

- **Controllers**: Recebem as requisições HTTP.
- **FormRequests**: Validam os dados de entrada.
- **Services**: Orquestram a lógica de negócio.
- **Repositories**: Abstraem o acesso ao banco de dados.
- **Resources**: Padronizam as respostas da API.
- **Events/Listeners**: Desacoplam as notificações da lógica principal.

Esta arquitetura garante um código limpo, testável e de fácil manutenção.
