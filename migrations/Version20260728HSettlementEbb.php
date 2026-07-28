<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * L'etiage annonce (FOY-10).
 *
 * Sans cette date, une descente de rang serait instantanee et invisible : le
 * foyer passerait sous son seuil au tick, perdrait son rang dans la meme
 * seconde, et les joueurs l'apprendraient en trouvant un service ferme. Le champ
 * porte le delai — une maree pour redresser — et se vide des que la ville
 * repasse au-dessus, ce qui lui redonne une grace pleine la fois suivante.
 */
final class Version20260728HSettlementEbb extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Announced ebb: a settlement gets a full tide to recover before losing a rank';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settlement ADD COLUMN IF NOT EXISTS ebb_since TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settlement DROP COLUMN IF EXISTS ebb_since');
    }
}
