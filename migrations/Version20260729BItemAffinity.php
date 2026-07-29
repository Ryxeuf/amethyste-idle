<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * L'affinite elementaire d'une ressource (ZON-36, loi 10).
 *
 * **Une colonne de plus, et non `element` reutilise.** Les deux disent un flux,
 * mais pas le meme : `element` est ce qu'une arme projette, `affinity` ce dont
 * une matiere est faite. Les confondre aurait fait d'une epee de feu une
 * ressource Feu — et aurait rendu impossible le seul cas que la loi nomme, celui
 * de l'amethyste, dont la reponse est « aucune » et non « neutre ».
 *
 * D'ou le `NULL` plutot qu'un defaut : `element` vaut `none` par defaut, ce qui
 * ne distingue pas « pas encore renseigne » de « substrat ». La colonne est donc
 * nullable, et la table declarative (`config/game/affinities.yaml`) reste la
 * source de verite — cette colonne n'en est que la projection interrogeable.
 */
final class Version20260729BItemAffinity extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Item elemental affinity: what a resource is made of, distinct from what a weapon projects';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE item ADD COLUMN IF NOT EXISTS affinity VARCHAR(25) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE item DROP COLUMN IF EXISTS affinity');
    }
}
