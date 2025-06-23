import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'Home',
    component: () => import('@/views/Home.vue'),
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login.vue'),
    meta: { requiresGuest: true },
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/views/Register.vue'),
    meta: { requiresGuest: true },
  },
  {
    path: '/forgot-password',
    name: 'ForgotPassword',
    component: () => import('@/views/ForgotPassword.vue'),
    meta: { requiresGuest: true },
  },
  {
    path: '/reset-password',
    name: 'ResetPassword',
    component: () => import('@/views/ResetPassword.vue'),
    meta: { requiresGuest: true },
  },
  {
    path: '/forgot-password',
    name: 'ForgotPassword',
    component: () => import('@/views/ForgotPassword.vue'),
    meta: { requiresGuest: true },
  },
  {
    path: '/reset-password',
    name: 'ResetPassword',
    component: () => import('@/views/ResetPassword.vue'),
    meta: { requiresGuest: true },
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('@/views/Dashboard.vue'),
    meta: { requiresAuth: true },
  },
    {
    path: '/designs',
    name: 'Designs',
    component: () => import('@/views/Designs.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/editor/:id?',
    name: 'Editor',
    component: () => import('@/views/Editor.vue'),
    props: true,
    meta: { requiresAuth: true },
  },

  {
    path: '/templates',
    name: 'Templates',
    component: () => import('@/views/Templates.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/media',
    name: 'Media',
    component: () => import('@/views/Media.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/search',
    name: 'SearchResults',
    component: () => import('@/views/SearchResults.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/exports',
    name: 'Exports',
    component: () => import('@/views/Exports.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/settings/:tab?',
    name: 'Settings',
    component: () => import('@/views/Settings.vue'),
    props: true,
    meta: { requiresAuth: true },
  },
  {
    path: '/video-to-design',
    name: 'VideoToDesign',
    component: () => import('@/views/VideoToDesign.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/views/NotFound.vue'),
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Navigation guards
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  
  // Check if route requires authentication
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    // Redirect to login if not authenticated
    next({ name: 'Login', query: { redirect: to.fullPath } })
  } else if ((to.name === 'Login' || to.name === 'Register') && authStore.isAuthenticated) {
    // Redirect to dashboard if already authenticated and trying to access auth pages
    next({ name: 'Dashboard' })
  } else {
    next()
  }
})

export default router
