<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Les couleurs qu'on porte (FAC-01).
 *
 * GAME_WORLD § 6.4 c : « on porte les couleurs d'une seule faction a la fois ».
 * Une colonne unique plutot qu'une table de liaison : c'est le schema qui tient
 * l'exclusivite, et non un service qu'un appelant distrait pourrait contourner.
 *
 * **Aucune donnee a migrer.** Les `FactionReward` de statistiques deviennent des
 * bonus de patronage sans changer de forme — c'est leur **portee** qui change,
 * et elle vit dans le code (`PatronageBonusResolver`). Rien a reecrire en base ;
 * les joueurs commencent sans couleurs, ce qui est la position neutre et pas une
 * perte : avant ce jalon, aucun de ces bonus ne s'appliquait.
 */
final class Version20260729GPlayerPatronFaction extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Player patronage: the colours one carries, one faction at a time (FAC-01)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS patron_faction_id INT DEFAULT NULL');

        // PostgreSQL ne connait pas `ADD CONSTRAINT IF NOT EXISTS`.
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_patron_faction') THEN
                    ALTER TABLE player
                        ADD CONSTRAINT fk_player_patron_faction
                        FOREIGN KEY (patron_faction_id) REFERENCES game_factions (id) ON DELETE SET NULL;
                END IF;
            END $$;
            SQL);

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_patron_faction ON player (patron_faction_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_player_patron_faction');
        $this->addSql('ALTER TABLE player DROP CONSTRAINT IF EXISTS fk_player_patron_faction');
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS patron_faction_id');
    }
}
