<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ECO-20 : compensation du branchement du gardien « plan appris ».
 *
 * Jusqu'ici, atteindre le niveau de metier suffisait a fabriquer toutes les
 * recettes de ce metier. Le gardien branche, un personnage existant perd l'acces
 * aux recettes dont il n'a pas achete le nœud — pour une correction qui n'est
 * pas de son fait.
 *
 * Le respec existe deja (`SkillRespecManager`) et son cout croit de 25 % a
 * chaque usage. Remettre le compteur a zero rend le prochain respec au tarif de
 * base : la reorientation reste payante, mais elle n'est pas surtaxee par un
 * historique constitue sous des regles differentes.
 */
final class Version20260726ResetRespecCount extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Reset player.respec_count (ECO-20 recipe gate compensation)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE player SET respec_count = 0 WHERE respec_count > 0');
    }

    public function down(Schema $schema): void
    {
        // Le compteur d'origine n'est pas conservable : la remise a zero est une
        // faveur ponctuelle, pas un changement de schema a annuler.
        $this->addSql('SELECT 1');
    }
}
