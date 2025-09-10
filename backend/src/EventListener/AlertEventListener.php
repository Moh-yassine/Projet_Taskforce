<?php

namespace App\EventListener;

use App\Service\AlertService;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
class AlertEventListener
{
    public function __construct(
        private AlertService $alertService
    ) {}

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        
        // Déclencher les alertes pour les nouvelles tâches et assignations
        if ($entity instanceof \App\Entity\Task || $entity instanceof \App\Entity\TaskAssignment) {
            $this->triggerAlerts();
        }
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        
        // Déclencher les alertes pour les tâches et assignations mises à jour
        if ($entity instanceof \App\Entity\Task || $entity instanceof \App\Entity\TaskAssignment) {
            $this->triggerAlerts();
        }
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        
        // Déclencher les alertes pour les tâches et assignations supprimées
        if ($entity instanceof \App\Entity\Task || $entity instanceof \App\Entity\TaskAssignment) {
            $this->triggerAlerts();
        }
    }

    private function triggerAlerts(): void
    {
        try {
            // Vérifier et générer les alertes automatiquement
            $this->alertService->checkAndGenerateAllAlerts();
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas faire échouer l'opération
            error_log('Erreur lors de la génération automatique des alertes: ' . $e->getMessage());
        }
    }
}
