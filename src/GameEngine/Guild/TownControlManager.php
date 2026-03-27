<?php

namespace App\GameEngine\Guild;

use App\Entity\App\Guild;
use App\Entity\App\GuildInfluence;
use App\Entity\App\InfluenceSeason;
use App\Entity\App\Region;
use App\Entity\App\RegionControl;
use Doctrine\ORM\EntityManagerInterface;

class TownControlManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Attribue le controle de chaque region contestable a la guilde avec le plus de points d'influence.
     *
     * @return array<string, string|null> regionSlug => guildName|null
     */
    public function attributeControl(InfluenceSeason $season): array
    {
        $regions = $this->entityManager->getRepository(Region::class)->findBy([
            'isContestable' => true,
        ]);

        $results = [];

        foreach ($regions as $region) {
            $this->closeActiveControl($region);

            $winnerGuild = $this->resolveWinner($region, $season);

            $control = new RegionControl();
            $control->setRegion($region);
            $control->setGuild($winnerGuild);
            $control->setSeason($season);
            $control->setStartedAt(new \DateTime());
            $control->setCreatedAt(new \DateTime());
            $control->setUpdatedAt(new \DateTime());

            $this->entityManager->persist($control);

            $results[$region->getSlug()] = $winnerGuild?->getName();
        }

        $this->entityManager->flush();

        return $results;
    }

    /**
     * Retourne la guilde controlant actuellement une region, ou null.
     */
    public function getControllingGuild(Region $region): ?Guild
    {
        $control = $this->getActiveControl($region);

        return $control?->getGuild();
    }

    /**
     * Retourne le RegionControl actif pour une region, ou null.
     */
    public function getActiveControl(Region $region): ?RegionControl
    {
        return $this->entityManager->getRepository(RegionControl::class)->findOneBy([
            'region' => $region,
            'endsAt' => null,
        ]);
    }

    /**
     * Determine la guilde gagnante pour une region/saison.
     * En cas d'egalite, la guilde tenant conserve le controle.
     */
    private function resolveWinner(Region $region, InfluenceSeason $season): ?Guild
    {
        /** @var GuildInfluence[] $influences */
        $influences = $this->entityManager->getRepository(GuildInfluence::class)->findBy(
            ['region' => $region, 'season' => $season],
            ['points' => 'DESC'],
        );

        if ($influences === []) {
            return null;
        }

        $topPoints = $influences[0]->getPoints();
        if ($topPoints <= 0) {
            return null;
        }

        $topGuilds = [];
        foreach ($influences as $influence) {
            if ($influence->getPoints() < $topPoints) {
                break;
            }
            $topGuilds[] = $influence->getGuild();
        }

        if (\count($topGuilds) === 1) {
            return $topGuilds[0];
        }

        // Egalite : la guilde tenant conserve le controle
        $currentControl = $this->getActiveControl($region);
        if ($currentControl !== null) {
            foreach ($topGuilds as $guild) {
                if ($guild === $currentControl->getGuild()) {
                    return $guild;
                }
            }
        }

        // Aucun tenant : premiere guilde du classement
        return $topGuilds[0];
    }

    /**
     * Ferme le controle actif d'une region (fixe ends_at).
     */
    private function closeActiveControl(Region $region): void
    {
        $current = $this->getActiveControl($region);
        if ($current !== null) {
            $current->setEndsAt(new \DateTime());
            $current->setUpdatedAt(new \DateTime());
        }
    }
}
