<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ARC-20b — la barre de vie passe du bonus plat au Socle.
 *
 * `Skill::life` etait **plat** (donc ineequilibrable — la lecon d'ARC-03a),
 * **cumulatif** (donc explosif : +3 200 PV au joueur qui aurait mene les
 * 32 arbres) et **ecrit en dur** dans `player.max_life` a chaque
 * apprentissage — la meme fuite que les echelons de port de l'ecart n° 5.
 *
 * La barre se lit desormais comme un **maximum** (`VitalityTier`), et cette
 * migration remet les personnages existants a leur plancher : le Socle
 * s'appliquera des leur prochain apprentissage. **On remonte, on ne descend
 * jamais** — un personnage au-dessus du plancher a paye ses points, et lui
 * retirer de la vie serait une perte que rien dans le jeu n'annonce.
 *
 * Le plancher (96) est la barre du palier 1, derivee de ce qu'une elite de
 * palier 1 prend en une rencontre entiere. Il est ecrit ici plutot que calcule
 * parce qu'une migration doit rester lisible dans dix ans, meme si la loi a
 * bouge depuis — *une migration raconte ce qui s'est passe ce jour-la*.
 */
final class Version20260818GArcVitalitySocle extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ARC-20b : les personnages existants passent au plancher de vitalite (96 PV).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE player SET max_life = 96 WHERE max_life < 96');
        $this->addSql('UPDATE player SET life = max_life WHERE life > max_life');
    }

    public function down(Schema $schema): void
    {
        // Irreversible par nature : on ne sait plus quels bonus plats chaque
        // personnage avait accumules. Redescendre a l'aveugle retirerait de la
        // vie a des joueurs qui n'ont rien fait.
    }
}
