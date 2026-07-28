<?php

namespace App\GameEngine\Retention;

use App\Entity\App\Player;
use App\Entity\App\PlayerWeeklyCommission;
use App\Entity\App\Zone;
use App\Enum\WeeklyCommissionStatus;
use App\Repository\PlayerWeeklyCommissionRepository;
use App\Repository\SettlementRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Tirage de la commission de la semaine (RET-02).
 *
 * **L'objectif sort de ce que le joueur travaille deja.** Proposer de la peche a
 * qui n'a jamais peche transforme un rendez-vous en corvee : le tirage se limite
 * aux gabarits dont le domaine figure dans l'experience du personnage, et ne
 * s'ouvre au pool entier que pour un joueur qui n'a encore rien travaille.
 *
 * **La livraison va a un foyer**, jamais a un guichet abstrait. Ce qu'on
 * rapporte se depose quelque part, dans une ville qui monte — c'est ce qui
 * branche le joueur solo sur le chantier collectif sans lui demander de rejoindre
 * une guilde.
 *
 * Le tirage est **deterministe** pour une semaine et un joueur donnes. Deux
 * executions de la rotation — un rejeu, un `--force` — ne doivent pas donner deux
 * commissions differentes, sinon relancer la commande devient un reroll.
 */
class WeeklyCommissionGenerator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerWeeklyCommissionRepository $commissionRepository,
        private readonly SettlementRepository $settlementRepository,
        private readonly WeeklyCommissionTemplateLoader $loader,
    ) {
    }

    /**
     * @param list<Player> $players
     *
     * @return array{created: int, skipped: int, expired: int, unassigned: int}
     */
    public function generateFor(array $players, ?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();
        $weekKey = self::weekKey($now);
        $pool = $this->loader->load()['commissions'];

        $report = ['created' => 0, 'skipped' => 0, 'expired' => 0, 'unassigned' => 0];

        // Les semaines precedentes se ferment d'abord : une commission de la
        // semaine passee restee ouverte ferait deux rendez-vous en cours, et le
        // joueur ne saurait plus lequel court.
        foreach ($this->commissionRepository->findStaleOpen($weekKey) as $stale) {
            $stale->setStatus(WeeklyCommissionStatus::Expired);
            ++$report['expired'];
        }

        $zones = $this->zonesWithSettlement();

        foreach ($players as $player) {
            if ($this->commissionRepository->findOneForWeek($player, $weekKey) !== null) {
                // Une commission par semaine et par personnage. C'est ici que le
                // refus du reroll devient effectif cote code — la contrainte
                // d'unicite en base le tient face aux appels concurrents.
                ++$report['skipped'];
                continue;
            }

            $template = $this->pick($pool, $player, $weekKey);
            if ($template === null) {
                ++$report['skipped'];
                continue;
            }

            $commission = new PlayerWeeklyCommission(
                $player,
                $weekKey,
                $template->slug,
                $template->activity,
                $template->target,
            );

            $zone = $this->pickZone($zones, $player, $weekKey);
            $commission->setDeliveryZone($zone);
            if ($zone === null) {
                // Aucun foyer dans le monde : la commission existe quand meme,
                // et se livrera des qu'une ville en aura un. La refuser priverait
                // le joueur de son rendez-vous pour une raison qui ne le regarde
                // pas.
                ++$report['unassigned'];
            }

            $this->entityManager->persist($commission);
            ++$report['created'];
        }

        $this->entityManager->flush();

        return $report;
    }

    /**
     * Semaine ISO — la meme clef que la rotation des defis de guilde, pour que
     * les deux rendez-vous tombent le meme lundi.
     */
    public static function weekKey(\DateTimeImmutable $now): string
    {
        return $now->modify('monday this week')->setTime(0, 0, 0)->format('o-\WW');
    }

    /**
     * @param list<WeeklyCommissionTemplate> $pool
     */
    private function pick(array $pool, Player $player, string $weekKey): ?WeeklyCommissionTemplate
    {
        if ($pool === []) {
            return null;
        }

        $worked = $this->workedDomains($player);
        $eligible = array_values(array_filter(
            $pool,
            static fn (WeeklyCommissionTemplate $t): bool => isset($worked[$t->domain]),
        ));

        // Un personnage tout neuf n'a travaille aucun domaine : lui refuser une
        // commission le priverait du rendez-vous precisement la ou il compte le
        // plus (GAME_PROGRESSION § 3, le passage des semaines 3 a 6).
        if ($eligible === []) {
            $eligible = $pool;
        }

        return $eligible[$this->cursor($player, $weekKey) % \count($eligible)];
    }

    /**
     * @param list<Zone> $zones
     */
    private function pickZone(array $zones, Player $player, string $weekKey): ?Zone
    {
        if ($zones === []) {
            return null;
        }

        // La zone courante du joueur d'abord, si elle a un foyer : livrer la ou
        // l'on est deja est le contraire d'une corvee de deplacement.
        $current = $player->getCurrentZone();
        if ($current !== null) {
            foreach ($zones as $zone) {
                if ($zone === $current) {
                    return $zone;
                }
            }
        }

        return $zones[$this->cursor($player, $weekKey) % \count($zones)];
    }

    /**
     * Curseur stable : meme joueur, meme semaine, meme tirage.
     */
    private function cursor(Player $player, string $weekKey): int
    {
        return abs(crc32($weekKey . ':' . $player->getId()));
    }

    /**
     * @return array<string, true>
     */
    private function workedDomains(Player $player): array
    {
        $worked = [];
        foreach ($player->getDomainExperiences() as $experience) {
            if ($experience->getTotalExperience() <= 0) {
                continue;
            }
            $slug = $experience->getDomain()->getSlug();
            if ($slug !== '') {
                $worked[$slug] = true;
            }
        }

        return $worked;
    }

    /**
     * @return list<Zone>
     */
    private function zonesWithSettlement(): array
    {
        $zones = [];
        foreach ($this->settlementRepository->findAllRanked() as $settlement) {
            $zones[] = $settlement->getZone();
        }

        return $zones;
    }
}
