// Configuration centralisée de l'API
export const API_CONFIG = {
  BASE_URL: 'http://127.0.0.1:8000/api',
  AUTH_URL: 'http://127.0.0.1:8000/api/auth',
  PROJECTS_URL: 'http://127.0.0.1:8000/api/projects',
  TASKS_URL: 'http://127.0.0.1:8000/api/tasks',
  USERS_URL: 'http://127.0.0.1:8000/api/users',
  SKILLS_URL: 'http://127.0.0.1:8000/api/skills',
  ROLES_URL: 'http://127.0.0.1:8000/api/roles',
  NOTIFICATIONS_URL: 'http://127.0.0.1:8000/api/notifications',
  PAYMENTS_URL: 'http://127.0.0.1:8000/api/payment',
  WORKLOAD_URL: 'http://127.0.0.1:8000/api/workload',
  USER_SKILLS_URL: 'http://127.0.0.1:8000/api/user-skills',
  USER_PROJECT_ROLES_URL: 'http://127.0.0.1:8000/api/user-project-roles',
} as const

// Fonction utilitaire pour obtenir l'URL de base
export const getApiBaseUrl = (): string => {
  return import.meta.env.VITE_API_BASE_URL || API_CONFIG.BASE_URL
}
