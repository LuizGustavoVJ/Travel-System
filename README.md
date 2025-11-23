# Travel System - Microsserviço de Gerenciamento de Pedidos de Viagem

> **🚨 ATENÇÃO:** Antes de começar, configure suas credenciais do MySQL! Veja a seção [Configuração Inicial Obrigatória](#-configuração-inicial-obrigatória) abaixo.

## 📋 Índice

1. [⚠️ Configuração Inicial Obrigatória](#-configuração-inicial-obrigatória)
2. [Visão Geral](#visão-geral)
3. [Pré-requisitos](#pré-requisitos)
4. [Instalação e Configuração](#instalação-e-configuração)
5. [Executando o Serviço Localmente (Docker)](#executando-o-serviço-localmente-docker)
6. [Configuração do Ambiente](#configuração-do-ambiente)
7. [Verificando se Está Funcionando](#verificando-se-está-funcionando)
8. [Usando a API](#usando-a-api)
9. [Usando o Postman (Collection Completa)](#-usando-o-postman-collection-completa)
10. [Executando Testes](#executando-testes)
11. [Comandos Úteis](#comandos-úteis)
12. [Troubleshooting](#troubleshooting)
13. [Informações Adicionais](#informações-adicionais)

---

## 🎯 Visão Geral

Este projeto é um microsserviço completo para gerenciamento de pedidos de viagem corporativa, desenvolvido com uma arquitetura robusta e moderna. Ele inclui um backend em **Laravel** que expõe uma API REST e um frontend em **Vue.js 3** para interação do usuário.

**Foco no Backend:** Como um microsserviço, o core do sistema é o backend. O frontend é um cliente de exemplo e sua execução é opcional para validar o funcionamento da API.

### Tecnologias Principais

- **Backend**: Laravel 11
- **Frontend**: Vue.js 3 (Composition API)
- **Banco de Dados**: MySQL 8
- **Cache**: Redis
- **Filas**: RabbitMQ
- **Autenticação**: JWT (JSON Web Tokens)
- **Containerização**: Docker e Docker Compose

### Funcionalidades

- **Autenticação de Usuários**: Registro, login e gerenciamento de sessão com JWT
- **Controle de Acesso**: Perfis de `usuário` e `administrador` com permissões distintas
- **CRUD de Pedidos**: Usuários podem criar, listar, visualizar, atualizar e deletar seus próprios pedidos
- **Ações de Admin**: Administradores podem aprovar ou cancelar pedidos
- **Filtros Avançados**: Listagem de pedidos com filtros por status, destino e período
- **Notificações Assíncronas**: Envio de e-mails para aprovação e cancelamento de pedidos, processados em fila com RabbitMQ
- **Validação de Regras de Negócio**: Um pedido não pode ser cancelado se já foi aprovado

---

## ⚠️ Configuração Inicial Obrigatória

**ANTES de executar o sistema, você DEVE configurar suas credenciais do MySQL:**

### Onde Configurar:

1. **Variável de Ambiente `MYSQL_PASSWORD`** (Recomendado)
   - Windows: `$env:MYSQL_PASSWORD="SUA_SENHA_MYSQL_AQUI"`
   - Linux/Mac: `export MYSQL_PASSWORD="SUA_SENHA_MYSQL_AQUI"`
   - Ou crie um arquivo `.env` na raiz do projeto com: `MYSQL_PASSWORD=SUA_SENHA_MYSQL_AQUI`

2. **Arquivo `backend/.env`** (Opcional, mas recomendado)
   - Copie `backend/.env.example` para `backend/.env`
   - Configure `DB_PASSWORD` com sua senha do MySQL

> **📌 Nota:** A senha `1012@lg` que aparece em alguns exemplos é apenas um valor padrão para desenvolvimento. **Sempre substitua pela sua própria senha!**

Veja a seção [Passo 2: Configure as Credenciais do MySQL](#passo-2-configure-as-credenciais-do-mysql) para instruções detalhadas.

---

## 📦 Pré-requisitos

Antes de começar, certifique-se de ter instalado:

- ✅ **Docker Desktop** (Windows/Mac) ou **Docker Engine + Docker Compose** (Linux)
- ✅ **Git** (para clonar o repositório, se necessário)
- ✅ **Postman** ou **Insomnia** (opcional, para testar a API)
- ✅ **MySQL local** (opcional - o Docker cria um MySQL automático se você não tiver)

---

## 🔧 Instalação e Configuração

### Passo 1: Clone ou Navegue até o Projeto

```bash
# Se você já está no diretório do projeto, pule este passo
git clone https://github.com/LuizGustavoVJ/Travel-System.git
cd Travel-System
```

### Passo 2: Configure as Credenciais do MySQL

> **⚠️ IMPORTANTE:** Você **DEVE** configurar suas próprias credenciais do MySQL antes de executar o sistema.

#### Opção 1: Variável de Ambiente (Recomendado)

**Windows (PowerShell):**
```powershell
$env:MYSQL_PASSWORD="SUA_SENHA_MYSQL_AQUI"
```

**Linux/Mac:**
```bash
export MYSQL_PASSWORD="SUA_SENHA_MYSQL_AQUI"
```

#### Opção 2: Arquivo `.env` na Raiz do Projeto

Crie um arquivo `.env` na raiz do projeto (mesmo nível do `docker-compose.yml`):

```env
MYSQL_PASSWORD=SUA_SENHA_MYSQL_AQUI
```

> **⚠️ IMPORTANTE:** 
> - A senha `1012@lg` que aparece como padrão é apenas para desenvolvimento/teste
> - **Sempre configure sua própria senha do MySQL antes de executar o sistema**
> - Se você não definir `MYSQL_PASSWORD`, o sistema usará `1012@lg` como padrão (não recomendado para produção)

---

## 🚀 Executando o Serviço Localmente (Docker)

### Passo 1: Suba Todos os Containers

```bash
docker-compose up -d --build
```

**O que acontece automaticamente:**
- ✅ Build das imagens Docker
- ✅ Criação de todos os containers
- ✅ Instalação automática de dependências (Composer)
- ✅ Criação do schema `travel_system` no MySQL
- ✅ Execução de migrations (criação de tabelas)
- ✅ Execução de seeders (criação de usuários de teste)
- ✅ Geração de chaves (APP_KEY e JWT_SECRET)
- ✅ Limpeza de cache

**⏳ Aguarde 2-5 minutos na primeira execução** (dependendo da sua internet)

### Passo 2: Verifique os Logs

```bash
# Ver logs do container principal (app)
docker-compose logs app --tail=50

# Ver todos os logs
docker-compose logs --tail=50

# Acompanhar logs em tempo real
docker-compose logs -f app
```

**Procure por:**
```
✅✅✅ Setup completo! Sistema pronto para uso! ✅✅✅
```

### Passo 3: Verifique o Status dos Containers

```bash
docker-compose ps
```

**Todos devem estar com status "Up" e "healthy":**
```
NAME                      STATUS
travel-system-app         Up (healthy)
travel-system-nginx       Up (healthy)
travel-system-mysql       Up (healthy)
travel-system-redis       Up (healthy)
travel-system-rabbitmq    Up (healthy)
travel-system-worker      Up
travel-system-scheduler   Up
travel-system-mailpit     Up (healthy)
```

---

## ⚙️ Configuração do Ambiente

### Variáveis de Ambiente

> **⚠️ IMPORTANTE:** Configure suas credenciais do MySQL antes de executar o sistema.

#### 1. Configure `MYSQL_PASSWORD` (Obrigatório)

Você **DEVE** definir a variável `MYSQL_PASSWORD` com a senha do seu MySQL. Veja a seção [Passo 2: Configure as Credenciais do MySQL](#passo-2-configure-as-credenciais-do-mysql) acima.

#### 2. Arquivo `.env` do Backend (Opcional)

O sistema funciona automaticamente com valores padrão, mas você pode personalizar criando um arquivo `.env` no diretório `backend/`:

```bash
cp backend/.env.example backend/.env
```

**Se você criar o `.env`, configure as variáveis de banco de dados:**

```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=travel_system
DB_USERNAME=root
DB_PASSWORD=${MYSQL_PASSWORD:-SUA_SENHA_AQUI}
```

> **⚠️ ATENÇÃO:** 
> - Substitua `SUA_SENHA_AQUI` pela sua senha real do MySQL
> - O `${MYSQL_PASSWORD:-...}` usa a variável de ambiente `MYSQL_PASSWORD` se estiver definida, caso contrário usa o valor padrão após `:-`
> - Se você não definir `MYSQL_PASSWORD`, o sistema usará `1012@lg` como padrão (apenas para desenvolvimento)

**Valores Padrão (se não criar .env):**
- `DB_HOST=db` (container MySQL)
- `DB_PORT=3306`
- `DB_DATABASE=travel_system`
- `DB_USERNAME=root`
- `DB_PASSWORD=${MYSQL_PASSWORD:-1012@lg}` (usa a variável `MYSQL_PASSWORD` ou `1012@lg` como padrão)

### Variáveis de Ambiente do RabbitMQ

O RabbitMQ é configurado automaticamente, mas você pode personalizar:

```env
RABBITMQ_USER=guest
RABBITMQ_PASS=guest
```

### Variáveis de Ambiente do Frontend

Se você quiser executar o frontend separadamente, crie um arquivo `.env` no diretório `frontend/`:

```env
VITE_API_URL=http://localhost:8080/api
```

---

## ✅ Verificando se Está Funcionando

### 1. Health Check

```bash
# Via curl (Linux/Mac/Git Bash)
curl http://localhost:8080/health

# Via PowerShell (Windows)
Invoke-WebRequest -Uri http://localhost:8080/health

# Ou abra no navegador
# http://localhost:8080/health
```

**Resposta esperada:**
```json
{
  "status": "healthy",
  "timestamp": "2025-11-23T...",
  "services": {
    "database": "ok",
    "redis": "ok"
  }
}
```

### 2. Acesse os Serviços

| Serviço | URL | Credenciais | Descrição |
|---------|-----|------------|-----------|
| **API Backend** | http://localhost:8080/api | - | API REST do microserviço |
| **RabbitMQ Management** | http://localhost:15672 | guest / guest | Interface de gerenciamento de filas |
| **Mailpit (Emails)** | http://localhost:8025 | - | Visualizador de emails enviados |
| **Redis Commander** | http://localhost:8081 | - | Interface web para monitorar Redis |
| **Health Check** | http://localhost:8080/health | - | Status de saúde do sistema |
| **MySQL** | localhost:3307 | root / (senha configurada) | Banco de dados |
| **Redis** | localhost:6379 | - | Cache e sessões |

---

## 🔌 Usando a API

> **💡 Recomendação:** Para testar todos os endpoints de forma completa e organizada, use a **Collection do Postman** (próxima seção). Esta seção é apenas uma visão geral rápida.

### Usuários Criados Automaticamente

O sistema cria automaticamente 3 usuários de teste:

1. **Admin:**
   - Email: `admin@example.com`
   - Senha: `password`
   - Role: `admin`
   - Nome: `Admin User`

2. **Usuário 1:**
   - Email: `user1@example.com`
   - Senha: `password`
   - Role: `user`
   - Nome: `Test User 1`

3. **Usuário 2:**
   - Email: `user2@example.com`
   - Senha: `password`
   - Role: `user`
   - Nome: `Test User 2`

> **Nota:** O sistema já cria automaticamente 3 travel requests de exemplo:
> - 2 pedidos para `user1@example.com` (1 com status `requested`, 1 com status `approved`)
> - 1 pedido para `user2@example.com` (status `requested`)

### Endpoints Disponíveis

**Autenticação:**
- `POST /api/auth/register` - Registrar novo usuário
- `POST /api/auth/login` - Fazer login
- `GET /api/auth/me` - Obter usuário autenticado
- `POST /api/auth/refresh` - Renovar token
- `POST /api/auth/logout` - Fazer logout

**Pedidos de Viagem:**
- `GET /api/travel-requests` - Listar pedidos (com filtros e paginação)
- `POST /api/travel-requests` - Criar novo pedido
- `GET /api/travel-requests/{id}` - Obter pedido específico
- `PUT /api/travel-requests/{id}` - Atualizar pedido
- `DELETE /api/travel-requests/{id}` - Deletar pedido

**Ações de Admin:**
- `POST /api/travel-requests/{id}/approve` - Aprovar pedido (apenas admin)
- `POST /api/travel-requests/{id}/cancel` - Cancelar pedido (apenas admin)

### Exemplo Rápido com cURL

```bash
# 1. Login
TOKEN=$(curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}' \
  | jq -r '.token')

# 2. Criar pedido (requester_name é preenchido automaticamente)
curl -X POST http://localhost:8080/api/travel-requests \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "destination": "São Paulo, Brasil",
    "start_date": "2025-12-15",
    "end_date": "2025-12-20",
    "notes": "Reunião com cliente"
  }'

# 3. Listar pedidos
curl -X GET http://localhost:8080/api/travel-requests \
  -H "Authorization: Bearer $TOKEN"
```

> **📮 Para testar todos os cenários de forma completa, continue para a próxima seção: [Usando o Postman](#-usando-o-postman-collection-completa)**

---

## 📮 Usando o Postman (Collection Completa)

### Passo 1: Importar a Collection

1. **Abra o Postman**
2. **Clique em "Import"** (canto superior esquerdo)
3. **Selecione o arquivo:** `Travel-System-API.postman_collection.json`
4. **Aguarde a importação** - você verá a collection "Travel System API" na lista

### Passo 2: Configurar Variáveis (Automático)

A collection já vem pré-configurada com:

- ✅ **`base_url`**: `http://localhost:8080/api` (já configurado)
- ✅ **`access_token`**: Será preenchido automaticamente após login
- ✅ **`travel_request_id`**: Será preenchido automaticamente ao criar um pedido

**Para verificar/editar variáveis:**
1. Clique com botão direito na collection "Travel System API"
2. Selecione "Edit"
3. Vá na aba "Variables"
4. Verifique se `base_url` está como `http://localhost:8080/api`

### ⚡ Como Funciona o Token Automático

**🎯 Resposta Rápida:** Sim! Você só precisa fazer login uma vez. O token é usado automaticamente em todas as requisições.

**Como funciona:**

1. **Auth Global Configurado:**
   - A collection tem um Bearer Token configurado globalmente
   - Todas as requisições usam automaticamente `{{access_token}}`

2. **Script Automático nos Logins:**
   - Quando você faz login (Admin, User 1 ou User 2)
   - O Postman executa um script que:
     - Pega o `token` da resposta
     - Salva automaticamente na variável `access_token`

3. **Uso Automático:**
   - Todas as outras requisições pegam o token da variável `access_token`
   - Você **NÃO precisa** copiar/colar o token manualmente!

**Exemplo prático:**
```
1. Você faz "Login Admin" → Token é salvo automaticamente
2. Você clica em "Listar Pedidos" → Usa o token automaticamente ✅
3. Você clica em "Criar Pedido" → Usa o token automaticamente ✅
4. Você clica em "Aprovar Pedido" → Usa o token automaticamente ✅
```

**💡 Dica:** Se você fizer login com outro usuário (ex: Login User 1), o token será atualizado automaticamente e todas as próximas requisições usarão o novo token.

### Passo 3: Ordem Recomendada de Uso

#### 🎯 **Cenário Inicial: Teste Básico**

**1. Login Admin**
- Vá em: `Autenticação` → `Login Admin`
- Clique em **Send**
- ✅ O token será salvo automaticamente na variável `access_token`

**2. Verificar Usuário Autenticado**
- Vá em: `Autenticação` → `Obter Usuário Autenticado`
- Clique em **Send**
- ✅ Deve retornar os dados do admin

**3. Listar Todos os Pedidos (Admin vê tudo)**
- Vá em: `Pedidos de Viagem` → `Listar Pedidos`
- Clique em **Send**
- ✅ Como admin, você verá todos os 3 pedidos criados pelo seeder

**4. Criar um Novo Pedido**
- Vá em: `Pedidos de Viagem` → `Criar Pedido`
- O body já está preenchido, mas você pode editar
- Clique em **Send**
- ✅ O ID do pedido será salvo automaticamente em `travel_request_id`

**5. Ver Detalhes do Pedido Criado**
- Vá em: `Pedidos de Viagem` → `Obter Pedido por ID`
- Clique em **Send**
- ✅ Usa automaticamente o `travel_request_id` salvo

**6. Aprovar o Pedido (Admin)**
- Vá em: `Ações de Admin` → `Aprovar Pedido`
- Clique em **Send**
- ✅ O pedido será aprovado e uma notificação será enviada

#### 🎯 **Cenário Completo: Usuário Comum**

**1. Login User 1**
- Vá em: `Autenticação` → `Login User 1`
- Clique em **Send**
- ✅ O token será atualizado automaticamente

**2. Listar Meus Pedidos (User vê apenas os seus)**
- Vá em: `Pedidos de Viagem` → `Listar Pedidos`
- Clique em **Send**
- ✅ User 1 verá apenas seus 2 pedidos (criados pelo seeder)

**3. Criar Novo Pedido**
- Vá em: `Pedidos de Viagem` → `Criar Pedido`
- Edite o body se necessário
- Clique em **Send**

**4. Atualizar Meu Pedido**
- Vá em: `Pedidos de Viagem` → `Atualizar Pedido`
- Edite o body se necessário
- Clique em **Send**
- ✅ Apenas o proprietário pode atualizar

#### 🎯 **Cenários de Teste Pré-Configurados**

A collection inclui 4 cenários completos prontos para uso:

**Cenário 1: Usuário cria pedido**
- Fluxo completo: Login → Criar → Listar
- Execute os 3 passos em sequência

**Cenário 2: Admin aprova pedido**
- Fluxo completo: Login Admin → Listar Todos → Aprovar
- Execute os 3 passos em sequência

**Cenário 3: Usuário atualiza seu pedido**
- Fluxo: Login → Atualizar
- Execute os 2 passos em sequência

**Cenário 4: Admin cancela pedido**
- Fluxo: Login Admin → Cancelar com motivo
- Execute os 2 passos em sequência

**Para executar um cenário completo:**
1. Vá em: `Cenários de Teste` → Escolha um cenário
2. Execute cada passo em ordem (1, 2, 3...)
3. Cada passo salva automaticamente o token/ID necessário para o próximo

### Passo 4: Dicas e Truques

#### ✅ **Token Automático**
- Após qualquer login (Admin, User 1, User 2), o token é salvo automaticamente
- Todos os endpoints protegidos usam esse token automaticamente
- Não precisa copiar/colar o token manualmente!

#### ✅ **ID do Pedido Automático**
- Ao criar um pedido, o ID é salvo automaticamente em `travel_request_id`
- Endpoints que precisam do ID usam essa variável automaticamente
- Você pode substituir manualmente se quiser usar outro ID

#### ✅ **Testar com Diferentes Usuários**
- **Login Admin**: Para testar ações de admin (aprovar, cancelar, ver todos os pedidos)
- **Login User 1**: Para testar como usuário comum (criar, atualizar, ver apenas seus pedidos)
- **Login User 2**: Para testar outro usuário comum

#### ✅ **Registrar Novo Usuário**
- Use `Autenticação` → `Registrar Novo Usuário`
- O token será salvo automaticamente
- O novo usuário terá role `user` (não admin)
- **Nota:** A senha deve ter no mínimo 8 caracteres

#### ✅ **Verificar Respostas**
- Todas as respostas são em JSON
- Status 200/201 = Sucesso
- Status 401 = Token inválido ou expirado (faça login novamente)
- Status 403 = Sem permissão (ex: usuário tentando aprovar pedido)
- Status 404 = Recurso não encontrado
- Status 422 = Erro de validação (verifique o body da requisição)

#### ✅ **Filtros na Listagem**
- No endpoint `Listar Pedidos`, você pode habilitar filtros:
  - `status`: Filtrar por status (requested, approved, cancelled)
  - `destination`: Filtrar por destino
  - `start_date_from` / `start_date_to`: Filtrar por data

#### ✅ **Refresh Token**
- Se o token expirar, use `Autenticação` → `Refresh Token`
- Isso renova o token sem precisar fazer login novamente

### Passo 5: Estrutura da Collection

```
Travel System API
├── Autenticação
│   ├── Login Admin
│   ├── Login User 1
│   ├── Login User 2
│   ├── Registrar Novo Usuário
│   ├── Obter Usuário Autenticado
│   ├── Refresh Token
│   └── Logout
├── Pedidos de Viagem
│   ├── Listar Pedidos
│   ├── Criar Pedido
│   ├── Obter Pedido por ID
│   ├── Atualizar Pedido
│   └── Deletar Pedido
├── Ações de Admin
│   ├── Aprovar Pedido
│   └── Cancelar Pedido
└── Cenários de Teste
    ├── Cenário 1: Usuário cria pedido
    ├── Cenário 2: Admin aprova pedido
    ├── Cenário 3: Usuário atualiza seu pedido
    └── Cenário 4: Admin cancela pedido
```

### Passo 6: Troubleshooting no Postman

**Problema: Token não está sendo salvo**
- ✅ Verifique se o login retornou status 200
- ✅ Verifique se a resposta contém `token` (não `access_token`)
- ✅ Verifique as variáveis da collection (Edit → Variables)

**Problema: 401 Unauthorized**
- ✅ Faça login novamente (o token pode ter expirado)
- ✅ Verifique se está usando o endpoint correto de login
- ✅ Verifique se o email/senha estão corretos

**Problema: 403 Forbidden**
- ✅ Você está tentando fazer uma ação de admin sem ser admin
- ✅ Faça login como `admin@example.com` para ações de admin
- ✅ Verifique se está tentando atualizar/deletar um pedido que não é seu

**Problema: 404 Not Found**
- ✅ Verifique se o `travel_request_id` está correto
- ✅ Verifique se o pedido existe (use Listar Pedidos primeiro)
- ✅ Verifique se a URL está correta (`{{base_url}}/travel-requests/...`)

**Problema: 422 Validation Error**
- ✅ Verifique o body da requisição
- ✅ Para criar pedido: `destination`, `start_date`, `end_date` são obrigatórios
- ✅ `start_date` deve ser >= hoje
- ✅ `end_date` deve ser > `start_date`
- ✅ Para registro: senha deve ter no mínimo 8 caracteres

---

## 🧪 Executando Testes

### Opção 1: Usando Docker Compose (Recomendado)

#### Executar Todos os Testes

```bash
docker-compose run --rm phpunit
```

#### Executar Apenas Testes Unitários

```bash
docker-compose run --rm phpunit vendor/bin/phpunit --testsuite=Unit --colors=always
```

#### Executar Apenas Testes Feature

```bash
docker-compose run --rm phpunit vendor/bin/phpunit --testsuite=Feature --colors=always
```

#### Executar Teste Específico

```bash
docker-compose run --rm phpunit vendor/bin/phpunit --filter=TravelRequestTest --colors=always
```

#### Executar com Coverage

```bash
docker-compose run --rm phpunit vendor/bin/phpunit --coverage-html coverage --colors=always
```

Após executar, o coverage estará em: `backend/coverage/index.html`

### Opção 2: Usando o Script Helper (Linux/Mac/Git Bash)

```bash
# Dar permissão de execução (apenas primeira vez)
chmod +x run-tests.sh

# Executar todos os testes
./run-tests.sh

# Executar apenas testes unitários
./run-tests.sh unit

# Executar apenas testes feature
./run-tests.sh feature

# Executar com coverage
./run-tests.sh coverage

# Filtrar por classe/método
./run-tests.sh filter TravelRequestTest
```

### Opção 3: Dentro do Container (Para Debug)

```bash
# Entrar no container
docker-compose exec app bash

# Dentro do container, executar testes
php artisan test

# Ou usar PHPUnit diretamente
vendor/bin/phpunit
```

### Verificar Resultados dos Testes

Os testes devem mostrar algo como:

```
PHPUnit 10.1.0 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.x
Configuration: /var/www/html/phpunit.xml

...                                                                 3 / 3 (100%)

Time: 00:01.234, Memory: 12.34 MB

OK (3 tests, 5 assertions)
```

---

## 🛠️ Comandos Úteis

### Gerenciamento de Containers

```bash
# Ver status de todos os containers
docker-compose ps

# Ver logs de um container específico
docker-compose logs app
docker-compose logs nginx
docker-compose logs db

# Acompanhar logs em tempo real
docker-compose logs -f app

# Parar todos os containers
docker-compose stop

# Iniciar containers parados
docker-compose start

# Reiniciar um container específico
docker-compose restart app

# Parar e remover todos os containers
docker-compose down

# Parar, remover containers E volumes (CUIDADO: apaga dados!)
docker-compose down -v

# Rebuild forçado (útil após mudanças no Dockerfile)
docker-compose up -d --build --force-recreate
```

### Acessar Containers

```bash
# Entrar no container da aplicação
docker-compose exec app bash

# Entrar no container do MySQL
docker-compose exec db mysql -u root -p

# Entrar no container do Redis
docker-compose exec redis redis-cli

# Ou conectar diretamente (se tiver redis-cli instalado localmente)
redis-cli -h localhost -p 6379

# Executar comandos Artisan
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan cache:clear
```

### Banco de Dados

```bash
# Ver databases
# ⚠️ Substitua ${MYSQL_PASSWORD:-1012@lg} pela sua senha ou use a variável MYSQL_PASSWORD
docker-compose exec db mysql -u root -p${MYSQL_PASSWORD:-SUA_SENHA_AQUI} -e "SHOW DATABASES;"

# Ver tabelas
docker-compose exec db mysql -u root -p${MYSQL_PASSWORD:-SUA_SENHA_AQUI} travel_system -e "SHOW TABLES;"

# Ver dados de uma tabela
docker-compose exec db mysql -u root -p${MYSQL_PASSWORD:-SUA_SENHA_AQUI} travel_system -e "SELECT * FROM users;"

# Backup do banco
docker-compose exec db mysqldump -u root -p${MYSQL_PASSWORD:-SUA_SENHA_AQUI} travel_system > backup.sql

# Restaurar backup
docker-compose exec -T db mysql -u root -p${MYSQL_PASSWORD:-SUA_SENHA_AQUI} travel_system < backup.sql
```

### Limpeza

```bash
# Limpar cache do Laravel
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Limpar tudo de uma vez
docker-compose exec app php artisan optimize:clear
```

---

## 🔍 Troubleshooting

### Problema: Containers não iniciam

**Solução:**
```bash
# Ver logs de erro
docker-compose logs

# Verificar se portas estão em uso
netstat -ano | findstr :8080  # Windows
lsof -i :8080                 # Linux/Mac

# Parar tudo e recomeçar
docker-compose down
docker-compose up -d --build
```

### Problema: Erro de conexão com MySQL

**Solução:**
```bash
# Verificar se MySQL está rodando
docker-compose ps db

# Ver logs do MySQL
docker-compose logs db

# Verificar se o schema foi criado
# ⚠️ Substitua ${MYSQL_PASSWORD:-1012@lg} pela sua senha ou use a variável MYSQL_PASSWORD
docker-compose exec db mysql -u root -p${MYSQL_PASSWORD:-SUA_SENHA_AQUI} -e "SHOW DATABASES;"
```

### Problema: Erro "Permission denied" no storage

**Solução:**
```bash
# Corrigir permissões
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Problema: Testes falhando

**Solução:**
```bash
# Verificar se database de testes existe
# ⚠️ Substitua ${MYSQL_PASSWORD:-1012@lg} pela sua senha ou use a variável MYSQL_PASSWORD
docker-compose exec db mysql -u root -p${MYSQL_PASSWORD:-SUA_SENHA_AQUI} -e "SHOW DATABASES LIKE 'travel_system_test';"

# Se não existir, criar manualmente
docker-compose exec db mysql -u root -p${MYSQL_PASSWORD:-SUA_SENHA_AQUI} -e "CREATE DATABASE IF NOT EXISTS travel_system_test;"

# Limpar cache de testes
docker-compose exec app php artisan config:clear
```

### Problema: Porta já em uso

**Solução:**
```bash
# Windows - Ver qual processo está usando a porta
netstat -ano | findstr :8080

# Linux/Mac - Ver qual processo está usando a porta
lsof -i :8080

# Parar o processo ou mudar a porta no docker-compose.yml
```

### Problema: Containers ficam reiniciando

**Solução:**
```bash
# Ver logs para identificar o erro
docker-compose logs app

# Verificar healthcheck
docker inspect travel-system-app | grep -A 10 Health

# Verificar se todos os serviços dependentes estão healthy
docker-compose ps
```

---

## 📚 Informações Adicionais

### Credenciais de Teste

O `DatabaseSeeder` cria os seguintes usuários para teste:

- **Administrador**:
  - **E-mail**: `admin@example.com`
  - **Senha**: `password`
- **Usuário 1**:
  - **E-mail**: `user1@example.com`
  - **Senha**: `password`
- **Usuário 2**:
  - **E-mail**: `user2@example.com`
  - **Senha**: `password`

### Processamento de Filas

O serviço `php-worker` é responsável por processar as filas (envio de e-mails). Você pode monitorar os logs do worker com:

```bash
docker-compose logs -f php-worker
```

### RabbitMQ - Sistema de Filas

O **RabbitMQ** é um message broker usado para processar tarefas assíncronas no sistema. No Travel System, ele é usado para:

- ✅ Envio de emails de notificação (aprovação/cancelamento de pedidos)
- ✅ Processamento de tarefas em background
- ✅ Desacoplamento de serviços

**Como Acessar:**
- **Interface Web**: http://localhost:15672 (guest/guest)
- **Ver logs**: `docker-compose logs rabbitmq`
- **Verificar status**: `docker-compose exec rabbitmq rabbitmq-diagnostics ping`

**Como Funciona:**
```
1. Usuário aprova/cancela pedido
   ↓
2. Sistema envia mensagem para fila RabbitMQ
   ↓
3. Worker (php-worker) processa a mensagem
   ↓
4. Email é enviado via Mailpit
```

### Monitorando o Redis

O Redis está configurado na porta **6379** e **não requer senha** (modo protegido desabilitado para desenvolvimento).

**Acessar via CLI:**
```bash
# Entrar no container do Redis
docker-compose exec redis redis-cli

# Ou conectar diretamente (se tiver redis-cli instalado localmente)
redis-cli -h localhost -p 6379
```

**Interface Web - Redis Commander:**
- Acesse: **http://localhost:8081**
- A conexão com o Redis já está configurada automaticamente

**O que o Redis faz no Travel System?**
- ✅ **Cache**: Armazena dados frequentemente acessados
- ✅ **Sessões**: Gerencia sessões de usuários (se configurado)
- ✅ **Rate Limiting**: Limita requisições por IP

### Arquitetura do Projeto

O projeto segue uma arquitetura robusta e escalável, separando as responsabilidades em camadas:

- **Controllers**: Recebem as requisições HTTP
- **FormRequests**: Validam os dados de entrada
- **Services**: Orquestram a lógica de negócio
- **Repositories**: Abstraem o acesso ao banco de dados
- **Resources**: Padronizam as respostas da API
- **Events/Listeners**: Desacoplam as notificações da lógica principal

Esta arquitetura garante um código limpo, testável e de fácil manutenção.

### Documentação da API (Postman)

Uma coleção completa do Postman está disponível na raiz do projeto:

- `Travel-System-API.postman_collection.json`

Importe este arquivo no seu Postman para ter acesso a todos os endpoints da API, com exemplos de requisições e respostas.

---

## ✅ Checklist de Verificação

Use este checklist para garantir que tudo está funcionando:

- [ ] Todos os containers estão rodando (`docker-compose ps`)
- [ ] Health check retorna `healthy` (http://localhost:8080/health)
- [ ] Consigo fazer login na API
- [ ] Consigo criar um pedido de viagem
- [ ] Consigo listar pedidos
- [ ] Testes passam (`docker-compose run --rm phpunit`)
- [ ] RabbitMQ está acessível (http://localhost:15672)
- [ ] Mailpit está acessível (http://localhost:8025)
- [ ] Logs não mostram erros críticos

---

**🎉 Pronto! Você está usando o Travel System Microserviço!**

> **💡 Lembrete:** Certifique-se de ter configurado suas credenciais do MySQL antes de executar o sistema. Veja a seção [Configuração Inicial Obrigatória](#-configuração-inicial-obrigatória) para mais detalhes.

Para dúvidas ou problemas, consulte a seção [Troubleshooting](#troubleshooting) ou os logs dos containers.
