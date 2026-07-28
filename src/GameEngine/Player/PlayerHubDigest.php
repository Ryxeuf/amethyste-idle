<?php

declare(strict_types=1);

namespace App\GameEngine\Player;

use App\Entity\App\Player;
use App\Entity\Game\Quest;
use App\GameEngine\Quest\PlayerQuestHelper;
use App\GameEngine\Retention\WeeklyAttendanceService;
use App\GameEngine\Retention\WeeklyCommissionGenerator;
use App\Repository\CraftJobRepository;
use App\Repository\CraftOrderRepository;
use App\Repository\GardenPlotRepository;
use App\Repository\PlayerExpeditionRepository;
use App\Repository\PlayerHouseRepository;
use App\Repository\PlayerJournalEntryRepository;
use App\Repository\PlayerWeeklyCommissionRepository;
use App\Repository\PrivateMessageRepository;

/**
 * Modele de lecture du tableau de bord : reprise, attentes, recap.
 *
 * Le tableau de bord d'avant montrait l'equipement porte, la zone trois fois et
 * une « barre de statut monde » qui repetait les Gils deja affiches deux lignes
 * plus haut. Autrement dit : un resume de fiche de personnage, la ou le joueur
 * qui se connecte pose deux questions — **qu'est-ce qui m'attend** et **je fais
 * quoi maintenant**. Aucune des deux n'avait de reponse, alors que le jeu en
 * accumule les elements (expedition finie, atelier termine, loyer en retard,
 * quete bouclee, XP de domaine non depensee).
 *
 * Trois regles de composition, tenues ici plutot que dans le gabarit :
 *
 * 1. **Une seule action primaire** — la reprise (`resume`), qui depend de l'etat
 *    du personnage et de rien d'autre.
 * 2. **Une attente est actionnable** — sinon c'est de l'information, et
 *    l'information a son ecran (`pending`). Les ventes conclues a l'hotel des
 *    ventes creditent les Gils immediatement et les encheres expirees rendent
 *    l'objet d'elles-memes : ni l'une ni l'autre n'attend le joueur, donc ni
 *    l'une ni l'autre n'a sa ligne ici.
 * 3. **Le hub ne double aucun ecran** — il y renvoie. Chaque ligne porte une
 *    route ; aucune ne rejoue le contenu de sa destination.
 *
 * Le cout est borne : huit lectures indexees par joueur, dont sept ne
 * retournent qu'un compte ou une poignee de lignes.
 */
final class PlayerHubDigest
{
    /** Fenetre du recap — « depuis la derniere fois », en pratique la veille. */
    public const RECAP_WINDOW_SECONDS = 86400;

    /** Lignes de journal affichees sous l'agregat. */
    public const RECAP_ENTRIES = 5;

    /**
     * Profondeur de lecture du journal.
     *
     * Une seule requete sert l'agregat **et** les lignes. La borne evite qu'un
     * joueur tres actif fasse remonter ses 200 entrees pour en afficher cinq ;
     * en contrepartie l'agregat sature au-dela de 40 evenements par jour, ce que
     * le libelle assume (« 40+ » n'apporterait rien de plus que « beaucoup »).
     */
    private const JOURNAL_LOOKBACK = 40;

    public function __construct(
        private readonly PlayerExpeditionRepository $expeditionRepository,
        private readonly CraftJobRepository $craftJobRepository,
        private readonly CraftOrderRepository $craftOrderRepository,
        private readonly PrivateMessageRepository $privateMessageRepository,
        private readonly PlayerHouseRepository $houseRepository,
        private readonly GardenPlotRepository $gardenPlotRepository,
        private readonly PlayerJournalEntryRepository $journalRepository,
        private readonly PlayerWeeklyCommissionRepository $commissionRepository,
        private readonly PlayerQuestHelper $questHelper,
        private readonly WeeklyAttendanceService $weeklyAttendance,
    ) {
    }

