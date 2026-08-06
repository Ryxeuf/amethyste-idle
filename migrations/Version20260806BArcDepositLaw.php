<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ARC-11b — ce qu'une application de statut rend par tour.
 *
 * La loi du depot (GAME_ARCHETYPES § 7 bis) veut que **la duree etale la
 * valeur sans l'augmenter** : la valeur totale d'un depot est fixee par le
 * palier de la materia, et la duree ne decide que de son etalement. La valeur
 * par tour appartient donc a l'**application**, pas a la fiche de l'effet —
 * celle-ci est partagee par toutes ses applications, et l'ecrire dessus
 * changerait la valeur de tous les depots deja poses.
 *
 * La colonne est **nullable**, et c'est ce qui fait qu'aucune valeur de jeu ne
 * bouge : `null` veut dire « rien n'a ete etale », et l'effet rend alors ce que
 * sa fiche declare, exactement comme avant ce jalon.
 */
final class Version20260806BArcDepositLaw extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ARC-11b: per-application spread value on a deposited status effect';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fight_status_effect ADD COLUMN IF NOT EXISTS value_per_turn INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fight_status_effect DROP COLUMN IF EXISTS value_per_turn');
    }
}
