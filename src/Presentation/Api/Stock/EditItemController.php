<?php

declare(strict_types=1);

namespace App\Presentation\Api\Stock;

use App\Application\Stock\EditItem\EditItem;
use App\Application\Stock\EditItem\EditItemHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Ulid;

#[Route('/api/items/{id}', name: 'api_items_edit', requirements: ['id' => Requirement::ULID], methods: ['PUT'], format: 'json')]
final class EditItemController extends AbstractController
{
    public function __invoke(Ulid $id, #[MapRequestPayload] ItemPayload $payload, EditItemHandler $editItem): JsonResponse
    {
        return $this->json($editItem(new EditItem($id, $payload->name, $payload->location, $payload->unit, $payload->manual, $payload->lowAt, $payload->months, null === $payload->variantOf ? null : Ulid::fromString($payload->variantOf), $payload->alert, $payload->kind)));
    }
}