    /**
     * L'unique action primaire de l'ecran.
     *
     * L'ordre des cas est un ordre de contrainte, pas de gout : mort et combat
     * interdisent tout le reste, le registre 2 (voyage, expedition) occupe un
     * creneau exclusif, et ce n'est qu'ensuite qu'on repart en zone.
     */
    public function resume(Player $player, ?\DateTimeImmutable $now = null): HubResume
    {
        $now ??= new \DateTimeImmutable();

        if ($player->isDead()) {
            return new HubResume(HubResume::STATE_DEAD, 'app_game_zone');
        }

        if (null !== $player->getFight()) {
            return new HubResume(HubResume::STATE_FIGHT, 'app_game_fight');
        }

        if ($player->isTraveling() && null !== $player->getTravelArrivesAt()) {
            return new HubResume(
                HubResume::STATE_TRAVEL,
                'app_game_zone',
                $player->getTravelToZone(),
                max(0, $player->getTravelArrivesAt()->getTimestamp() - $now->getTimestamp()),
                false,
            );
        }

        $expedition = $this->expeditionRepository->findForPlayer($player);
        if (null !== $expedition) {
            if ($expedition->isComplete($now)) {
                return new HubResume(
                    HubResume::STATE_EXPEDITION_DONE,
                    'app_game_zone',
                    $expedition->getZone(),
                );
            }

            return new HubResume(
                HubResume::STATE_EXPEDITION,
                'app_game_zone',
                $expedition->getZone(),
                max(0, $expedition->getEndsAt()->getTimestamp() - $now->getTimestamp()),
                false,
            );
        }

        $zone = $player->getCurrentZone();

        return null === $zone
            ? new HubResume(HubResume::STATE_LOST, 'app_game_world_map')
            : new HubResume(HubResume::STATE_READY, 'app_game_zone', $zone);
    }

    /**
     * Ce qui attend le joueur, du plus urgent au plus tranquille.
     *
     * L'ordre traduit un cout d'inaction : ce qui se degrade (loyer) passe avant
     * ce qui dort en attendant (butin), qui passe avant ce qui ne fait
     * qu'attendre (courrier).
     *
     * @return list<HubPendingItem>
     */
    public function pending(Player $player, ?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();

        $items = [];

        $house = $this->houseRepository->findForOwner($player);
        if (null !== $house && $house->isInArrears($now)) {
            $items[] = new HubPendingItem(
                'house_rent',
                'app_game_house',
                tone: HubPendingItem::TONE_LOSS,
            );
        }

        $expedition = $this->expeditionRepository->findForPlayer($player);
        if (null !== $expedition && $expedition->isComplete($now)) {
            $items[] = new HubPendingItem(
                'expedition_ready',
                'app_game_zone',
                tone: HubPendingItem::TONE_GAIN,
            );
        }

        // RET-02b : la commission de la semaine est prete, il reste a la porter
        // au foyer. C'est une attente **actionnable** au sens de la regle 2 : le
        // joueur doit se deplacer, et sans cette ligne il ne saurait pas que sa
        // semaine attend d'etre refermee.
        $commission = $this->commissionRepository->findCurrent(
            $player,
            WeeklyCommissionGenerator::weekKey($now),
        );
        if (null !== $commission && $commission->isComplete()) {
            $items[] = new HubPendingItem(
                'commission_ready',
                'app_game_zone',
                tone: HubPendingItem::TONE_GAIN,
            );
        }

        $craftJob = $this->craftJobRepository->findActiveForPlayer($player);
        if (null !== $craftJob && $craftJob->isReady($now)) {
            $items[] = new HubPendingItem(
                'craft_ready',
                'app_game_craft',
                tone: HubPendingItem::TONE_GAIN,
            );
        }

        if (null !== $house) {
            $ripe = 0;
            foreach ($this->gardenPlotRepository->findForHouse($house) as $plot) {
                if ($plot->isRipe($now)) {
                    ++$ripe;
                }
            }
            if ($ripe > 0) {
                $items[] = new HubPendingItem(
                    'garden_ripe',
                    'app_game_house',
                    self::countParams($ripe),
                    tone: HubPendingItem::TONE_GAIN,
                );
            }
        }

        $ordersToFulfill = 0;
        foreach ($this->craftOrderRepository->findClaimedByCrafter($player) as $order) {
            if ($order->isReady($now)) {
                ++$ordersToFulfill;
            }
        }
        if ($ordersToFulfill > 0) {
            $items[] = new HubPendingItem(
                'craft_orders',
                'app_game_craft_order_workshop',
                self::countParams($ordersToFulfill),
                tone: HubPendingItem::TONE_GAIN,
            );
        }

        $questsReady = 0;
        foreach ($player->getQuests() as $playerQuest) {
            if ($this->questHelper->isPlayerQuestCompleted($playerQuest)) {
                ++$questsReady;
            }
        }
        if ($questsReady > 0) {
            $items[] = new HubPendingItem(
                'quests_ready',
                'app_game_quests',
                self::countParams($questsReady),
                tone: HubPendingItem::TONE_GAIN,
            );
        }

        $availableXp = 0;
        foreach ($player->getDomainExperiences() as $domainExperience) {
            $availableXp += max(0, $domainExperience->getAvailableExperience());
        }
        if ($availableXp > 0) {
            $items[] = new HubPendingItem(
                'talent_xp',
                'app_game_skills',
                ['%xp%' => $availableXp],
                tone: HubPendingItem::TONE_GAIN,
            );
        }

        $unread = $this->privateMessageRepository->countUnreadForPlayer($player);
        if ($unread > 0) {
            $items[] = new HubPendingItem(
                'messages_unread',
                'app_game_messages_inbox',
                self::countParams($unread),
            );
        }

        return $items;
    }

