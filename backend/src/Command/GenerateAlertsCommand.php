<?php

namespace App\Command;

use App\Service\AlertService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-alerts',
    description: 'Vérifie et génère automatiquement les alertes de surcharge et de retard',
)]
class GenerateAlertsCommand extends Command
{
    public function __construct(
        private AlertService $alertService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Vérification des alertes automatiques');

        try {
            // Vérifier les alertes de surcharge
            $io->section('Vérification des alertes de surcharge de travail');
            $workloadAlerts = $this->alertService->checkAndGenerateWorkloadAlerts();
            $io->success(sprintf('%d alertes de surcharge générées', count($workloadAlerts)));

            // Vérifier les alertes de retard
            $io->section('Vérification des alertes de retard de tâches');
            $delayAlerts = $this->alertService->checkAndGenerateDelayAlerts();
            $io->success(sprintf('%d alertes de retard générées', count($delayAlerts)));

            // Nettoyer les anciennes alertes
            $io->section('Nettoyage des anciennes alertes');
            $cleanedCount = $this->alertService->cleanupOldAlerts();
            $io->success(sprintf('%d anciennes alertes supprimées', $cleanedCount));

            $totalAlerts = count($workloadAlerts) + count($delayAlerts);
            $io->success(sprintf('Vérification terminée: %d alertes au total', $totalAlerts));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de la vérification des alertes: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
