<?php

namespace App\Command;

use App\Entity\Game\Domain;
use App\Entity\Game\Item;
use App\Entity\Game\Monster;
use App\Entity\Game\MonsterItem;
use App\Entity\Game\Skill;
use App\Entity\Game\Spell;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:balance:report',
    description: 'Generate a balance report: monsters, items, drops, domains, spells, combat stats',
)]
class BalanceReportCommand extends Command
{
    private const BASE_XP_PER_KILL = 10;
    private const BOSS_XP_MULTIPLIER = 5;
    private const SELL_RATIO = 0.3;
    private const DPS_VARIANCE_THRESHOLD = 0.3;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('section', 's', InputOption::VALUE_OPTIONAL, 'Section to display: monsters, items, drops, domains, spells, combat, alerts, all', 'all')
            ->addOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Number of days to analyze for combat stats (default: 30)', '30');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string $section */
        $section = $input->getOption('section');

        $io->title('Rapport d\'equilibrage — Amethyste-Idle');

        $monsters = $this->entityManager->getRepository(Monster::class)->findAll();
        $items = $this->entityManager->getRepository(Item::class)->findAll();
        $monsterItems = $this->entityManager->getRepository(MonsterItem::class)->findAll();
        $domains = $this->entityManager->getRepository(Domain::class)->findAll();
        $spells = $this->entityManager->getRepository(Spell::class)->findAll();

        $alerts = [];

        if (\in_array($section, ['all', 'monsters'], true)) {
            $alerts = array_merge($alerts, $this->reportMonsters($io, $monsters));
        }

        if (\in_array($section, ['all', 'items'], true)) {
            $alerts = array_merge($alerts, $this->reportItems($io, $items));
        }

        if (\in_array($section, ['all', 'drops'], true)) {
            $alerts = array_merge($alerts, $this->reportDrops($io, $monsterItems, $monsters));
        }

        if (\in_array($section, ['all', 'domains'], true)) {
            $alerts = array_merge($alerts, $this->reportDomains($io, $domains));
        }

        if (\in_array($section, ['all', 'spells'], true)) {
            $alerts = array_merge($alerts, $this->reportSpells($io, $spells));
        }

        if (\in_array($section, ['all', 'combat'], true)) {
            /** @var string $daysOption */
            $daysOption = $input->getOption('days');
            $days = max(1, (int) $daysOption);
            $alerts = array_merge($alerts, $this->reportCombatStats($io, $days));
        }

        if (\in_array($section, ['all', 'alerts'], true)) {
            $this->reportAlerts($io, $alerts);
        }

