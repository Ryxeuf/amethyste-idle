<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ONB-07 — le peuple cesse de porter des chiffres.
 *
 * `race.stat_modifiers` donnait a l'Orc `+8 vie` sur une base de 20 — soit
 * +40 % de survie face a l'Humain a `0/0/0/0`. Ce n'etait pas equilibre, et
 * c'etait surtout un arbitrage de puissance demande au pas 3 d'un tunnel ou
 * aucune decision de build ne doit etre prise (decision A8).
 *
 * La colonne est **supprimee** plutot que remise a zero : une donnee que plus
 * rien ne lit est un piege, elle finit par etre relue un jour par quelqu'un qui
 * la croit vivante.
 *
 * Les personnages deja crees gardent leurs statistiques telles quelles. Les
 * recalculer serait pire que le defaut : on retirerait des points de vie a des
 * joueurs en place pour corriger une decision qu'ils n'ont pas prise.
 */
final class Version20260729MRaceDropStatModifiers extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Race stat modifiers removed: a people grants a capability, never numbers';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE race DROP COLUMN IF EXISTS stat_modifiers');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE race ADD COLUMN IF NOT EXISTS stat_modifiers JSON DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE race
            SET stat_modifiers = '{"life": 0, "energy": 0, "speed": 0, "hit": 0}'::json
            WHERE stat_modifiers IS NULL
        SQL);
    }
}
