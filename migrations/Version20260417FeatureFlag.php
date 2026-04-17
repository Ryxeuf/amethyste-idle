<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260417FeatureFlag extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add feature_flag table and feature_flag_user join table for per-user feature flag activation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS feature_flag (
            id SERIAL NOT NULL,
            slug VARCHAR(100) NOT NULL,
            name VARCHAR(150) NOT NULL,
            description TEXT DEFAULT NULL,
            enabled BOOLEAN DEFAULT FALSE NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_feature_flag_slug ON feature_flag (slug)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_feature_flag_slug ON feature_flag (slug)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_feature_flag_enabled ON feature_flag (enabled)');

        $this->addSql('CREATE TABLE IF NOT EXISTS feature_flag_user (
            feature_flag_id INT NOT NULL,
            user_id INT NOT NULL,
            PRIMARY KEY(feature_flag_id, user_id)
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_feature_flag_user_flag ON feature_flag_user (feature_flag_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_feature_flag_user_user ON feature_flag_user (user_id)');

        $this->addSql('DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'fk_feature_flag_user_flag\') THEN
                ALTER TABLE feature_flag_user ADD CONSTRAINT fk_feature_flag_user_flag FOREIGN KEY (feature_flag_id) REFERENCES feature_flag (id) ON DELETE CASCADE;
            END IF;
        END $$');
        $this->addSql('DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'fk_feature_flag_user_user\') THEN
                ALTER TABLE feature_flag_user ADD CONSTRAINT fk_feature_flag_user_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE;
            END IF;
        END $$');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS feature_flag_user');
        $this->addSql('DROP TABLE IF EXISTS feature_flag');
    }
}
