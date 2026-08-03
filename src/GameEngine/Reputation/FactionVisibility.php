<?php

namespace App\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\PlayerFaction;
use App\Entity\Game\Faction;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Ce qu'un joueur voit de l'ecran des factions (FAC-06).
 *
 * La Confrerie des Ruelles est invisible jusqu'au premier contact : pas de
 * tableau de quetes, pas de recruteur — c'est elle qui vous trouve. Le
 * predicat de rencontre est la ligne de reputation elle-meme, la meme
 * doctrine que « jamais Hostile par defaut » : pas de ligne, pas de
 * rencontre, pas de carte a l'ecran.
 *
 * Le filtre ne s'applique qu'a la liste **rendue** : l'axe des Chevaliers
 * continue de se calculer sur la liste complete — filtrer avant le calcul
 * ferait basculer l'Ordre en « hors tension » a tort.
 */
class FactionVisibility
{
    /**
     * Les factions invisibles avant le premier contact. Une seule
     * aujourd'hui ; en ajouter une est un acte de design, pas une config.
     */
    public const HIDDEN_UNTIL_MET = [ShadowsApproach::FACTION_SLUG];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param list<Faction> $factions
     *
     * @return list<Faction> la liste rendue au joueur
     */
    public function visibleFor(Player $player, array $factions): array
    {
        $visible = [];
        foreach ($factions as $faction) {
            if (\in_array($faction->getSlug(), self::HIDDEN_UNTIL_MET, true)
                && !$this->hasMet($player, $faction)) {
                continue;
            }
            $visible[] = $faction;
        }

        return $visible;
    }

    private function hasMet(Player $player, Faction $faction): bool
    {
        return null !== $this->entityManager->getRepository(PlayerFaction::class)->findOneBy([
            'player' => $player,
            'faction' => $faction,
        ]);
    }
}
