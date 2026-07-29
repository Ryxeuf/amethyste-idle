<?php

namespace App\GameEngine\Economy;

use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Enum\Purity;
use App\GameEngine\Progression\ActionYieldResolver;
use App\GameEngine\Retention\WeekKey;
use App\GameEngine\World\GameTimeService;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\Repository\WeeklyOutcropRepository;
use App\Repository\ZoneVeinRepository;

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

    /**
     * @var array<string, array{weight_shift: int, night_weight_shift: int}>|null
     */
    private ?array $signatures = null;

    public function __construct(
        private readonly PurityScope $scope,
        private readonly PurityDefinitionLoader $loader,
        private readonly ActionYieldResolver $yieldResolver,
        private readonly WeeklyOutcropRepository $outcropRepository,
        private readonly ZoneVeinRepository $veinRepository,
        private readonly SettlementDefinitionLoader $settlementLoader,
        private readonly GameTimeService $gameTimeService,
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
            $zone,
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

        // FOY-11 : la seconde borne, celle qu'ECO-22 avait laissee en attente.
        // Elle vient **en dernier** et rabat tout ce qui precede : un filon pali
        // ne rend pas mieux que du clair, meme plein, meme affleurant. Vitalite
        // et Paleur ne disent pas la meme chose — l'une dit « il est presse en
        // ce moment », l'autre « on l'a trop presse ces jours-ci » — et c'est la
        // seconde qui doit gagner, sinon un filon abime se racheterait en une
        // nuit de repousse.
        if ($this->isDulled($zone, $veinSlug) && $ceiling->level() > Purity::Clair->level()) {
            $ceiling = Purity::Clair;
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

        $outcrop = $this->outcropRepository->findForWeek(WeekKey::of(new \DateTimeImmutable()));

        return $outcrop !== null
            && $outcrop->getVeinSlug() === $veinSlug
            && $outcrop->getZone()->getSlug() === $zone->getSlug();
    }

    /**
     * Ce filon est-il assez pali pour que sa bande en souffre (FOY-11) ?
     *
     * Le seuil est declaratif : sous lui, la Paleur existe mais ne fait rien.
     * Un filon normalement frequente ne doit pas voir sa bande tomber pour une
     * trace que personne ne voit.
     */
    private function isDulled(?Zone $zone, ?string $veinSlug): bool
    {
        if ($zone === null || $veinSlug === null) {
            return false;
        }

        $vein = $this->veinRepository->findOneByZoneAndSlug($zone, $veinSlug);
        if ($vein === null) {
            return false;
        }

        return $vein->getPaleness() >= $this->settlementLoader->load()['paleness']['dulls_purity_from'];
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
    /**
     * Ce que la geologie de la zone deplace, a l'heure qu'il est (ZON-32).
     *
     * Une zone absente de la table tire comme la reference : livrer une zone
     * neuve ne doit pas exiger d'en decrire la geologie avant qu'on sache ce
     * qu'elle sera.
     *
     * L'heure compte parce qu'une signature peut etre **erratique** : le Marais
     * depose mal le jour et bien la nuit, et c'est l'information exclusive type
     * du prospecteur (GAME_ZONE_ACTIONS § 5.5) — savoir *quand* frapper.
     */
    private function signatureShift(?Zone $zone): int
    {
        if (null === $zone) {
            return 0;
        }

        $signature = $this->signatures()[$zone->getSlug()] ?? null;
        if (null === $signature) {
            return 0;
        }

        return $this->gameTimeService->isNight()
            ? $signature['night_weight_shift']
            : $signature['weight_shift'];
    }

    /**
     * Deplace des points de poids d'un cran a la fois.
     *
     * Vers le haut quand le signe est positif — le trouble nourrit le clair, le
     * clair nourrit le pur —, vers le bas quand il est negatif. Le **parfait ne
     * bouge jamais** : il ne s'achete ni a l'experience, ni a la geographie, et
     * c'est ce qui le garde rare sans table de drop (GAME_WORLD § 5.4).
     *
     * @param array<string, int> $weights
     *
     * @return array<string, int>
     */
    private function applyShift(array $weights, int $shift): array
    {
        if ($shift > 0) {
            $fromTrouble = min($shift, $weights[Purity::Trouble->value]);
            $weights[Purity::Trouble->value] -= $fromTrouble;
            $weights[Purity::Clair->value] += $fromTrouble;

            $fromClair = min($shift, $weights[Purity::Clair->value]);
            $weights[Purity::Clair->value] -= $fromClair;
            $weights[Purity::Pur->value] += $fromClair;

            return $weights;
        }

        if ($shift < 0) {
            $down = -$shift;

            $fromPur = min($down, $weights[Purity::Pur->value]);
            $weights[Purity::Pur->value] -= $fromPur;
            $weights[Purity::Clair->value] += $fromPur;

            $fromClair = min($down, $weights[Purity::Clair->value]);
            $weights[Purity::Clair->value] -= $fromClair;
            $weights[Purity::Trouble->value] += $fromClair;
        }

        return $weights;
    }

    public function weightsFor(Purity $ceiling, int $skillBonus, ?Zone $zone = null): array
    {
        $draw = $this->definition();
        $weights = $draw['base_weights'];

        // ZON-32 — la signature de la zone vient **en premier** : c'est la
        // geologie du lieu, et le savoir du recolteur travaille dessus. Un
        // prospecteur chevronne tire mieux aux Mines qu'un debutant, mais il y
        // tire toujours moins bien qu'a la Crete.
        $weights = $this->applyShift($weights, $this->signatureShift($zone));

        $shift = min(max(0, $skillBonus) * $draw['skill_weight_per_point'], $draw['skill_weight_cap']);
        $weights = $this->applyShift($weights, $shift);

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

    /**
     * Les signatures de zone, memoisees (ZON-32).
     *
     * @return array<string, array{weight_shift: int, night_weight_shift: int}>
     */
    private function signatures(): array
    {
        if ($this->signatures === null) {
            $this->signatures = $this->loader->load()['signatures'];
        }

        return $this->signatures;
    }

    protected function roll(int $max): int
    {
        return random_int(1, max(1, $max));
    }
}
