<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261101120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE item SET location = 'home' WHERE kind = 'household'");
        $this->addSql("UPDATE item SET location = 'pantry' WHERE kind = 'ingredient' AND location = 'home'");
    }

    public function down(Schema $schema): void
    {
    }
}
