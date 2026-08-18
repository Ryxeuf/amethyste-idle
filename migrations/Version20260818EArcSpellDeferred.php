<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ARC-18f — un geste peut frapper plus tard.
 *
 * La forme **differe** (GAME_ARCHETYPES § 13.1, n° 8), la seule qui exploite
 * l'asynchronie du donjon au lieu de la subir. La colonne nait a `0` sur les
 * 253 gestes livres — « tout de suite ».
 *
 * La **file**, elle, n'a pas de colonne : elle vit dans les metadonnees du
 * combat, comme le compteur de charge. C'est ce qui garantit qu'un differe pose
 * puis fui n'explose pas dans la rencontre suivante.
 */
final class Version20260818EArcSpellDeferred extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ARC-18f : Spell porte le nombre de tours au bout desquels un geste frappe.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_spells ADD COLUMN IF NOT EXISTS deferred_turns INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_spells DROP COLUMN IF EXISTS deferred_turns');
    }
}
