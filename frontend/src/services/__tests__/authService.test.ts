import { describe, it, expect, beforeEach, vi } from 'vitest'
import { authService } from '../authService'

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
  length: 0,
  key: vi.fn()
}

Object.defineProperty(window, 'localStorage', {
  value: localStorageMock
})

describe('AuthService', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('isAuthenticated', () => {
    it('returns false when no token is stored', () => {
      localStorageMock.getItem.mockReturnValue(null)
      expect(authService.isAuthenticated()).toBe(false)
    })

    it('returns true when token is stored', () => {
      localStorageMock.getItem.mockReturnValue('valid-token')
      expect(authService.isAuthenticated()).toBe(true)
    })
  })

  describe('getCurrentUser', () => {
    it('returns null when no user is stored', () => {
      localStorageMock.getItem.mockReturnValue(null)
      expect(authService.getCurrentUser()).toBe(null)
    })

    it('returns null when invalid user data is stored', () => {
      localStorageMock.getItem.mockReturnValue('invalid-json')
      expect(authService.getCurrentUser()).toBe(null)
    })

    it('returns user when valid user data is stored', () => {
      const userData = {
        id: 1,
        email: 'test@example.com',
        firstName: 'John',
        lastName: 'Doe'
      }
      localStorageMock.getItem.mockReturnValue(JSON.stringify(userData))
      
      const user = authService.getCurrentUser()
      expect(user).toEqual(userData)
    })
  })

  describe('getAuthToken', () => {
    it('returns stored token', () => {
      const token = 'test-jwt-token'
      localStorageMock.getItem.mockReturnValue(token)
      
      expect(authService.getAuthToken()).toBe(token)
    })
  })

  describe('logout', () => {
    it('removes auth token and user data from localStorage', () => {
      authService.logout()
      
      expect(localStorageMock.removeItem).toHaveBeenCalledWith('authToken')
      expect(localStorageMock.removeItem).toHaveBeenCalledWith('user')
      expect(localStorageMock.removeItem).toHaveBeenCalledWith('token')
    })
  })

  describe('getAuthHeaders', () => {
    it('returns headers with Authorization when token exists', () => {
      const token = 'test-jwt-token'
      localStorageMock.getItem.mockReturnValue(token)
      
      const headers = authService.getAuthHeaders()
      
      expect(headers).toEqual({
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      })
    })

    it('returns headers without Authorization when no token exists', () => {
      localStorageMock.getItem.mockReturnValue(null)
      
      const headers = authService.getAuthHeaders()
      
      expect(headers).toEqual({
        'Content-Type': 'application/json'
      })
    })
  })

  describe('cleanupOldTokens', () => {
    it('removes old fake token', () => {
      localStorageMock.getItem.mockReturnValue('fake-jwt-token-123')
      
      authService.cleanupOldTokens()
      
      expect(localStorageMock.removeItem).toHaveBeenCalledWith('token')
    })

    it('removes corrupted user data', () => {
      localStorageMock.getItem.mockReturnValue('undefined')
      
      authService.cleanupOldTokens()
      
      expect(localStorageMock.removeItem).toHaveBeenCalledWith('user')
    })
  })
})
