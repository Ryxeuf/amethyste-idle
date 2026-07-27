<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Porte le plafond d'energie d'action de 100 a 240 points, soit 24 h de
 * regeneration a 360 s/pt. En dessous, un joueur qui ne se connecte qu'une fois
 * par jour perdait l'energie accumulee au-dela de 10 h (58 % du potentiel
 * quotidien) : le plafond penalisait l'absence longue. Le budget quotidien
 * (240 pts) est inchange — le joueur assidu ne gagne rien, il depense plus tot.
 */
final class Version20260727ActionEnergyCap extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Raise action energy cap from 100 to 240 (24 h of regen) so daily-login players stop wasting regenerated energy';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ALTER COLUMN action_energy SET DEFAULT 240');
        $this->addSql('ALTER TABLE player ALTER COLUMN max_action_energy SET DEFAULT 240');
        // Seuls les joueurs restes sur l'ancien defaut sont releves : un plafond
        // deja personnalise (talent, cas particulier admin) est preserve.
        $this->addSql('UPDATE player SET max_action_energy = 240 WHERE max_action_energy = 100');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE player SET max_action_energy = 100 WHERE max_action_energy = 240');
        $this->addSql('ALTER TABLE player ALTER COLUMN max_action_energy SET DEFAULT 100');
        $this->addSql('ALTER TABLE player ALTER COLUMN action_energy SET DEFAULT 100');
        $this->addSql('UPDATE player SET action_energy = 100 WHERE action_energy > 100');
    }
}
