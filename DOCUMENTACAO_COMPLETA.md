# 📚 Documentação Completa - Travel System

## 📋 Índice

1. [Visão Geral da Arquitetura](#visão-geral-da-arquitetura)
2. [Arquitetura de Código (Laravel)](#arquitetura-de-código-laravel)
3. [Fluxo de Requisição HTTP](#fluxo-de-requisição-http)
4. [Camadas da Aplicação](#camadas-da-aplicação)
5. [Sistema de Eventos](#sistema-de-eventos)
6. [Docker e Containers](#docker-e-containers)
7. [Entrypoint Script](#entrypoint-script)
8. [Ordem de Inicialização](#ordem-de-inicialização)
9. [Funcionalidades do Sistema](#funcionalidades-do-sistema)

---

## 🏗️ Visão Geral da Arquitetura

O **Travel System** é um microsserviço desenvolvido em **Laravel 11** que gerencia pedidos de viagem corporativa. A arquitetura segue o padrão **Repository-Service-Controller**, garantindo separação de responsabilidades e facilitando manutenção e testes.

### Padrão Arquitetural

```
Cliente (Postman/Frontend)
    ↓
Nginx (Web Server)
    ↓
PHP-FPM (app container)
    ↓
Laravel Kernel
    ↓
Middleware (Autenticação JWT)
    ↓
Controller
    ↓
Service (Lógica de Negócio)
    ↓
Repository (Acesso a Dados)
    ↓
Model (Eloquent ORM)
    ↓
MySQL Database
```

---

## 💻 Arquitetura de Código (Laravel)

### 1. **Controller** (`app/Http/Controllers/Api/`)

**Responsabilidade**: Receber requisições HTTP, validar dados, chamar Services e retornar respostas JSON.

**Arquivos**:
- `AuthController.php`: Gerencia autenticação (login, registro, logout, refresh token)
- `TravelRequestController.php`: Gerencia CRUD de pedidos de viagem

**O que faz**:
- Valida requisições usando Form Requests
- Verifica autorização usando `$this->authorize()` com Policy (padrão Laravel)
- Chama Services para executar lógica de negócio
- Retorna respostas JSON formatadas

**Autorização**:
O Controller usa `$this->authorize()` para verificar permissões via Policy. Se a autorização falhar, o Laravel automaticamente retorna:
- Status HTTP: `403 Forbidden`
- Mensagem: `"This action is unauthorized."`

**Exemplo de fluxo**:
```php
// TravelRequestController::store()
1. Recebe requisição POST /api/travel-requests
2. Valida dados com StoreTravelRequestRequest
3. Chama TravelRequestService::create()
4. Retorna JSON com o pedido criado
```

---

### 2. **Service** (`app/Services/`)

**Responsabilidade**: Contém a **lógica de negócio** da aplicação. É a camada intermediária entre Controller e Repository.

**Arquivo**: `TravelRequestService.php`

**O que faz**:
- Implementa regras de negócio (ex: status inicial sempre é 'requested')
- Preenche campos automaticamente (ex: `user_id`, `requester_name`)
- Dispara eventos quando necessário (ex: `TravelRequestCreated`)
- Chama Repository para persistir dados
- Não conhece detalhes de HTTP ou banco de dados

**Métodos principais**:
- `create()`: Cria pedido e dispara evento `TravelRequestCreated`
- `update()`: Atualiza pedido (remove campos protegidos)
- `delete()`: Deleta pedido
- `approve()`: Aprova pedido e dispara evento `TravelRequestApproved`
- `cancel()`: Cancela pedido e dispara evento `TravelRequestCancelled`
- `getAllForUser()`: Lista pedidos (com filtros e paginação)
- `getById()`: Busca pedido por ID

**Exemplo**:
```php
public function create(User $user, array $data): TravelRequest
{
    // Lógica de negócio: define valores padrão
    $data['user_id'] = $user->id;
    $data['requester_name'] = $user->name;
    $data['status'] = 'requested'; // Sempre começa como 'requested'
    
    // Chama Repository para salvar
    $travelRequest = $this->repository->create($data);
    
    // Dispara evento para notificação por email
    event(new TravelRequestCreated($travelRequest));
    
    return $travelRequest;
}
```

---

### 3. **Repository** (`app/Repositories/`)

**Responsabilidade**: **Acesso a dados**. Abstrai operações de banco de dados usando Eloquent ORM.

**Arquivo**: `TravelRequestRepository.php`

**O que faz**:
- Executa queries no banco de dados
- Aplica filtros (status, destino, datas)
- Gerencia paginação
- Não conhece regras de negócio ou HTTP

**Métodos principais**:
- `getAllForUser()`: Lista pedidos de um usuário com filtros
- `getAll()`: Lista todos os pedidos (admin) com filtros
- `findById()`: Busca pedido por ID
- `create()`: Cria novo pedido no banco
- `update()`: Atualiza pedido existente
- `delete()`: Deleta pedido (soft delete)
- `approve()`: Atualiza status para 'approved'
- `cancel()`: Atualiza status para 'cancelled'
- `applyFilters()`: Aplica filtros na query (privado)

**Exemplo**:
```php
public function getAllForUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
{
    $query = TravelRequest::where('user_id', $userId);
    $query = $this->applyFilters($query, $filters);
    
    return $query->with(['user', 'approver', 'canceller'])
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);
}
```

---

### 4. **Model** (`app/Models/`)

**Responsabilidade**: Representa uma tabela do banco de dados. Usa Eloquent ORM.

**Arquivos**:
- `User.php`: Model de usuários
- `TravelRequest.php`: Model de pedidos de viagem

**O que faz**:
- Define relacionamentos (ex: `TravelRequest` pertence a `User`)
- Define campos preenchíveis (`$fillable`)
- Define casts (ex: datas como Carbon)
- Implementa soft deletes (se necessário)

**Exemplo**:
```php
class TravelRequest extends Model
{
    protected $fillable = [
        'user_id',
        'requester_name',
        'destination',
        'start_date',
        'end_date',
        'status',
        'notes',
        'approved_by',
        'cancelled_by',
        'cancelled_reason',
    ];
    
    // Relacionamentos
    public function user() {
        return $this->belongsTo(User::class);
    }
}
```

---

### 5. **Policy** (`app/Policies/`)

**Responsabilidade**: Define **quem pode fazer o quê** (autorização). Verifica permissões antes de executar ações.

**Arquivo**: `TravelRequestPolicy.php`

**O que faz**:
- Define regras de autorização (não autenticação!)
- Verifica se usuário pode criar, atualizar, deletar, aprovar, cancelar pedidos
- Usado automaticamente pelo Laravel quando você usa `authorize()` no controller

**Métodos**:
- `viewAny()`: Todos podem listar pedidos
- `view()`: Admin ou dono do pedido pode ver
- `create()`: Todos podem criar pedidos
- `update()`: Apenas dono pode atualizar **e** pedido não pode estar 'approved' ou 'cancelled'
- `delete()`: Apenas dono pode deletar **e** pedido não pode estar 'approved' ou 'cancelled'
- `approve()`: Apenas admin pode aprovar (e pedido deve estar 'requested')
- `cancel()`: Admin pode cancelar qualquer pedido não aprovado; dono pode cancelar seu próprio pedido não aprovado

**Uso no Controller**:
Todos os métodos do Controller usam `$this->authorize()` para verificar permissões:
```php
// Exemplo no TravelRequestController
$this->authorize('update', $travelRequest);
```

**Comportamento quando autorização falha**:
Quando `$this->authorize()` retorna `false`, o Laravel automaticamente:
- Lança uma exceção `AccessDeniedHttpException`
- Retorna status HTTP: `403 Forbidden`
- Retorna mensagem padrão: `"This action is unauthorized."`

**Vantagens de usar `$this->authorize()`**:
- ✅ Código mais limpo e consistente
- ✅ Centralização da lógica de autorização no Policy
- ✅ Facilita testes (Policy pode ser testado isoladamente)
- ✅ Segue padrão recomendado do Laravel

**Exemplo**:
```php
public function approve(User $user, TravelRequest $travelRequest): bool
{
    // Apenas admin pode aprovar
    // E o pedido deve estar com status 'requested'
    return $user->isAdmin() && $travelRequest->status === 'requested';
}
```

---

### 6. **Events** (`app/Events/`)

**Responsabilidade**: Representa algo que **aconteceu** na aplicação. É um objeto de dados que carrega informações sobre o evento.

**Arquivos**:
- `UserRegistered.php`: Disparado quando um usuário se registra
- `TravelRequestCreated.php`: Disparado quando um pedido é criado
- `TravelRequestApproved.php`: Disparado quando um pedido é aprovado
- `TravelRequestCancelled.php`: Disparado quando um pedido é cancelado

**O que faz**:
- Carrega dados do evento (ex: `$user`, `$travelRequest`)
- É disparado usando `event(new UserRegistered($user))`
- Não executa ações, apenas notifica que algo aconteceu

**Exemplo**:
```php
class TravelRequestCreated
{
    public function __construct(
        public TravelRequest $travelRequest
    ) {}
}
```

---

### 7. **Listeners** (`app/Listeners/`)

**Responsabilidade**: **Reage** a eventos. Executa ações quando um evento é disparado.

**Arquivos**:
- `SendWelcomeEmailNotification.php`: Envia email de boas-vindas quando `UserRegistered` é disparado
- `SendTravelRequestCreatedNotification.php`: Envia email quando `TravelRequestCreated` é disparado
- `SendTravelRequestApprovedNotification.php`: Envia email quando `TravelRequestApproved` é disparado
- `SendTravelRequestCancelledNotification.php`: Envia email quando `TravelRequestCancelled` é disparado

**O que faz**:
- Implementa `ShouldQueue` para executar em background (RabbitMQ)
- Recebe o evento no método `handle()`
- Executa ações (ex: enviar email)

**Exemplo**:
```php
class SendWelcomeEmailNotification implements ShouldQueue
{
    public function handle(UserRegistered $event): void
    {
        Mail::to($event->user->email)
            ->send(new WelcomeMail($event->user));
    }
}
```

**Registro**: Os eventos e listeners são registrados em `app/Providers/EventServiceProvider.php`:
```php
protected $listen = [
    UserRegistered::class => [
        SendWelcomeEmailNotification::class,
    ],
    TravelRequestCreated::class => [
        SendTravelRequestCreatedNotification::class,
    ],
    // ...
];
```

---

### 8. **Mail** (`app/Mail/`)

**Responsabilidade**: Define **como** um email será enviado. Usa templates Blade.

**Arquivos**:
- `WelcomeMail.php`: Email de boas-vindas
- `TravelRequestCreatedMail.php`: Email de criação de pedido
- `TravelRequestApprovedMail.php`: Email de aprovação
- `TravelRequestCancelledMail.php`: Email de cancelamento

**O que faz**:
- Define assunto, remetente, template
- Implementa `ShouldQueue` para envio assíncrono
- Usa templates em `resources/views/emails/`

**Exemplo**:
```php
class WelcomeMail extends Mailable implements ShouldQueue
{
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.welcome',
            with: ['user' => $this->user]
        );
    }
}
```

---

### 9. **Form Requests** (`app/Http/Requests/`)

**Responsabilidade**: Valida dados de entrada antes de chegar no Controller.

**Arquivos**:
- `LoginRequest.php`: Valida login
- `RegisterRequest.php`: Valida registro
- `StoreTravelRequestRequest.php`: Valida criação de pedido
- `UpdateTravelRequestRequest.php`: Valida atualização de pedido

**O que faz**:
- Define regras de validação (ex: `required`, `email`, `date`)
- Retorna erros 422 se validação falhar
- Apenas dados válidos chegam no Controller

---

### 10. **Resources** (`app/Http/Resources/`)

**Responsabilidade**: Formata dados para resposta JSON. Define quais campos serão retornados.

**Arquivos**:
- `UserResource.php`: Formata dados do usuário
- `TravelRequestResource.php`: Formata dados do pedido

**O que faz**:
- Transforma Models em arrays JSON
- Controla quais campos são expostos
- Pode incluir relacionamentos

---

## 🔄 Fluxo de Requisição HTTP

### Exemplo: Criar um Pedido de Viagem

```
1. Cliente faz POST /api/travel-requests
   Headers: Authorization: Bearer {token}
   Body: { destination, start_date, end_date, notes }

2. Nginx recebe requisição na porta 8080
   ↓
3. Nginx encaminha para PHP-FPM (app:9000)
   ↓
4. Laravel Kernel processa requisição
   ↓
5. Middleware 'auth:api' valida token JWT
   ↓
6. RouteServiceProvider encontra rota
   Route::apiResource('travel-requests', TravelRequestController::class)
   ↓
7. TravelRequestController::store() é chamado
   ↓
8. StoreTravelRequestRequest valida dados
   Se inválido → retorna 422
   Se válido → continua
   ↓
9. Controller verifica autorização: $this->authorize('create', TravelRequest::class)
   Se não autorizado → retorna 403
   Se autorizado → continua
   ↓
10. Controller chama TravelRequestService::create()
   ↓
11. Service aplica lógica de negócio:
    - Define user_id = auth()->id()
    - Define requester_name = auth()->user()->name
    - Define status = 'requested'
    ↓
12. Service chama TravelRequestRepository::create()
    ↓
13. Repository executa TravelRequest::create($data)
    ↓
14. Eloquent salva no MySQL
    ↓
15. Repository retorna TravelRequest para Service
    ↓
16. Service dispara evento: event(new TravelRequestCreated($travelRequest))
    ↓
17. EventServiceProvider encontra listener: SendTravelRequestCreatedNotification
    ↓
18. Listener enfileira job no RabbitMQ (porque implementa ShouldQueue)
    ↓
19. Service retorna TravelRequest para Controller
    ↓
20. Controller formata com TravelRequestResource
    ↓
21. Controller retorna JSON 201 com dados do pedido
    ↓
22. Nginx retorna resposta para cliente
    ↓
23. Worker (php-worker container) processa fila RabbitMQ
    ↓
24. Worker executa SendTravelRequestCreatedNotification::handle()
    ↓
25. Listener envia email via Mailpit
```

---

## 📦 Camadas da Aplicação

### Ordem de Chamada

```
Controller
    ↓ (chama)
Service
    ↓ (chama)
Repository
    ↓ (usa)
Model (Eloquent)
    ↓ (executa)
MySQL Database
```

### Responsabilidades

| Camada | Responsabilidade | Conhece HTTP? | Conhece Banco? | Conhece Regras de Negócio? |
|--------|------------------|---------------|----------------|----------------------------|
| **Controller** | Receber HTTP, validar, autorizar, formatar resposta | ✅ Sim | ❌ Não | ❌ Não |
| **Service** | Lógica de negócio, disparar eventos | ❌ Não | ❌ Não | ✅ Sim |
| **Repository** | Acesso a dados, queries, filtros | ❌ Não | ✅ Sim | ❌ Não |
| **Model** | Estrutura de dados, relacionamentos | ❌ Não | ✅ Sim | ❌ Não |

---

## 🎯 Sistema de Eventos

### Fluxo de Eventos

```
Ação na Aplicação
    ↓
Service dispara evento: event(new UserRegistered($user))
    ↓
EventServiceProvider registra listener
    ↓
Listener implementa ShouldQueue → Job vai para RabbitMQ
    ↓
Worker processa fila
    ↓
Listener::handle() executa ação (ex: enviar email)
```

### Eventos Implementados

1. **UserRegistered**
   - Disparado em: `AuthController::register()`
   - Listener: `SendWelcomeEmailNotification`
   - Ação: Envia email de boas-vindas

2. **TravelRequestCreated**
   - Disparado em: `TravelRequestService::create()`
   - Listener: `SendTravelRequestCreatedNotification`
   - Ação: Envia email informando criação do pedido

3. **TravelRequestApproved**
   - Disparado em: `TravelRequestService::approve()`
   - Listener: `SendTravelRequestApprovedNotification`
   - Ação: Envia email informando aprovação

4. **TravelRequestCancelled**
   - Disparado em: `TravelRequestService::cancel()`
   - Listener: `SendTravelRequestCancelledNotification`
   - Ação: Envia email informando cancelamento

---

## 🐳 Docker e Containers

### Containers e Suas Funções

#### 1. **app** (PHP-FPM Application)
- **Imagem**: `php:8.2-fpm` (customizada via Dockerfile)
- **Porta**: 9000 (interno, não exposta)
- **Função**: Processa requisições PHP. É o "cérebro" da aplicação.
- **Dependências**: `db`, `redis`, `rabbitmq`
- **Entrypoint**: Executa `entrypoint.sh` que:
  - Aguarda MySQL
  - Cria schema se não existir
  - Executa migrations (cria tabelas)
  - Executa seeders (popula dados iniciais)
  - Gera chaves (APP_KEY, JWT_SECRET)
  - Limpa cache
- **Comando final**: `php-fpm` (fica escutando requisições)

#### 2. **nginx** (Web Server)
- **Imagem**: `nginx:alpine`
- **Porta**: 8080 (exposta para host)
- **Função**: Recebe requisições HTTP e encaminha para PHP-FPM
- **Dependências**: `app` (deve estar healthy)
- **Configuração**: `docker/nginx/default.conf`
- **Healthcheck**: Testa endpoint `/health`

#### 3. **db** (MySQL Database)
- **Imagem**: `mysql:8.0`
- **Porta**: 3307 (exposta para host, 3306 interno)
- **Função**: Armazena dados da aplicação
- **Schema**: `travel_system`
- **Healthcheck**: `mysqladmin ping`
- **Volumes**: `mysql_data` (persistência)

#### 4. **redis** (Cache & Session)
- **Imagem**: `redis:7-alpine`
- **Porta**: 6379 (exposta)
- **Função**: Cache e sessões (não usado atualmente, mas disponível)
- **Healthcheck**: `redis-cli ping`
- **Volumes**: `redis_data`

#### 5. **rabbitmq** (Message Broker)
- **Imagem**: `rabbitmq:3-management-alpine`
- **Portas**: 
  - 5672 (AMQP, exposta)
  - 15672 (Management UI, exposta)
- **Função**: Gerencia filas para processamento assíncrono (emails)
- **Healthcheck**: `rabbitmq-diagnostics ping`
- **Volumes**: `rabbitmq_data`
- **Acesso UI**: http://localhost:15672 (guest/guest)

#### 6. **php-worker** (Queue Worker)
- **Imagem**: Mesma do `app` (PHP-FPM)
- **Função**: Processa jobs da fila RabbitMQ (envio de emails)
- **Comando**: `php artisan queue:work rabbitmq`
- **Dependências**: `app`, `rabbitmq`
- **SKIP_MIGRATIONS**: `true` (não executa migrations, apenas aguarda tabelas)
- **Aguarda**: 15 segundos antes de iniciar (para app terminar setup)

#### 7. **scheduler** (Laravel Scheduler)
- **Imagem**: Mesma do `app`
- **Função**: Executa tarefas agendadas (cron jobs do Laravel)
- **Comando**: `php artisan schedule:work`
- **Dependências**: `app`, `rabbitmq`
- **SKIP_MIGRATIONS**: `true`

#### 8. **mailpit** (Email Testing)
- **Imagem**: `axllent/mailpit`
- **Portas**:
  - 1025 (SMTP, exposta)
  - 8025 (Web UI, exposta)
- **Função**: Captura todos os emails enviados (desenvolvimento)
- **Acesso UI**: http://localhost:8025

#### 9. **rediscommander** (Redis UI)
- **Imagem**: `rediscommander/redis-commander:latest`
- **Porta**: 8081 (exposta)
- **Função**: Interface web para gerenciar Redis
- **Acesso**: http://localhost:8081

#### 10. **phpunit** (Test Container)
- **Imagem**: Customizada via `Dockerfile.test`
- **Função**: Executa testes PHPUnit
- **Comando**: `vendor/bin/phpunit`
- **Ambiente**: `testing` (usa `travel_system_test` database)
- **Queue**: `sync` (processa emails imediatamente nos testes)

---

## 🚀 Entrypoint Script

### Arquivo: `backend/docker/entrypoint.sh`

### Quando é Executado?

O entrypoint é executado **automaticamente** quando um container PHP-FPM (`app`, `php-worker`, `scheduler`) é iniciado.

### O que Faz?

#### 1. **Configuração Inicial**
- Cria/atualiza arquivo `.env`
- Define variáveis de ambiente (DB_HOST, DB_PASSWORD, etc.)

#### 2. **Instalação de Dependências**
- Verifica se `vendor/` existe
- Se não existe, executa `composer install`

#### 3. **Conexão com MySQL**
- Função `wait_for_mysql()`:
  - Tenta conectar via `host.docker.internal` (MySQL local)
  - Se falhar, tenta `db` (MySQL do Docker)
  - Aguarda até 15 tentativas (30 segundos)

#### 4. **Criação do Schema**
- Função `create_database()`:
  - Verifica se schema `travel_system` existe
  - Se não existe, cria com charset `utf8mb4`

#### 5. **Geração de Chaves**
- `APP_KEY`: Chave de criptografia do Laravel
- `JWT_SECRET`: Chave para assinar tokens JWT

#### 6. **Migrations e Seeders** (apenas no container `app`)
- Se `SKIP_MIGRATIONS=true` (worker/scheduler):
  - Pula migrations/seeders
  - Aguarda tabelas serem criadas (até 60 tentativas)
- Se `SKIP_MIGRATIONS=false` (app):
  - Executa `php artisan migrate:fresh` (apaga e recria tabelas)
  - Executa `php artisan db:seed` (popula dados iniciais)
  - Limpa cache

#### 7. **Execução do Comando Final**
- Executa o comando passado como argumento
- Para `app`: `php-fpm`
- Para `php-worker`: `php artisan queue:work`
- Para `scheduler`: `php artisan schedule:work`

### Fluxo Completo do Entrypoint

```
Container inicia
    ↓
entrypoint.sh executa
    ↓
1. Cria/atualiza .env
    ↓
2. Instala dependências (se necessário)
    ↓
3. Aguarda MySQL estar disponível
    ↓
4. Cria schema se não existir
    ↓
5. Gera APP_KEY e JWT_SECRET (se necessário)
    ↓
6. Verifica SKIP_MIGRATIONS
    ↓
   Se false (app):
      - Executa migrations
      - Executa seeders
      - Limpa cache
   Se true (worker/scheduler):
      - Aguarda tabelas serem criadas
    ↓
7. Executa comando final (php-fpm, queue:work, etc.)
```

---

## ⚡ Ordem de Inicialização

### Quando você executa `docker-compose up -d --build`

#### 1. **Containers de Infraestrutura** (sem dependências)
- `db` (MySQL) - inicia primeiro
- `redis` - inicia em paralelo
- `rabbitmq` - inicia em paralelo
- `mailpit` - inicia em paralelo

#### 2. **Aguardam Healthchecks**
- `db`: Aguarda `mysqladmin ping` (até 30s)
- `redis`: Aguarda `redis-cli ping`
- `rabbitmq`: Aguarda `rabbitmq-diagnostics ping` (até 60s)

#### 3. **Container App** (depende de db, redis, rabbitmq)
- Aguarda todos estarem healthy
- Inicia `entrypoint.sh`
- Executa migrations e seeders
- Inicia PHP-FPM
- Healthcheck verifica se está pronto (até 90s)

#### 4. **Container Nginx** (depende de app)
- Aguarda `app` estar healthy
- Inicia e escuta na porta 8080
- Healthcheck testa `/health`

#### 5. **Container Worker** (depende de app, rabbitmq)
- Aguarda `app` estar healthy
- Aguarda 15 segundos (para app terminar setup)
- Inicia `entrypoint.sh` com `SKIP_MIGRATIONS=true`
- Aguarda tabelas serem criadas
- Inicia `php artisan queue:work`

#### 6. **Container Scheduler** (depende de app, rabbitmq)
- Aguarda `app` estar healthy
- Inicia `entrypoint.sh` com `SKIP_MIGRATIONS=true`
- Aguarda tabelas serem criadas
- Inicia `php artisan schedule:work`

#### 7. **Containers Auxiliares**
- `rediscommander`: Inicia após `redis` estar healthy

### Diagrama de Dependências

```
db (MySQL)
  ↑
  │ depende
  │
app (PHP-FPM)
  ↑
  │ depende
  │
nginx (Web Server)
  │
  └─→ Cliente acessa http://localhost:8080

rabbitmq
  ↑
  │ depende
  │
php-worker (Queue Worker)
scheduler (Laravel Scheduler)

redis
  ↑
  │ depende
  │
rediscommander (Redis UI)
```

---

## 🎯 Funcionalidades do Sistema

### 1. **Autenticação (AuthController)**

#### Registro de Usuário
- **Endpoint**: `POST /api/auth/register`
- **Fluxo**:
  1. Valida dados (`RegisterRequest`)
  2. Cria usuário com role `user`
  3. Dispara evento `UserRegistered`
  4. Gera token JWT
  5. Retorna usuário + token
- **Evento**: `UserRegistered` → Envia email de boas-vindas

#### Login
- **Endpoint**: `POST /api/auth/login`
- **Fluxo**:
  1. Valida credenciais
  2. Gera token JWT
  3. Retorna token + dados do usuário

#### Logout
- **Endpoint**: `POST /api/auth/logout`
- **Fluxo**: Invalida token JWT

#### Refresh Token
- **Endpoint**: `POST /api/auth/refresh`
- **Fluxo**: Gera novo token JWT

#### Me (Dados do Usuário Logado)
- **Endpoint**: `GET /api/auth/me`
- **Fluxo**: Retorna dados do usuário autenticado

---

### 2. **Pedidos de Viagem (TravelRequestController)**

#### Listar Pedidos
- **Endpoint**: `GET /api/travel-requests`
- **Query Params**: `status`, `destination`, `start_date_from`, `start_date_to`, `per_page`
- **Fluxo**:
  1. Controller recebe requisição
  2. Service::getAllForUser() verifica se é admin
  3. Se admin → Repository::getAll() (todos os pedidos)
  4. Se user → Repository::getAllForUser() (apenas seus pedidos)
  5. Repository aplica filtros
  6. Retorna paginação

#### Criar Pedido
- **Endpoint**: `POST /api/travel-requests`
- **Fluxo**:
  1. Valida dados (`StoreTravelRequestRequest`)
  2. Service::create() define:
     - `user_id` = usuário logado
     - `requester_name` = nome do usuário
     - `status` = 'requested'
  3. Repository::create() salva no banco
  4. Service dispara `TravelRequestCreated`
  5. Listener envia email (via RabbitMQ)
  6. Retorna pedido criado

#### Visualizar Pedido
- **Endpoint**: `GET /api/travel-requests/{id}`
- **Fluxo**:
  1. Service::getById() busca pedido
  2. Policy verifica se pode ver (admin ou dono)
  3. Retorna pedido

#### Atualizar Pedido
- **Endpoint**: `PUT /api/travel-requests/{id}`
- **Fluxo**:
  1. Valida dados (`UpdateTravelRequestRequest`)
  2. Policy verifica se é dono do pedido **e** se status não é 'approved' ou 'cancelled' (via `$this->authorize('update', $travelRequest)`)
  3. Se autorizado, Service::update() remove campos protegidos
  4. Repository::update() atualiza no banco
  5. Retorna pedido atualizado

#### Deletar Pedido
- **Endpoint**: `DELETE /api/travel-requests/{id}`
- **Fluxo**:
  1. Policy verifica se é dono do pedido **e** se status não é 'approved' ou 'cancelled' (via `$this->authorize('delete', $travelRequest)`)
  2. Se autorizado, Service::delete() → Repository::delete() (soft delete)
  3. Retorna sucesso

#### Aprovar Pedido
- **Endpoint**: `POST /api/travel-requests/{id}/approve`
- **Permissão**: Apenas admin
- **Fluxo**:
  1. Policy verifica se é admin e status é 'requested' (via `$this->authorize('approve', $travelRequest)`)
  2. Se autorizado, Service::approve() atualiza:
     - `status` = 'approved'
     - `approved_by` = admin ID
  3. Dispara evento `TravelRequestApproved`
  4. Listener envia email (via RabbitMQ)
  5. Retorna pedido aprovado

#### Cancelar Pedido
- **Endpoint**: `POST /api/travel-requests/{id}/cancel`
- **Body**: `{ "reason": "Motivo do cancelamento" }`
- **Permissão**: Admin pode cancelar qualquer pedido não aprovado; dono pode cancelar seu próprio pedido não aprovado
- **Fluxo**:
  1. Policy verifica permissão e status (via `$this->authorize('cancel', $travelRequest)`)
  2. Se autorizado, Service::cancel() atualiza:
     - `status` = 'cancelled'
     - `cancelled_by` = usuário ID
     - `cancelled_reason` = motivo
  3. Dispara evento `TravelRequestCancelled`
  4. Listener envia email (via RabbitMQ)
  5. Retorna pedido cancelado

---

### 3. **Sistema de Emails**

#### Emails Enviados

1. **Email de Boas-Vindas**
   - Evento: `UserRegistered`
   - Quando: Usuário se registra
   - Template: `emails/welcome.blade.php`
   - Enviado via: RabbitMQ (assíncrono)

2. **Email de Criação de Pedido**
   - Evento: `TravelRequestCreated`
   - Quando: Pedido é criado
   - Template: `emails/travel-request-created.blade.php`
   - Enviado via: RabbitMQ (assíncrono)

3. **Email de Aprovação**
   - Evento: `TravelRequestApproved`
   - Quando: Admin aprova pedido
   - Template: `emails/travel-request-approved.blade.php`
   - Enviado via: RabbitMQ (assíncrono)

4. **Email de Cancelamento**
   - Evento: `TravelRequestCancelled`
   - Quando: Pedido é cancelado
   - Template: `emails/travel-request-cancelled.blade.php`
   - Enviado via: RabbitMQ (assíncrono)

#### Fluxo de Envio de Email

```
Ação na aplicação
    ↓
Service dispara evento
    ↓
EventServiceProvider encontra listener
    ↓
Listener implementa ShouldQueue
    ↓
Job é enfileirado no RabbitMQ
    ↓
php-worker processa fila
    ↓
Listener::handle() executa
    ↓
Mail::send() envia email
    ↓
Mailpit captura email (desenvolvimento)
    ↓
Email visível em http://localhost:8025
```

---

## 📊 Resumo da Arquitetura

### Padrão Repository-Service-Controller

```
┌─────────────────────────────────────────┐
│         HTTP Request (Nginx)            │
└─────────────────┬───────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────┐
│         Controller                        │
│  - Recebe requisição                      │
│  - Valida (Form Request)                  │
│  - Autoriza (Policy)                      │
│  - Chama Service                          │
│  - Retorna JSON (Resource)                 │
└─────────────────┬───────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────┐
│         Service                          │
│  - Lógica de negócio                     │
│  - Dispara eventos                       │
│  - Chama Repository                      │
└─────────────────┬───────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────┐
│         Repository                       │
│  - Acesso a dados                       │
│  - Queries e filtros                    │
│  - Usa Model (Eloquent)                  │
└─────────────────┬───────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────┐
│         Model (Eloquent)                 │
│  - Representa tabela                     │
│  - Relacionamentos                       │
└─────────────────┬───────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────┐
│         MySQL Database                   │
└─────────────────────────────────────────┘
```

### Sistema de Eventos

```
Service
    │
    │ event(new TravelRequestCreated($request))
    ▼
EventServiceProvider
    │
    │ encontra listener
    ▼
SendTravelRequestCreatedNotification (ShouldQueue)
    │
    │ enfileira no RabbitMQ
    ▼
php-worker
    │
    │ processa fila
    ▼
Listener::handle()
    │
    │ Mail::send()
    ▼
Mailpit (desenvolvimento)
```

---

## 🔑 Pontos Importantes para Apresentação

### 1. **Separação de Responsabilidades**
- **Controller**: HTTP, validação, autorização
- **Service**: Lógica de negócio
- **Repository**: Acesso a dados
- **Model**: Estrutura de dados

### 2. **Desacoplamento**
- Service não conhece HTTP
- Repository não conhece regras de negócio
- Eventos permitem extensão sem modificar código existente

### 3. **Processamento Assíncrono**
- Emails são enviados via fila (RabbitMQ)
- Não bloqueia resposta HTTP
- Worker processa em background

### 4. **Testabilidade**
- Cada camada pode ser testada isoladamente
- Services podem ser testados sem HTTP ou banco
- Repositories podem ser testados sem regras de negócio

### 5. **Escalabilidade**
- Worker pode ser escalado horizontalmente
- Nginx pode fazer load balancing
- RabbitMQ garante processamento de filas

---

## 📝 Conclusão

Este documento cobre toda a arquitetura do **Travel System**, desde o fluxo de requisições HTTP até o processamento assíncrono de emails. A arquitetura segue boas práticas de desenvolvimento, garantindo:

- ✅ Separação de responsabilidades
- ✅ Código testável e manutenível
- ✅ Processamento assíncrono
- ✅ Escalabilidade
- ✅ Desacoplamento entre camadas

Para mais detalhes sobre uso prático, consulte o `README.md`.

