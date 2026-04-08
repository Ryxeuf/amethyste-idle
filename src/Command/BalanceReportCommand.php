<?php

namespace App\Command;

use App\Entity\App\FightLog;
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
    description: 'Generate a balance report: monsters, items, drops, domains, spells, combat',
)]
class BalanceReportCommand extends Command
{
    private const BASE_XP_PER_KILL = 10;
    private const BOSS_XP_MULTIPLIER = 5;
    private const SELL_RATIO = 0.3;
    private const DPS_VARIANCE_THRESHOLD = 0.3;
    private const LONG_FIGHT_THRESHOLD = 20;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('section', 's', InputOption::VALUE_OPTIONAL, 'Section to display: monsters, items, drops, domains, spells, combat, alerts, all', 'all')
            ->addOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Number of days to analyse for combat stats (default: 30)', '30');
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
            /** @var string $daysStr */
            $daysStr = $input->getOption('days');
            $days = max(1, (int) $daysStr);
            $alerts = array_merge($alerts, $this->reportCombat($io, $days));
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
    private function reportCombat(SymfonyStyle $io, int $days): array
    {
        $io->section(sprintf('Statistiques de combat — %d derniers jours', $days));

        $alerts = [];
        $since = new \DateTimeImmutable(sprintf('-%d days', $days));
        $sinceStr = $since->format('Y-m-d H:i:s');

        // 1. Global fight outcomes
        $outcomes = $this->queryFightOutcomes($sinceStr);
        $totalFights = $outcomes['victories'] + $outcomes['defeats'] + $outcomes['flees'];

        if ($totalFights === 0) {
            $io->warning('Aucun combat termine trouve dans la periode.');

            return $alerts;
        }

        $io->text(sprintf('Combats termines : %d (Victoires: %d, Defaites: %d, Fuites: %d)',
            $totalFights, $outcomes['victories'], $outcomes['defeats'], $outcomes['flees']
        ));
        $io->text(sprintf('Taux de victoire : %.1f%% | Taux de defaite : %.1f%% | Taux de fuite : %.1f%%',
            $totalFights > 0 ? ($outcomes['victories'] / $totalFights) * 100 : 0,
            $totalFights > 0 ? ($outcomes['defeats'] / $totalFights) * 100 : 0,
            $totalFights > 0 ? ($outcomes['flees'] / $totalFights) * 100 : 0,
        ));
        $io->newLine();

        if ($outcomes['defeats'] > 0 && $totalFights > 0) {
            $deathRate = ($outcomes['defeats'] / $totalFights) * 100;
            if ($deathRate > 40) {
                $alerts[] = sprintf('[COMBAT] Taux de defaite eleve : %.1f%% (seuil: 40%%)', $deathRate);
            }
        }

        // 2. Average fight duration (turns)
        $avgDuration = $this->queryAverageFightDuration($sinceStr);
        $io->text(sprintf('Duree moyenne des combats : %.1f tours', $avgDuration));
        $io->newLine();

        // 3. DPS moyen par tier de monstre (degats infliges par les mobs aux joueurs)
        $mobDpsRows = $this->queryMobDpsByTier($sinceStr);
        if ($mobDpsRows !== []) {
            $io->text('--- DPS moyen des monstres par tier (degats infliges aux joueurs) ---');
            $io->table(
                ['Tier (niveau)', 'Combats', 'Degats totaux', 'Degats/combat', 'Degats/tour'],
                $mobDpsRows,
            );
        }

        // 4. Taux de victoire/defaite par monstre
        $monsterOutcomes = $this->queryOutcomesByMonster($sinceStr);
        if ($monsterOutcomes !== []) {
            $io->text('--- Taux de victoire/defaite par monstre ---');

            $monsterRows = [];
            foreach ($monsterOutcomes as $row) {
                $total = $row['victories'] + $row['defeats'] + $row['flees'];
                $winRate = $total > 0 ? ($row['victories'] / $total) * 100 : 0;
                $monsterRows[] = [
                    $row['monster_name'],
                    $row['level'],
                    $total,
                    $row['victories'],
                    $row['defeats'],
                    $row['flees'],
                    sprintf('%.1f%%', $winRate),
                    sprintf('%.1f', $row['avg_turns']),
                ];

                if ($winRate < 30 && $total >= 5) {
                    $alerts[] = sprintf('[COMBAT] %s (lvl %d) : taux victoire tres bas (%.1f%%, %d combats)',
                        $row['monster_name'], $row['level'], $winRate, $total);
                }
                if ($winRate > 95 && $total >= 5) {
                    $alerts[] = sprintf('[COMBAT] %s (lvl %d) : taux victoire tres haut (%.1f%%, %d combats) — trop facile ?',
                        $row['monster_name'], $row['level'], $winRate, $total);
                }
            }

            $io->table(
                ['Monstre', 'Niveau', 'Combats', 'Victoires', 'Defaites', 'Fuites', 'Win%', 'Moy. tours'],
                $monsterRows,
            );
        }

        // 5. DPS moyen des joueurs par monstre (degats infliges par les joueurs aux mobs)
        $playerDpsRows = $this->queryPlayerDpsByMonster($sinceStr);
        if ($playerDpsRows !== []) {
            $io->text('--- DPS moyen des joueurs par monstre (degats infliges aux mobs) ---');
            $io->table(
                ['Monstre', 'Niveau', 'Combats', 'Degats totaux', 'Degats/tour'],
                array_map(fn (array $r) => [
                    $r['monster_name'],
                    $r['level'],
                    $r['fight_count'],
                    $r['total_damage'],
                    sprintf('%.1f', $r['dps']),
                ], $playerDpsRows),
            );

            $alerts = array_merge($alerts, $this->detectDpsVarianceAlerts($playerDpsRows));
        }

        // 6. Alertes duree de combat par monstre
        if ($monsterOutcomes !== []) {
            $alerts = array_merge($alerts, $this->detectLongFightAlerts($monsterOutcomes));
        }

        // 7. Player death rate
        $playerDeaths = $this->queryPlayerDeathStats($sinceStr);
        if ($playerDeaths !== []) {
            $io->text('--- Morts joueurs les plus frequentes (top 10) ---');
            $io->table(
                ['Joueur', 'Morts', 'Combats', 'Taux de mort'],
                array_slice($playerDeaths, 0, 10),
            );
        }

        return $alerts;
    }

