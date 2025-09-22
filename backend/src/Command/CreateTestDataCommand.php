<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\Skill;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-data',
    description: 'Créer des données de test pour le système d\'assignation de tâches',
)]
class CreateTestDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Création des données de test');

        try {
            // Créer des utilisateurs de test
            $this->createTestUsers($io);
            
            // Créer des compétences
            $this->createTestSkills($io);
            
            // Créer des projets de test
            $this->createTestProjects($io);
            
            // Créer des tâches de test
            $this->createTestTasks($io);

            $this->entityManager->flush();

            $io->success('Données de test créées avec succès !');
            $io->note('Vous pouvez maintenant tester le système d\'assignation de tâches.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de la création des données de test : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createTestUsers(SymfonyStyle $io): void
    {
        $io->section('Création des utilisateurs de test');

        $users = [
            [
                'email' => 'project.manager@test.com',
                'firstName' => 'Alice',
                'lastName' => 'Martin',
                'roles' => ['ROLE_PROJECT_MANAGER'],
                'password' => 'password123'
            ],
            [
                'email' => 'manager1@test.com',
                'firstName' => 'Bob',
                'lastName' => 'Dupont',
                'roles' => ['ROLE_MANAGER'],
                'password' => 'password123'
            ],
            [
                'email' => 'manager2@test.com',
                'firstName' => 'Claire',
                'lastName' => 'Bernard',
                'roles' => ['ROLE_MANAGER'],
                'password' => 'password123'
            ],
            [
                'email' => 'collaborator1@test.com',
                'firstName' => 'David',
                'lastName' => 'Moreau',
                'roles' => ['ROLE_COLLABORATOR'],
                'password' => 'password123'
            ],
            [
                'email' => 'collaborator2@test.com',
                'firstName' => 'Emma',
                'lastName' => 'Petit',
                'roles' => ['ROLE_COLLABORATOR'],
                'password' => 'password123'
            ],
            [
                'email' => 'collaborator3@test.com',
                'firstName' => 'François',
                'lastName' => 'Leroy',
                'roles' => ['ROLE_COLLABORATOR'],
                'password' => 'password123'
            ]
        ];

        foreach ($users as $userData) {
            // Vérifier si l'utilisateur existe déjà
            $existingUser = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => $userData['email']]);
            
            if ($existingUser) {
                $io->text("⚠ Utilisateur existe déjà : {$userData['firstName']} {$userData['lastName']}");
                continue;
            }

            $user = new User();
            $user->setEmail($userData['email']);
            $user->setFirstName($userData['firstName']);
            $user->setLastName($userData['lastName']);
            $user->setRoles($userData['roles']);
            
            $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $this->entityManager->flush(); // Flush immédiatement pour éviter les problèmes de référence
            
            $io->text("✓ Utilisateur créé : {$userData['firstName']} {$userData['lastName']} ({$userData['roles'][0]})");
        }
    }

    private function createTestSkills(SymfonyStyle $io): void
    {
        $io->section('Création des compétences de test');

        $skills = [
            ['name' => 'JavaScript', 'description' => 'Langage de programmation web'],
            ['name' => 'Vue.js', 'description' => 'Framework JavaScript'],
            ['name' => 'PHP', 'description' => 'Langage de programmation backend'],
            ['name' => 'Symfony', 'description' => 'Framework PHP'],
            ['name' => 'MySQL', 'description' => 'Base de données relationnelle'],
            ['name' => 'CSS', 'description' => 'Feuilles de style'],
            ['name' => 'HTML', 'description' => 'Langage de balisage'],
            ['name' => 'Git', 'description' => 'Système de contrôle de version'],
            ['name' => 'Docker', 'description' => 'Conteneurisation'],
            ['name' => 'API REST', 'description' => 'Architecture d\'API']
        ];

        foreach ($skills as $skillData) {
            $skill = new Skill();
            $skill->setName($skillData['name']);
            $skill->setDescription($skillData['description']);

            $this->entityManager->persist($skill);
            $io->text("✓ Compétence créée : {$skillData['name']}");
        }
    }

    private function createTestProjects(SymfonyStyle $io): void
    {
        $io->section('Création des projets de test');

        $projects = [
            [
                'name' => 'Application E-commerce',
                'description' => 'Développement d\'une plateforme de vente en ligne avec panier et paiement',
                'status' => 'in_progress',
                'startDate' => new \DateTimeImmutable('-30 days'),
                'endDate' => new \DateTimeImmutable('+60 days')
            ],
            [
                'name' => 'Système de Gestion',
                'description' => 'Application de gestion des tâches et des utilisateurs',
                'status' => 'in_progress',
                'startDate' => new \DateTimeImmutable('-15 days'),
                'endDate' => new \DateTimeImmutable('+45 days')
            ],
            [
                'name' => 'API Mobile',
                'description' => 'Développement d\'une API pour application mobile',
                'status' => 'planning',
                'startDate' => new \DateTimeImmutable('+7 days'),
                'endDate' => new \DateTimeImmutable('+90 days')
            ]
        ];

        // Récupérer le project manager
        $projectManager = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'project.manager@test.com']);

        foreach ($projects as $projectData) {
            $project = new Project();
            $project->setName($projectData['name']);
            $project->setDescription($projectData['description']);
            $project->setStatus($projectData['status']);
            $project->setStartDate($projectData['startDate']);
            $project->setEndDate($projectData['endDate']);
            $project->setProjectManager($projectManager);
            $project->setCreatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($project);
            $io->text("✓ Projet créé : {$projectData['name']}");
        }
    }

    private function createTestTasks(SymfonyStyle $io): void
    {
        $io->section('Création des tâches de test');

        // Récupérer les projets
        $projects = $this->entityManager->getRepository(Project::class)->findAll();
        $users = $this->entityManager->getRepository(User::class)->findAll();

        if (empty($projects) || empty($users)) {
            $io->warning('Aucun projet ou utilisateur trouvé. Créez d\'abord des projets et utilisateurs.');
            return;
        }

        $tasks = [
            [
                'title' => 'Créer l\'interface utilisateur',
                'description' => 'Développer les composants Vue.js pour l\'interface utilisateur',
                'priority' => 'high',
                'status' => 'todo',
                'estimatedHours' => 16
            ],
            [
                'title' => 'Implémenter l\'authentification',
                'description' => 'Créer le système d\'authentification JWT',
                'priority' => 'high',
                'status' => 'in_progress',
                'estimatedHours' => 12
            ],
            [
                'title' => 'Créer la base de données',
                'description' => 'Concevoir et implémenter le schéma de base de données',
                'priority' => 'medium',
                'status' => 'completed',
                'estimatedHours' => 8
            ],
            [
                'title' => 'Tests unitaires',
                'description' => 'Écrire les tests unitaires pour les fonctionnalités principales',
                'priority' => 'medium',
                'status' => 'todo',
                'estimatedHours' => 20
            ],
            [
                'title' => 'Documentation API',
                'description' => 'Rédiger la documentation de l\'API REST',
                'priority' => 'low',
                'status' => 'todo',
                'estimatedHours' => 6
            ],
            [
                'title' => 'Optimisation des performances',
                'description' => 'Optimiser les requêtes et améliorer les performances',
                'priority' => 'medium',
                'status' => 'todo',
                'estimatedHours' => 14
            ]
        ];

        foreach ($tasks as $index => $taskData) {
            $task = new Task();
            $task->setTitle($taskData['title']);
            $task->setDescription($taskData['description']);
            $task->setPriority($taskData['priority']);
            $task->setStatus($taskData['status']);
            $task->setEstimatedHours($taskData['estimatedHours']);
            $task->setCreatedAt(new \DateTimeImmutable());
            
            // Assigner à un projet aléatoire
            $task->setProject($projects[array_rand($projects)]);
            
            // Assigner certaines tâches à des utilisateurs
            if ($index < 3 && !empty($users)) {
                $task->setAssignee($users[array_rand($users)]);
            }

            $this->entityManager->persist($task);
            $io->text("✓ Tâche créée : {$taskData['title']}");
        }
    }
}
