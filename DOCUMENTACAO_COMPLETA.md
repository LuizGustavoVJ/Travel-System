# 📚 Documentação Completa - Travel System

## 📋 Índice

1. [Visão Geral da Arquitetura](#visão-geral-da-arquitetura)
2. [Arquitetura de Código (Laravel)](#arquitetura-de-código-laravel)
3. [Sistema de UUIDs](#-sistema-de-uuids-identificadores-únicos-universais)
4. [Fluxo de Requisição HTTP](#fluxo-de-requisição-http)
5. [Camadas da Aplicação](#camadas-da-aplicação)
6. [Sistema de Eventos](#sistema-de-eventos)
7. [Tratamento de Exceções](#tratamento-de-exceções)
8. [Form Requests (Validação)](#form-requests-validação)
9. [Sistema de Testes](#sistema-de-testes)
10. [Scripts Auxiliares](#scripts-auxiliares)
11. [Autenticação JWT](#-autenticação-jwt-json-web-token)
12. [Middleware](#️-middleware)
13. [Service Providers](#-service-providers)
14. [Soft Deletes](#️-soft-deletes)
15. [Mailpit (Email Testing)](#-mailpit-email-testing)
16. [Redis Commander (Redis UI)](#-redis-commander-redis-ui)
17. [Docker e Containers](#docker-e-containers)
18. [Entrypoint Script](#entrypoint-script)
19. [Ordem de Inicialização](#ordem-de-inicialização)
20. [Funcionalidades do Sistema](#funcionalidades-do-sistema)

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
O Controller usa `$this->authorize()` para verificar permissões via Policy. Se a autorização falhar:
- O Laravel lança `AuthorizationException` ou `AccessDeniedHttpException`
- O Handler Global captura e retorna:
  - Status HTTP: `403 Forbidden`
  - Mensagem: `"This action is unauthorized."`

**Tratamento de Exceções**:
Todos os métodos têm try-catch que:
- Re-lança `AuthorizationException` e `AccessDeniedHttpException` (tratadas pelo Handler)
- Re-lança `ValidationException` (tratada pelo Handler)
- Captura outras exceções e retorna 500 com log detalhado

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
- `User.php`: Model de usuários (usa ID auto-incremento)
- `TravelRequest.php`: Model de pedidos de viagem (usa UUID como chave primária)

**O que faz**:
- Define relacionamentos (ex: `TravelRequest` pertence a `User`)
- Define campos preenchíveis (`$fillable`)
- Define casts (ex: datas como Carbon)
- Implementa soft deletes (se necessário)
- **TravelRequest usa UUID** como chave primária (via trait `HasUuids`)

**Exemplo**:
```php
class TravelRequest extends Model
{
    use HasFactory, HasUuids, SoftDeletes; // HasUuids gera UUID automaticamente
    
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

## 🔑 Sistema de UUIDs (Identificadores Únicos Universais)

### O que é UUID?

**UUID** (Universally Unique Identifier) é um identificador único de 128 bits, representado como uma string de 36 caracteres no formato:
```
xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
```

**Exemplo**: `a06dd1bf-63ee-412b-801c-8cdd09ba826c`

### Por que usar UUID no TravelRequest?

#### ✅ Vantagens

1. **Segurança**: Não expõe informações sobre quantidade de registros
   - IDs sequenciais (1, 2, 3...) revelam quantos pedidos existem
   - UUIDs são imprevisíveis e não revelam informações

2. **Distribuição**: Facilita integração entre sistemas
   - Pode gerar IDs sem consultar o banco
   - Útil em sistemas distribuídos ou microserviços

3. **Privacidade**: Dificulta enumeração de recursos
   - Não é possível "adivinhar" IDs de outros pedidos
   - Protege contra ataques de enumeração

4. **Fusão de Bancos**: Facilita merge de dados de diferentes fontes
   - Evita conflitos de IDs ao combinar bancos

#### ⚠️ Desvantagens

1. **Performance**: UUIDs são maiores que inteiros (36 chars vs 4-8 bytes)
2. **Índices**: Podem ser mais lentos para ordenação
3. **Legibilidade**: Menos legível que IDs numéricos

### Implementação no Travel System

#### 1. **Migration** (`database/migrations/2025_11_23_125452_create_travel_requests_table.php`)

```php
Schema::create('travel_requests', function (Blueprint $table) {
    $table->uuid('id')->primary(); // Chave primária do tipo UUID
    // ... outros campos
});
```

**O que faz**:
- Cria coluna `id` do tipo `UUID` no MySQL
- Define como chave primária
- MySQL armazena como `CHAR(36)` ou `BINARY(16)` (dependendo da versão)

#### 2. **Model** (`app/Models/TravelRequest.php`)

```php
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TravelRequest extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    // ...
}
```

**O que o trait `HasUuids` faz**:
- Gera UUID automaticamente antes de salvar no banco
- Usa UUID v4 (aleatório) por padrão
- Não precisa definir `$incrementing = false` (Laravel detecta automaticamente)
- O UUID é gerado no método `boot()` do Model

#### 3. **Geração Automática**

**Quando o UUID é gerado**:
- Automaticamente quando você cria um novo `TravelRequest`
- Antes de salvar no banco de dados
- Não precisa especificar o ID manualmente

**Exemplo**:
```php
// UUID é gerado automaticamente
$travelRequest = TravelRequest::create([
    'user_id' => $user->id,
    'destination' => 'São Paulo',
    // 'id' não precisa ser fornecido!
]);

// UUID gerado: "a06dd1bf-63ee-412b-801c-8cdd09ba826c"
echo $travelRequest->id; // UUID gerado automaticamente
```

#### 4. **Uso nas Rotas e Controllers**

**Rota**:
```php
Route::apiResource('travel-requests', TravelRequestController::class);
// Gera rotas como: GET /api/travel-requests/{travel_request}
// O {travel_request} aceita UUID
```

**Controller**:
```php
public function show(string $id): JsonResponse
{
    // $id é uma string UUID (ex: "a06dd1bf-63ee-412b-801c-8cdd09ba826c")
    $travelRequest = $this->service->getById($id);
    // ...
}
```

**Repository**:
```php
public function findById(string $id): ?TravelRequest
{
    // Eloquent automaticamente busca por UUID
    return TravelRequest::find($id);
}
```

### Formato do UUID

**Estrutura**:
```
xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
```

**Onde**:
- `x` = dígito hexadecimal (0-9, a-f)
- `4` = versão do UUID (4 = aleatório)
- `y` = variante (8, 9, a, ou b)

**Exemplo real do sistema**:
```
a06dd1bf-63ee-412b-801c-8cdd09ba826c
```

### Fluxo Completo de Geração

```
1. Controller recebe requisição POST /api/travel-requests
   ↓
2. Service::create() é chamado
   ↓
3. TravelRequest::create($data) é executado
   ↓
4. Laravel detecta trait HasUuids no Model
   ↓
5. Método boot() do HasUuids é executado
   ↓
6. UUID v4 é gerado automaticamente (ex: "a06dd1bf-63ee-412b-801c-8cdd09ba826c")
   ↓
7. UUID é atribuído ao atributo 'id' do Model
   ↓
8. Model é salvo no banco com UUID como chave primária
   ↓
9. UUID é retornado na resposta JSON
```

### Exemplo Prático

**Criar um pedido**:
```php
// No Service
$travelRequest = TravelRequest::create([
    'user_id' => $user->id,
    'destination' => 'São Paulo',
    'start_date' => '2025-12-01',
    'end_date' => '2025-12-10',
]);

// UUID gerado automaticamente
echo $travelRequest->id; 
// Output: "a06dd1bf-63ee-412b-801c-8cdd09ba826c"
```

**Buscar por UUID**:
```php
// No Repository
$id = "a06dd1bf-63ee-412b-801c-8cdd09ba826c";
$travelRequest = TravelRequest::find($id);
```

**Resposta JSON**:
```json
{
    "message": "Travel request created successfully",
    "data": {
        "id": "a06dd1bf-63ee-412b-801c-8cdd09ba826c",
        "destination": "São Paulo",
        "status": "requested",
        ...
    }
}
```

### Comparação: ID Auto-incremento vs UUID

| Aspecto | ID Auto-incremento | UUID |
|---------|-------------------|------|
| **Formato** | `1, 2, 3, 4...` | `a06dd1bf-63ee-412b-801c-8cdd09ba826c` |
| **Tamanho** | 4-8 bytes | 16 bytes (36 chars como string) |
| **Geração** | Banco de dados | Aplicação (antes de salvar) |
| **Sequencial** | ✅ Sim | ❌ Não |
| **Previsível** | ✅ Sim | ❌ Não |
| **Segurança** | ⚠️ Expõe quantidade | ✅ Não expõe |
| **Performance** | ✅ Mais rápido | ⚠️ Ligeiramente mais lento |
| **Distribuição** | ❌ Requer banco | ✅ Pode gerar offline |

### Banco de Dados

**MySQL**:
- Tipo de coluna: `CHAR(36)` ou `BINARY(16)`
- Indexação: Funciona normalmente com índices
- Performance: Ligeiramente mais lento que `INT`, mas aceitável

**Estrutura no banco**:
```sql
CREATE TABLE travel_requests (
    id CHAR(36) PRIMARY KEY,  -- UUID como string
    user_id BIGINT UNSIGNED,
    -- ... outros campos
);
```

### Validação de UUID

**No Laravel**:
- Eloquent valida automaticamente o formato UUID ao buscar
- Se UUID inválido for fornecido, retorna `null` (não encontrado)

**Exemplo**:
```php
// UUID válido
$request = TravelRequest::find('a06dd1bf-63ee-412b-801c-8cdd09ba826c');
// ✅ Funciona

// UUID inválido
$request = TravelRequest::find('invalid-uuid');
// ❌ Retorna null (não encontrado)
```

### Relacionamentos com UUID

**TravelRequest → User**:
```php
// TravelRequest tem user_id (INT) que referencia User
// User tem id (INT auto-incremento)
// Relacionamento funciona normalmente
$travelRequest->user; // Retorna User relacionado
```

**Observação**: Apenas `TravelRequest` usa UUID. `User` continua usando ID auto-incremento (`BIGINT`), o que é comum em sistemas híbridos.

### Resumo

**O que acontece automaticamente**:
1. ✅ UUID é gerado quando você cria um `TravelRequest`
2. ✅ Não precisa especificar o ID manualmente
3. ✅ UUID é usado automaticamente em rotas e queries
4. ✅ Formato UUID v4 (aleatório) é usado por padrão

**Onde o UUID aparece**:
- ✅ Na coluna `id` da tabela `travel_requests`
- ✅ Nas rotas da API: `/api/travel-requests/{uuid}`
- ✅ Nas respostas JSON
- ✅ Nos relacionamentos Eloquent

**Arquivos relacionados**:
- `backend/app/Models/TravelRequest.php` - Model com trait `HasUuids`
- `backend/database/migrations/2025_11_23_125452_create_travel_requests_table.php` - Migration com `uuid('id')`
- `backend/app/Repositories/TravelRequestRepository.php` - Busca por UUID
- `backend/app/Http/Controllers/Api/TravelRequestController.php` - Recebe UUID nas rotas

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
- Mensagens customizadas em português
- Validação condicional para campos opcionais (update)

**Validação Condicional**:
O `UpdateTravelRequestRequest` implementa validação condicional:
- Se ambos `start_date` e `end_date` forem fornecidos → valida `end_date > start_date`
- Se apenas `end_date` for fornecido → valida que é `>= today`
- Validação adicional no Service garante integridade

---

### 10. **Resources** (`app/Http/Resources/`)

**Responsabilidade**: Formata dados de Models para JSON de resposta da API.

**Arquivos**:
- `UserResource.php`: Formata dados do usuário
- `TravelRequestResource.php`: Formata dados do pedido

**O que faz**:
- Transforma Models em arrays JSON
- Controla quais campos são expostos na API
- Formata datas e relacionamentos
- Garante consistência nas respostas JSON

**Exemplo - TravelRequestResource**:
```php
class TravelRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, // UUID
            'destination' => $this->destination,
            'status' => $this->status,
            'start_date' => $this->start_date?->toDateString(), // Formata data
            'user' => new UserResource($this->whenLoaded('user')), // Relacionamento lazy
            'approver' => new UserResource($this->whenLoaded('approver')),
        ];
    }
}
```

**Uso no Controller**:
```php
// Retorna um único recurso
return new TravelRequestResource($travelRequest);

// Retorna coleção de recursos
return TravelRequestResource::collection($travelRequests);
```

**Vantagens**:
- ✅ Controle total sobre campos expostos
- ✅ Formatação consistente de dados
- ✅ Relacionamentos carregados sob demanda (`whenLoaded`)
- ✅ Facilita versionamento da API

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

## 🛡️ Tratamento de Exceções

### Visão Geral

O sistema implementa tratamento robusto de exceções em dois níveis:
1. **Handler Global** (`app/Exceptions/Handler.php`) - Trata exceções automaticamente para requisições de API
2. **Try-Catch nos Controllers** - Tratamento específico em cada método

### Handler Global de Exceções

O Handler intercepta todas as exceções em requisições de API (`/api/*`) e retorna respostas JSON padronizadas.

#### Exceções Tratadas

1. **JWT Exceptions**
   - `TokenExpiredException` → 401 "Token has expired"
   - `TokenInvalidException` → 401 "Token is invalid"
   - `JWTException` → 401 "Token error"

2. **Authentication Exception**
   - `AuthenticationException` → 401 "Unauthenticated"

3. **Authorization Exception**
   - `AuthorizationException` → 403 "This action is unauthorized."
   - `AccessDeniedHttpException` → 403 "This action is unauthorized"

4. **Validation Exception**
   - `ValidationException` → 422 "Validation failed" + erros detalhados

5. **Model Not Found Exception**
   - `ModelNotFoundException` → 404 "{Model} not found"

6. **HTTP Exceptions**
   - `NotFoundHttpException` → 404 "Resource not found"
   - `MethodNotAllowedHttpException` → 405 "Method not allowed for this route"

7. **Database Exceptions**
   - `QueryException` → 500 "Database error occurred" (ou detalhes se debug)
   - `PDOException` → 500 "Database connection error" (ou detalhes se debug)

8. **Exceções Genéricas**
   - Qualquer outra exceção → 500 com mensagem apropriada
   - Em modo debug: inclui file, line e trace
   - Em produção: mensagem genérica

### Tratamento nos Controllers

Todos os métodos dos Controllers têm tratamento de exceções com:
- ✅ Try-catch para capturar erros
- ✅ Log de erros com contexto (user_id, travel_request_id, dados)
- ✅ Respostas JSON padronizadas
- ✅ Re-throw de `ValidationException` e `AuthorizationException` (tratadas pelo Handler)

**Exemplo**:
```php
public function show(string $id): JsonResponse
{
    try {
        $travelRequest = $this->service->getById($id);
        
        if (!$travelRequest) {
            return response()->json([
                'message' => 'Travel request not found',
                'status' => 'error',
            ], 404);
        }
        
        $this->authorize('view', $travelRequest);
        
        return response()->json([
            'data' => new TravelRequestResource($travelRequest),
        ]);
    } catch (AuthorizationException|AccessDeniedHttpException $e) {
        throw $e; // Deixa o Handler tratar exceções de autorização
    } catch (ModelNotFoundException $e) {
        return response()->json([
            'message' => 'Travel request not found',
            'status' => 'error',
        ], 404);
    } catch (\Exception $e) {
        Log::error('Error showing travel request', [
            'travel_request_id' => $id,
            'user_id' => auth()->id(),
            'error' => $e->getMessage(),
        ]);
        
        return response()->json([
            'message' => 'An error occurred while retrieving the travel request',
            'status' => 'error',
        ], 500);
    }
}
```

### Formato de Respostas de Erro

**Erro Genérico**:
```json
{
    "message": "Error message",
    "status": "error"
}
```

**Erro de Validação**:
```json
{
    "message": "Validation failed",
    "status": "error",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password must be at least 8 characters."]
    }
}
```

**Erro com Debug (APP_DEBUG=true)**:
```json
{
    "message": "Detailed error message",
    "status": "error",
    "file": "/path/to/file.php",
    "line": 123,
    "trace": "Stack trace..."
}
```

### Logging de Exceções

Todas as exceções são logadas com contexto:
- User ID (quando disponível)
- IDs de recursos relacionados
- Dados da requisição (quando relevante)
- Mensagem de erro
- Stack trace (apenas em casos críticos)

---

## 📝 Form Requests (Validação)

### Visão Geral

As classes de Request (Form Requests) validam dados de entrada antes de chegar no Controller. Todas as classes estão sendo usadas corretamente e foram melhoradas com mensagens customizadas em português.

### Classes de Request

#### 1. **RegisterRequest**
- **Uso**: `AuthController::register()`
- **Validações**:
  - `name`: required, string, max:255
  - `email`: required, email, unique:users,email, max:255
  - `password`: required, string, min:8, confirmed
- **Mensagens**: Customizadas em português

#### 2. **LoginRequest**
- **Uso**: `AuthController::login()`
- **Validações**:
  - `email`: required, email, max:255
  - `password`: required, string
- **Mensagens**: Customizadas em português

#### 3. **StoreTravelRequestRequest**
- **Uso**: `TravelRequestController::store()`
- **Validações**:
  - `destination`: required, string, max:255
  - `start_date`: required, date, after_or_equal:today
  - `end_date`: required, date, after:start_date
  - `notes`: nullable, string
- **Mensagens**: Customizadas em português

#### 4. **UpdateTravelRequestRequest**
- **Uso**: `TravelRequestController::update()`
- **Validações**:
  - `destination`: sometimes, string, max:255
  - `start_date`: sometimes, date, after_or_equal:today
  - `end_date`: sometimes, date (validação condicional)
  - `notes`: nullable, string
- **Validação Condicional**:
  - Se ambos `start_date` e `end_date` forem fornecidos → valida `end_date > start_date`
  - Se apenas `end_date` for fornecido → valida que é `>= today`
  - Validação adicional no Service garante `end_date > start_date` (do banco ou fornecido)
- **Mensagens**: Customizadas em português

### Boas Práticas

1. **Uso de `$request->validated()`**: ✅
   - Todos os controllers usam `$request->validated()` em vez de `$request->all()`
   - Garante que apenas dados validados sejam processados

2. **Método `authorize()`**: ✅
   - Todos retornam `true` (correto, pois autorização é feita via middleware ou Policy)
   - Endpoints públicos: `RegisterRequest`, `LoginRequest`
   - Endpoints protegidos: `StoreTravelRequestRequest`, `UpdateTravelRequestRequest` (têm middleware `auth:api`)

3. **Validações Apropriadas**: ✅
   - Regras de validação corretas para cada campo
   - Uso de `sometimes` para campos opcionais em update
   - Uso de `nullable` para campos que podem ser null

4. **Mensagens Customizadas**: ✅
   - Todas as classes têm mensagens em português
   - Melhora a experiência do usuário

5. **Atributos Customizados**: ✅
   - Nomes de campos em português nas mensagens de erro
   - Melhora a legibilidade

---

## 🧪 Sistema de Testes

### Visão Geral

O sistema possui cobertura completa de testes, incluindo testes de Feature e Unit para todas as funcionalidades principais.

### Estrutura de Testes

#### Testes de Feature (`tests/Feature/`)
- `AuthTest.php`: Testes de autenticação
- `AuthValidationTest.php`: Testes de validação de registro e login
- `TravelRequestTest.php`: Testes básicos de CRUD
- `TravelRequestValidationsTest.php`: Testes de validação e regras de negócio
- `TravelRequestBusinessRulesTest.php`: Testes de regras de negócio (aprovação, cancelamento)
- `TravelRequestFiltersTest.php`: Testes de filtros e paginação
- `TravelRequestNotFoundTest.php`: Testes de recursos não encontrados
- `TravelRequestCreatedEmailTest.php`: Testes de email de criação
- `TravelRequestApprovedEmailTest.php`: Testes de email de aprovação
- `TravelRequestCancelledEmailTest.php`: Testes de email de cancelamento
- `UserRegistrationEmailTest.php`: Testes de email de boas-vindas
- `AuthRefreshTokenTest.php`: Testes de refresh token

#### Testes Unitários (`tests/Unit/`)
- `TravelRequestServiceTest.php`: Testes do Service (lógica de negócio)
- `TravelRequestRepositoryTest.php`: Testes do Repository (acesso a dados)
- `TravelRequestPolicyTest.php`: Testes da Policy (autorização)
- `SendWelcomeEmailNotificationTest.php`: Testes do Listener de boas-vindas
- `SendTravelRequestCreatedNotificationTest.php`: Testes do Listener de criação
- `WelcomeMailTest.php`: Testes do Mailable de boas-vindas
- `TravelRequestCreatedMailTest.php`: Testes do Mailable de criação

### Cobertura de Testes

#### Validações
- ✅ Validações básicas de criação
- ✅ Validações básicas de atualização
- ✅ Validação condicional de `end_date` no update (4 testes)
- ✅ Validação adicional no Service (4 testes)

#### Regras de Negócio
- ✅ Aprovação apenas por admin
- ✅ Cancelamento apenas se não aprovado
- ✅ Atualização/deleção apenas se não aprovado/cancelado
- ✅ Filtros e paginação

#### Emails
- ✅ Email de boas-vindas
- ✅ Email de criação de pedido
- ✅ Email de aprovação
- ✅ Email de cancelamento

### Executar Testes

```bash
# Todos os testes
docker-compose run --rm phpunit

# Testes específicos
docker-compose run --rm phpunit --filter TravelRequestServiceTest
```

---

## 🔧 Scripts Auxiliares

O projeto inclui dois scripts shell (`.sh`) que facilitam o desenvolvimento e setup:

### 1. `get-docker.sh` - Instalador do Docker

**O que é**: Script oficial do Docker para instalação do Docker Engine em sistemas Linux. É o mesmo script disponível em https://get.docker.com.

**Para que serve**:
- Instala Docker Engine, Docker CLI, Docker Compose e dependências
- Configura repositórios de pacotes do Docker automaticamente
- Detecta a distribuição Linux (Ubuntu, Debian, CentOS, Fedora, etc.) e adapta a instalação
- Instala a versão estável mais recente por padrão

**Quando usar**:
- ✅ Em sistemas Linux sem Docker instalado
- ✅ Para atualizar o Docker (com cuidado, pode resetar configurações)
- ⚠️ **NÃO recomendado para produção** - use métodos oficiais de instalação

**Como usar**:
```bash
# Baixar e executar (requer sudo)
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Ou executar diretamente
curl -fsSL https://get.docker.com | sudo sh
```

**Opções disponíveis**:
- `--version <VERSION>`: Instala versão específica (ex: `--version 23.0`)
- `--channel <stable|test>`: Escolhe canal (stable ou test)
- `--dry-run`: Simula instalação sem executar
- `--setup-repo`: Apenas configura repositórios, não instala

**Observação**: Este script é opcional. Se você já tem Docker instalado, pode ignorá-lo ou removê-lo do projeto.

---

### 2. `run-tests.sh` - Executor de Testes

**O que é**: Script customizado do projeto para facilitar a execução de testes PHPUnit via Docker Compose.

**Para que serve**:
- Executa testes PHPUnit dentro do container Docker
- Facilita execução de diferentes suites de testes
- Verifica e inicia containers necessários automaticamente
- Oferece opções para diferentes tipos de execução

**Funcionalidades**:

#### Verificação Automática
- Verifica se containers `db` e `redis` estão rodando
- Se não estiverem, inicia automaticamente antes de executar testes

#### Opções de Execução

**Todos os testes** (padrão):
```bash
./run-tests.sh
# ou
./run-tests.sh all
```

**Apenas testes Unit**:
```bash
./run-tests.sh unit
```

**Apenas testes Feature**:
```bash
./run-tests.sh feature
```

**Com coverage**:
```bash
./run-tests.sh coverage
# Gera relatório em backend/coverage/index.html
```

**Filtrar por classe/método**:
```bash
./run-tests.sh filter TravelRequestServiceTest
./run-tests.sh filter test_create_travel_request
```

**Como funciona**:
1. Verifica se containers estão rodando
2. Se não estiverem, inicia `db` e `redis`
3. Executa `docker-compose run --rm phpunit` com parâmetros apropriados
4. Usa o container `phpunit` definido no `docker-compose.yml`

**Primeira execução**:
```bash
# Dar permissão de execução (apenas primeira vez)
chmod +x run-tests.sh

# Executar
./run-tests.sh
```

**Compatibilidade**:
- ✅ Linux
- ✅ macOS
- ✅ WSL (Windows Subsystem for Linux)
- ✅ Git Bash (Windows)

**Observação**: No Windows puro (sem WSL/Git Bash), use diretamente:
```bash
docker-compose run --rm phpunit
```

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

---

## 🔐 Autenticação JWT (JSON Web Token)

### O que é JWT?

**JWT** é um padrão aberto (RFC 7519) para transmitir informações de forma segura entre partes como um objeto JSON. No Travel System, JWT é usado para autenticação stateless.

### Configuração

**Arquivo**: `backend/config/auth.php`
```php
'guards' => [
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],
```

**Arquivo**: `backend/config/jwt.php`
- Define algoritmo de assinatura (HS256 por padrão)
- Define tempo de expiração do token
- Define chave secreta (`JWT_SECRET`)

### Model User implementa JWTSubject

**Arquivo**: `backend/app/Models/User.php`
```php
class User extends Authenticatable implements JWTSubject
{
    // Retorna o ID do usuário (usado no token)
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    // Retorna claims customizados (role)
    public function getJWTCustomClaims()
    {
        return ['role' => $this->role];
    }
}
```

### Fluxo de Autenticação

#### 1. **Registro/Login**
```
Cliente → POST /api/auth/register ou /login
    ↓
AuthController valida credenciais
    ↓
JWTAuth::fromUser($user) gera token
    ↓
Token retornado: "eyJ0eXAiOiJKV1QiLCJhbGc..."
```

#### 2. **Uso do Token**
```
Cliente → GET /api/travel-requests
    Header: Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
    ↓
Middleware 'auth:api' valida token
    ↓
Se válido: extrai user_id e role do token
    ↓
Controller recebe usuário autenticado via auth()->user()
```

#### 3. **Refresh Token**
```
Cliente → POST /api/auth/refresh
    Header: Authorization: Bearer <token_expirado>
    ↓
JWTAuth::refresh() gera novo token
    ↓
Novo token retornado
```

#### 4. **Logout**
```
Cliente → POST /api/auth/logout
    Header: Authorization: Bearer <token>
    ↓
JWTAuth::invalidate() adiciona token à blacklist
    ↓
Token não pode mais ser usado
```

### Estrutura do Token JWT

**Header**:
```json
{
  "typ": "JWT",
  "alg": "HS256"
}
```

**Payload**:
```json
{
  "sub": "1",           // ID do usuário
  "role": "admin",      // Role customizado
  "iat": 1234567890,    // Issued at
  "exp": 1234571490     // Expiration
}
```

**Signature**: `HMACSHA256(base64UrlEncode(header) + "." + base64UrlEncode(payload), secret)`

### Middleware de Autenticação

**Arquivo**: `backend/routes/api.php`
```php
Route::middleware('auth:api')->group(function () {
    // Rotas protegidas
});
```

**O que faz**:
- Valida token JWT no header `Authorization: Bearer <token>`
- Se inválido/expirado: retorna 401 Unauthorized
- Se válido: injeta usuário autenticado via `auth()->user()`

### Tratamento de Exceções JWT

**Arquivo**: `backend/app/Exceptions/Handler.php`
- `TokenExpiredException` → 401 "Token has expired"
- `TokenInvalidException` → 401 "Token is invalid"
- `JWTException` → 401 "Token error"

---

## 🛡️ Middleware

### O que é Middleware?

**Middleware** são camadas que interceptam requisições HTTP antes que cheguem ao Controller. No Laravel, eles podem modificar a requisição, validar autenticação, ou bloquear requisições.

### Middleware Global

**Arquivo**: `backend/app/Http/Kernel.php`

**Aplicado a TODAS as requisições**:
```php
protected $middleware = [
    \App\Http\Middleware\TrustProxies::class,        // Confia em proxies (load balancers)
    \Illuminate\Http\Middleware\HandleCors::class,    // CORS
    \App\Http\Middleware\PreventRequestsDuringMaintenance::class, // Bloqueia durante manutenção
    \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class, // Valida tamanho POST
    \App\Http\Middleware\TrimStrings::class,          // Remove espaços de strings
    \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class, // Converte "" para null
];
```

### Middleware Groups

#### **API Group** (`routes/api.php`)
```php
'api' => [
    \Illuminate\Routing\Middleware\ThrottleRequests::class.':api', // Rate limiting
    \Illuminate\Routing\Middleware\SubstituteBindings::class,     // Route model binding
],
```

**Aplicado automaticamente** a todas as rotas em `routes/api.php`.

#### **Web Group** (`routes/web.php`)
```php
'web' => [
    \App\Http\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \App\Http\Middleware\VerifyCsrfToken::class, // Proteção CSRF
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

### Middleware de Autenticação

**Arquivo**: `backend/app/Http/Middleware/Authenticate.php`
- Usado pelo alias `'auth' => Authenticate::class`
- Redireciona para login se não autenticado
- Para APIs, retorna JSON 401

**Uso**:
```php
Route::middleware('auth:api')->group(function () {
    // Rotas protegidas
});
```

### Rate Limiting

**Arquivo**: `backend/app/Providers/RouteServiceProvider.php`
```php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

**O que faz**:
- Limita requisições a **60 por minuto**
- Por usuário autenticado (se logado) ou por IP (se não logado)
- Se exceder: retorna 429 Too Many Requests

**Middleware**: `ThrottleRequests::class` aplicado automaticamente no grupo `api`.

### CORS (Cross-Origin Resource Sharing)

**Arquivo**: `backend/app/Http/Kernel.php`
- `HandleCors::class` aplicado globalmente
- Permite requisições de diferentes origens (domínios)
- Configurado em `backend/config/cors.php`

**Configuração padrão**:
- Permite todas as origens (`allowed_origins: ['*']`)
- Permite métodos: GET, POST, PUT, DELETE, OPTIONS
- Permite headers: Authorization, Content-Type, X-Requested-With

---

## 🏭 Service Providers

### O que são Service Providers?

**Service Providers** são classes que registram serviços, bindings, eventos e configurações da aplicação. Eles são o "coração" do Laravel.

### Service Providers do Projeto

#### 1. **AppServiceProvider** (`app/Providers/AppServiceProvider.php`)

**Responsabilidade**: Configurações gerais da aplicação.

**O que faz**:
- Registra bindings de serviços
- Configurações globais
- No projeto atual: vazio (sem configurações customizadas)

#### 2. **AuthServiceProvider** (`app/Providers/AuthServiceProvider.php`)

**Responsabilidade**: Registra Policies de autorização.

**Arquivo**:
```php
protected $policies = [
    TravelRequest::class => TravelRequestPolicy::class,
];
```

**O que faz**:
- Mapeia Models para suas Policies
- Permite usar `$this->authorize()` no Controller

#### 3. **EventServiceProvider** (`app/Providers/EventServiceProvider.php`)

**Responsabilidade**: Registra eventos e listeners.

**Arquivo**:
```php
protected $listen = [
    UserRegistered::class => [
        SendWelcomeEmailNotification::class,
    ],
    TravelRequestCreated::class => [
        SendTravelRequestCreatedNotification::class,
    ],
    TravelRequestApproved::class => [
        SendTravelRequestApprovedNotification::class,
    ],
    TravelRequestCancelled::class => [
        SendTravelRequestCancelledNotification::class,
    ],
];
```

**O que faz**:
- Quando `event(new UserRegistered($user))` é disparado
- Laravel automaticamente executa `SendWelcomeEmailNotification::handle()`

#### 4. **RouteServiceProvider** (`app/Providers/RouteServiceProvider.php`)

**Responsabilidade**: Configura rotas e rate limiting.

**O que faz**:
- Define prefixo `/api` para rotas da API
- Configura rate limiting (60 req/min)
- Carrega `routes/api.php` e `routes/web.php`

#### 5. **BroadcastServiceProvider** (`app/Providers/BroadcastServiceProvider.php`)

**Responsabilidade**: Configura broadcasting (WebSockets, etc).

**No projeto**: Não utilizado (comentado em `config/app.php`).

---

## 🗑️ Soft Deletes

### O que são Soft Deletes?

**Soft Deletes** é um recurso do Laravel que permite "deletar" registros sem removê-los fisicamente do banco de dados. O registro fica marcado como deletado, mas ainda existe na tabela.

### Implementação no TravelRequest

**Arquivo**: `backend/app/Models/TravelRequest.php`
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class TravelRequest extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
}
```

**Migration**:
```php
$table->softDeletes(); // Adiciona coluna `deleted_at`
```

### Como Funciona

#### **Deletar** (Soft Delete):
```php
$travelRequest->delete(); // Não remove do banco!
```

**O que acontece**:
- Coluna `deleted_at` recebe timestamp atual
- Registro fica "invisível" nas queries normais
- Ainda existe no banco de dados

#### **Buscar** (ignora soft deleted):
```php
TravelRequest::find($id); // Não retorna se deleted_at não for null
TravelRequest::all();     // Não retorna soft deleted
```

#### **Buscar incluindo soft deleted**:
```php
TravelRequest::withTrashed()->find($id); // Inclui soft deleted
TravelRequest::onlyTrashed()->get();     // Apenas soft deleted
```

#### **Restaurar**:
```php
$travelRequest->restore(); // Remove deleted_at (restaura)
```

#### **Deletar permanentemente**:
```php
$travelRequest->forceDelete(); // Remove do banco definitivamente
```

### Vantagens

- ✅ Histórico: Registros deletados podem ser recuperados
- ✅ Auditoria: Sabe quando foi deletado
- ✅ Integridade: Relacionamentos não quebram
- ✅ Segurança: Dados não são perdidos acidentalmente

### Uso no Projeto

**Controller**:
```php
public function destroy(string $id): JsonResponse
{
    $travelRequest = $this->service->getById($id);
    $this->service->delete($travelRequest); // Soft delete
    return response()->json(['message' => 'Deleted successfully']);
}
```

**Service**:
```php
public function delete(TravelRequest $travelRequest): bool
{
    return $this->repository->delete($travelRequest); // Soft delete
}
```

**Repository**:
```php
public function delete(TravelRequest $travelRequest): bool
{
    return $travelRequest->delete(); // Soft delete (marca deleted_at)
}
```

---

## 📧 Mailpit (Email Testing)

### O que é Mailpit?

**Mailpit** é uma ferramenta de desenvolvimento para capturar e visualizar emails enviados pela aplicação. Substitui ferramentas como Mailtrap ou MailHog.

### Configuração

**Docker Compose**:
```yaml
mailpit:
  image: axllent/mailpit
  ports:
    - "1025:1025"  # SMTP (envio)
    - "8025:8025"  # Web UI (visualização)
```

**Laravel** (`backend/.env`):
```env
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
```

### Como Funciona

1. **Aplicação envia email**:
   ```
   Mail::to($user->email)->send(new WelcomeMail($user));
   ```

2. **Mailpit captura**:
   - Email não é enviado para servidor real
   - Mailpit intercepta na porta 1025

3. **Visualizar**:
   - Acesse: `http://localhost:8025`
   - Veja todos os emails enviados
   - Visualize HTML, texto, anexos

### Interface Web

**URL**: `http://localhost:8025`

**Funcionalidades**:
- ✅ Lista todos os emails enviados
- ✅ Visualiza HTML renderizado
- ✅ Visualiza texto plano
- ✅ Mostra headers (From, To, Subject)
- ✅ Download de anexos
- ✅ Busca e filtros

### Uso no Desenvolvimento

**Vantagens**:
- ✅ Não precisa de conta de email real
- ✅ Não envia emails reais
- ✅ Testa templates de email
- ✅ Debug rápido de emails

---

## 🔴 Redis Commander (Redis UI)

### O que é Redis Commander?

**Redis Commander** é uma interface web para gerenciar e visualizar dados do Redis.

### Configuração

**Docker Compose**:
```yaml
rediscommander:
  image: rediscommander/redis-commander:latest
  ports:
    - "8081:8081"  # Web UI
  environment:
    - REDIS_HOSTS=local:redis:6379
```

### Acesso

**URL**: `http://localhost:8081`

**Funcionalidades**:
- ✅ Visualiza todas as chaves do Redis
- ✅ Edita valores
- ✅ Deleta chaves
- ✅ Monitora comandos em tempo real
- ✅ Busca e filtros

### Uso no Projeto

**Redis é usado para**:
- Cache de dados
- Sessões (se configurado)
- Queue (se configurado como driver)

**Visualizar**:
- Acesse `http://localhost:8081`
- Veja todas as chaves armazenadas
- Monitore uso do Redis

---

## 📝 Conclusão

Este documento cobre toda a arquitetura do **Travel System**, desde o fluxo de requisições HTTP até o processamento assíncrono de emails. A arquitetura segue boas práticas de desenvolvimento, garantindo:

- ✅ Separação de responsabilidades
- ✅ Código testável e manutenível
- ✅ Processamento assíncrono
- ✅ Escalabilidade
- ✅ Desacoplamento entre camadas
- ✅ Autenticação segura (JWT)
- ✅ Rate limiting e segurança
- ✅ Soft deletes para auditoria
- ✅ Ferramentas de desenvolvimento (Mailpit, Redis Commander)

Para mais detalhes sobre uso prático, consulte o `README.md`.

