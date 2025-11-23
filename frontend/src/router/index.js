import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { guest: true }
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/views/auth/RegisterView.vue'),
    meta: { guest: true }
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/views/travel-requests/DashboardView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/travel-requests/create',
    name: 'travel-request-create',
    component: () => import('@/views/travel-requests/CreateView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/travel-requests/:id',
    name: 'travel-request-detail',
    component: () => import('@/views/travel-requests/DetailView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/travel-requests/:id/edit',
    name: 'travel-request-edit',
    component: () => import('@/views/travel-requests/EditView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/forbidden',
    name: 'forbidden',
    component: () => import('@/views/ForbiddenView.vue')
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/NotFoundView.vue')
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

// Navigation Guard - Autenticação
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  
  // Rotas que requerem autenticação
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login', query: { redirect: to.fullPath } })
    return
  }
  
  // Rotas apenas para guests (não autenticados)
  if (to.meta.guest && authStore.isAuthenticated) {
    next({ name: 'dashboard' })
    return
  }
  
  // Rotas que requerem admin
  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    next({ name: 'forbidden' })
    return
  }
  
  next()
})

export default router
