<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE, priority: 1000)]
class SecurityHeadersListener
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        // Ne pas ajouter les headers pour les requêtes de développement si nécessaire
        if ($request->server->get('APP_ENV') === 'dev' && $request->server->get('APP_DEBUG')) {
            return;
        }

        // Headers de sécurité obligatoires
        $securityHeaders = [
            // Protection contre le clickjacking
            'X-Frame-Options' => 'SAMEORIGIN',
            
            // Protection contre le MIME sniffing
            'X-Content-Type-Options' => 'nosniff',
            
            // Protection XSS (pour les anciens navigateurs)
            'X-XSS-Protection' => '1; mode=block',
            
            // Politique de référent
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            
            // Permissions Policy (anciennement Feature Policy)
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), speaker=(), vibrate=(), fullscreen=(self), sync-xhr=()',
            
            // Cross-Origin Embedder Policy
            'Cross-Origin-Embedder-Policy' => 'require-corp',
            
            // Cross-Origin Opener Policy
            'Cross-Origin-Opener-Policy' => 'same-origin',
            
            // Cross-Origin Resource Policy
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ];

        // Headers HTTPS uniquement
        if ($request->isSecure()) {
            // HSTS - HTTP Strict Transport Security
            $securityHeaders['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains; preload';
        }

        // Content Security Policy - Configuration stricte
        $cspDirectives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://js.stripe.com",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https: blob:",
            "font-src 'self' data:",
            "connect-src 'self' https://api.stripe.com wss: ws:",
            "frame-src 'self' https://js.stripe.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "upgrade-insecure-requests"
        ];
        
        $securityHeaders['Content-Security-Policy'] = implode('; ', $cspDirectives);

        // Ajouter tous les headers de sécurité
        foreach ($securityHeaders as $header => $value) {
            $response->headers->set($header, $value);
        }

        // Headers de cache sécurisés pour les API
        if (str_starts_with($request->getPathInfo(), '/api')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        // Supprimer les headers qui révèlent des informations sensibles
        $response->headers->remove('Server');
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('X-Debug-Token');
        $response->headers->remove('X-Debug-Token-Link');
    }
}
