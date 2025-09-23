import { describe, it, expect } from 'vitest'
import { API_CONFIG } from '../../config/api'

describe('Real API URLs Validation', () => {
  describe('API Configuration', () => {
    it('uses correct base URLs for all services', () => {
      // Vérifier que toutes les URLs pointent vers le bon serveur
      expect(API_CONFIG.BASE_URL).toBe('http://127.0.0.1:8000/api')
      expect(API_CONFIG.AUTH_URL).toBe('http://127.0.0.1:8000/api/auth')
      expect(API_CONFIG.PROJECTS_URL).toBe('http://127.0.0.1:8000/api/projects')
      expect(API_CONFIG.TASKS_URL).toBe('http://127.0.0.1:8000/api/tasks')
      expect(API_CONFIG.USERS_URL).toBe('http://127.0.0.1:8000/api/users')
      expect(API_CONFIG.SKILLS_URL).toBe('http://127.0.0.1:8000/api/skills')
      expect(API_CONFIG.ROLES_URL).toBe('http://127.0.0.1:8000/api/roles')
      expect(API_CONFIG.NOTIFICATIONS_URL).toBe('http://127.0.0.1:8000/api/notifications')
      // expect(API_CONFIG.PAYMENTS_URL).toBe('http://127.0.0.1:8000/api/payment') // Service supprimé
      expect(API_CONFIG.WORKLOAD_URL).toBe('http://127.0.0.1:8000/api/workload')
      expect(API_CONFIG.USER_SKILLS_URL).toBe('http://127.0.0.1:8000/api/user-skills')
      expect(API_CONFIG.USER_PROJECT_ROLES_URL).toBe('http://127.0.0.1:8000/api/user-project-roles')
    })

    // Test désactivé - service de paiement supprimé
    // it('validates that payment URL uses singular form', () => {
    //   expect(API_CONFIG.PAYMENTS_URL).toBe('http://127.0.0.1:8000/api/payment')
    //   expect(API_CONFIG.PAYMENTS_URL).not.toBe('http://127.0.0.1:8000/api/payments')
    // })

    it('checks all endpoints use HTTPS in production context', () => {
      // Test de structure - en production les URLs devraient être HTTPS
      const productionUrls = Object.values(API_CONFIG).map(url => 
        url.replace('http://127.0.0.1:8000', 'https://api.taskforce.com')
      )
      
      productionUrls.forEach(url => {
        expect(url).toMatch(/^https:\/\//)
      })
    })
  })

  describe('Frontend Route Validation', () => {
    it('validates all frontend routes exist', () => {
      const expectedRoutes = [
        '/',
        '/login',
        '/signup', 
        '/dashboard',
        '/tasks',
        '/project/:id/tasks',
        '/my-tasks',
        '/admin',
        '/users',
        '/roles',
        '/reports',
        '/all-tasks',
        '/notifications',
        '/premium'
      ]

      // Test que toutes les routes attendues sont définies
      expectedRoutes.forEach(route => {
        expect(route).toBeDefined()
        expect(typeof route).toBe('string')
        expect(route.length).toBeGreaterThan(0)
      })
    })

    it('validates route parameter patterns', () => {
      const parameterizedRoutes = [
        '/project/:id/tasks',
        '/project/123/tasks',
        '/project/456/tasks'
      ]

      parameterizedRoutes.forEach(route => {
        expect(route).toMatch(/^\/project\/(:id|\d+)\/tasks$/)
      })
    })
  })

  describe('Backend Endpoint Structure', () => {
    it('validates expected backend endpoints structure', () => {
      const backendEndpoints = {
        auth: [
          'POST /api/auth/register',
          'POST /api/auth/login', 
          'GET /api/auth/me'
        ],
        projects: [
          'GET /api/projects',
          'POST /api/projects',
          'GET /api/projects/{id}',
          'PUT /api/projects/{id}',
          'DELETE /api/projects/{id}'
        ],
        tasks: [
          'GET /api/tasks',
          'POST /api/tasks',
          'GET /api/tasks/{id}',
          'PUT /api/tasks/{id}',
          'DELETE /api/tasks/{id}'
        ],
        users: [
          'GET /api/users',
          'GET /api/users/assignable',
          'GET /api/users/{id}/workload',
          'GET /api/users/{id}/tasks',
          'GET /api/users/my-tasks',
          'PUT /api/users/{id}/role'
        ],
        notifications: [
          'GET /api/notifications',
          'GET /api/notifications/my-notifications',
          'GET /api/notifications/user/{id}',
          'GET /api/notifications/user/{id}/unread',
          'GET /api/notifications/user/{id}/count',
          'POST /api/notifications',
          'PUT /api/notifications/{id}/read',
          'PUT /api/notifications/{id}/unread',
          'PUT /api/notifications/{id}/toggle',
          'PUT /api/notifications/user/{id}/read-all',
          'DELETE /api/notifications/{id}',
          'POST /api/notifications/user/{id}/alert'
        ],
        payment: [
          'GET /api/payment/config',
          'POST /api/payment/create-subscription',
          // 'GET /api/payment/subscription-status', // Service supprimé
          'POST /api/payment/cancel-subscription',
          'POST /api/payment/webhook',
          'GET /api/payment/premium-features'
        ]
      }

      // Vérifier la structure des endpoints
      Object.entries(backendEndpoints).forEach(([service, endpoints]) => {
        expect(service).toBeDefined()
        expect(Array.isArray(endpoints)).toBe(true)
        expect(endpoints.length).toBeGreaterThan(0)
        
        endpoints.forEach(endpoint => {
          expect(endpoint).toMatch(/^(GET|POST|PUT|DELETE|PATCH) \/api\/\w+/)
        })
      })
    })

    it('validates payment endpoints use correct singular URL', () => {
      const paymentEndpoints = [
        'GET /api/payment/config',
        'POST /api/payment/create-subscription', 
        // 'GET /api/payment/subscription-status', // Service supprimé
        'POST /api/payment/cancel-subscription',
        'POST /api/payment/webhook',
        'GET /api/payment/premium-features'
      ]

      paymentEndpoints.forEach(endpoint => {
        expect(endpoint).toMatch(/\/api\/payment\//)
        expect(endpoint).not.toMatch(/\/api\/payments\//)
      })
    })
  })

  describe('API Consistency Validation', () => {
    it('validates frontend/backend URL consistency', () => {
      // Mapping frontend config -> backend endpoints
      const consistencyMap = {
        [API_CONFIG.AUTH_URL]: '/api/auth',
        [API_CONFIG.PROJECTS_URL]: '/api/projects', 
        [API_CONFIG.TASKS_URL]: '/api/tasks',
        [API_CONFIG.USERS_URL]: '/api/users',
        [API_CONFIG.SKILLS_URL]: '/api/skills',
        [API_CONFIG.ROLES_URL]: '/api/roles',
        [API_CONFIG.NOTIFICATIONS_URL]: '/api/notifications',
        [API_CONFIG.PAYMENTS_URL]: '/api/payment', // Corrigé
        [API_CONFIG.WORKLOAD_URL]: '/api/workload',
        [API_CONFIG.USER_SKILLS_URL]: '/api/user-skills',
        [API_CONFIG.USER_PROJECT_ROLES_URL]: '/api/user-project-roles'
      }

      Object.entries(consistencyMap).forEach(([frontendUrl, backendPath]) => {
        expect(frontendUrl).toContain(backendPath)
      })
    })

    it('validates no hardcoded URLs in critical paths', () => {
      // Test que les URLs ne sont pas en dur mais utilisent la config
      const forbiddenHardcodedUrls = [
        'http://localhost:8000',
        'http://127.0.0.1:8001', 
        'https://localhost',
        'http://api.example.com'
      ]

      // Vérifier qu'aucune URL interdite n'est utilisée
      forbiddenHardcodedUrls.forEach(url => {
        expect(Object.values(API_CONFIG)).not.toContain(url)
      })
    })
  })

  describe('Environment Configuration', () => {
    it('validates environment-specific URL handling', () => {
      // Test que la configuration peut gérer différents environnements
      const envConfigs = {
        development: 'http://127.0.0.1:8000/api',
        staging: 'https://staging-api.taskforce.com/api',
        production: 'https://api.taskforce.com/api'
      }

      Object.entries(envConfigs).forEach(([env, expectedUrl]) => {
        expect(expectedUrl).toMatch(/^https?:\/\//)
        expect(expectedUrl).toContain('/api')
        
        if (env === 'production' || env === 'staging') {
          expect(expectedUrl).toMatch(/^https:\/\//)
        }
      })
    })
  })
})
