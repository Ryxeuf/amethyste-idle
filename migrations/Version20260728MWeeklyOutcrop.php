<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * L'Affleurement de la semaine (RET-06).
 *
 * Une ligne par semaine : le filon dont la bande maximale monte d'un cran
 * pendant sept jours. La rotation hebdomadaire du monde a cout d'ecriture nul —
 * rien n'est cree, rien n'est deplace, une seule ligne change ce que la carte
 * vaut cette semaine.
 *
 * La table n'est **jamais** lue par un ecran ni par une API : seul le tirage de
 * purete la consulte. Un affleurement annonce deviendrait une ruee et cesserait
 * d'etre une decouverte.
 */
final class Version20260728MWeeklyOutcrop extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Weekly outcrop: one vein a week yields one band higher, and nobody is told';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS weekly_outcrop (
            id SERIAL PRIMARY KEY,
            week_key VARCHAR(10) NOT NULL,
            zone_id INT NOT NULL,
            vein_slug VARCHAR(64) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uq_weekly_outcrop_week ON weekly_outcrop (week_key)');

        $this->addSql("DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_weekly_outcrop_zone') THEN
                ALTER TABLE weekly_outcrop ADD CONSTRAINT fk_weekly_outcrop_zone
                    FOREIGN KEY (zone_id) REFERENCES zone (id) ON DELETE CASCADE;
            END IF;
        END $$");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS weekly_outcrop');
    }
}
