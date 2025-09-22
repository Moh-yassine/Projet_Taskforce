import type { Router } from 'vue-router'
import { authService } from '@/services/authService'
import { roleService } from '@/services/roleService'

// Définition des permissions requises pour chaque route
const routePermissions: Record<string, string[]> = {
  '/users': ['canManageUsers'],
  '/roles': ['canManageUsers'],
  '/projects': ['canManageProjects'],
  '/reports': ['canViewReports'],
  '/skills': ['canManageSkills'],
  '/all-tasks': ['canViewAllTasks'],
}

export function setupRouteGuards(router: Router) {
  // Guard global pour vérifier l'authentification
  router.beforeEach((to, from, next) => {
    const isAuthenticated = authService.isAuthenticated()
    const currentUser = authService.getCurrentUser()

    // Routes publiques qui ne nécessitent pas d'authentification
    const publicRoutes = ['/login', '/signup', '/']

    if (publicRoutes.includes(to.path)) {
      // Si l'utilisateur est déjà connecté et essaie d'accéder à login/signup, rediriger vers le dashboard
      if (isAuthenticated && (to.path === '/login' || to.path === '/signup')) {
        next('/dashboard')
        return
      }
      next()
      return
    }

    // Vérifier l'authentification pour les routes protégées
    if (!isAuthenticated || !currentUser) {
      next('/login')
      return
    }

    // Vérifier les permissions spécifiques à la route
    const requiredPermissions = routePermissions[to.path]
    if (requiredPermissions) {
      const hasPermission = requiredPermissions.some((permission) => {
        return (
          currentUser.permissions?.[permission as keyof typeof currentUser.permissions] === true
        )
      })

      if (!hasPermission) {
        // Rediriger vers le dashboard avec un message d'erreur
        next({
          path: '/dashboard',
          query: { error: 'access_denied' },
        })
        return
      }
    }

    next()
  })

  // Guard pour vérifier les permissions après navigation
  router.afterEach((to) => {
    // Mettre à jour les permissions dans le localStorage si nécessaire
    const currentUser = authService.getCurrentUser()
    if (currentUser && currentUser.permissions) {
      // Vérifier si les permissions sont à jour
      roleService
        .getPermissions()
        .then((permissions) => {
          if (JSON.stringify(permissions) !== JSON.stringify(currentUser.permissions)) {
            // Mettre à jour les permissions de l'utilisateur
            const updatedUser = { ...currentUser, permissions }
            localStorage.setItem('user', JSON.stringify(updatedUser))
          }
        })
        .catch((error) => {
          console.error('Erreur lors de la vérification des permissions:', error)
        })
    }
  })
}

// Fonction utilitaire pour vérifier si l'utilisateur peut accéder à une route
export function canAccessRoute(routePath: string): boolean {
  const currentUser = authService.getCurrentUser()
  if (!currentUser || !currentUser.permissions) {
    return false
  }

  const requiredPermissions = routePermissions[routePath]
  if (!requiredPermissions) {
    return true // Pas de permissions spécifiques requises
  }

  return requiredPermissions.some((permission) => {
    return currentUser.permissions?.[permission as keyof typeof currentUser.permissions] === true
  })
}

// Fonction utilitaire pour obtenir les routes accessibles par l'utilisateur
export function getAccessibleRoutes(): string[] {
  const currentUser = authService.getCurrentUser()
  if (!currentUser || !currentUser.permissions) {
    return ['/dashboard', '/my-tasks', '/notifications']
  }

  const accessibleRoutes: string[] = ['/dashboard', '/my-tasks', '/notifications']

  // Ajouter les routes selon les permissions
  if (currentUser.permissions.canViewAllTasks) {
    accessibleRoutes.push('/all-tasks')
  }

  if (currentUser.permissions.canManageProjects) {
    accessibleRoutes.push('/projects')
  }

  if (currentUser.permissions.canViewReports) {
    accessibleRoutes.push('/reports')
  }

  if (currentUser.permissions.canManageUsers) {
    accessibleRoutes.push('/users', '/roles')
  }

  if (currentUser.permissions.canManageSkills) {
    accessibleRoutes.push('/skills')
  }

  return accessibleRoutes
}