    /**
     * @return array{victories: int, defeats: int, flees: int}
     */
    private function queryFightOutcomes(string $since): array
    {
        $sql = <<<'SQL'
            SELECT
                type,
                COUNT(DISTINCT fight_id) as cnt
            FROM fight_log
            WHERE type IN (:victory, :defeat, :flee)
              AND created_at >= :since
            GROUP BY type
            SQL;

        $rows = $this->connection->executeQuery($sql, [
            'victory' => FightLog::TYPE_VICTORY,
            'defeat' => FightLog::TYPE_DEFEAT,
            'flee' => FightLog::TYPE_FLEE,
            'since' => $since,
        ])->fetchAllAssociative();

        $outcomes = ['victories' => 0, 'defeats' => 0, 'flees' => 0];
        foreach ($rows as $row) {
            match ($row['type']) {
                FightLog::TYPE_VICTORY => $outcomes['victories'] = (int) $row['cnt'],
                FightLog::TYPE_DEFEAT => $outcomes['defeats'] = (int) $row['cnt'],
                FightLog::TYPE_FLEE => $outcomes['flees'] = (int) $row['cnt'],
                default => null,
            };
        }

        return $outcomes;
    }

    private function queryAverageFightDuration(string $since): float
    {
        $sql = <<<'SQL'
            SELECT AVG(max_turn) as avg_turns
            FROM (
                SELECT fight_id, MAX(turn) as max_turn
                FROM fight_log
                WHERE created_at >= :since
                GROUP BY fight_id
            ) sub
            SQL;

        $result = $this->connection->executeQuery($sql, ['since' => $since])->fetchOne();

        return round((float) $result, 1);
    }

