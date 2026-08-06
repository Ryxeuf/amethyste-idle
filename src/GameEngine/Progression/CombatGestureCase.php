<?php

namespace App\GameEngine\Progression;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use App\Entity\Game\Spell;
use App\Enum\Element;
use Doctrine\ORM\EntityManagerInterface;

/**
 * L'arbre qu'un geste de combat fait progresser (ARC-06b).
 *
 * **La decision du 2026-08-06, et la seule chose que cette classe fait** : le
 * gain va a la **case du geste** — son element et son registre —, en entier et
 * a un seul arbre. On progresse dans ce qu'on **joue**, jamais dans ce qu'on a
 * achete : lancer des gestes de feu fait monter le feu, quel que soit l'arbre
 * qui a ouvert la materia. Les 39 gestes qu'ARC-02b a comptes comme
 * « ambigus » le sont par leurs **ouvreurs**, pas par leur nature —
 * `magnetic-pull` employe en combat a un element et un registre, donc une
 * case. Jamais de division, jamais de multiplication : mener quatre arbres ne
 * rapporte ni plus ni moins que d'en mener un, ce que GAME_PROGRESSION § 1
 * suppose sans le garantir.
 *
 * **Ce que la decision ne dit pas, et comment on tranche ici.** La grille
 * compte 24 arbres pour **18** cases element x registre : la fonction (ARC-01)
 * est le troisieme axe, et trois arbres se partagent l'eau x sorts
 * (Hydromancien, Guerisseur, Maremancien). Un geste porte un element et un
 * registre ; il ne porte pas de fonction, et l'intention d'ARC-11a ne la
 * designe pas non plus — les palettes de `domain_roles.yaml` declarent des
 * **minimums** (l'assaut exige 3 intentions de degat, le controle 1), pas une
 * partition. La case peut donc contenir jusqu'a trois arbres.
 *
 * On applique alors le tranchage que la decision a **deja** rendu pour son
 * point 3, ou la meme ambiguite se pose sur les nœuds de port partages :
 * **l'arbre ou le joueur a effectivement appris, le premier ouvert si
 * plusieurs**. Un arbre non ouvert ne recoit rien — le parchemin reste la
 * porte (GAME_ONBOARDING), et l'ordre d'ouverture est celui des
 * `DomainExperience`, donc stable.
 *
 * La question de fond reste ouverte et est notee au plan : *faut-il que le
 * geste porte une fonction, pour que sa case designe un arbre sans
 * departage ?* Elle se pose a l'ecriture des arbres (ARC-07/08) et se mesure
 * au simulateur (ARC-17).
 */
class CombatGestureCase
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EquipmentPortCatalog $portCatalog,
    ) {
    }

    /**
     * L'arbre credite par un geste de materia.
     *
     * `Element::None` ne designe aucune case : c'est l'**absence** d'element
     * (ARC-13a), et lui donner un arbre en inventerait un dix-neuvieme.
     */
    public function forSpell(Player $player, Spell $spell): ?Domain
    {
        $element = $spell->getElement();
        if ($element === Element::None) {
            return null;
        }

        $candidates = $this->entityManager->getRepository(Domain::class)->findBy([
            'element' => $element->value,
            'register' => $spell->getRegister(),
        ]);

        return $this->firstOpened($player, $candidates);
    }

    /**
     * L'arbre credite par l'attaque d'arme de base.
     *
     * Elle ne vient d'aucune materia, donc d'aucune case : elle credite
     * l'arbre **ou le joueur a appris a porter cette arme**. A mains nues il
     * n'y a aucun nœud de port, donc aucun point — le repli reste un repli, et
     * un combat gagne sans arme ne fait progresser aucune ecole.
     */
    public function forWeaponAttack(Player $player): ?Domain
    {
        foreach ($this->portSkillsOfEquippedWeapon($player) as $skill) {
            if (!$player->hasSkill($skill)) {
                continue;
            }

            $domain = $this->firstOpened($player, $skill->getDomains()->toArray());
            if ($domain !== null) {
                return $domain;
            }
        }

        return null;
    }

    /**
     * Les nœuds de port exiges par l'arme tenue en main principale.
     *
     * L'echelle de port est deja la table qui sait « ceci est-il une epee ? »
     * (`EquipmentPortCatalog`, ONB-12a) : on ne construit pas de table
     * parallele des familles d'arme, on relit celle-la.
     *
     * @return list<Skill>
     */
    private function portSkillsOfEquippedWeapon(Player $player): array
    {
        $skills = [];

        foreach ($player->getInventories() as $inventory) {
            foreach ($inventory->getItems() as $playerItem) {
                if ($playerItem->getGear() !== PlayerItem::GEAR_MAIN_WEAPON) {
                    continue;
                }

                foreach ($playerItem->getGenericItem()->getRequirements() as $requirement) {
                    if ($this->portCatalog->familyOfPortSkill($requirement->getSlug()) !== null) {
                        $skills[] = $requirement;
                    }
                }
            }
        }

        return $skills;
    }

    /**
     * Le premier de ces arbres que le joueur a ouvert.
     *
     * L'ordre est celui des `DomainExperience` du joueur — c'est-a-dire celui
     * dans lequel il a ouvert ses arbres. « Le premier appris » est donc une
     * reponse stable, et non le hasard d'un tri de base de donnees.
     *
     * @param list<Domain> $candidates
     */
    private function firstOpened(Player $player, array $candidates): ?Domain
    {
        if ($candidates === []) {
            return null;
        }

        foreach ($player->getDomainExperiences() as $domainExperience) {
            foreach ($candidates as $candidate) {
                if ($domainExperience->getDomain() === $candidate) {
                    return $candidate;
                }
            }
        }

        return null;
    }
}
