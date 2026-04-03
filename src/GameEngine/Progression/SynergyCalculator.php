<?php

namespace App\GameEngine\Progression;

use App\Entity\App\Player;
use App\Entity\Game\DomainSynergy;
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
     * Calcule les bonus de combat issus des synergies actives.
     *
     * @return array{damage: int, heal: int, hit: int, critical: int, life: int}
     */
    public function getSynergyBonuses(Player $player): array
    {
        $bonuses = [
            'damage' => 0,
            'heal' => 0,
            'hit' => 0,
            'critical' => 0,
            'life' => 0,
        ];

        foreach ($this->getActiveSynergies($player) as $entry) {
            $synergy = $entry['synergy'];
            $type = $synergy->getBonusType();

            if (isset($bonuses[$type])) {
                $bonuses[$type] += $synergy->getBonusValue();
            }
        }

        return $bonuses;
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
