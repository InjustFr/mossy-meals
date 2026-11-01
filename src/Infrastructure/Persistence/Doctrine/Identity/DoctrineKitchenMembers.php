<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Identity;

use App\Application\Identity\KitchenMembers;
use App\Application\Identity\MemberView;
use App\Domain\Identity\User;
use App\Domain\Shared\Alphabetical;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineKitchenMembers implements KitchenMembers
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function all(): array
    {
        $users = $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->getQuery()
            ->getResult();

        return array_map(static fn (User $user): MemberView => new MemberView((string) $user->id(), $user->displayName()), Alphabetical::sort($users, static fn (User $user): string => $user->displayName(), static fn (User $user): string => (string) $user->id()));
    }
}
