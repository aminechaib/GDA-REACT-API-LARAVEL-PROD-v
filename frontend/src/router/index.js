import { createRouter, createWebHistory } from 'vue-router'
import Login from '../views/Login.vue' // The login component we made earlier

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { requiresGuest: true } // Only non-logged-in users should see this
  },
  {
    path: '/products',
    name: 'Products',
    // Assuming you have a Products component. Lazy loading it here:
    component: () => import('../views/Products.vue'),
    meta: { requiresAuth: true } // PROTECTED ROUTE
  },
  {
    // Redirect the base URL to products
    path: '/',
    redirect: '/products'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// --- NAVIGATION GUARD ---
// This runs before EVERY page change to check if the user is allowed
router.beforeEach((to, from, next) => {
  const isAuthenticated = !!localStorage.getItem('auth_token')

  if (to.meta.requiresAuth && !isAuthenticated) {
    // Trying to access a protected page without a token? Send to login.
    next('/login')
  } else if (to.meta.requiresGuest && isAuthenticated) {
    // Trying to access login page while already logged in? Send to products.
    next('/products')
  } else {
    // Otherwise, let them through
    next()
  }
})

export default router
