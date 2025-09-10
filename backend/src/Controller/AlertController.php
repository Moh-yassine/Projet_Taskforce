<?php

namespace App\Controller;

use App\Service\AlertService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/alerts')]
#[IsGranted('ROLE_USER')]
final class AlertController extends AbstractController
{
    public function __construct(
        private AlertService $alertService
    ) {}

    #[Route('/check-workload', name: 'check_workload_alerts', methods: ['POST'])]
    public function checkWorkloadAlerts(): JsonResponse
    {
        try {
            $alerts = $this->alertService->checkAndGenerateWorkloadAlerts();
            
            return $this->json([
                'message' => sprintf('%d alertes de surcharge générées', count($alerts)),
                'alerts' => $alerts
            ], 200, [], ['groups' => ['notification:read']]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la vérification des alertes de surcharge',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/check-delays', name: 'check_delay_alerts', methods: ['POST'])]
    public function checkDelayAlerts(): JsonResponse
    {
        try {
            $alerts = $this->alertService->checkAndGenerateDelayAlerts();
            
            return $this->json([
                'message' => sprintf('%d alertes de retard générées', count($alerts)),
                'alerts' => $alerts
            ], 200, [], ['groups' => ['notification:read']]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la vérification des alertes de retard',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/cleanup', name: 'cleanup_old_alerts', methods: ['POST'])]
    public function cleanupOldAlerts(): JsonResponse
    {
        try {
            $count = $this->alertService->cleanupOldAlerts();
            
            return $this->json([
                'message' => sprintf('%d anciennes alertes supprimées', $count)
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors du nettoyage des alertes',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
