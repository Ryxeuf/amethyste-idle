<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ARC-11a — l'intention et la portee du geste.
 *
 * GAME_ARCHETYPES § 3.1 : le registre dit **comment** on agit, jamais **ce
 * qu'on fait** ni **a qui**. Deux etiquettes de plus, et elles ne sont pas
 * cosmetiques — sans elles ni les leviers ni les palettes ne peuvent viser
 * juste, et `scope: le groupe` n'existe pas, donc la loi du depot non plus.
 *
 * **Les deux colonnes sont nullables, et c'est la decision du jalon** : elles
 * portent la decision d'auteur quand il y en a une, et restent vides quand la
 * donnee suffit a dire l'intention. `SpellIntentDeriver` repond alors — les
 * huit types de `StatusEffect` se rangent sans reste dans les cinq intentions.
 * Ecrire 253 valeurs a la main aurait ete 253 occasions de se tromper, et le
 * depot derive deja partout ailleurs (materia depuis le sort, stats depuis le
 * gabarit, cible depuis le bestiaire).
 *
 * **Aucune valeur de jeu ne bouge** : les colonnes naissent vides, et tout ce
 * qui les lit passe par le repli de derivation.
 */
final class Version20260805AIntentScope extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ARC-11: intent and scope on gestures (nullable, derived when absent)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_spells ADD COLUMN IF NOT EXISTS intent VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE game_spells ADD COLUMN IF NOT EXISTS scope VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_spells DROP COLUMN IF EXISTS intent');
        $this->addSql('ALTER TABLE game_spells DROP COLUMN IF EXISTS scope');
    }
}
