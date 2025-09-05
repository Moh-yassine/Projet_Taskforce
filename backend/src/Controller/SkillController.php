<?php

namespace App\Controller;

use App\Entity\Skill;
use App\Repository\SkillRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/skills')]
class SkillController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SkillRepository $skillRepository,
        private SerializerInterface $serializer
    ) {}

    #[Route('', name: 'api_skills_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $skills = $this->skillRepository->findAll();
        $data = $this->serializer->serialize($skills, 'json', ['groups' => 'skill:read']);
        return new JsonResponse($data, 200, [], true);
    }

    #[Route('', name: 'api_skills_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || empty($data['name'])) {
            return $this->json(['message' => 'Le nom de la compétence est requis'], Response::HTTP_BAD_REQUEST);
        }

        $skill = new Skill();
        $skill->setName($data['name']);
        $skill->setDescription($data['description'] ?? '');

        $this->entityManager->persist($skill);
        $this->entityManager->flush();

        $data = $this->serializer->serialize($skill, 'json', ['groups' => 'skill:read']);
        return new JsonResponse($data, Response::HTTP_CREATED, [], true);
    }

    #[Route('/{id}', name: 'api_skills_show', methods: ['GET'])]
    public function show(Skill $skill): JsonResponse
    {
        $data = $this->serializer->serialize($skill, 'json', ['groups' => 'skill:read']);
        return new JsonResponse($data, 200, [], true);
    }

    #[Route('/{id}', name: 'api_skills_update', methods: ['PUT'])]
    public function update(Skill $skill, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $skill->setName($data['name']);
        }
        if (isset($data['description'])) {
            $skill->setDescription($data['description']);
        }

        $this->entityManager->flush();

        $data = $this->serializer->serialize($skill, 'json', ['groups' => 'skill:read']);
        return new JsonResponse($data, 200, [], true);
    }

    #[Route('/{id}', name: 'api_skills_delete', methods: ['DELETE'])]
    public function delete(Skill $skill): JsonResponse
    {
        $this->entityManager->remove($skill);
        $this->entityManager->flush();

        return $this->json(['message' => 'Compétence supprimée avec succès']);
    }
}