        return Command::SUCCESS;
    }

    /**
     * @param Monster[] $monsters
     *
     * @return string[]
     */
    private function reportMonsters(SymfonyStyle $io, array $monsters): array
    {
        $io->section('Monstres — Stats par palier de niveau');

        $alerts = [];

        usort($monsters, fn (Monster $a, Monster $b) => $a->getLevel() <=> $b->getLevel());

        $rows = [];
        foreach ($monsters as $monster) {
            $level = $monster->getLevel();
            $xp = self::BASE_XP_PER_KILL * $level;
            if ($monster->isBoss()) {
                $xp *= self::BOSS_XP_MULTIPLIER;
            }

            $attack = $monster->getAttack();
            $attackDmg = $attack->getDamage() ?? 0;

            $rows[] = [
                $monster->getName(),
                $level,
                $monster->isBoss() ? 'Boss' : 'Normal',
                $monster->getDifficulty(),
                $monster->getLife(),
                $attackDmg,
                $monster->getHit(),
                $monster->getSpeed(),
                $xp,
            ];

            // Alertes : monstre avec 0 HP ou 0 degats d'attaque
            if ($monster->getLife() <= 0) {
                $alerts[] = sprintf('[MONSTRE] %s (lvl %d) a 0 HP', $monster->getName(), $level);
            }
            if ($attackDmg <= 0) {
                $alerts[] = sprintf('[MONSTRE] %s (lvl %d) a 0 degats d\'attaque', $monster->getName(), $level);
            }

            // Alerte : HP trop faible ou trop eleve pour le niveau
            $expectedHpMin = $level * 10;
            $expectedHpMax = $level * 80;
            if ($monster->isBoss()) {
                $expectedHpMax *= 3;
            }
            if ($monster->getLife() < $expectedHpMin) {
                $alerts[] = sprintf('[MONSTRE] %s (lvl %d) HP %d < seuil min %d', $monster->getName(), $level, $monster->getLife(), $expectedHpMin);
            }
            if ($monster->getLife() > $expectedHpMax) {
                $alerts[] = sprintf('[MONSTRE] %s (lvl %d) HP %d > seuil max %d', $monster->getName(), $level, $monster->getLife(), $expectedHpMax);
            }
        }

        $io->table(
            ['Nom', 'Niveau', 'Type', 'Diff.', 'HP', 'Degats', 'Hit', 'Speed', 'XP'],
            $rows,
        );

        $io->text(sprintf('Total : %d monstres', \count($monsters)));

        return $alerts;
    }

    /**
     * @param Item[] $items
     *
     * @return string[]
     */
    private function reportItems(SymfonyStyle $io, array $items): array
    {
        $io->section('Items — Prix et stats par type');

        $alerts = [];

        // Grouper par type
        $byType = [];
        foreach ($items as $item) {
            $byType[$item->getType()][] = $item;
        }

        foreach ($byType as $type => $typeItems) {
            $io->text(sprintf('--- Type : %s (%d items) ---', $type, \count($typeItems)));

            usort($typeItems, fn (Item $a, Item $b) => ($a->getLevel() ?? 0) <=> ($b->getLevel() ?? 0));

            $rows = [];
            foreach ($typeItems as $item) {
                $buyPrice = $item->getPrice();
                $sellPrice = $buyPrice !== null ? max(1, (int) ($buyPrice * self::SELL_RATIO)) : null;

                $rows[] = [
                    $item->getName(),
                    $item->getSlug(),
                    $item->getRarity() ?? '-',
                    $item->getLevel() ?? '-',
                    $item->getProtection() ?: '-',
                    $item->getMateriaSlots() ?: '-',
                    $buyPrice ?? '-',
                    $sellPrice ?? '-',
                    $item->getElement()->value,
                ];

                // Alerte : equipement sans prix
                if (\in_array($type, [Item::TYPE_GEAR_PIECE, Item::TYPE_STUFF], true) && $buyPrice === null) {
                    $alerts[] = sprintf('[ITEM] %s (%s) n\'a pas de prix d\'achat', $item->getName(), $type);
                }

                // Alerte : equipement sans rarete
                if ($type === Item::TYPE_GEAR_PIECE && $item->getRarity() === null) {
                    $alerts[] = sprintf('[ITEM] %s n\'a pas de rarete', $item->getName());
                }
            }

            $io->table(
                ['Nom', 'Slug', 'Rarete', 'Niveau', 'Protect.', 'Materia', 'Achat', 'Vente', 'Element'],
                $rows,
            );
        }

        $io->text(sprintf('Total : %d items', \count($items)));

        return $alerts;
    }

    /**
     * @param MonsterItem[] $monsterItems
     * @param Monster[]     $monsters
     *
     * @return string[]
     */
    private function reportDrops(SymfonyStyle $io, array $monsterItems, array $monsters): array
    {
        $io->section('Table de drops — Taux par monstre');

        $alerts = [];

        // Grouper par monstre
        $dropsByMonster = [];
        foreach ($monsterItems as $mi) {
            $monsterName = $mi->getMonster()->getName();
            $dropsByMonster[$monsterName][] = $mi;
        }

        ksort($dropsByMonster);

        $rows = [];
        foreach ($dropsByMonster as $monsterName => $drops) {
            foreach ($drops as $mi) {
                $item = $mi->getItem();
                $rows[] = [
                    $monsterName,
                    $item->getName(),
                    $item->getRarity() ?? '-',
                    $mi->isGuaranteed() ? 'OUI' : sprintf('%.1f%%', $mi->getProbability()),
                    $mi->getMinDifficulty() ?? '-',
                    $item->getPrice() ?? '-',
                ];

                // Alerte : probabilite aberrante
                if (!$mi->isGuaranteed() && $mi->getProbability() <= 0) {
                    $alerts[] = sprintf('[DROP] %s -> %s a une probabilite de %.1f%% (inutile)', $monsterName, $item->getName(), $mi->getProbability());
                }
            }
        }

        $io->table(
            ['Monstre', 'Item', 'Rarete', 'Probabilite', 'Diff. min', 'Prix achat'],
            $rows,
        );

        // Monstres sans drops
        $monstersWithDrops = array_unique(array_map(fn (MonsterItem $mi) => $mi->getMonster()->getId(), $monsterItems));
        foreach ($monsters as $monster) {
            if (!\in_array($monster->getId(), $monstersWithDrops, true)) {
                $alerts[] = sprintf('[DROP] %s (lvl %d) n\'a aucun drop configure', $monster->getName(), $monster->getLevel());
            }
        }

        $io->text(sprintf('Total : %d lignes de loot, %d monstres avec drops', \count($monsterItems), \count($dropsByMonster)));

        return $alerts;
    }

    /**
     * @param Domain[] $domains
     *
     * @return string[]
     */
    private function reportDomains(SymfonyStyle $io, array $domains): array
    {
        $io->section('Domaines — Courbe XP par arbre de talent');

        $alerts = [];

        foreach ($domains as $domain) {
            $skills = $domain->getSkills()->toArray();
            if ($skills === []) {
                $alerts[] = sprintf('[DOMAINE] %s n\'a aucune competence', $domain->getTitle());
                continue;
            }

            usort($skills, fn (Skill $a, Skill $b) => $a->getRequiredPoints() <=> $b->getRequiredPoints());

            $io->text(sprintf('--- %s (element: %s) ---', $domain->getTitle(), $domain->getElement() ?? 'aucun'));

            $rows = [];
            $totalCost = 0;
            foreach ($skills as $skill) {
                $totalCost += $skill->getRequiredPoints();
                $bonuses = [];
                if ($skill->getDamage() > 0) {
                    $bonuses[] = sprintf('+%d dmg', $skill->getDamage());
                }
                if ($skill->getHeal() > 0) {
                    $bonuses[] = sprintf('+%d heal', $skill->getHeal());
                }
                if ($skill->getHit() > 0) {
                    $bonuses[] = sprintf('+%d hit', $skill->getHit());
                }
                if ($skill->getCritical() > 0) {
                    $bonuses[] = sprintf('+%d crit', $skill->getCritical());
                }
                if ($skill->getLife() > 0) {
                    $bonuses[] = sprintf('+%d hp', $skill->getLife());
                }

                $rows[] = [
                    $skill->getTitle(),
                    $skill->getRequiredPoints(),
                    $totalCost,
                    implode(', ', $bonuses) ?: '-',
                ];
            }

            $io->table(
                ['Competence', 'Cout XP', 'Cumul XP', 'Bonus passifs'],
                $rows,
            );

            $io->text(sprintf('  Total XP pour tout debloquer : %d (%d competences)', $totalCost, \count($skills)));
        }

        return $alerts;
    }

    /**
     * @param Spell[] $spells
     *
     * @return string[]
     */
    private function reportSpells(SymfonyStyle $io, array $spells): array
    {
        $io->section('Sorts — Equilibrage degats/soins');

        $alerts = [];

        usort($spells, fn (Spell $a, Spell $b) => $a->getLevel() <=> $b->getLevel());

        $rows = [];
        foreach ($spells as $spell) {
            $dmgDisplay = $spell->getDamage() !== null
                ? ($spell->isPercent() ? sprintf('%d%%', $spell->getDamage()) : (string) $spell->getDamage())
                : '-';
            $healDisplay = $spell->getHeal() !== null
                ? ($spell->isPercent() ? sprintf('%d%%', $spell->getHeal()) : (string) $spell->getHeal())
                : '-';

            $rows[] = [
                $spell->getName(),
                $spell->getLevel(),
                $dmgDisplay,
                $healDisplay,
                $spell->getHit(),
                $spell->getCritical(),
                $spell->getEnergyCost(),
                $spell->getCooldown() ?? '-',
                $spell->getAoeTargets() > 1 || $spell->getAoeTargets() === 0 ? 'AoE(' . $spell->getAoeTargets() . ')' : 'Mono',
                $spell->getElement()->value,
                $spell->getStatusEffectSlug() ?? '-',
            ];

            // Alerte : sort sans degats et sans soin
            if (($spell->getDamage() === null || $spell->getDamage() === 0)
                && ($spell->getHeal() === null || $spell->getHeal() === 0)
                && $spell->getStatusEffectSlug() === null) {
                $alerts[] = sprintf('[SORT] %s (lvl %d) n\'a ni degats, ni soin, ni effet de statut', $spell->getName(), $spell->getLevel());
            }

            // Alerte : sort gratuit avec beaucoup de degats
            if ($spell->getEnergyCost() === 0 && $spell->getDamage() !== null && $spell->getDamage() > 30 && !$spell->isPercent()) {
                $alerts[] = sprintf('[SORT] %s (lvl %d) fait %d degats pour 0 energie', $spell->getName(), $spell->getLevel(), $spell->getDamage());
            }
        }

        $io->table(
            ['Nom', 'Niveau', 'Degats', 'Soin', 'Hit', 'Crit', 'Energie', 'CD', 'Cible', 'Element', 'Statut'],
            $rows,
        );

        $io->text(sprintf('Total : %d sorts', \count($spells)));

        return $alerts;
    }

    /**
     * @return string[]
     */
    private function reportCombatStats(SymfonyStyle $io, int $days): array
    {
        $io->section(sprintf('Statistiques de combat — %d derniers jours', $days));

        $conn = $this->entityManager->getConnection();
        $since = (new \DateTimeImmutable(sprintf('-%d days', $days)))->format('Y-m-d H:i:s');

        // 1. Nombre de combats termines par tier de monstre, victoires et defaites
        $outcomeData = $this->fetchCombatOutcomes($conn, $since);
        // 2. Duree moyenne des combats (nombre de tours) par tier
        $durationData = $this->fetchCombatDurations($conn, $since);
        // 3. DPS moyen joueur par tier (degats totaux joueur / nombre de tours)
        $dpsData = $this->fetchPlayerDps($conn, $since);

        if ($outcomeData === []) {
            $io->warning('Aucun combat termine trouve dans la periode.');

            return [];
        }

        // Fusionner les donnees par monstre (cle = "level:name")
        $tiers = [];
        foreach ($outcomeData as $row) {
            $key = $row['monster_level'] . ':' . $row['monster_name'];
            $tiers[$key] = [
                'level' => (int) $row['monster_level'],
                'monster_name' => $row['monster_name'],
                'is_boss' => (bool) $row['is_boss'],
                'total_fights' => (int) $row['total_fights'],
                'victories' => (int) $row['victories'],
                'defeats' => (int) $row['defeats'],
                'flees' => (int) $row['flees'],
                'avg_turns' => 0.0,
                'avg_player_dps' => 0.0,
            ];
        }

        foreach ($durationData as $row) {
            $key = $row['monster_level'] . ':' . $row['monster_name'];
            if (isset($tiers[$key])) {
                $tiers[$key]['avg_turns'] = round((float) $row['avg_turns'], 1);
            }
        }

        foreach ($dpsData as $row) {
            $key = $row['monster_level'] . ':' . $row['monster_name'];
            if (isset($tiers[$key])) {
                $tiers[$key]['avg_player_dps'] = round((float) $row['avg_dps'], 1);
            }
        }

        uasort($tiers, fn (array $a, array $b) => $a['level'] <=> $b['level'] ?: $a['monster_name'] <=> $b['monster_name']);

        // Tableau principal
        $rows = [];
        foreach ($tiers as $tier) {
            $total = $tier['total_fights'];
            $winRate = $total > 0 ? round(($tier['victories'] / $total) * 100, 1) : 0;
            $deathRate = $total > 0 ? round(($tier['defeats'] / $total) * 100, 1) : 0;
            $fleeRate = $total > 0 ? round(($tier['flees'] / $total) * 100, 1) : 0;

            $rows[] = [
                $tier['monster_name'] . ($tier['is_boss'] ? ' [Boss]' : ''),
                $tier['level'],
                $total,
                sprintf('%s (%.1f%%)', $tier['victories'], $winRate),
                sprintf('%s (%.1f%%)', $tier['defeats'], $deathRate),
                sprintf('%s (%.1f%%)', $tier['flees'], $fleeRate),
                $tier['avg_turns'],
                $tier['avg_player_dps'],
            ];
        }

        $io->table(
            ['Monstre', 'Niveau', 'Combats', 'Victoires', 'Defaites', 'Fuites', 'Tours moy.', 'DPS joueur'],
            $rows,
        );

        $totalFights = array_sum(array_column($tiers, 'total_fights'));
        $totalVictories = array_sum(array_column($tiers, 'victories'));
        $totalDefeats = array_sum(array_column($tiers, 'defeats'));
        $io->text(sprintf(
            'Total : %d combats, %d victoires (%.1f%%), %d defaites (%.1f%%)',
            $totalFights,
            $totalVictories,
            $totalFights > 0 ? ($totalVictories / $totalFights) * 100 : 0,
            $totalDefeats,
            $totalFights > 0 ? ($totalDefeats / $totalFights) * 100 : 0,
        ));

        // Alertes d'equilibrage
        return $this->detectCombatAlerts($tiers);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchCombatOutcomes(Connection $conn, string $since): array
    {
        return $conn->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    mon.level AS monster_level,
                    mon.name AS monster_name,
                    mon.is_boss,
                    COUNT(DISTINCT f.id) AS total_fights,
                    COUNT(DISTINCT CASE WHEN fl_v.id IS NOT NULL THEN f.id END) AS victories,
                    COUNT(DISTINCT CASE WHEN fl_d.id IS NOT NULL THEN f.id END) AS defeats,
                    COUNT(DISTINCT CASE WHEN fl_f.id IS NOT NULL THEN f.id END) AS flees
                FROM fight f
                INNER JOIN mob m ON m.fight_id = f.id
                INNER JOIN monster mon ON m.monster_id = mon.id
                LEFT JOIN fight_log fl_v ON fl_v.fight_id = f.id AND fl_v.type = 'victory'
                LEFT JOIN fight_log fl_d ON fl_d.fight_id = f.id AND fl_d.type = 'defeat'
                LEFT JOIN fight_log fl_f ON fl_f.fight_id = f.id AND fl_f.type = 'flee'
                WHERE f.in_progress = false
                  AND f.created_at >= :since
                GROUP BY mon.level, mon.name, mon.is_boss
                ORDER BY mon.level ASC, mon.name ASC
                SQL,
            ['since' => $since],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchCombatDurations(Connection $conn, string $since): array
    {
        return $conn->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    mon.level AS monster_level,
                    mon.name AS monster_name,
                    AVG(f.step) AS avg_turns
                FROM fight f
                INNER JOIN mob m ON m.fight_id = f.id
                INNER JOIN monster mon ON m.monster_id = mon.id
                WHERE f.in_progress = false
                  AND f.created_at >= :since
                GROUP BY mon.level, mon.name
                ORDER BY mon.level ASC, mon.name ASC
                SQL,
            ['since' => $since],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchPlayerDps(Connection $conn, string $since): array
    {
        return $conn->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    mon.level AS monster_level,
                    mon.name AS monster_name,
                    CASE WHEN SUM(sub.total_turns) > 0
                        THEN CAST(SUM(sub.total_damage) AS FLOAT) / SUM(sub.total_turns)
                        ELSE 0
                    END AS avg_dps
                FROM (
                    SELECT
                        f.id AS fight_id,
                        f.step AS total_turns,
                        COALESCE(SUM(
                            CASE WHEN fl.actor_type = 'player' AND fl.type = 'attack'
                                THEN CAST(fl.metadata::json->>'damage' AS INTEGER)
                                ELSE 0
                            END
                        ), 0) AS total_damage
                    FROM fight f
                    INNER JOIN fight_log fl ON fl.fight_id = f.id
                    WHERE f.in_progress = false
                      AND f.created_at >= :since
                    GROUP BY f.id, f.step
                ) sub
                INNER JOIN mob m ON m.fight_id = sub.fight_id
                INNER JOIN monster mon ON m.monster_id = mon.id
                GROUP BY mon.level, mon.name
                ORDER BY mon.level ASC, mon.name ASC
                SQL,
            ['since' => $since],
        );
    }

    /**
     * @param array<int, array<string, mixed>> $tiers
     *
     * @return string[]
     */
    private function detectCombatAlerts(array $tiers): array
    {
        $alerts = [];
        $normalTiers = array_values(array_filter($tiers, fn (array $t) => !$t['is_boss']));

        // Alerte : taux de mort trop eleve (> 50%) pour un monstre non-boss
        foreach ($normalTiers as $tier) {
            $total = $tier['total_fights'];
            if ($total >= 5 && $tier['defeats'] / $total > 0.5) {
                $alerts[] = sprintf(
                    '[COMBAT] %s (lvl %d) — taux de defaite %.0f%% (> 50%%)',
                    $tier['monster_name'],
                    $tier['level'],
                    ($tier['defeats'] / $total) * 100,
                );
            }
        }

        // Alerte : ecart DPS > 30% entre niveaux adjacents (agrege par niveau)
        $dpsByLevel = [];
        foreach ($normalTiers as $tier) {
            $level = $tier['level'];
            if (!isset($dpsByLevel[$level])) {
                $dpsByLevel[$level] = [];
            }
            $dpsByLevel[$level][] = $tier['avg_player_dps'];
        }
        $avgDpsByLevel = [];
        foreach ($dpsByLevel as $level => $values) {
            $nonZero = array_filter($values, fn (float $v) => $v > 0);
            if ($nonZero !== []) {
                $avgDpsByLevel[$level] = array_sum($nonZero) / \count($nonZero);
            }
        }
        ksort($avgDpsByLevel);
        $levelKeys = array_keys($avgDpsByLevel);
        for ($i = 1, $count = \count($levelKeys); $i < $count; ++$i) {
            $prevLevel = $levelKeys[$i - 1];
            $currLevel = $levelKeys[$i];
            $prevDps = $avgDpsByLevel[$prevLevel];
            $currDps = $avgDpsByLevel[$currLevel];

            $variance = abs($currDps - $prevDps) / $prevDps;
            if ($variance > self::DPS_VARIANCE_THRESHOLD) {
                $alerts[] = sprintf(
                    '[COMBAT] Ecart DPS joueur entre lvl %d (%.1f) et lvl %d (%.1f) : %.0f%% (> %d%%)',
                    $prevLevel,
                    $prevDps,
                    $currLevel,
                    $currDps,
                    $variance * 100,
                    (int) (self::DPS_VARIANCE_THRESHOLD * 100),
                );
            }
        }

        // Alerte : combats trop longs (> 20 tours en moyenne) pour un monstre normal
        foreach ($normalTiers as $tier) {
            if ($tier['total_fights'] >= 5 && $tier['avg_turns'] > 20) {
                $alerts[] = sprintf(
                    '[COMBAT] %s (lvl %d) — duree moyenne %.1f tours (> 20)',
                    $tier['monster_name'],
                    $tier['level'],
                    $tier['avg_turns'],
                );
            }
        }

        return $alerts;
    }

    /**
     * @param string[] $alerts
     */
    private function reportAlerts(SymfonyStyle $io, array $alerts): void
    {
        $io->section('Alertes d\'equilibrage');

        if ($alerts === []) {
            $io->success('Aucune alerte detectee.');

            return;
        }

        $io->warning(sprintf('%d alerte(s) detectee(s) :', \count($alerts)));

        foreach ($alerts as $alert) {
            $io->text(sprintf('  ! %s', $alert));
        }
    }
}
