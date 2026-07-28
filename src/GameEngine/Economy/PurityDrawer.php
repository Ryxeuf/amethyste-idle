<?php

namespace App\GameEngine\Economy;

use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Enum\Purity;
use App\GameEngine\Progression\ActionYieldResolver;
use App\GameEngine\Retention\WeeklyCommissionGenerator;
use App\Repository\WeeklyOutcropRepository;

/**
 * D'ou vient la bande d'un lot (ECO-22).
 *
 * C'est ici que le savoir du prospecteur prend une valeur marchande : deux
 * joueurs qui recoltent le meme filon ne rapportent pas la meme chose, et celui
 * qui sait **ou** et **quand** frapper rapporte mieux. Sans ce tirage, la
 * purete d'ECO-21 resterait un champ nul et `Recipe.quality` continuerait de
 * dormir.
 *
 * Trois facteurs, dans cet ordre de priorite :
 *
 * 1. **Le plafond de vitalite prime sur tout.** Un filon ereinte ne rend plus
 *    que du trouble, quel que soit le talent du recolteur. C'est ce qui empeche
 *    un vetéran de vider un filon en continuant d'en tirer du parfait — et c'est
 *    le seul endroit ou la couche de rarete de ZON-37 devient consequence.
 * 2. **Le savoir deplace les poids**, il ne garantit rien. Chaque point de bonus
 *    de recolte transfere du poids du trouble vers le clair et du clair vers le
 *    pur — **jamais** directement vers le parfait, qui ne s'achete pas a
 *    l'experience.
 * 3. **Le hasard tranche**, dans la fourchette que les deux premiers laissent.
 *
 * Hors perimetre, la reponse est `null` : une botte d'herbes n'a pas de bande,
 * et ce n'est pas une erreur (ECO-21).
 */
class PurityDrawer
{
    /**
     * @var array{base_weights: array<string, int>, vitality_ceilings: list<array{at_least: float, band: Purity}>, skill_weight_per_point: int, skill_weight_cap: int}|null
     */
    private ?array $draw = null;

    public function __construct(
        private readonly PurityScope $scope,
        private readonly PurityDefinitionLoader $loader,
        private readonly ActionYieldResolver $yieldResolver,
        private readonly WeeklyOutcropRepository $outcropRepository,
    ) {
    }

    /**
     * Tire la bande d'un lot recolte.
     *
     * @return Purity|null `null` quand la matiere est hors perimetre
     */
    public function draw(Player $player, string $itemSlug, int $stock, int $capacity, ?Zone $zone = null, ?string $veinSlug = null): ?Purity
    {
        if (!$this->scope->coversSlug($itemSlug)) {
            return null;
        }

        $weights = $this->weightsFor(
            $this->ceiling($stock, $capacity, $zone, $veinSlug),
            $this->yieldResolver->getBonusPercent($player, ActionYieldResolver::CATEGORY_GATHER),
        );

        return $this->pick($weights, $this->roll(array_sum($weights)));
    }

    /**
     * Bande maximale qu'un filon peut rendre en l'etat.
     *
     * Rendue publiquement parce que c'est **l'information exclusive** du
     * prospecteur (GAME_ZONE_ACTIONS § 5.5) : savoir qu'un filon ne rend plus
     * que du clair est une decision, pas un butin.
     */
    public function ceiling(int $stock, int $capacity, ?Zone $zone = null, ?string $veinSlug = null): Purity
    {
        $vitality = $capacity > 0 ? max(0.0, min(1.0, $stock / $capacity)) : 0.0;

        $ceiling = Purity::Trouble;
        foreach ($this->definition()['vitality_ceilings'] as $entry) {
            if ($vitality >= $entry['at_least']) {
                $ceiling = $entry['band'];
                break;
            }
        }

        // RET-06 : l'Affleurement de la semaine monte le plafond d'un cran, sept
        // jours durant. Il s'applique **apres** la vitalite et non a sa place :
        // un filon ereinte reste ereinte, l'affleurement ne le ressuscite pas.
        // C'est ce qui empeche la brique de devenir une dispense de gestion.
        if ($this->isOutcrop($zone, $veinSlug)) {
            $ceiling = $ceiling->next() ?? $ceiling;
        }

        return $ceiling;
    }