    /**
     * Les quetes en cours, les plus avancees d'abord.
     *
     * Le hub montre l'avancement, pas les objectifs : une barre par quete, et un
     * lien vers le journal de quetes pour le detail. Rejouer le detail ici
     * revenait a maintenir deux fois le meme ecran.
     *
     * @return list<array{quest: Quest, percent: int}>
     */
    public function quests(Player $player, int $limit = 3): array
    {
        $lines = [];
        foreach ($player->getQuests() as $playerQuest) {
            $lines[] = [
                'quest' => $playerQuest->getQuest(),
                'percent' => $this->questHelper->getPlayerQuestProgress($playerQuest),
            ];
        }

        usort($lines, static fn (array $a, array $b): int => $b['percent'] <=> $a['percent']);

        return \array_slice($lines, 0, max(0, $limit));
    }

    /**
     * Ce que le personnage a fait dans les dernieres 24 h.
     */
    public function recap(Player $player, ?\DateTimeImmutable $now = null): HubRecap
    {
        $now ??= new \DateTimeImmutable();
        $since = $now->getTimestamp() - self::RECAP_WINDOW_SECONDS;

        $entries = $this->journalRepository->findByPlayer($player, null, self::JOURNAL_LOOKBACK);

        $counts = [];
        $recent = [];
        foreach ($entries as $entry) {
            // `createdAt` vient du trait Gedmo, sans type de retour declare :
            // le garde n'est pas defensif, il est necessaire.
            $createdAt = $entry->getCreatedAt();
            if (!$createdAt instanceof \DateTimeInterface || $createdAt->getTimestamp() < $since) {
                continue;
            }

            $type = $entry->getType();
            $counts[$type] = ($counts[$type] ?? 0) + 1;

            if (\count($recent) < self::RECAP_ENTRIES) {
                $recent[] = $entry;
            }
        }

        arsort($counts);

        return new HubRecap($counts, $recent);
    }

    /**
     * L'assiduite de la semaine — une **restitution**, pas une relance.
     *
     * Elle est en lecture pure : regarder son tableau de bord n'a jamais compte
     * comme une journee active. C'est le seul endroit ou la distinction entre
     * « s'etre connecte » et « avoir joue » pourrait se perdre, et elle est ce
     * qui immunise la brique contre le multi-compte comme contre la corvee.
     */
    public function attendance(Player $player, ?\DateTimeImmutable $now = null): HubAttendance
    {
        $days = $this->weeklyAttendance->currentDays($player, $now);
        $next = $this->weeklyAttendance->nextTier($days);

        if ($next === null) {
            return new HubAttendance($days);
        }

        return new HubAttendance($days, $next->days, $next->gils, $next->energy);
    }

    /**
     * Parametres d'un libelle compte : le nombre, et la marque du pluriel.
     *
     * Convention deja en place dans le catalogue (`game.journal.entries_count`) :
     * les fichiers sont du JSON plat, sans ICU, donc l'accord se joue par un
     * `%plural%` interpole dans le libelle. C'est rustique, mais c'est la seule
     * facon d'ecrire « 1 quete achevee » et « 3 quetes achevees » avec une
     * chaine unique tant que le catalogue n'est pas passe a ICU.
     *
     * @return array<string, int|string>
     */
    private static function countParams(int $count): array
    {
        return ['%count%' => $count, '%plural%' => $count > 1 ? 's' : ''];
    }
}
