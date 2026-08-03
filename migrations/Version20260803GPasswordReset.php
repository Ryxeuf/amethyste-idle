<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ONB-02 — le mot de passe oublie.
 *
 * Une demande par compte (index unique sur user_id : la nouvelle remplace
 * l'ancienne), le jeton stocke hache (seul sha256 du verificateur est en
 * base), l'expiration lue a la validation — jamais nettoyee par un cron.
 */
final class Version20260803GPasswordReset extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ONB-02: password reset requests (hashed token, one per account)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS password_reset_requests (
            id SERIAL NOT NULL,
            user_id INT NOT NULL,
            selector VARCHAR(24) NOT NULL,
            hashed_verifier VARCHAR(64) NOT NULL,
            requested_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('COMMENT ON COLUMN password_reset_requests.requested_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN password_reset_requests.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_password_reset_user ON password_reset_requests (user_id)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_password_reset_selector ON password_reset_requests (selector)');
        $this->addSql('DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'fk_password_reset_user\') THEN
                ALTER TABLE password_reset_requests
                    ADD CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id)
                    REFERENCES "users" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
            END IF;
        END $$');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS password_reset_requests');
    }
}