    /**
     * @return list<array{0: string, 1: int, 2: int, 3: string, 4: string}>
     */
    private function queryMobDpsByTier(string $since): array
    {
        // Get damage dealt by mobs to players, grouped by monster level tier
        // We join fight_log (attack events from mobs) with fight_start metadata to find monster levels
        // Since there's no direct FK from fight_log to monster, we match via mob actor_name to game_monsters.name
        $sql = <<<'SQL'
            SELECT
                gm.level,
                COUNT(DISTINCT fl.fight_id) as fight_count,
                COALESCE(SUM((fl.metadata->>'damage')::int), 0) as total_damage
            FROM fight_log fl
            INNER JOIN game_monsters gm ON gm.name = fl.actor_name
            WHERE fl.actor_type = :mob
              AND fl.type = :attack
              AND fl.metadata IS NOT NULL
              AND fl.metadata->>'damage' IS NOT NULL
              AND fl.created_at >= :since
            GROUP BY gm.level
            ORDER BY gm.level ASC
            SQL;

        $rows = $this->connection->executeQuery($sql, [
            'mob' => FightLog::ACTOR_MOB,
            'attack' => FightLog::TYPE_ATTACK,
            'since' => $since,
        ])->fetchAllAssociative();

        // Get average turns per fight for DPS calculation
        $avgTurnsMap = $this->queryAverageTurnsByFight($since);

        $result = [];
        foreach ($rows as $row) {
            $level = (int) $row['level'];
            $fightCount = (int) $row['fight_count'];
            $totalDamage = (int) $row['total_damage'];
            $dmgPerFight = $fightCount > 0 ? $totalDamage / $fightCount : 0;

            // Estimate DPS per turn using global avg turns
            $avgTurns = $avgTurnsMap > 0 ? $avgTurnsMap : 1;
            $dmgPerTurn = $fightCount > 0 ? $totalDamage / ($fightCount * $avgTurns) : 0;

            $tierLabel = sprintf('Lvl %d', $level);
            $result[] = [
                $tierLabel,
                $fightCount,
                $totalDamage,
                sprintf('%.1f', $dmgPerFight),
                sprintf('%.1f', $dmgPerTurn),
            ];
        }

        return $result;
    }

    private function queryAverageTurnsByFight(string $since): float
    {
        $sql = <<<'SQL'
            SELECT AVG(max_turn) as avg_turns
            FROM (
                SELECT fight_id, MAX(turn) as max_turn
                FROM fight_log
                WHERE created_at >= :since
                GROUP BY fight_id
            ) sub
            SQL;

        return (float) $this->connection->executeQuery($sql, ['since' => $since])->fetchOne();
    }

