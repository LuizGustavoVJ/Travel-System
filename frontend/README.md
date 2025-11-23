# Travel System - Frontend

Frontend Vue.js 3 para o sistema de gerenciamento de pedidos de viagem corporativa.

## Arquitetura Sênior Implementada

### ✅ Composition API
- Todos os componentes usam `<script setup>`
- Lógica reutilizável em Composables

### ✅ Pinia (State Management)
- `stores/auth.js` - Gerenciamento de autenticação
- `stores/travelRequest.js` - Gerenciamento de pedidos de viagem

### ✅ Vue Router com Guards
- Proteção de rotas autenticadas
- Redirecionamento automático
- Guards de autorização (admin)

### ✅ Axios + Interceptors
- Configuração centralizada em `api/axios.js`
- Request Interceptor: Adiciona token JWT automaticamente
- Response Interceptor: Tratamento global de erros (401, 403)

### ✅ Clean Architecture
- **Services**: Camada de comunicação com API
  - `authService.js`
  - `travelRequestService.js`
- **Composables**: Lógica reutilizável
  - `useAuth.js`
  - `useFormValidation.js`

### ✅ Validação de Formulários
- Sistema de validação customizado
- Validators reutilizáveis (required, email, minLength, etc.)
- Feedback visual de erros

### ✅ Tailwind CSS
- Utility-first CSS
- Classes customizadas para botões, inputs, badges
- Design system consistente

### ✅ Vitest (Testes)
- Configurado e pronto para uso
- Happy-DOM para testes de componentes

## Tecnologias

- **Vue 3** - Framework progressivo
- **Vite** - Build tool
- **Pinia** - State management
- **Vue Router 4** - Roteamento
- **Axios** - HTTP client
- **Tailwind CSS** - Utility-first CSS
- **Vitest** - Testing framework

## Instalação

```bash
npm install
```

## Desenvolvimento

```bash
npm run dev
```

## Build

```bash
npm run build
```

## Testes

```bash
npm run test
```

## Variáveis de Ambiente

Crie um arquivo `.env` na raiz do projeto:

```
VITE_API_URL=http://localhost:8080/api
```
