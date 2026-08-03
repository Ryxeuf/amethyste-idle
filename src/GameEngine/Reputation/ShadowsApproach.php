<?php

namespace App\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\PlayerFaction;
use App\Entity\Game\Faction;
use Doctrine\ORM\EntityManagerInterface;

/**
 * L'entree differee des Ruelles (FAC-06).
 *
 * GAME_WORLD § 12.4 : « on ne la trouve pas : c'est elle qui vous trouve. »
 * Chaque exploration nocturne compte ; au seuil du catalogue, un mot est
 * glisse — la ligne de reputation apparait, **a Neutre** (zero point, jamais
 * un gain : decouvrir n'est pas un geste qui nourrit). Avant elle, la faction
 * est invisible partout.
 *
 * Le compteur vit sur le joueur et ne sert qu'avant le premier contact : la
 * ligne creee, il cesse de compter — pas de fenetre, pas de cron.
 */
class ShadowsApproach
{
    public const FACTION_SLUG = 'ombres';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ShadowsMarketCatalog $catalog,
    ) {
    }

    /**
     * Une exploration de nuit. Rend `true` la fois ou la faction apparait —
     * l'appelant peut alors glisser le mot au joueur.
     */
    public function recordNightExploration(Player $player): bool
    {
        $faction = $this->entityManager->getRepository(Faction::class)
            ->findOneBy(['slug' => self::FACTION_SLUG]);
        if (null === $faction) {
            return false;
        }

        if (null !== $this->lineOf($player, $faction)) {
            // Le premier contact est deja fait : la nuit n'a plus rien a
            // presenter.
            return false;
        }

        $player->incrementNightExplorations();
        if ($player->getNightExplorations() < $this->catalog->nightExplorationsThreshold()) {
            return false;
        }

        // Le mot est glisse : la ligne nait a zero — Neutre. Creer sans
        // accorder : la decouverte n'est pas un geste, elle ne nourrit pas.
        $line = new PlayerFaction();
        $line->setPlayer($player);
        $line->setFaction($faction);
        $this->entityManager->persist($line);

        return true;
    }

    /**
     * Le premier contact est-il fait ? La ligne de reputation EST la
     * rencontre — la meme doctrine que « jamais Hostile par defaut ».
     */
    public function hasMet(Player $player): bool
    {
        $faction = $this->entityManager->getRepository(Faction::class)
            ->findOneBy(['slug' => self::FACTION_SLUG]);

        return null !== $faction && null !== $this->lineOf($player, $faction);
    }

    private function lineOf(Player $player, Faction $faction): ?PlayerFaction
    {
        return $this->entityManager->getRepository(PlayerFaction::class)->findOneBy([
            'player' => $player,
            'faction' => $faction,
        ]);
    }
}
