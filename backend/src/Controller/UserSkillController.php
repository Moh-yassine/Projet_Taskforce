<?php

namespace App\Controller;

use App\Entity\UserSkill;
use App\Repository\UserSkillRepository;
use App\Repository\UserRepository;
use App\Repository\SkillRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/user-skills')]
final class UserSkillController extends AbstractController
{
    public function __construct(
        private UserSkillRepository $userSkillRepository,
        private UserRepository $userRepository,
        private SkillRepository $skillRepository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer
    ) {}

    #[Route('', name: 'user_skills_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $userSkills = $this->userSkillRepository->findAll();
        
        return $this->json($userSkills, 200, [], ['groups' => ['user_skill:read']]);
    }

    #[Route('/user/{userId}', name: 'user_skills_by_user', methods: ['GET'])]
    public function getByUser(int $userId): JsonResponse
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $userSkills = $this->userSkillRepository->findBy(['user' => $user]);
        
        return $this->json($userSkills, 200, [], ['groups' => ['user_skill:read']]);
    }

    #[Route('', name: 'user_skill_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $user = $this->userRepository->find($data['userId'] ?? null);
        $skill = $this->skillRepository->find($data['skillId'] ?? null);
        
        if (!$user || !$skill) {
            return $this->json(['error' => 'User or Skill not found'], 404);
        }

        // Vérifier si la relation existe déjà
        $existingUserSkill = $this->userSkillRepository->findOneBy([
            'user' => $user,
            'skill' => $skill
        ]);

        if ($existingUserSkill) {
            return $this->json(['error' => 'User skill already exists'], 400);
        }

        $userSkill = new UserSkill();
        $userSkill->setUser($user);
        $userSkill->setSkill($skill);
        $userSkill->setLevel($data['level'] ?? 1);
        $userSkill->setExperience($data['experience'] ?? 0);

        $this->entityManager->persist($userSkill);
        $this->entityManager->flush();

        return $this->json($userSkill, 201, [], ['groups' => ['user_skill:read']]);
    }

    #[Route('/{id}', name: 'user_skill_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $userSkill = $this->userSkillRepository->find($id);
        if (!$userSkill) {
            return $this->json(['error' => 'User skill not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        
        if (isset($data['level'])) {
            $userSkill->setLevel($data['level']);
        }
        
        if (isset($data['experience'])) {
            $userSkill->setExperience($data['experience']);
        }

        $this->entityManager->flush();

        return $this->json($userSkill, 200, [], ['groups' => ['user_skill:read']]);
    }

    #[Route('/{id}', name: 'user_skill_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $userSkill = $this->userSkillRepository->find($id);
        if (!$userSkill) {
            return $this->json(['error' => 'User skill not found'], 404);
        }

        $this->entityManager->remove($userSkill);
        $this->entityManager->flush();

        return $this->json(['message' => 'User skill deleted successfully']);
    }
}
