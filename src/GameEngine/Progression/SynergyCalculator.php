<?php

namespace App\GameEngine\Progression;

use App\Entity\App\Player;
use App\Entity\Game\DomainSynergy;
use App\Enum\AccointanceForm;
use Doctrine\ORM\EntityManagerInterface;

class SynergyCalculator
{
    /** @var DomainSynergy[]|null Cache en mémoire par requête */
    private ?array $synergiesCache = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Retourne les synergies actives pour un joueur.
     * Une synergie est active si le joueur a >= activationThreshold XP dans chacun des deux domaines.
     *
     * @return array<array{synergy: DomainSynergy, xpA: int, xpB: int}>
     */
    public function getActiveSynergies(Player $player): array
    {
        $synergies = $this->getAllSynergies();
        $domainXpMap = $this->buildDomainXpMap($player);
        $active = [];

        foreach ($synergies as $synergy) {
            $domainAId = $synergy->getDomainA()->getId();
            $domainBId = $synergy->getDomainB()->getId();
            $xpA = $domainXpMap[$domainAId] ?? 0;
            $xpB = $domainXpMap[$domainBId] ?? 0;
            $threshold = $synergy->getActivationThreshold();

            if ($xpA >= $threshold && $xpB >= $threshold) {
                $active[] = [
                    'synergy' => $synergy,
                    'xpA' => $xpA,
                    'xpB' => $xpB,
                ];
            }
        }

        return $active;
    }

    /**
     * Retourne toutes les synergies avec leur statut (actif/inactif) pour un joueur.
     *
     * @return array<array{synergy: DomainSynergy, active: bool, xpA: int, xpB: int}>
     */
    public function getAllSynergiesWithStatus(Player $player): array
    {
        $synergies = $this->getAllSynergies();
        $domainXpMap = $this->buildDomainXpMap($player);
        $result = [];

        foreach ($synergies as $synergy) {
            $domainAId = $synergy->getDomainA()->getId();
            $domainBId = $synergy->getDomainB()->getId();
            $xpA = $domainXpMap[$domainAId] ?? 0;
            $xpB = $domainXpMap[$domainBId] ?? 0;
            $threshold = $synergy->getActivationThreshold();

            $result[] = [
                'synergy' => $synergy,
                'active' => $xpA >= $threshold && $xpB >= $threshold,
                'xpA' => $xpA,
                'xpB' => $xpB,
            ];
        }

        return $result;
    }

    /**
     * Les domaines qu'une accointance active fait **aussi** exprimer (ARC-16).
     *
     * C'est la forme `domain_expression` du canon, et la seule dont le lecteur
     * existe : *ce qu'on porte pour l'un parle aussi pour l'autre*. Le service
     * rend, pour chaque domaine, la liste de ses voisins d'accointance — c'est
     * `BuildDomainResolver` qui decide ensuite si l'un d'eux s'exprime.
     *
     * **Ce qu'il ne rend plus** : des statistiques. `getSynergyBonuses()`
     * ajoutait `damage`, `heal`, `hit`, `critical` et `life` dans
     * `CombatSkillResolver`, hors des 50 points de budget, hors des plafonds
     * par levier et hors des palettes de fonction — la porte de service que la
     * decision 15 ferme.
     *
     * @return array<int, list<int>> domainId => les domaines qui l'expriment aussi
     */
    public function getExpressionWidenings(Player $player): array
    {
        $widenings = [];

        foreach ($this->getActiveSynergies($player) as $entry) {
            $synergy = $entry['synergy'];
            if ($synergy->getForm() !== AccointanceForm::DomainExpression) {
                continue;
            }

            $a = $synergy->getDomainA()->getId();
            $b = $synergy->getDomainB()->getId();

            $widenings[$a][] = $b;
            $widenings[$b][] = $a;
        }

        return $widenings;
    }

    /**
     * Les accointances actives d'une forme donnee (ARC-16b).
     *
     * C'est le point d'entree des trois lecteurs livres par ARC-16b : chacun ne
     * lit que sa forme, et aucun ne recalcule l'activation — elle se decide ici,
     * une fois, sur les seuils d'XP des deux ecoles.
     *
     * @return list<DomainSynergy>
     */
    public function activeOfForm(Player $player, AccointanceForm $form): array
    {
        $matching = [];
        foreach ($this->getActiveSynergies($player) as $entry) {
            if ($entry['synergy']->getForm() === $form) {
                $matching[] = $entry['synergy'];
            }
        }

        return $matching;
    }

    /**
     * Ce qui satisfait **aussi** chaque condition de build (ARC-16b).
     *
     * La forme `condition_widening` du canon : *les passifs conditionnes « en
     * cuir » sont aussi satisfaits par la plaque*. Le service rend la table des
     * elargissements actifs — c'est `BuildConditionEvaluator` qui decide ensuite
     * si l'une ou l'autre condition est reellement portee.
     *
     * @return array<string, list<string>> condition => ce qui la satisfait aussi
     */
    public function conditionWidenings(Player $player): array
    {
        $widenings = [];
        foreach ($this->activeOfForm($player, AccointanceForm::ConditionWidening) as $synergy) {
            $subject = $synergy->getSubject();
            $widenedBy = $synergy->getWidenedBy();
            if ($subject === null || $widenedBy === null) {
                continue;
            }

            $widenings[$subject][] = $widenedBy;
        }

        return $widenings;
    }

    /**
     * Les familles de port dont l'echelon 3 coute un barreau de moins (ARC-16b).
     *
     * La forme `access_discount` : la remise est **fixe par la regle** (un
     * barreau sur `SkillCostScale`), le sujet ne nomme que la famille — il n'y a
     * pas de nombre a lire, et c'est voulu.
     *
     * @return list<string>
     */
    public function accessDiscountFamilies(Player $player): array
    {
        $families = [];
        foreach ($this->activeOfForm($player, AccointanceForm::AccessDiscount) as $synergy) {
            $subject = $synergy->getSubject();
            if ($subject !== null) {
                $families[] = $subject;
            }
        }

        return array_values(array_unique($families));
    }

    /**
     * @return array<int, int> domainId => totalExperience
     */
    private function buildDomainXpMap(Player $player): array
    {
        $map = [];
        foreach ($player->getDomainExperiences() as $de) {
            $map[$de->getDomain()->getId()] = $de->getTotalExperience();
        }

        return $map;
    }

    /**
     * Charge toutes les DomainSynergy avec cache en mémoire par requête.
     * Évite les appels multiples à findAll() dans le même cycle requête.
     *
     * @return DomainSynergy[]
     */
    private function getAllSynergies(): array
    {
        if ($this->synergiesCache === null) {
            $this->synergiesCache = $this->entityManager->getRepository(DomainSynergy::class)->findAll();
        }

        return $this->synergiesCache;
    }
}