    /**
     * Ce filon est-il l'Affleurement de la semaine ?
     *
     * Prive, et il doit le rester : **rien n'annonce l'affleurement**.
     * L'information se decouvre par prospection sur place — c'est-a-dire en
     * lisant le plafond du filon — ou s'achete a qui l'a trouvee. L'exposer
     * publiquement en ferait une ruee, et la brique perdrait la seule chose
     * qu'elle produit.
     */
    private function isOutcrop(?Zone $zone, ?string $veinSlug): bool
    {
        if ($zone === null || $veinSlug === null) {
            return false;
        }

        $outcrop = $this->outcropRepository->findForWeek(WeeklyCommissionGenerator::weekKey(new \DateTimeImmutable()));

        return $outcrop !== null
            && $outcrop->getVeinSlug() === $veinSlug
            && $outcrop->getZone()->getSlug() === $zone->getSlug();
    }

    public function coversSlug(string $itemSlug): bool
    {
        return $this->scope->coversSlug($itemSlug);
    }

    /**
     * Poids effectifs, plafond et savoir appliques.
     *
     * Le transfert se fait **vers le haut d'un cran a la fois** : le trouble
     * nourrit le clair, le clair nourrit le pur. Un raccourci du trouble vers le
     * parfait ferait du niveau de recolte un achat de rarete, ce que le socle de
     * monde ecarte explicitement.
     *
     * @return array<string, int>
     */
    public function weightsFor(Purity $ceiling, int $skillBonus): array
    {
        $draw = $this->definition();
        $weights = $draw['base_weights'];

        $shift = min(max(0, $skillBonus) * $draw['skill_weight_per_point'], $draw['skill_weight_cap']);
        if ($shift > 0) {
            $fromTrouble = min($shift, $weights[Purity::Trouble->value]);
            $weights[Purity::Trouble->value] -= $fromTrouble;
            $weights[Purity::Clair->value] += $fromTrouble;

            $fromClair = min($shift, $weights[Purity::Clair->value]);
            $weights[Purity::Clair->value] -= $fromClair;
            $weights[Purity::Pur->value] += $fromClair;
        }

        // Le plafond est applique **en dernier** : il doit primer sur le savoir,
        // sinon un recolteur chevronne continuerait de tirer du parfait dans un
        // filon a sec et la vitalite cesserait d'etre un signal.
        foreach (Purity::ordered() as $band) {
            if (!$ceiling->isAtLeast($band)) {
                $weights[$band->value] = 0;
            }
        }

        if (array_sum($weights) <= 0) {
            // Le plafond a tout ecrase : la bande plafond reste la seule
            // possible. Rendre un tirage vide obligerait chaque appelant a
            // gerer un `null` qui ne veut rien dire ici.
            $weights[$ceiling->value] = 1;
        }

        return $weights;
    }

    /**
     * @param array<string, int> $weights
     */
    private function pick(array $weights, int $roll): Purity
    {
        $cursor = 0;
        foreach (Purity::ordered() as $band) {
            $cursor += $weights[$band->value] ?? 0;
            if ($roll <= $cursor) {
                return $band;
            }
        }

        return Purity::Trouble;
    }

    /**
     * @return array{base_weights: array<string, int>, vitality_ceilings: list<array{at_least: float, band: Purity}>, skill_weight_per_point: int, skill_weight_cap: int}
     */
    private function definition(): array
    {
        if ($this->draw === null) {
            $this->draw = $this->loader->load()['draw'];
        }

        return $this->draw;
    }

    protected function roll(int $max): int
    {
        return random_int(1, max(1, $max));
    }
}
