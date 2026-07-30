<?php

namespace App\DataFixtures;

use App\Entity\App\Player;
use App\Entity\App\PlayerDomainAccess;
use App\Entity\Game\Domain;
use App\GameEngine\Progression\EquipmentPortCatalog;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Le grand-perisage des personnages de fixtures (ONB-08).
 *
 * ONB-08 fait de l'ouverture d'un arbre un prealable a l'apprentissage de ses
 * nœuds. C'est **un changement de comportement**, et il ne doit pas s'appliquer
 * a ce qui existait avant lui : un personnage qui minait hier doit miner
 * aujourd'hui, sans avoir a racheter le droit d'exercer son metier.
 *
 * La regle appliquee ici est **exactement** celle de la migration de production
 * (`Version20260730AOnbDomainAccess`) : on ouvre tout arbre dont le personnage
 * porte deja une competence **ou** une experience de domaine. Les deux passent
 * par le meme enonce a dessein — une divergence entre le grand-perisage des
 * fixtures et celui de la base rendrait les tests menteurs precisement la ou
 * ils devraient prevenir.
 *
 * Ce qui n'est **pas** ouvert : les arbres que personne n'a touches. Un
 * personnage de demonstration n'a aucune raison de naitre omniscient, et un
 * test qui verifie qu'un arbre ferme n'accorde aucun nœud a besoin qu'il en
 * reste.
 */
class PlayerDomainAccessFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly EquipmentPortCatalog $portCatalog,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $rungOne = $this->portCatalog->rungOneSlugs();

        foreach ($manager->getRepository(Player::class)->findAll() as $player) {
            foreach ($this->practisedDomains($player) as $domain) {
                $manager->persist(new PlayerDomainAccess($player, $domain));

                // ONB-20b : ouvrir un arbre livre son kit de port. Le
                // grand-perisage doit le rejouer, sinon un personnage de
                // fixtures se reveillerait incapable de tenir l'arme de palier
                // 1 qu'il portait la veille.
                foreach ($domain->getSkills() as $skill) {
                    if (\in_array($skill->getSlug(), $rungOne, true) && !$player->hasSkill($skill)) {
                        $player->addSkill($skill);
                    }
                }
            }
        }

        $manager->flush();
    }

    /**
     * @return Domain[] les arbres deja pratiques, dedoublonnes par identifiant
     */
    private function practisedDomains(Player $player): array
    {
        $domains = [];

        foreach ($player->getSkills() as $skill) {
            foreach ($skill->getDomains() as $domain) {
                $domains[$domain->getId()] = $domain;
            }
        }

        foreach ($player->getDomainExperiences() as $experience) {
            $domain = $experience->getDomain();
            $domains[$domain->getId()] = $domain;
        }

        return array_values($domains);
    }

    public function getDependencies(): array
    {
        return [
            PlayerFixtures::class,
            DomainFixtures::class,
            DomainExperienceFixtures::class,
        ];
    }
}
