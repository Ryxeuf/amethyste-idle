<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260428WeeklyChallengeTranslations extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add title_translations and description_translations JSON columns to weekly_challenge (task 135 sous-phase 3e.p)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE weekly_challenge ADD COLUMN IF NOT EXISTS title_translations JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE weekly_challenge ADD COLUMN IF NOT EXISTS description_translations JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE weekly_challenge DROP COLUMN IF EXISTS title_translations');
        $this->addSql('ALTER TABLE weekly_challenge DROP COLUMN IF EXISTS description_translations');
    }
}
