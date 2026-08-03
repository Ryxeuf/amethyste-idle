<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ONB-04 — la verification differee et sa porte.
 *
 * Deux gestes, et le second est la loi la plus importante du jalon :
 *
 * - la table des jetons de verification (meme anatomie que le mot de passe
 *   oublie : un actif par compte, stocke hache) et le compteur de rappels ;
 * - **aucun blocage retroactif** — tout compte ne a AVANT la porte est marque
 *   verifie a la livraison. La porte ne vaut que pour les comptes qui
 *   naissent apres elle : personne ne perd un acces qu'il avait la veille.
 */
final class Version20260803HEmailVerification extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ONB-04: email verification requests, reminder counter, and grandfathering (no retroactive gate)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS email_verification_requests (
            id SERIAL NOT NULL,
            user_id INT NOT NULL,
            selector VARCHAR(24) NOT NULL,
            hashed_verifier VARCHAR(64) NOT NULL,
            requested_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('COMMENT ON COLUMN email_verification_requests.requested_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN email_verification_requests.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_email_verification_user ON email_verification_requests (user_id)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_email_verification_selector ON email_verification_requests (selector)');
        $this->addSql('DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'fk_email_verification_user\') THEN
                ALTER TABLE email_verification_requests
                    ADD CONSTRAINT fk_email_verification_user FOREIGN KEY (user_id)
                    REFERENCES "users" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
            END IF;
        END $$');

        $this->addSql('ALTER TABLE "users" ADD COLUMN IF NOT EXISTS verification_reminder_count SMALLINT DEFAULT 0 NOT NULL');

        // Aucun blocage retroactif : les comptes d'avant la porte sont
        // verifies d'office. La porte ne s'applique qu'aux comptes qui
        // naissent apres cette migration.
        $this->addSql('UPDATE "users" SET email_verified_at = NOW() WHERE email_verified_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS email_verification_requests');
        $this->addSql('ALTER TABLE "users" DROP COLUMN IF EXISTS verification_reminder_count');
    }
}
