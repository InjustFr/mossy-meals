<?php

declare(strict_types=1);

namespace App\Presentation\Api\Stock;

use App\Application\Stock\AddItem\AddItem;
use App\Application\Stock\AddItem\AddItemHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Ulid;

#[Route('/api/items', name: 'api_items_add', methods: ['POST'], format: 'json')]
final class AddItemController extends AbstractController
{
    public function __invoke(#[MapRequestPayload] AddItemPayload $payload, AddItemHandler $addItem): JsonResponse
    {
        return $this->json($addItem(new AddItem($payload->name, $payload->location, $payload->unit, $payload->manual, $payload->lowAt, $payload->stock, $payload->months, null === $payload->variantOf ? null : Ulid::fromString($payload->variantOf), $payload->alert, $payload->kind)), Response::HTTP_CREATED);
    }
}
