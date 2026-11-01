<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261101100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'The stock holds items of a kind, ingredient or household, and home is a place';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ingredient RENAME TO item');
        $this->addSql('ALTER INDEX ingredient_pkey RENAME TO item_pkey');
        $this->addSql('ALTER INDEX idx_6baf7870da873c21 RENAME TO IDX_1F1B251EDA873C21');
        $this->addSql('ALTER TABLE item RENAME CONSTRAINT fk_6baf7870da873c21 TO FK_1F1B251EDA873C21');
        $this->addSql('ALTER TABLE grocery_item RENAME COLUMN ingredient_id TO item_id');
        $this->addSql('ALTER INDEX uniq_8f9edb8a3dae168b933fe08c RENAME TO UNIQ_8F9EDB8A3DAE168B126F525E');
        $this->addSql('ALTER INDEX idx_8f9edb8a933fe08c RENAME TO IDX_8F9EDB8A126F525E');
        $this->addSql('ALTER TABLE grocery_item RENAME CONSTRAINT fk_8f9edb8a933fe08c TO FK_8F9EDB8A126F525E');
        $this->addSql("ALTER TABLE item ADD kind VARCHAR(16) DEFAULT 'ingredient' NOT NULL");
        $this->addSql("UPDATE item SET kind = 'household' WHERE location = 'household'");
        $this->addSql("UPDATE item SET location = 'home' WHERE location = 'household'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE item SET location = 'household' WHERE kind = 'household'");
        $this->addSql('ALTER TABLE item DROP kind');
        $this->addSql('ALTER TABLE grocery_item RENAME CONSTRAINT FK_8F9EDB8A126F525E TO fk_8f9edb8a933fe08c');
        $this->addSql('ALTER INDEX IDX_8F9EDB8A126F525E RENAME TO idx_8f9edb8a933fe08c');
        $this->addSql('ALTER INDEX UNIQ_8F9EDB8A3DAE168B126F525E RENAME TO uniq_8f9edb8a3dae168b933fe08c');
        $this->addSql('ALTER TABLE grocery_item RENAME COLUMN item_id TO ingredient_id');
        $this->addSql('ALTER TABLE item RENAME CONSTRAINT FK_1F1B251EDA873C21 TO fk_6baf7870da873c21');
        $this->addSql('ALTER INDEX IDX_1F1B251EDA873C21 RENAME TO idx_6baf7870da873c21');
        $this->addSql('ALTER INDEX item_pkey RENAME TO ingredient_pkey');
        $this->addSql('ALTER TABLE item RENAME TO ingredient');
    }
}
