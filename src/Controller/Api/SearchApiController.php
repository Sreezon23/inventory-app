<?php

namespace App\Controller\Api;

use App\Repository\InventoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/search', name: 'api_search_')]
class SearchApiController extends AbstractController
{
    #[Route('', name: 'inventories', methods: ['GET'])]
    public function searchInventories(Request $request, InventoryRepository $repo): JsonResponse
    {
        $query = $request->query->get('q', '');
        $category = $request->query->get('category', null);

        $qb = $repo->createQueryBuilder('i')
            ->leftJoin('i.creator', 'c')
            ->addSelect('c');

        if ($query) {
            $qb->andWhere('i.title LIKE :query OR i.description LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        if ($category) {
            $qb->andWhere('i.category = :category')
                ->setParameter('category', $category);
        }

        if ($this->getUser()) {
            $qb->andWhere('i.isPublic = true OR i.creator = :user')
                ->setParameter('user', $this->getUser());
        } else {
            $qb->andWhere('i.isPublic = true');
        }

        $inventories = $qb->orderBy('i.createdAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        return $this->json([
            'success' => true,
            'query' => $query,
            'count' => count($inventories),
            'data' => array_map(fn ($inv) => [
                'id' => $inv->getId(),
                'title' => $inv->getTitle(),
                'category' => $inv->getCategory(),
                'creator' => $inv->getCreator()?->getName(),
            ], $inventories),
        ]);
    }
}