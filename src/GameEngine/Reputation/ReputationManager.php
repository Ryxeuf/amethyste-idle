<?php

namespace App\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\PlayerFaction;
use App\Entity\Game\Faction;
use App\Enum\ReputationTier;
use Doctrine\ORM\EntityManagerInterface;

class ReputationManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FactionTensionCatalog $tensionCatalog,
        private readonly GestureReputationCatalog $gestureCatalog,
    ) {
    }

    public function addReputation(Player $player, Faction $faction, int $amount): PlayerFaction
    {
        $playerFaction = $this->resolve($player, $faction);

        $before = $playerFaction->getReputation();
        $playerFaction->addReputation($amount);

        $this->applyTension($player, $faction, $before, $amount);

        $this->entityManager->flush();

        return $playerFaction;
    }

    /**
     * Un geste route par le catalogue (FAC-02).
     *
     * GAME_WORLD § 6.4 b : les quetes amorcent, les gestes font le regime de
     * croisiere. Le geste ne connait pas sa faction — il porte une cle
     * (`auction_sale`, `undead_kill`...), et le catalogue dit qui il nourrit.
     *
     * **Le crochet est inerte tant que la cible n'existe pas.** Une route vers
     * une faction pas encore semee (la Fonderie avant FAC-04, les Ruelles du
     * marche gris avant FAC-06) rend `null` sans rien ecrire : le jour ou la
     * faction arrive, le geste nourrit — sans qu'on ait a revenir ici.
     *
     * `$fallbackAmount` sert aux gestes dont le montant n'est pas fixe dans la
     * route (le kill suit le palier du monstre).
     */
    public function grantGestureReputation(Player $player, string $gesture, ?int $fallbackAmount = null): ?PlayerFaction
    {
        $route = $this->gestureCatalog->routeFor($gesture);
        if (null === $route) {
            return null;
        }

        $faction = $this->entityManager->getRepository(Faction::class)->findOneBy(['slug' => $route['faction']]);
        if (null === $faction) {
            // Crochet declare, faction pas encore semee : inerte, pas casse.
            return null;
        }

        $amount = $route['amount'] ?? $fallbackAmount ?? 0;
        if ($amount <= 0) {
            return null;
        }

        return $this->grantCappedReputation($player, $faction, $amount);
    }

    /**
     * Un gain de geste, borne par le plafond journalier de la faction.
     *
     * Le plafond ne refuse pas le geste — il rogne le gain a hauteur du reste
     * disponible, puis le laisse a zero jusqu'au lendemain. Les quetes passent
     * par `addReputation()` et ne sont jamais plafonnees : on ne refait pas
     * une quete.
     */
    public function grantCappedReputation(Player $player, Faction $faction, int $amount): PlayerFaction
    {
        $playerFaction = $this->resolve($player, $faction);

        $dayKey = $this->todayKey();
        $granted = min($amount, max(0, $this->gestureCatalog->dailyCap() - $playerFaction->gestureGainedOn($dayKey)));

        if ($granted > 0) {
            $before = $playerFaction->getReputation();
            $playerFaction->addReputation($granted);
            $playerFaction->recordGestureGain($dayKey, $granted);

            $this->applyTension($player, $faction, $before, $granted);
        }

        $this->entityManager->flush();

        return $playerFaction;
    }

    /**
     * La cle du jour courant — protegee pour que les tests la fixent.
     */
    protected function todayKey(): string
    {
        return (new \DateTimeImmutable('today'))->format('Y-m-d');
    }

    /**
     * La tension par paires (FAC-01).
     *
     * GAME_WORLD § 6.4 a : « progresser chez l'un fait decroitre chez son
     * oppose **au-dela du palier Ami** ». En dessous, on peut etre bien vu de
     * tout le monde ; au-dela, il faut choisir.
     *
     * **La decote ne se propage pas.** Retirer chez l'oppose n'est pas un
     * geste : ca ne redescend pas en cascade sur son propre oppose, et ca ne
     * cree pas de `PlayerFaction` la ou le joueur n'en a jamais eu. Un joueur
     * qui n'a jamais rencontre la faction adverse n'a rien a y perdre.
     */
    private function applyTension(Player $player, Faction $faction, int $reputationBefore, int $gain): void
    {
        $offset = $this->tensionCatalog->offsetFor($reputationBefore, $gain);
        if ($offset <= 0) {
            return;
        }

        $opponentSlug = $this->tensionCatalog->opponentOf($faction->getSlug());
        if ($opponentSlug === null) {
            return;
        }

        $opponent = $this->entityManager->getRepository(Faction::class)->findOneBy(['slug' => $opponentSlug]);
        if ($opponent === null) {
            // Paire declaree, faction pas encore semee (la Fonderie arrive avec
            // FAC-04). La tension est inerte, pas cassee.
            return;
        }

        $playerOpponent = $this->entityManager->getRepository(PlayerFaction::class)->findOneBy([
            'player' => $player,
            'faction' => $opponent,
        ]);
        if ($playerOpponent === null) {
            return;
        }

        $playerOpponent->setReputation(max(
            $this->tensionCatalog->offsetFloor(),
            $playerOpponent->getReputation() - $offset,
        ));
    }

    private function resolve(Player $player, Faction $faction): PlayerFaction
    {
        $playerFaction = $this->entityManager->getRepository(PlayerFaction::class)->findOneBy([
            'player' => $player,
            'faction' => $faction,
        ]);

        if (null === $playerFaction) {
            $playerFaction = new PlayerFaction();
            $playerFaction->setPlayer($player);
            $playerFaction->setFaction($faction);
            $this->entityManager->persist($playerFaction);
        }

        return $playerFaction;
    }

    public function getPlayerFaction(Player $player, Faction $faction): ?PlayerFaction
    {
        return $this->entityManager->getRepository(PlayerFaction::class)->findOneBy([
            'player' => $player,
            'faction' => $faction,
        ]);
    }

    /**
     * BES-01 : les seuils suivent le palier, plus une echelle 1-40.
     * Recalibrage a magnitude constante — l'ancien seuil >= 20 (50 points)
     * couvrait les ex-niveaux du bloc de fin, devenus T4.
     */
    public function getReputationAmount(int $monsterTier): int
    {
        return match (true) {
            $monsterTier >= 4 => 50,
            $monsterTier >= 3 => 25,
            $monsterTier >= 2 => 15,
            default => 10,
        };
    }

    /**
     * @return array<string, ReputationTier>
     */
    public function getUnlockedTiers(Player $player, Faction $faction): array
    {
        $playerFaction = $this->getPlayerFaction($player, $faction);
        if (null === $playerFaction) {
            return [];
        }

        $currentTier = $playerFaction->getTier();
        $unlocked = [];

        foreach (ReputationTier::cases() as $tier) {
            if ($tier === ReputationTier::Hostile || $tier === ReputationTier::Inconnu) {
                continue;
            }
            if ($playerFaction->getReputation() >= $tier->threshold()) {
                $unlocked[$tier->value] = $tier;
            }
        }

        return $unlocked;
    }
}
