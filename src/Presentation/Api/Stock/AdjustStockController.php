<?php

declare(strict_types=1);

namespace App\Presentation\Api\Stock;

use App\Application\Stock\AdjustStock\AdjustStock;
use App\Application\Stock\AdjustStock\AdjustStockHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Ulid;

#[Route('/api/items/{id}/adjust', name: 'api_items_adjust', requirements: ['id' => Requirement::ULID], methods: ['POST'], format: 'json')]
final class AdjustStockController extends AbstractController
{
    public function __invoke(Ulid $id, #[MapRequestPayload] AdjustStockPayload $payload, AdjustStockHandler $adjustStock): JsonResponse
    {
        return $this->json($adjustStock(new AdjustStock($id, $payload->by)));
    }
}
