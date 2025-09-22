/**
 * Utilitaires de sécurité pour le frontend
 */

/**
 * Configuration CSP (Content Security Policy) pour le frontend
 * Ces directives doivent correspondre à celles configurées côté serveur
 */
export const CSP_CONFIG = {
  // Sources autorisées pour les scripts
  scriptSrc: [
    "'self'",
    "'unsafe-inline'", // Nécessaire pour Vue en développement
    "'unsafe-eval'", // Nécessaire pour Vue en développement
    'https://js.stripe.com',
  ],

  // Sources autorisées pour les styles
  styleSrc: [
    "'self'",
    "'unsafe-inline'", // Nécessaire pour les styles dynamiques
  ],

  // Sources autorisées pour les images
  imgSrc: ["'self'", 'data:', 'https:', 'blob:'],

  // Sources autorisées pour les connexions
  connectSrc: ["'self'", 'https://api.stripe.com', 'wss:', 'ws:'],

  // Sources autorisées pour les frames
  frameSrc: ["'self'", 'https://js.stripe.com'],
}

/**
 * Vérifie si l'application fonctionne en HTTPS
 */
export function isSecureContext(): boolean {
  // Si window.isSecureContext est défini, l'utiliser
  if (typeof window.isSecureContext !== 'undefined') {
    return window.isSecureContext
  }
  // Sinon, vérifier le protocole
  return location.protocol === 'https:'
}

/**
 * Force la redirection vers HTTPS si nécessaire
 */
export function enforceHTTPS(): void {
  if (!isSecureContext() && location.hostname !== 'localhost') {
    location.replace('https:' + window.location.href.substring(window.location.protocol.length))
  }
}

/**
 * Valide l'origine d'une URL pour la sécurité
 */
export function isValidOrigin(url: string): boolean {
  try {
    const urlObj = new URL(url)
    return ['http:', 'https:'].includes(urlObj.protocol)
  } catch {
    return false
  }
}

/**
 * Nettoie les données utilisateur pour éviter les injections
 */
export function sanitizeInput(input: string): string {
  return input
    .replace(/<[^>]*>/g, '') // Supprime toutes les balises HTML
    .replace(/javascript:/gi, '') // Supprime les URLs javascript
    .replace(/on\w+\s*=\s*"[^"]*"/gi, '') // Supprime les gestionnaires d'événements avec guillemets
    .replace(/on\w+\s*=\s*'[^']*'/gi, '') // Supprime les gestionnaires d'événements avec apostrophes
    .trim()
}

/**
 * Configuration des headers de sécurité pour les requêtes
 */
export function getSecurityHeaders(): Record<string, string> {
  return {
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    Accept: 'application/json',
  }
}

/**
 * Valide un token JWT basique (vérification de format)
 */
export function isValidJWTFormat(token: string): boolean {
  if (!token) return false

  const parts = token.split('.')
  return parts.length === 3 && parts.every((part) => part.length > 0)
}

/**
 * Configuration des paramètres de sécurité pour les cookies
 */
export function getSecureCookieOptions(): {
  secure: boolean
  sameSite: 'strict' | 'lax' | 'none'
  httpOnly: boolean
} {
  return {
    secure: isSecureContext(),
    sameSite: 'strict',
    httpOnly: false, // Pour les cookies accessibles via JavaScript
  }
}
