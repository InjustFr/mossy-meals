<?php

declare(strict_types=1);

namespace App\Presentation\Api\Stock;

use App\Application\Stock\RemoveItem\RemoveItem;
use App\Application\Stock\RemoveItem\RemoveItemHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Ulid;

#[Route('/api/items/{id}', name: 'api_items_remove', requirements: ['id' => Requirement::ULID], methods: ['DELETE'], format: 'json')]
final class RemoveItemController extends AbstractController
{
    public function __invoke(Ulid $id, RemoveItemHandler $removeItem): Response
    {
        $removeItem(new RemoveItem($id));

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
