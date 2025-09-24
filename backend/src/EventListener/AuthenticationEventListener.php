<?php

namespace App\EventListener;

use App\Service\SecurityLogService;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsEventListener(event: 'lexik_jwt_authentication.on_authentication_success', method: 'onAuthenticationSuccess')]
#[AsEventListener(event: 'lexik_jwt_authentication.on_authentication_failure', method: 'onAuthenticationFailure')]
#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_authenticated', method: 'onJWTAuthenticated')]
#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_expired', method: 'onJWTExpired')]
#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_invalid', method: 'onJWTInvalid')]
#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_not_found', method: 'onJWTNotFound')]
class AuthenticationEventListener
{
    public function __construct(
        private SecurityLogService $securityLogService,
        private RequestStack $requestStack
    ) {}

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $user = $event->getUser();
        if ($user && method_exists($user, 'getEmail')) {
            $this->securityLogService->logSuccessfulLogin(
                $user->getEmail(),
                $this->securityLogService->getClientIpAddress($request),
                $this->securityLogService->getClientUserAgent($request)
            );
        }
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        // Essayer d'extraire l'email de la requête
        $email = $this->extractEmailFromRequest($request);
        
        $this->securityLogService->logFailedLogin(
            $email ?: 'unknown',
            $this->securityLogService->getClientIpAddress($request),
            $this->securityLogService->getClientUserAgent($request),
            'Token JWT invalide ou expiré'
        );
    }

    public function onJWTAuthenticated(JWTAuthenticatedEvent $event): void
    {
        // Token JWT validé avec succès
        // Ceci est appelé à chaque requête authentifiée
    }

    public function onJWTExpired(JWTExpiredEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $this->securityLogService->logExpiredSession(
            'unknown', // L'email n'est pas disponible dans ce contexte
            $this->securityLogService->getClientIpAddress($request),
            $this->securityLogService->getClientUserAgent($request),
            $request->getPathInfo()
        );
    }

    public function onJWTInvalid(JWTInvalidEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $this->securityLogService->logUnauthorizedAccess(
            'unknown',
            $this->securityLogService->getClientIpAddress($request),
            $this->securityLogService->getClientUserAgent($request),
            $request->getPathInfo()
        );
    }

    public function onJWTNotFound(JWTNotFoundEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $this->securityLogService->logUnauthorizedAccess(
            'unknown',
            $this->securityLogService->getClientIpAddress($request),
            $this->securityLogService->getClientUserAgent($request),
            $request->getPathInfo()
        );
    }

    private function extractEmailFromRequest($request): ?string
    {
        // Essayer d'extraire l'email du body de la requête
        $content = $request->getContent();
        if ($content) {
            $data = json_decode($content, true);
            if (isset($data['email'])) {
                return $data['email'];
            }
        }

        // Essayer d'extraire l'email des paramètres de requête
        $email = $request->query->get('email');
        if ($email) {
            return $email;
        }

        return null;
    }
}
