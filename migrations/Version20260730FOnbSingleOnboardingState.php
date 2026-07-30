<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ONB-14 — un seul etat d'onboarding.
 *
 * `tutorial_step` portait un avancement parallele a l'arc `intro`, et les deux
 * pouvaient se contredire (dette D7). Il disparaît : l'etape se **deduit**
 * desormais des quetes de l'arc terminees.
 *
 * Reste le seul etat que l'arc n'exprime pas — le **refus**. Un joueur qui a
 * passe le tutoriel l'a dit une fois pour toutes, et aucune quete n'enregistre
 * cela. La colonne naît nulle : personne n'a rien refuse jusqu'ici.
 *
 * Le suffixe `F` la trie apres les cinq migrations du meme jour (cf. la section
 * « Pieges courants » de CLAUDE.md).
 */
final class Version20260730FOnbSingleOnboardingState extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ONB-14: player.onboarding_skipped_at replaces player.tutorial_step';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS onboarding_skipped_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS tutorial_step');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS tutorial_step SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS onboarding_skipped_at');
    }
}
