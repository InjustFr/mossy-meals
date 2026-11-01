<?php

declare(strict_types=1);

namespace App\Presentation\Api\Stock;

use App\Application\Stock\ItemQueries;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/items', name: 'api_items_list', methods: ['GET'], format: 'json')]
final class ListItemsController extends AbstractController
{
    public function __invoke(ItemQueries $items): JsonResponse
    {
        return $this->json($items->all());
    }
}
