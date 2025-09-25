<?php

namespace App\Command;

use App\Repository\ColumnRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:initialize-columns',
    description: 'Initialize default columns for all projects',
)]
class InitializeColumnsCommand extends Command
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private ColumnRepository $columnRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $projects = $this->projectRepository->findAll();
        $initializedCount = 0;

        foreach ($projects as $project) {
            // Vérifier si le projet a déjà des colonnes
            $existingColumns = $this->columnRepository->findByProjectOrderedByPosition($project);
            
            if (empty($existingColumns)) {
                $this->columnRepository->createDefaultColumns($project);
                $initializedCount++;
                $io->writeln(sprintf('Colonnes initialisées pour le projet: %s (ID: %d)', $project->getName(), $project->getId()));
            }
        }

        $io->success(sprintf('Initialisation terminée. %d projets ont été initialisés avec des colonnes par défaut.', $initializedCount));

        return Command::SUCCESS;
    }
}
