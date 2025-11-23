# Travel System - Sistema de Gerenciamento de Viagens Corporativas

Sistema completo para gerenciamento de pedidos de viagem corporativa, desenvolvido com Laravel (backend) e Vue.js 3 (frontend).

## Tecnologias Utilizadas

### Backend
- **Laravel 11** - Framework PHP
- **MySQL 8** - Banco de dados
- **Redis** - Cache e sessões
- **RabbitMQ** - Filas assíncronas
- **JWT** - Autenticação
- **PHPUnit** - Testes

### Frontend
- **Vue.js 3** - Framework JavaScript
- **Vite** - Build tool
- **Vue Router** - Roteamento
- **Pinia** - Gerenciamento de estado
- **Axios** - Requisições HTTP
- **Tailwind CSS** - Estilização

### DevOps
- **Docker** - Containerização
- **Docker Compose** - Orquestração de contêineres
- **Nginx** - Servidor web

## Estrutura do Projeto

```
Travel-System/
├── backend/          # Aplicação Laravel
├── frontend/         # Aplicação Vue.js 3
├── docker/           # Configurações Docker
│   ├── Dockerfile
│   └── nginx/
│       └── default.conf
└── docker-compose.yml
```

## Requisitos

- Docker 20.10+
- Docker Compose 2.0+

## Instalação e Execução

### 1. Clonar o repositório

```bash
git clone https://github.com/LuizGustavoVJ/Travel-System.git
cd Travel-System
```

### 2. Configurar o backend

```bash
cd backend
cp .env.example .env
```

### 3. Subir os contêineres

```bash
docker-compose up -d
```

### 4. Instalar dependências do backend

```bash
docker-compose exec app composer install
```

### 5. Gerar chave da aplicação

```bash
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan jwt:secret
```

### 6. Executar migrations e seeders

```bash
docker-compose exec app php artisan migrate --seed
```

### 7. Acessar a aplicação

- **API Backend:** http://localhost:8080
- **RabbitMQ Management:** http://localhost:15672 (guest/guest)

## Executar Testes

### Backend

```bash
docker-compose exec app php artisan test
```

## Usuários de Teste

Após executar o seeder, os seguintes usuários estarão disponíveis:

- **Admin:** admin@example.com / password
- **Usuários regulares:** Gerados automaticamente pelo seeder

## Estrutura do Backend

### Models
- `User` - Usuários do sistema (com roles: user, admin)
- `TravelRequest` - Pedidos de viagem

### Migrations
- `add_role_to_users_table` - Adiciona coluna role na tabela users
- `create_travel_requests_table` - Cria tabela de pedidos de viagem (com UUIDs e soft deletes)

### Factories
- `UserFactory` - Gera usuários de teste
- `TravelRequestFactory` - Gera pedidos de viagem de teste

## Funcionalidades Implementadas (Fase 1)

- ✅ Configuração Docker completa (app, db, redis, rabbitmq, nginx, php-worker)
- ✅ Projeto Laravel inicializado
- ✅ JWT configurado
- ✅ RabbitMQ configurado
- ✅ Migrations com UUIDs e Soft Deletes
- ✅ Models com relacionamentos
- ✅ Factories para testes
- ✅ Database Seeder

## Próximas Fases

- **Fase 2:** Autenticação e CRUD básico
- **Fase 3:** Regras de negócio e autorização
- **Fase 4:** Notificações e frontend completo

## Licença

MIT
