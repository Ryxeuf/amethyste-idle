<?php

namespace App\GameEngine\Progression;

use App\Entity\App\DomainExperience;
use App\Entity\App\Player;
use App\Entity\Game\Domain;
use App\Entity\Game\Item;
use App\Entity\Game\Skill;
use App\Helper\PlayerDomainHelper;
use Doctrine\ORM\EntityManagerInterface;

/**
 * La materia de l'etape 3 de l'acte I (ONB-12b).
 *
 * GAME_ONBOARDING § 5.2 : *« une materia de l'element du domaine choisi + les
 * points de domaine pour en prendre l'accord — on ne montre jamais une materia
 * qu'on ne peut pas utiliser »*.
 *
 * **Deriver par l'element serait faux.** Le berserker est feu, et `m1-fire` est
 * une materia de feu ; mais son sort est debloque par un nœud de l'arbre du
 * **pyromancien**. Un berserker recevrait donc une materia qu'il ne peut pas
 * sertir — exactement ce que la phrase interdit, et sans aucun message pour le
 * lui dire (`canEquipMateria()` se contente de refuser).
 *
 * La derivation exacte est donc : **la materia que l'arbre ouvert apprend a
 * utiliser**. On lit le nœud `materia.unlock` le moins cher de l'arbre, et l'on
 * remet la materia dont il ouvre le sort. Par construction, le nœud existe,
 * l'arbre est ouvert, et l'etape 4 — « l'accord » — consiste precisement a le
 * prendre. Les points remis sont ceux qu'il coute : ni plus (on n'offre pas
 * l'arbre), ni moins (l'accord doit etre finançable le jour meme).
 */
class ActOneMateriaGranter
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerDomainHelper $playerDomainHelper,
    ) {
    }

    /**
     * Remet la materia du dernier arbre de combat ouvert, et de quoi l'accorder.
     *
     * @return array{item: Item, domain: Domain, points: int}|null null si le
     *                                                            joueur n'a ouvert aucun arbre de combat, ou si celui-ci
     *                                                            n'enseigne aucune materia
     */
    public function resolve(Player $player): ?array
    {
        $domain = $this->lastOpenedCombatDomain($player);
        if (null === $domain) {
            return null;
        }

        $node = $this->cheapestMateriaNode($domain);
        if (null === $node) {
            return null;
        }

        $actions = $node->getActions() ?? [];
        $spellSlug = (string) ($actions['materia']['unlock'] ?? '');

        $item = $this->materiaGranting($spellSlug);
        if (null === $item) {
            return null;
        }

        return ['item' => $item, 'domain' => $domain, 'points' => $node->getRequiredPoints()];
    }

    /**
     * Cree, si besoin, l'experience de domaine et y verse les points de l'accord.
     *
     * Verser dans **ce** domaine et pas au prorata de tous : l'accord se prend
     * dans l'arbre qu'on vient d'ouvrir, et une repartition uniforme le rendrait
     * d'autant moins finançable que le joueur a ouvert d'arbres.
     */
    public function grantAccordPoints(Player $player, Domain $domain, int $points): void
    {
        if ($points <= 0) {
            return;
        }

        $experience = $this->playerDomainHelper->getDomainExperience($domain, $player);
        if (null === $experience) {
            $experience = new DomainExperience();
            $experience->setPlayer($player);
            $experience->setDomain($domain);
            $player->addDomainExperience($experience);
        }

        $experience->setTotalExperience($experience->getTotalExperience() + $points);
        $this->entityManager->persist($experience);
    }

    private function lastOpenedCombatDomain(Player $player): ?Domain
    {
        $latest = null;
        foreach ($player->getDomainAccesses() as $access) {
            $domain = $access->getDomain();
            if (null === $domain->getRegister()) {
                continue;
            }
            if (null === $latest || $access->getOpenedAt() >= $latest->getOpenedAt()) {
                $latest = $access;
            }
        }

        return $latest?->getDomain();
    }

    /**
     * Le nœud de materia le moins cher de l'arbre.
     *
     * « Le moins cher » plutot que « le premier declare » : l'ordre des fixtures
     * est un detail d'ecriture, le cout est une intention de design. C'est aussi
     * celui que le joueur prendrait de lui-meme.
     */
    private function cheapestMateriaNode(Domain $domain): ?Skill
    {
        $best = null;
        foreach ($domain->getSkills() as $skill) {
            $actions = $skill->getActions() ?? [];
            if (!isset($actions['materia']['unlock'])) {
                continue;
            }
            if (null === $best || $skill->getRequiredPoints() < $best->getRequiredPoints()) {
                $best = $skill;
            }
        }

        return $best;
    }

    private function materiaGranting(string $spellSlug): ?Item
    {
        if ('' === $spellSlug) {
            return null;
        }

        foreach ($this->entityManager->getRepository(Item::class)->findBy(['type' => Item::TYPE_MATERIA]) as $item) {
            if ($item->getSpell()?->getSlug() === $spellSlug) {
                return $item;
            }
        }

        return null;
    }
}
