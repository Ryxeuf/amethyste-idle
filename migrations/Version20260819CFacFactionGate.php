<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * FAC-09 — la loi laterale et les cinq portes.
 *
 * Deux choses, et elles vont ensemble : la **forme** d'une recompense de palier
 * (`stat_bonus` devient `patronage` — la seule forme du jeu qui puisse nommer
 * une statistique porte desormais le nom de la regle qui la borne), et la
 * **garde** d'une zone (`required_faction` + `required_tier`).
 *
 * Le renommage se fait en donnees et non en code : la colonne reste une chaine,
 * et c'est ce qui permet a cette migration de la relire.
 */
final class Version20260819CFacFactionGate extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'FAC-09 : forme patronage + garde de reputation sur les zones';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE game_faction_rewards SET reward_type = 'patronage' WHERE reward_type = 'stat_bonus'");

        $this->addSql('ALTER TABLE zone ADD COLUMN IF NOT EXISTS required_faction VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE zone ADD COLUMN IF NOT EXISTS required_tier VARCHAR(32) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE game_faction_rewards SET reward_type = 'stat_bonus' WHERE reward_type = 'patronage'");

        $this->addSql('ALTER TABLE zone DROP COLUMN IF EXISTS required_faction');
        $this->addSql('ALTER TABLE zone DROP COLUMN IF EXISTS required_tier');
    }
}
