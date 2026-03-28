<?php

namespace App\GameEngine\Guild;

use App\Entity\App\InfluenceSeason;
use App\Entity\App\Player;
use App\Entity\App\Region;
use App\Entity\App\RegionControl;
use Doctrine\ORM\EntityManagerInterface;

class PrestigeTitleManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Met a jour les titres de prestige apres l'attribution du controle de region.
     * Revoque les anciens titres et attribue les nouveaux.
     */
    public function updateTitles(InfluenceSeason $season): void
    {
        $this->revokeAllTitles();

        $activeControls = $this->entityManager->getRepository(RegionControl::class)->findBy([
            'endsAt' => null,
        ]);

        foreach ($activeControls as $control) {
            if ($control->getGuild() === null) {
                continue;
            }

            $title = $this->buildTitle($control->getRegion());

            foreach ($control->getGuild()->getMembers() as $member) {
                $member->getPlayer()->setPrestigeTitle($title);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Revoque tous les titres de prestige existants.
     */
    public function revokeAllTitles(): void
    {
        $this->entityManager->createQueryBuilder()
            ->update(Player::class, 'p')
            ->set('p.prestigeTitle', ':null')
            ->where('p.prestigeTitle IS NOT NULL')
            ->setParameter('null', null)
            ->getQuery()
            ->execute();
    }

    /**
     * Genere le titre de prestige pour une region.
     */
    public function buildTitle(Region $region): string
    {
        return sprintf('Protecteur de %s', $region->getName());
    }
}
