<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323AddMapWeather extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add weather columns (current_weather, weather_changed_at) to map table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE map ADD COLUMN IF NOT EXISTS current_weather VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE map ADD COLUMN IF NOT EXISTS weather_changed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql("COMMENT ON COLUMN map.weather_changed_at IS '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE map DROP COLUMN IF EXISTS current_weather');
        $this->addSql('ALTER TABLE map DROP COLUMN IF EXISTS weather_changed_at');
    }
}
