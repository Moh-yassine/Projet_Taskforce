import { describe, it, expect } from 'vitest'
import { 
  sanitizeInput, 
  getSecurityHeaders, 
  isValidJWTFormat, 
  isSecureContext,
  getSecureCookieOptions 
} from '../security'

describe('Security Utils', () => {
  describe('sanitizeInput', () => {
    it('removes HTML tags', () => {
      expect(sanitizeInput('<script>alert("xss")</script>')).toBe('alert("xss")')
      expect(sanitizeInput('<div>Hello</div>')).toBe('Hello')
    })

    it('removes javascript: URLs', () => {
      expect(sanitizeInput('javascript:alert("xss")')).toBe('alert("xss")')
      expect(sanitizeInput('JAVASCRIPT:alert("xss")')).toBe('alert("xss")')
    })

    it('removes event handlers', () => {
      expect(sanitizeInput('onclick="alert(1)"')).toBe('')
      expect(sanitizeInput('onload="malicious()"')).toBe('')
    })

    it('trims whitespace', () => {
      expect(sanitizeInput('  hello  ')).toBe('hello')
    })

    it('handles normal text correctly', () => {
      expect(sanitizeInput('Hello World!')).toBe('Hello World!')
      expect(sanitizeInput('123')).toBe('123')
    })
  })

  describe('getSecurityHeaders', () => {
    it('returns correct security headers', () => {
      const headers = getSecurityHeaders()
      
      expect(headers).toEqual({
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      })
    })
  })

  describe('isValidJWTFormat', () => {
    it('validates correct JWT format', () => {
      const validJWT = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c'
      expect(isValidJWTFormat(validJWT)).toBe(true)
    })

    it('rejects invalid JWT formats', () => {
      expect(isValidJWTFormat('invalid.jwt')).toBe(false)
      expect(isValidJWTFormat('onlytwo.parts')).toBe(false)
      expect(isValidJWTFormat('')).toBe(false)
      expect(isValidJWTFormat('one')).toBe(false)
      expect(isValidJWTFormat('one.two.three.four')).toBe(false)
    })

    it('rejects null or undefined', () => {
      expect(isValidJWTFormat(null as any)).toBe(false)
      expect(isValidJWTFormat(undefined as any)).toBe(false)
    })
  })

  describe('isSecureContext', () => {
    it('detects HTTPS context', () => {
      // Mock window.isSecureContext
      Object.defineProperty(window, 'isSecureContext', {
        value: true,
        writable: true
      })
      
      expect(isSecureContext()).toBe(true)
    })

    it('detects HTTPS protocol', () => {
      // Mock location.protocol
      Object.defineProperty(window, 'location', {
        value: { protocol: 'https:' },
        writable: true
      })
      
      expect(isSecureContext()).toBe(true)
    })

    it('detects HTTP as insecure', () => {
      // Mock window.isSecureContext to false
      Object.defineProperty(window, 'isSecureContext', {
        value: false,
        writable: true
      })
      // Mock location.protocol to http
      Object.defineProperty(window, 'location', {
        value: { protocol: 'http:' },
        writable: true
      })
      
      expect(isSecureContext()).toBe(false)
    })
  })

  describe('getSecureCookieOptions', () => {
    it('returns secure options for HTTPS', () => {
      Object.defineProperty(window, 'isSecureContext', {
        value: true,
        writable: true
      })
      
      const options = getSecureCookieOptions()
      
      expect(options).toEqual({
        secure: true,
        sameSite: 'strict',
        httpOnly: false
      })
    })

    it('returns insecure options for HTTP', () => {
      Object.defineProperty(window, 'isSecureContext', {
        value: false,
        writable: true
      })
      
      const options = getSecureCookieOptions()
      
      expect(options).toEqual({
        secure: false,
        sameSite: 'strict',
        httpOnly: false
      })
    })
  })
})
