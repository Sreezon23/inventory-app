<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260107171812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add writers, public inventories, custom item IDs, and optimistic locking';
    }

    public function up(Schema $schema): void
    {
        // Inventory writers (shared access)
        $this->addSql('CREATE TABLE inventory_writers (inventory_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY (inventory_id, user_id))');
        $this->addSql('CREATE INDEX IDX_592B2B239EEA759 ON inventory_writers (inventory_id)');
        $this->addSql('CREATE INDEX IDX_592B2B23A76ED395 ON inventory_writers (user_id)');
        $this->addSql('ALTER TABLE inventory_writers ADD CONSTRAINT FK_592B2B239EEA759 FOREIGN KEY (inventory_id) REFERENCES inventory (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE inventory_writers ADD CONSTRAINT FK_592B2B23A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE');

        // Inventory public flag (SAFE ADD)
        $this->addSql('ALTER TABLE inventory ADD is_public BOOLEAN DEFAULT FALSE');
        $this->addSql('UPDATE inventory SET is_public = FALSE WHERE is_public IS NULL');
        $this->addSql('ALTER TABLE inventory ALTER is_public SET NOT NULL');

        // Inventory optimistic locking
        $this->addSql('ALTER TABLE inventory ALTER version SET DEFAULT 1');

        // Item custom ID + optimistic locking
        $this->addSql('ALTER TABLE item ADD custom_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE item ADD version INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE item ALTER likes DROP NOT NULL');
        $this->addSql('ALTER TABLE item ALTER inventory_id SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Rollback writers
        $this->addSql('ALTER TABLE inventory_writers DROP CONSTRAINT FK_592B2B239EEA759');
        $this->addSql('ALTER TABLE inventory_writers DROP CONSTRAINT FK_592B2B23A76ED395');
        $this->addSql('DROP TABLE inventory_writers');

        // Rollback inventory fields
        $this->addSql('ALTER TABLE inventory DROP is_public');
        $this->addSql('ALTER TABLE inventory ALTER version DROP DEFAULT');

        // Rollback item fields
        $this->addSql('ALTER TABLE item DROP custom_id');
        $this->addSql('ALTER TABLE item DROP version');
        $this->addSql('ALTER TABLE item ALTER likes SET NOT NULL');
        $this->addSql('ALTER TABLE item ALTER inventory_id DROP NOT NULL');
    }
}