    /**
     * @return list<array{monster_name: string, level: int, victories: int, defeats: int, flees: int, avg_turns: float}>
     */
    private function queryOutcomesByMonster(string $since): array
    {
        // Find the first mob name per fight via fight_start metadata, then join with outcomes
        $sql = <<<'SQL'
            WITH fight_mobs AS (
                SELECT
                    fl.fight_id,
                    jsonb_array_elements_text(fl.metadata::jsonb->'mobs') as mob_name
                FROM fight_log fl
                WHERE fl.type = :fight_start
                  AND fl.metadata IS NOT NULL
                  AND fl.created_at >= :since
            ),
            fight_outcomes AS (
                SELECT
                    fight_id,
                    type
                FROM fight_log
                WHERE type IN (:victory, :defeat, :flee)
                  AND created_at >= :since
            ),
            fight_turns AS (
                SELECT
                    fight_id,
                    MAX(turn) as max_turn
                FROM fight_log
                WHERE created_at >= :since
                GROUP BY fight_id
            )
            SELECT
                fm.mob_name as monster_name,
                COALESCE(gm.level, 0) as level,
                COUNT(DISTINCT CASE WHEN fo.type = :victory THEN fo.fight_id END) as victories,
                COUNT(DISTINCT CASE WHEN fo.type = :defeat THEN fo.fight_id END) as defeats,
                COUNT(DISTINCT CASE WHEN fo.type = :flee THEN fo.fight_id END) as flees,
                COALESCE(AVG(ft.max_turn), 0) as avg_turns
            FROM fight_mobs fm
            LEFT JOIN fight_outcomes fo ON fo.fight_id = fm.fight_id
            LEFT JOIN fight_turns ft ON ft.fight_id = fm.fight_id
            LEFT JOIN game_monsters gm ON gm.name = fm.mob_name
            WHERE fo.type IS NOT NULL
            GROUP BY fm.mob_name, gm.level
            ORDER BY gm.level ASC, fm.mob_name ASC
            SQL;

        $rows = $this->connection->executeQuery($sql, [
            'fight_start' => FightLog::TYPE_FIGHT_START,
            'victory' => FightLog::TYPE_VICTORY,
            'defeat' => FightLog::TYPE_DEFEAT,
            'flee' => FightLog::TYPE_FLEE,
            'since' => $since,
        ])->fetchAllAssociative();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'monster_name' => $row['monster_name'],
                'level' => (int) $row['level'],
                'victories' => (int) $row['victories'],
                'defeats' => (int) $row['defeats'],
                'flees' => (int) $row['flees'],
                'avg_turns' => round((float) $row['avg_turns'], 1),
            ];
        }

        return $result;
    }

    /**
     * @return list<array{0: string, 1: int, 2: int, 3: string}>
     */
    private function queryPlayerDeathStats(string $since): array
    {
        // Count player deaths and total fights per player
        $sql = <<<'SQL'
            WITH player_fights AS (
                SELECT
                    fl.actor_name as player_name,
                    COUNT(DISTINCT fl.fight_id) as total_fights
                FROM fight_log fl
                WHERE fl.actor_type = :player
                  AND fl.type = :attack
                  AND fl.created_at >= :since
                GROUP BY fl.actor_name
            ),
            player_deaths AS (
                SELECT
                    fl.actor_name as player_name,
                    COUNT(*) as death_count
                FROM fight_log fl
                WHERE fl.type = :death
                  AND fl.actor_type = :player
                  AND fl.created_at >= :since
                GROUP BY fl.actor_name
            )
            SELECT
                pd.player_name,
                pd.death_count,
                COALESCE(pf.total_fights, 0) as total_fights,
                CASE WHEN pf.total_fights > 0
                    THEN ROUND(pd.death_count::numeric / pf.total_fights * 100, 1)
                    ELSE 0
                END as death_rate
            FROM player_deaths pd
            LEFT JOIN player_fights pf ON pf.player_name = pd.player_name
            ORDER BY pd.death_count DESC
            SQL;

        $rows = $this->connection->executeQuery($sql, [
            'player' => FightLog::ACTOR_PLAYER,
            'attack' => FightLog::TYPE_ATTACK,
            'death' => FightLog::TYPE_DEATH,
            'since' => $since,
        ])->fetchAllAssociative();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                $row['player_name'],
                (int) $row['death_count'],
                (int) $row['total_fights'],
                sprintf('%.1f%%', (float) $row['death_rate']),
            ];
        }

        return $result;
    }

    /**
     * @return list<array{monster_name: string, level: int, fight_count: int, total_damage: int, dps: float}>
     */
    private function queryPlayerDpsByMonster(string $since): array
    {
        $sql = <<<'SQL'
            WITH fight_mobs AS (
                SELECT
                    fl.fight_id,
                    jsonb_array_elements_text(fl.metadata::jsonb->'mobs') as mob_name
                FROM fight_log fl
                WHERE fl.type = :fight_start
                  AND fl.metadata IS NOT NULL
                  AND fl.created_at >= :since
            ),
            fight_player_damage AS (
                SELECT
                    fl.fight_id,
                    COALESCE(SUM((fl.metadata->>'damage')::int), 0) as total_damage
                FROM fight_log fl
                WHERE fl.actor_type = :player
                  AND fl.type = :attack
                  AND fl.metadata IS NOT NULL
                  AND fl.metadata->>'damage' IS NOT NULL
                  AND fl.created_at >= :since
                GROUP BY fl.fight_id
            ),
            fight_turns AS (
                SELECT
                    fight_id,
                    MAX(turn) as max_turn
                FROM fight_log
                WHERE created_at >= :since
                GROUP BY fight_id
            )
            SELECT
                fm.mob_name as monster_name,
                COALESCE(gm.level, 0) as level,
                COUNT(DISTINCT fm.fight_id) as fight_count,
                COALESCE(SUM(fpd.total_damage), 0) as total_damage,
                CASE WHEN SUM(ft.max_turn) > 0
                    THEN CAST(SUM(fpd.total_damage) AS FLOAT) / SUM(ft.max_turn)
                    ELSE 0
                END as dps
            FROM fight_mobs fm
            INNER JOIN fight_player_damage fpd ON fpd.fight_id = fm.fight_id
            INNER JOIN fight_turns ft ON ft.fight_id = fm.fight_id
            LEFT JOIN game_monsters gm ON gm.name = fm.mob_name
            GROUP BY fm.mob_name, gm.level
            ORDER BY gm.level ASC, fm.mob_name ASC
            SQL;

        $rows = $this->connection->executeQuery($sql, [
            'fight_start' => FightLog::TYPE_FIGHT_START,
            'player' => FightLog::ACTOR_PLAYER,
            'attack' => FightLog::TYPE_ATTACK,
            'since' => $since,
        ])->fetchAllAssociative();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'monster_name' => $row['monster_name'],
                'level' => (int) $row['level'],
                'fight_count' => (int) $row['fight_count'],
                'total_damage' => (int) $row['total_damage'],
                'dps' => round((float) $row['dps'], 1),
            ];
        }

        return $result;
    }

    /**
     * Detecte les ecarts de DPS joueur > 30% entre niveaux adjacents.
     *
     * @param list<array{monster_name: string, level: int, fight_count: int, total_damage: int, dps: float}> $playerDpsRows
     *
     * @return string[]
     */
    private function detectDpsVarianceAlerts(array $playerDpsRows): array
    {
        $alerts = [];

        // Agreger DPS par niveau
        $dpsByLevel = [];
        foreach ($playerDpsRows as $row) {
            if ($row['dps'] > 0 && $row['fight_count'] >= 3) {
                $dpsByLevel[$row['level']][] = $row['dps'];
            }
        }

        $avgDpsByLevel = [];
        foreach ($dpsByLevel as $level => $values) {
            $avgDpsByLevel[$level] = array_sum($values) / \count($values);
        }
        ksort($avgDpsByLevel);

        $levels = array_keys($avgDpsByLevel);
        for ($i = 1, $count = \count($levels); $i < $count; ++$i) {
            $prevLevel = $levels[$i - 1];
            $currLevel = $levels[$i];
            $prevDps = $avgDpsByLevel[$prevLevel];
            $currDps = $avgDpsByLevel[$currLevel];

            $variance = abs($currDps - $prevDps) / $prevDps;
            if ($variance > self::DPS_VARIANCE_THRESHOLD) {
                $alerts[] = sprintf(
                    '[COMBAT] Ecart DPS joueur entre lvl %d (%.1f) et lvl %d (%.1f) : %.0f%% (seuil: %d%%)',
                    $prevLevel,
                    $prevDps,
                    $currLevel,
                    $currDps,
                    $variance * 100,
                    (int) (self::DPS_VARIANCE_THRESHOLD * 100),
                );
            }
        }

        return $alerts;
    }

    /**
     * Detecte les combats trop longs (> 20 tours en moyenne) pour les monstres non-boss.
     *
     * @param list<array{monster_name: string, level: int, victories: int, defeats: int, flees: int, avg_turns: float}> $monsterOutcomes
     *
     * @return string[]
     */
    private function detectLongFightAlerts(array $monsterOutcomes): array
    {
        $alerts = [];

        foreach ($monsterOutcomes as $row) {
            $total = $row['victories'] + $row['defeats'] + $row['flees'];
            if ($total >= 5 && $row['avg_turns'] > self::LONG_FIGHT_THRESHOLD) {
                $alerts[] = sprintf(
                    '[COMBAT] %s (lvl %d) — duree moyenne %.1f tours (seuil: %d)',
                    $row['monster_name'],
                    $row['level'],
                    $row['avg_turns'],
                    self::LONG_FIGHT_THRESHOLD,
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
