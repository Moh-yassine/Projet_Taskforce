<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:initialize-roles',
    description: 'Initialize roles for existing users',
)]
class InitializeRolesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Initialisation des rôles utilisateurs');

        $users = $this->userRepository->findAll();
        $updatedCount = 0;

        foreach ($users as $user) {
            $currentRoles = $user->getRoles();
            
            // Si l'utilisateur n'a que ROLE_USER, ajouter ROLE_COLLABORATOR
            if (count($currentRoles) === 1 && in_array('ROLE_USER', $currentRoles)) {
                $user->setRoles(['ROLE_COLLABORATOR', 'ROLE_USER']);
                $this->entityManager->persist($user);
                $updatedCount++;
                
                $io->text(sprintf(
                    'Utilisateur %s (%s) - Rôle mis à jour vers: Collaborateur',
                    $user->getFullName(),
                    $user->getEmail()
                ));
            } else {
                $io->text(sprintf(
                    'Utilisateur %s (%s) - Rôles existants: %s',
                    $user->getFullName(),
                    $user->getEmail(),
                    implode(', ', $currentRoles)
                ));
            }
        }

        if ($updatedCount > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('%d utilisateur(s) mis à jour avec succès', $updatedCount));
        } else {
            $io->info('Aucun utilisateur à mettre à jour');
        }

        return Command::SUCCESS;
    }
}
