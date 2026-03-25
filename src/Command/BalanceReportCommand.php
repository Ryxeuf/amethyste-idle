<?php

namespace App\Command;

use App\Entity\Game\Domain;
use App\Entity\Game\Item;
use App\Entity\Game\Monster;
use App\Entity\Game\MonsterItem;
use App\Entity\Game\Skill;
use App\Entity\Game\Spell;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:balance:report',
    description: 'Generate a balance report: monsters, items, drops, domains, spells',
)]
class BalanceReportCommand extends Command
{
    private const BASE_XP_PER_KILL = 10;
    private const BOSS_XP_MULTIPLIER = 5;
    private const SELL_RATIO = 0.3;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('section', 's', InputOption::VALUE_OPTIONAL, 'Section to display: monsters, items, drops, domains, spells, alerts, all', 'all');
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

            $attackDmg = $monster->getAttack() ? ($monster->getAttack()->getDamage() ?? 0) : 0;

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
