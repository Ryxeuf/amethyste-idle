<?php

namespace App\GameEngine\Reputation;

use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerFaction;
use App\Entity\App\PlayerItem;
use App\Entity\App\PlayerJournalEntry;
use App\Entity\App\Pnj;
use App\Entity\Game\Faction;
use App\GameEngine\World\GameTimeService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Les contrats de placement des Ruelles (FAC-08).
 *
 * GAME_WORLD § 12.4 : « ecouler ses faux via les contacts PNJ de la Confrerie
 * — remunerateur, chaque placement risque la fouille : confiscation, amende,
 * grosse decote Chevaliers. » C'est le SEUL debouche d'une contrefacon : les
 * canaux entre joueurs sont tous verrouilles (FAC-07), le receleur paie moins
 * — le placement paie mieux, et le risque est ce qui l'equilibre.
 *
 * On ne place que ce qu'on VOIT : une contrefacon identifiee, ou percee par
 * l'œil du faussaire. Ecouler sans savoir n'est pas un geste.
 */
class ShadowsPlacement
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ShadowsMarketCatalog $catalog,
        private readonly ShadowsApproach $approach,
        private readonly CounterfeitService $counterfeitService,
        private readonly GameTimeService $gameTimeService,
        private readonly ReputationManager $reputationManager,
    ) {
    }

    /**
     * Le guichet prend-il des faux de ce joueur, ici, maintenant ?
     */
    public function isAvailableFor(Player $player, ?Pnj $pnj): bool
    {
        return null !== $pnj
            && $this->catalog->isCounter($pnj->getSlug())
            && $this->approach->hasMet($player)
            && GameTimeService::PHASE_NIGHT === $this->gameTimeService->getPhase();
    }

    /**
     * Les contrefacons que ce joueur peut placer : vues, ni serties ni
     * portees — on ne place que ce qu'on tient en main et qu'on connait.
     *
     * @return list<PlayerItem>
     */
    public function placeableItems(Player $player, ?Pnj $pnj): array
    {
        if (!$this->isAvailableFor($player, $pnj)) {
            return [];
        }

        $items = [];
        foreach ($player->getInventories() as $inventory) {
            if (Inventory::TYPE_MATERIA !== $inventory->getType()) {
                continue;
            }
            foreach ($inventory->getItems() as $item) {
                if ($item->isMateria()
                    && $this->counterfeitService->eyeSees($player, $item)
                    && 0 === $item->getGear()
                    && null === $item->getSlotSet()) {
                    $items[] = $item;
                }
            }
        }

        return $items;
    }

    /**
     * Place une contrefacon via le contact. Deux issues, jamais de retour :
     * ecoulee (la prime tombe), ou saisie (confiscation, amende, decote
     * Chevaliers). Dans les deux cas l'objet quitte le monde des joueurs.
     *
     * @return array{caught: bool, gils: int}
     *
     * @throws ShadowsMarketException si le guichet ou l'objet refuse (cle en message)
     */
    public function place(Player $player, ?Pnj $pnj, PlayerItem $item): array
    {
        if (null === $pnj || !$this->catalog->isCounter($pnj->getSlug()) || !$this->approach->hasMet($player)) {
            // Le refus neutre d'un mauvais guichet — ne rien reveler.
            throw new ShadowsMarketException('game.shadows.placement.error.counter');
        }
        if (GameTimeService::PHASE_NIGHT !== $this->gameTimeService->getPhase()) {
            throw new ShadowsMarketException('game.shadows.placement.error.daylight');
        }
        if (!$this->tierAtLeast($player, $this->catalog->placementRequiredTier())) {
            throw new ShadowsMarketException('game.shadows.placement.error.tier');
        }
        if (!$item->isMateria() || !$this->counterfeitService->eyeSees($player, $item)) {
            // Une authentique, ou un faux que le joueur ne voit pas : rien a
            // placer ici — et le refus ne revele rien.
            throw new ShadowsMarketException('game.shadows.placement.error.item');
        }
        if (0 !== $item->getGear() || null !== $item->getSlotSet()) {
            throw new ShadowsMarketException('game.shadows.placement.error.item');
        }

        $caught = $this->roll(100) <= $this->catalog->placementSearchChancePercent();
        $this->entityManager->remove($item);

        if ($caught) {
            $fine = min($player->getGils(), $this->catalog->placementFineGils());
            if ($fine > 0) {
                $player->removeGils($fine);
            }
            $this->penalizeChevaliers($player, $this->catalog->placementCaughtPenalty());
            $this->addJournalEntry($player, 'Le contact s\'est fait pincer, et votre nom est sorti. Saisie, amende — et l\'Ordre vous regarde autrement.', [
                'action' => 'placement_caught',
                'fine' => $fine,
            ]);
            $this->entityManager->persist($player);
            $this->entityManager->flush();

            return ['caught' => true, 'gils' => -$fine];
        }

        $price = $item->getGenericItem()->getPrice() ?? 0;
        $gils = max(1, intdiv($price * $this->catalog->placementRewardPercent(), 100));
        $player->addGils($gils);
        $this->addJournalEntry($player, 'Le faux est parti chez un collectionneur qui ne posera pas de questions.', [
            'action' => 'placement_sold',
            'gils' => $gils,
        ]);
        $this->entityManager->persist($player);
        $this->entityManager->flush();

        $this->reputationManager->grantGestureReputation($player, 'grey_market_sale');

        return ['caught' => false, 'gils' => $gils];
    }

    private function tierAtLeast(Player $player, \App\Enum\ReputationTier $tier): bool
    {
        $faction = $this->entityManager->getRepository(Faction::class)
            ->findOneBy(['slug' => ShadowsApproach::FACTION_SLUG]);
        if (null === $faction) {
            return false;
        }

        $line = $this->entityManager->getRepository(PlayerFaction::class)->findOneBy([
            'player' => $player,
            'faction' => $faction,
        ]);

        return null !== $line && $line->getReputation() >= $tier->threshold();
    }

    /**
     * La meme decote que la contrebande : se faire prendre EST le geste
     * oppose. Crochet inerte si l'Ordre n'est pas seme.
     */
    private function penalizeChevaliers(Player $player, int $penalty): void
    {
        $chevaliers = $this->entityManager->getRepository(Faction::class)
            ->findOneBy(['slug' => ShadowsSmuggling::CHEVALIERS_SLUG]);
        if (null === $chevaliers) {
            return;
        }

        $this->reputationManager->addReputation($player, $chevaliers, -$penalty);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function addJournalEntry(Player $player, string $message, array $metadata): void
    {
        $entry = new PlayerJournalEntry();
        $entry->setPlayer($player);
        $entry->setType(PlayerJournalEntry::TYPE_EXPLORATION);
        $entry->setMessage($message);
        $entry->setMetadata($metadata);
        $this->entityManager->persist($entry);
    }

    /**
     * Protegee pour que les tests fixent le tirage.
     */
    protected function roll(int $max): int
    {
        return random_int(1, max(1, $max));
    }
}
