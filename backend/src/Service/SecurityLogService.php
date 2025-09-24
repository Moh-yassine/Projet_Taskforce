<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class SecurityLogService
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    /**
     * Log une tentative de connexion réussie
     */
    public function logSuccessfulLogin(string $email, string $ipAddress, string $userAgent): void
    {
        $logData = [
            'event' => 'login_success',
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        
        $this->logger->info('Connexion réussie', $logData);
        $this->writeToFile('Connexion réussie', $logData);
    }

    /**
     * Log une tentative de connexion échouée
     */
    public function logFailedLogin(string $email, string $ipAddress, string $userAgent, string $reason = 'Invalid credentials'): void
    {
        $logData = [
            'event' => 'login_failed',
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'reason' => $reason,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        
        $this->logger->warning('Tentative de connexion échouée', $logData);
        $this->writeToFile('Tentative de connexion échouée', $logData);
    }

    /**
     * Log une déconnexion
     */
    public function logLogout(string $email, string $ipAddress, string $userAgent): void
    {
        $logData = [
            'event' => 'logout',
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        
        $this->logger->info('Déconnexion', $logData);
        $this->writeToFile('Déconnexion', $logData);
    }

    /**
     * Log une tentative d'accès non autorisé
     */
    public function logUnauthorizedAccess(string $email, string $ipAddress, string $userAgent, string $endpoint): void
    {
        $this->logger->warning('Tentative d\'accès non autorisé', [
            'event' => 'unauthorized_access',
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'endpoint' => $endpoint,
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    /**
     * Log une session expirée
     */
    public function logExpiredSession(string $email, string $ipAddress, string $userAgent, string $endpoint): void
    {
        $this->logger->info('Session expirée', [
            'event' => 'session_expired',
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'endpoint' => $endpoint,
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    /**
     * Log une inscription réussie
     */
    public function logSuccessfulRegistration(string $email, string $ipAddress, string $userAgent): void
    {
        $this->logger->info('Inscription réussie', [
            'event' => 'registration_success',
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    /**
     * Log une tentative d'inscription échouée
     */
    public function logFailedRegistration(string $email, string $ipAddress, string $userAgent, string $reason): void
    {
        $this->logger->warning('Tentative d\'inscription échouée', [
            'event' => 'registration_failed',
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'reason' => $reason,
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    /**
     * Log une activité suspecte
     */
    public function logSuspiciousActivity(string $email, string $ipAddress, string $userAgent, string $activity, array $details = []): void
    {
        $this->logger->error('Activité suspecte détectée', [
            'event' => 'suspicious_activity',
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'activity' => $activity,
            'details' => $details,
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    /**
     * Extraire l'adresse IP de la requête
     */
    public function getClientIpAddress(Request $request): string
    {
        $ip = $request->getClientIp();
        
        // Si l'IP est localhost, essayer de récupérer l'IP réelle
        if ($ip === '127.0.0.1' || $ip === '::1') {
            $forwardedFor = $request->headers->get('X-Forwarded-For');
            if ($forwardedFor) {
                $ips = explode(',', $forwardedFor);
                $ip = trim($ips[0]);
            }
        }
        
        return $ip ?: 'unknown';
    }

    /**
     * Extraire le User-Agent de la requête
     */
    public function getClientUserAgent(Request $request): string
    {
        return $request->headers->get('User-Agent', 'unknown');
    }

    /**
     * Écrire dans le fichier de log de sécurité
     */
    private function writeToFile(string $message, array $data): void
    {
        $logFile = 'var/log/security.log';
        $logDir = dirname($logFile);
        
        // Créer le répertoire s'il n'existe pas
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logEntry = sprintf(
            "[%s] %s - %s - IP: %s - User-Agent: %s\n",
            $data['timestamp'],
            $data['event'],
            $message,
            $data['ip_address'],
            $data['user_agent']
        );
        
        // Ajouter des détails supplémentaires si disponibles
        if (isset($data['reason'])) {
            $logEntry = str_replace("\n", " - Raison: " . $data['reason'] . "\n", $logEntry);
        }
        if (isset($data['endpoint'])) {
            $logEntry = str_replace("\n", " - Endpoint: " . $data['endpoint'] . "\n", $logEntry);
        }
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
