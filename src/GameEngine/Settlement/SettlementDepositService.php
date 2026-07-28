<?php

namespace App\GameEngine\Settlement;

use App\Entity\App\Player;
use App\Entity\App\Settlement;
use App\Entity\App\SettlementContribution;
use App\Entity\App\Zone;
use App\Enum\SettlementIndex;
use App\Repository\SettlementContributionRepository;
use App\Repository\SettlementRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Depot de sediment dans le foyer d'une zone (FOY-02).
 *
 * L'activite des joueurs devient la matiere du monde : chaque action deposee
 * ici fait monter un foyer, et l'absence d'action le fait redescendre (FOY-03).
 *
 * Trois regles portent ce service, et chacune existe contre une facon precise
 * de se tromper :
 *
 * 1. **Le grind ne bat jamais la regularite.** Au-dela du seuil, chaque grain
 *    compte pour moitie ; au-dela du plafond, plus rien. Ce n'est pas une
 *    punition : c'est ce qui garantit qu'une ville se batit en etant frequentee
 *    plutot qu'en etant optimisee par trois joueurs.
 * 2. **Ce qui n'atteint pas l'unite n'est pas perdu.** La traversee vaut 0,2
 *    grain reparti sur quatre indices, soit 0,05 chacun : arrondi a chaque
 *    evenement, ce serait zero, et la zone de transit annoncee par GAME_WORLD
 *    § 5.5 ne vivrait jamais. Le reste attend sur la contribution du joueur.
 * 3. **Une zone sans foyer n'accumule rien, en silence et sans erreur.** Lumiere
 *    et les Jardins sont batis sur la Voute ; y jouer est normal, y deposer ne
 *    l'est pas. Le refus n'est donc pas une exception, c'est un zero.
 */
class SettlementDepositService
{
    /**
     * @var array{
     *     sediment: array<string, SedimentRule>,
     *     daily_cap_per_player: int,
     *     diminishing_threshold: int,
     *     diminishing_factor: float
     * }|null
     */
    private ?array $rules = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SettlementRepository $settlementRepository,
        private readonly SettlementContributionRepository $contributionRepository,
        private readonly SettlementDefinitionLoader $loader,
    ) {
    }

    /**
     * Depose le sediment d'une action dans le foyer d'une zone.
     *
     * @param string $action clef de la table `sediment` (BALANCE § 23.1)
     * @param ?Zone  $zone   zone visee ; a defaut, la zone courante du joueur (regle 7)
     *
     * @return int grains entiers reellement deposes — zero est un resultat normal
     */
    public function deposit(Player $player, string $action, ?Zone $zone = null, ?\DateTimeImmutable $now = null): int
    {
        $zone ??= $player->getCurrentZone();
        if ($zone === null) {
            return 0;
        }

        $settlement = $this->settlementRepository->findOneByZone($zone);
        if ($settlement === null) {
            return 0;
        }

        $rules = $this->rules();
        $rule = $rules['sediment'][$action] ?? null;
        if ($rule === null) {
            return 0;
        }

        $now ??= new \DateTimeImmutable();
        $contribution = $this->contributionFor($settlement, $player);

        $granted = $this->applyAntiExploit($rule->grains, $contribution->getDailyGrains($now), $rules);
        if ($granted <= 0.0) {
            return 0;
        }

        $index = $rule->index;
        $deposited = $index === null
            ? $this->depositSpread($settlement, $contribution, $granted)
            : $this->depositOn($settlement, $contribution, $index, $granted);

        if ($deposited > 0) {
            $contribution->addGrains($deposited);
            $contribution->addDailyGrains($now, $deposited);
        }

        return $deposited;
    }

    /**
     * Plafond journalier et rendements decroissants.
     *
     * Le plafond mord sur ce qui **depasse**, pas sur l'action entiere : un
     * joueur a 59 grains depose son dernier grain, il ne le perd pas parce que
     * l'action en valait cinq.
     *
     * @param array{daily_cap_per_player: int, diminishing_threshold: int, diminishing_factor: float} $rules
     */
    private function applyAntiExploit(float $grains, int $alreadyToday, array $rules): float
    {
        $remaining = $rules['daily_cap_per_player'] - $alreadyToday;
        if ($remaining <= 0) {
            return 0.0;
        }

        if ($alreadyToday >= $rules['diminishing_threshold']) {
            $grains *= $rules['diminishing_factor'];
        }

        return min($grains, (float) $remaining);
    }

    /**
     * Depot sur un seul indice, en reportant le reste fractionnaire.
     */
    private function depositOn(Settlement $settlement, SettlementContribution $contribution, SettlementIndex $index, float $grains): int
    {
        $total = $contribution->getCarry($index) + $grains;

        // Le report est une comptabilite en dixiemes, tenue en binaire. Vingt
        // fois 0,05 tombe a 1,0000000000000002 sur cette machine — et pourrait
        // tomber a 0,9999999999999998 sur une autre. Sans cette tolerance, la
        // vingtieme traversee rendrait un grain ici et zero ailleurs, ce qui
        // ferait dependre le chiffrage de BALANCE § 23.1 de l'arrondi du
        // processeur. Une erreur de representation ne doit jamais couter un
        // grain au joueur.
        $whole = (int) floor($total + 1e-9);

        $contribution->setCarry($index, $total - $whole);
        if ($whole > 0) {
            $settlement->addSediment($index, $whole);
        }

        return $whole;
    }

    /**
     * Depot **reparti** : la traversee d'une zone n'est ni du negoce ni de la
     * guerre, elle nourrit les quatre a parts egales — et ne donne donc jamais
     * d'identite a la ville. Passer n'a jamais fait une ville.
     */
    private function depositSpread(Settlement $settlement, SettlementContribution $contribution, float $grains): int
    {
        $indices = SettlementIndex::cases();
        $share = $grains / \count($indices);

        $deposited = 0;
        foreach ($indices as $index) {
            $deposited += $this->depositOn($settlement, $contribution, $index, $share);
        }

        return $deposited;
    }

    private function contributionFor(Settlement $settlement, Player $player): SettlementContribution
    {
        $contribution = $this->contributionRepository->findOneFor($settlement, $player);
        if ($contribution === null) {
            $contribution = new SettlementContribution($settlement, $player);
            $this->entityManager->persist($contribution);
        }

        return $contribution;
    }

    /**
     * @return array{
     *     sediment: array<string, SedimentRule>,
     *     daily_cap_per_player: int,
     *     diminishing_threshold: int,
     *     diminishing_factor: float
     * }
     */
    private function rules(): array
    {
        if ($this->rules === null) {
            $definition = $this->loader->load();
            $this->rules = [
                'sediment' => $definition['sediment'],
                'daily_cap_per_player' => $definition['daily_cap_per_player'],
                'diminishing_threshold' => $definition['diminishing_threshold'],
                'diminishing_factor' => $definition['diminishing_factor'],
            ];
        }

        return $this->rules;
    }
}
