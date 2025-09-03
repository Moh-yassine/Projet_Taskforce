<?php

namespace App\Controller;

use App\Entity\Board;
use App\Entity\BoardList;
use App\Entity\BoardCard;
use App\Repository\BoardRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/boards')]
class BoardController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BoardRepository $boardRepository,
        private UserRepository $userRepository
    ) {}

    #[Route('', name: 'api_boards_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(): JsonResponse
    {
        $user = $this->getUser();
        $boards = $this->boardRepository->findByUser($user);

        $boardsData = array_map(function (Board $board) {
            return [
                'id' => $board->getId(),
                'name' => $board->getName(),
                'description' => $board->getDescription(),
                'color' => $board->getColor(),
                'visibility' => $board->getVisibility(),
                'createdAt' => $board->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $board->getUpdatedAt()->format('Y-m-d H:i:s'),
                'lists' => $board->getLists()->map(function (BoardList $list) {
                    return [
                        'id' => $list->getId(),
                        'name' => $list->getName(),
                        'position' => $list->getPosition(),
                        'cards' => $list->getCards()->map(function (BoardCard $card) {
                            return [
                                'id' => $card->getId(),
                                'title' => $card->getTitle(),
                                'description' => $card->getDescription(),
                                'position' => $card->getPosition()
                            ];
                        })->toArray()
                    ];
                })->toArray()
            ];
        }, $boards);

        return $this->json($boardsData);
    }

    #[Route('', name: 'api_boards_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        $board = new Board();
        $board->setName($data['name']);
        $board->setDescription($data['description'] ?? '');
        $board->setColor($data['color'] ?? '#0079bf');
        $board->setVisibility($data['visibility'] ?? 'private');
        $board->setCreatedBy($user);
        $board->setWorkspace($user->getCompany());

        $this->entityManager->persist($board);
        $this->entityManager->flush();

        return $this->json([
            'id' => $board->getId(),
            'name' => $board->getName(),
            'description' => $board->getDescription(),
            'color' => $board->getColor(),
            'visibility' => $board->getVisibility(),
            'createdAt' => $board->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $board->getUpdatedAt()->format('Y-m-d H:i:s')
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_boards_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(Board $board): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$this->canAccessBoard($board, $user)) {
            return $this->json(['message' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'id' => $board->getId(),
            'name' => $board->getName(),
            'description' => $board->getDescription(),
            'color' => $board->getColor(),
            'visibility' => $board->getVisibility(),
            'createdAt' => $board->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $board->getUpdatedAt()->format('Y-m-d H:i:s'),
            'lists' => $board->getLists()->map(function (BoardList $list) {
                return [
                    'id' => $list->getId(),
                    'name' => $list->getName(),
                    'position' => $list->getPosition(),
                    'cards' => $list->getCards()->map(function (BoardCard $card) {
                        return [
                            'id' => $card->getId(),
                            'title' => $card->getTitle(),
                            'description' => $card->getDescription(),
                            'position' => $card->getPosition()
                        ];
                    })->toArray()
                ];
            })->toArray()
        ]);
    }

    #[Route('/{id}', name: 'api_boards_update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(Request $request, Board $board): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$this->canAccessBoard($board, $user)) {
            return $this->json(['message' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $board->setName($data['name']);
        }
        if (isset($data['description'])) {
            $board->setDescription($data['description']);
        }
        if (isset($data['color'])) {
            $board->setColor($data['color']);
        }
        if (isset($data['visibility'])) {
            $board->setVisibility($data['visibility']);
        }

        $this->entityManager->flush();

        return $this->json([
            'id' => $board->getId(),
            'name' => $board->getName(),
            'description' => $board->getDescription(),
            'color' => $board->getColor(),
            'visibility' => $board->getVisibility(),
            'updatedAt' => $board->getUpdatedAt()->format('Y-m-d H:i:s')
        ]);
    }

    #[Route('/{id}', name: 'api_boards_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Board $board): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$this->canAccessBoard($board, $user)) {
            return $this->json(['message' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $this->entityManager->remove($board);
        $this->entityManager->flush();

        return $this->json(['message' => 'Tableau supprimé avec succès']);
    }

    private function canAccessBoard(Board $board, $user): bool
    {
        if ($board->getVisibility() === 'public') {
            return true;
        }

        if ($board->getVisibility() === 'workspace' && $board->getWorkspace() === $user->getCompany()) {
            return true;
        }

        if ($board->getVisibility() === 'private' && $board->getCreatedBy() === $user) {
            return true;
        }

        return false;
    }
}
