<?php

namespace App\GameEngine\Race;

use App\Entity\App\Player;
use App\Entity\Game\Race;

/**
 * ONB-07 — le point unique d'ou l'on demande « ce personnage voit-il ceci ? ».
 *
 * Les ecrans qui exposeront ces capacites (ONB-07b) sont eparpilles — zone,
 * bestiaire, catalogue de ressources, exploration. Sans point d'entree unique,
 * chacun re-deriverait la capacite depuis le slug du peuple, et la regle « une
 * capacite ne touche jamais ce qu'on produit » n'aurait aucun endroit ou etre
 * verifiee.
 */
class RaceCapabilityResolver
{
    public function forPlayer(?Player $player): ?RaceCapability
    {
        return $this->forRace($player?->getRace());
    }

    public function forRace(?Race $race): ?RaceCapability
    {
        if ($race === null) {
            return null;
        }

        return RaceCapability::forRaceSlug($race->getSlug());
    }

    public function playerHas(?Player $player, RaceCapability $capability): bool
    {
        return $this->forPlayer($player) === $capability;
    }

    /**
     * Ce que chaque peuple laisse voir, indexe par son slug.
     *
     * Sert l'ecran du peuple, dans le tunnel (ONB-05) comme dans la creation du
     * second personnage. Les deux controleurs en tenaient chacun une copie :
     * deux endroits pour decider ce qu'un peuple apporte, donc une occasion de
     * les faire diverger sans que rien ne le signale.
     *
     * @return array<string, array{name: string, description: string}>
     */
    public function byRaceSlug(): array
    {
        $capabilities = [];
        foreach (RaceCapability::cases() as $capability) {
            $capabilities[$capability->raceSlug()] = [
                'name' => $capability->nameKey(),
                'description' => $capability->descriptionKey(),
            ];
        }

        return $capabilities;
    }
}
