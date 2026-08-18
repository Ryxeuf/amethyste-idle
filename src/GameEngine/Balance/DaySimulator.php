<?php

namespace App\GameEngine\Balance;

use App\Enum\MonsterRank;
use App\GameEngine\Zone\ActionEnergyManager;
use App\GameEngine\Zone\HuntService;
use App\GameEngine\Zone\LifeRegenManager;
use App\GameEngine\Zone\ManaRegenManager;

/**
 * La journee jouee, et non plus seulement calculee (ARC-17c-c).
 *
 * `DailyAnchor` (ARC-05b) a rendu la journee **calculable** : combien de
 * rencontres le budget d'energie autorise, et ce qu'une perte coute en minutes
 * d'attente. Il lui manquait ce que seule une simulation donne — **ce qu'une
 * rencontre coute reellement a ce build-la**. La table du § 9 septies.2 a ete
 * remplie a la main sur six builds ; le canon (§ 0.2) previent que c'est
 * precisement ce qu'il ne faut plus faire.
 *
 * ## La journee du canon, jouee
 *
 * *14 communs et 2 tentatives d'elite* (§ 9 sexies). Les deux moities disent
 * deux choses differentes, et il faut les deux :
 *
 *  - **les communs** mesurent l'entretien — le cout par rencontre, multiplie par
 *    ce que la journee autorise ;
 *  - **les elites** mesurent le plafond — *une elite tue un joueur seul* est un
 *    seuil du § 9 octies, et il ne se lit pas sur un commun.
 *
 * Le nombre de communs n'est pas ecrit : il se **derive** du budget reel
 * (`zone.energy.regen_seconds`, la part de combat, `zone.energy.cost.hunt`), et
 * les deux tentatives d'elite s'y prennent dessus. Deplacer un curseur deplace la
 * journee, ce qu'une constante ne ferait pas.
 *
 * ## Ce qu'une journee reelle a et qu'une rencontre isolee n'a pas
 *
 * **La regeneration entre deux rencontres.** Un build ne repart pas a plein a
 * chaque combat : il repart avec ce que le temps lui a rendu. Mais le temps est
 * justement ce qu'on mesure — l'attente **est** le cout. On joue donc chaque
 * rencontre a plein et on **cumule l'attente** que la remise a plein represente,
 * ce qui est la lecture de `DailyAnchor` : *les PV paient les coups recus, les PM
 * paient les gestes faits, et les deux se rechargent en temps reel*.
 *
 * Un build qui **tombe** ne joue pas la suite de sa journee : la mort n'est pas
 * une attente plus longue, c'est une journee qui s'arrete. Les deux colonnes —
 * l'attente et la part du budget jouee — se lisent ensemble, et c'est ce qui
 * empeche de lire « ce guerisseur attend peu » quand il est mort au troisieme
 * combat.
 */
final class DaySimulator
{
    /**
     * Les tentatives d'elite d'une journee (§ 9 sexies).
     *
     * Deux, et le mot compte : ce sont des **tentatives**. Une elite qui tue son
     * joueur reste dans la journee — c'est meme ce que le seuil du § 9 octies
     * mesure.
     */
    public const ELITE_ATTEMPTS = 2;

    public function __construct(
        private readonly EncounterSimulator $simulator,
        private readonly ActionEnergyManager $actionEnergy,
        private readonly HuntService $huntService,
        private readonly LifeRegenManager $lifeRegen,
        private readonly ManaRegenManager $manaRegen,
    ) {
    }

    public function simulate(ReferenceCharacter $character, int $tier): DayOutcome
    {
        $budget = DailyAnchor::dailyEnergyBudget($this->actionEnergy->getRegenSeconds());
        $encounters = DailyAnchor::encountersPerDay($budget, $this->huntService->getHuntCost());

        // Les elites se prennent **sur** le budget, jamais en plus : une journee
        // ne s'agrandit pas parce qu'on choisit un adversaire plus gros.
        $elites = min(self::ELITE_ATTEMPTS, $encounters);
        $commons = max(0, $encounters - $elites);

        $lifeLost = 0;
        $resourceSpent = 0;
        $cleared = 0;
        $deaths = 0;

        foreach ($this->plannedRanks($commons, $elites) as $rank) {
            $outcome = $this->simulator->simulate($character, $tier, $rank);

            $lifeLost += $outcome->lifeLost;
            $resourceSpent += $outcome->resourceSpent;

            if ($outcome->victory) {
                ++$cleared;
                continue;
            }

            // Tomber arrete la journee. Continuer a compter des rencontres
            // apres une mort donnerait a un build fragile la meme journee qu'a
            // un build qui tient — c'est exactement l'ecart qu'on mesure.
            ++$deaths;
            break;
        }

        return new DayOutcome(
            buildLabel: $character->label,
            tier: $tier,
            encountersBudgeted: $encounters,
            commonsBudgeted: $commons,
            encountersCleared: $cleared,
            deaths: $deaths,
            lifeLost: $lifeLost,
            resourceSpent: $resourceSpent,
            restSeconds: DailyAnchor::restSeconds(
                $lifeLost,
                $resourceSpent,
                $this->lifeRegen->getRegenSeconds(),
                $this->manaRegen->getRegenSeconds(),
            ),
        );
    }

    /**
     * L'ordre de la journee : les communs d'abord, les elites ensuite.
     *
     * Ce n'est pas indifferent. Placer les elites en tete ferait tomber les
     * builds fragiles au premier combat et rendrait une journee de deux
     * rencontres pour tout le monde ; les placer a la fin mesure ce que le canon
     * decrit — *on chasse, et on tente*.
     *
     * @return list<MonsterRank>
     */
    private function plannedRanks(int $commons, int $elites): array
    {
        return array_merge(
            array_fill(0, max(0, $commons), MonsterRank::Common),
            array_fill(0, max(0, $elites), MonsterRank::Elite),
        );
    }
}
