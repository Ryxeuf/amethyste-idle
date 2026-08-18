<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ARC-18g — un joueur peut preparer un geste pour la prochaine rencontre.
 *
 * La forme **ouverture** (GAME_ARCHETYPES § 13.1, n° 5) : *le combat commence
 * avant le combat*. Elle rend concret `tempo`, jusqu'ici un levier decoratif
 * dans deux palettes sur quatre.
 *
 * La colonne vit sur le **joueur** et non dans un combat, et c'est la
 * definition meme de la forme : une ouverture se pose **hors** rencontre et
 * attend la suivante — la ranger dans un combat serait une contradiction dans
 * les termes. Elle nait a `0`.
 */
final class Version20260818FArcPlayerOpening extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ARC-18g : Player porte le geste prepare pour sa prochaine rencontre.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS pending_opening INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS pending_opening');
    }
}
