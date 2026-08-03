<?php

namespace App\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\PlayerWeeklyFenceSale;
use App\Entity\App\Pnj;
use App\Entity\Game\Faction;
use App\Entity\Game\Item;
use App\GameEngine\Retention\WeekKey;
use App\GameEngine\World\GameTimeService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Le receleur — le marche gris des Ruelles (FAC-06).
 *
 * Vendre hors taxe de cite, contre une coupe qui va a la Confrerie au lieu du
 * tresor de la guilde controlante. En gils, le receleur bat le rachat PNJ
 * commun (30 %) mais reste sous le HV : la coupe (15 %) est **toujours**
 * superieure a la taxe max de cite (10 %), le loader du catalogue le refuse
 * autrement. Ses trois garde-fous : la coupe, le plafond de lots hebdomadaire,
 * l'acces au palier Ami — la Confrerie ne travaille pas avec des inconnus.
 *
 * Le guichet suit les horaires de son PNJ : Tancrede ne recele qu'a la nuit,
 * c'est sa couverture qui ferme le jour.
 */
class ShadowsMarket
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ShadowsMarketCatalog $catalog,
        private readonly GameTimeService $gameTimeService,
    ) {
    }

    /**
     * Le prix du receleur pour cet objet, ou `null` si cette vente n'est pas
     * une vente au marche gris (mauvais guichet, guichet ferme, palier
     * insuffisant, plafond atteint, objet lie). Le repli est alors le rachat
     * commun : le receleur est un privilege, jamais un droit — et son refus ne
     * ferme jamais la vente.
     */
    public function fencePriceFor(?Pnj $pnj, Item $item, Player $player, bool $exchangeable): ?int
    {
        if (null === $pnj || !$this->catalog->isCounter($pnj->getSlug())) {
            return null;
        }
        if (!$pnj->isShopOpen($this->gameTimeService->getHour())) {
            // La couverture est fermee : pas de receleur en plein jour.
            return null;
        }
        if (!$exchangeable) {
            // Un objet lie ne circule pas, meme au marche gris — le receleur
            // vole le systeme, jamais la regle de liaison.
            return null;
        }
        if (!$this->isEligible($player)) {
            return null;
        }
        if ($this->lotsThisWeek($player) >= $this->catalog->weeklyLotCap()) {
            return null;
        }

        $price = $item->getPrice() ?? 0;

        return max(1, intdiv($price * (100 - $this->catalog->fenceCutPercent()), 100));
    }

    /**
     * Enregistre le lot passe cette semaine. A appeler apres la vente reussie
     * — jamais avant : un refus tardif ne doit pas consommer le plafond.
     */
    public function recordFenceSale(Player $player, ?\DateTimeImmutable $now = null): void
    {
        $weekKey = WeekKey::of($now ?? new \DateTimeImmutable());

        $row = $this->entityManager->getRepository(PlayerWeeklyFenceSale::class)->findOneBy([
            'player' => $player,
            'weekKey' => $weekKey,
        ]);
        if (null === $row) {
            $row = new PlayerWeeklyFenceSale();
            $row->setPlayer($player);
            $row->setWeekKey($weekKey);
            $this->entityManager->persist($row);
        }

        $row->incrementLots();
    }

    public function isEligible(Player $player): bool
    {
        $faction = $this->entityManager->getRepository(Faction::class)
            ->findOneBy(['slug' => ShadowsApproach::FACTION_SLUG]);
        if (null === $faction) {
            return false;
        }

        $line = $this->entityManager->getRepository(\App\Entity\App\PlayerFaction::class)->findOneBy([
            'player' => $player,
            'faction' => $faction,
        ]);

        return null !== $line
            && $line->getReputation() >= $this->catalog->fenceRequiredTier()->threshold();
    }

    public function lotsThisWeek(Player $player, ?\DateTimeImmutable $now = null): int
    {
        $row = $this->entityManager->getRepository(PlayerWeeklyFenceSale::class)->findOneBy([
            'player' => $player,
            'weekKey' => WeekKey::of($now ?? new \DateTimeImmutable()),
        ]);

        return $row?->getLots() ?? 0;
    }
}
