<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ONB-01 — le compte nait non verifie, et pleinement jouable.
 *
 * La verification d'e-mail est **differee** (decision A1, GAME_ONBOARDING §3.2) :
 * elle ne barre pas le jeu, elle barre le marche, les guildes et le chat. La
 * colonne est donc nullable et le reste — un compte sans date de verification
 * n'est pas un compte incomplet, c'est un compte qui n'a pas encore ouvert ses
 * portes sociales.
 *
 * Les comptes existants sont laisses a NULL : aucun blocage retroactif (ONB-04).
 */
final class Version20260729KUserEmailVerifiedAt extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Deferred email verification: nullable email_verified_at on users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "users" ADD COLUMN IF NOT EXISTS email_verified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN "users".email_verified_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "users" DROP COLUMN IF EXISTS email_verified_at');
    }
}
