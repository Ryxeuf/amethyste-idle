<?php

namespace App\Tests\Unit\GameEngine\Balance;

use App\Entity\App\Player;
use App\Enum\CombatRegister;
use App\GameEngine\Balance\DailyAnchor;
use App\GameEngine\Zone\ActionEnergyManager;
use App\GameEngine\Zone\HuntService;
use App\GameEngine\Zone\LifeRegenManager;
use App\GameEngine\Zone\ManaRegenManager;
use PHPUnit\Framework\TestCase;

/**
 * La seconde ancre, et ce qu'elle justifie (ARC-05b).
 *
 * GAME_ARCHETYPES § 6.4 : la duree d'un combat n'est qu'une moitie de l'ancre.
 * Deux archetypes peuvent tenir onze tours de la meme facon et n'avoir rien a
 * voir a l'echelle de la journee. La seconde moitie mesure **le cout d'une
 * rencontre en ressource, rapporte au budget du jour**, converti dans la seule
 * monnaie commune : le temps.
 *
 * Ce jalon ne deplace aucune valeur de jeu. Il pose la regle, la rend
 * calculable, et **donne enfin sa raison au curseur des PM** — que le canon
 * (§ 9 septies.2) designe comme « le curseur qui decide de tout l'equilibre
 * solo, et il n'existe pas ». Il existe depuis ARC-04a ; ce test montre ce
 * qu'il tient.
 */
class DailyAnchorTest extends TestCase
{
    /**
     * Le releve du § 9 septies.2 — six builds sur une journee.
     *
     * **Ce ne sont pas des valeurs de jeu** (§ 0.2) : ce sont les reperes
     * calcules a la main par le canon, sur son echelle illustrative. Ils
     * servent ici de **jeu d'essai de l'instrument** : si la classe les rejoue
     * a la minute pres, elle mesure bien ce que le canon a mesure. ARC-17
     * remplacera cette table par les vraies donnees ; la regle, elle, ne
     * bougera pas.
     *
     * @var array<string, array{life: int, mana: int, minutes: int}>
     */
    private const CANON_DAY = [
        'Soldat — la Ligne mobile' => ['life' => 494, 'mana' => 0, 'minutes' => 99],
        'Archer — le Guet' => ['life' => 592, 'mana' => 0, 'minutes' => 118],
        'Soldat — le Mur' => ['life' => 710, 'mana' => 0, 'minutes' => 142],
        'Guerisseur — le Ressac' => ['life' => 70, 'mana' => 1440, 'minutes' => 158],
        'Hydromancien — la Vague' => ['life' => 445, 'mana' => 900, 'minutes' => 179],
        'Pyromancien — l\'Eclat' => ['life' => 619, 'mana' => 1600, 'minutes' => 284],
    ];

    /**
     * Les cinq builds que le canon tient pour calibres.
     *
     * Le sixieme — le Pyromancien — en sort, et le canon le dit lui-meme : il
     * *paie deux fois (fragile et depensier) et reste a recalibrer*. Le ranger
     * ici serait masquer le seul defaut que l'exercice a trouve.
     */
    private const CALIBRATED = [
        'Soldat — la Ligne mobile',
        'Archer — le Guet',
        'Soldat — le Mur',
        'Guerisseur — le Ressac',
        'Hydromancien — la Vague',
    ];

    /**
     * L'attente quotidienne des builds retenus, au curseur de PM donne.
     *
     * @param list<string> $names
     *
     * @return array<string, int>
     */
    private function restSecondsFor(array $names, int $manaRegenSeconds): array
    {
        $rest = [];
        foreach (self::CANON_DAY as $name => $day) {
            if (!\in_array($name, $names, true)) {
                continue;
            }

            $rest[$name] = DailyAnchor::restSeconds(
                $day['life'],
                $day['mana'],
                LifeRegenManager::DEFAULT_REGEN_SECONDS,
                $manaRegenSeconds,
            );
        }

        return $rest;
    }

    /**
     * L'instrument rejoue le releve du canon, a la minute pres.
     *
     * C'est l'aller-retour du jalon : la classe lit les **curseurs livres**
     * (12 s par PV, 6 s par PM) et retrouve la colonne « total » du § 9
     * septies.2. Si l'un des deux curseurs bougeait sans que le canon soit
     * revu, ce test le dirait — ce qui est tout l'objet d'une ancre.
     */
    public function testTheInstrumentReproducesTheCanonDay(): void
    {
        foreach (self::CANON_DAY as $name => $day) {
            self::assertSame(
                $day['minutes'],
                DailyAnchor::restMinutes(
                    $day['life'],
                    $day['mana'],
                    LifeRegenManager::DEFAULT_REGEN_SECONDS,
                    ManaRegenManager::DEFAULT_REGEN_SECONDS,
                ),
                sprintf('%s : la seconde ancre ne retrouve pas la journee du canon.', $name),
            );
        }
    }

    /**
     * Les cinq builds calibres tiennent dans l'ancre de fonction.
     *
     * *A arbre complet et equipement egal, les quatre fonctions doivent
     * enchainer le meme nombre de rencontres par jour et en sortir dans un etat
     * comparable* (correction 16). Mesure : x1,81 — du simple au double, borne
     * comprise.
     */
    public function testTheCalibratedBuildsHoldTheFunctionAnchor(): void
    {
        $rest = $this->restSecondsFor(self::CALIBRATED, ManaRegenManager::DEFAULT_REGEN_SECONDS);

        self::assertTrue(
            DailyAnchor::isWithinFunctionAnchor($rest),
            sprintf('Ecart mesure : x%.2f pour une borne de x%.1f.', DailyAnchor::restSpread($rest), DailyAnchor::MAX_REST_SPREAD),
        );
    }

    /**
     * Sans le curseur des PM, l'ancre de fonction ne tient pas.
     *
     * C'est le resultat du jalon, et la justification chiffree de
     * `zone.mana.regen_seconds`. Tant que les PM sont gratuits, le guerisseur
     * paie 14 minutes la ou le Mur en paie 142 : *il enchaine les combats
     * jusqu'a epuiser son energie d'action pendant que les autres attendent
     * leur regeneration*. L'ecart passe a x10, soit cinq fois la borne — une
     * fonction jouerait plusieurs fois plus de contenu que les autres, ce que
     * l'ancre de fonction interdit.
     *
     * Ce test est la reponse a BALANCE § 24.2 : le curseur n'est pas un confort,
     * c'est ce qui met l'entretien sur la meme ligne que les trois autres.
     */
    public function testWithoutTheManaCursorTheFunctionAnchorBreaks(): void
    {
        $free = $this->restSecondsFor(self::CALIBRATED, 0);

        self::assertFalse(
            DailyAnchor::isWithinFunctionAnchor($free),
            'Des PM gratuits laisseraient l\'entretien hors de toute bande.',
        );
        self::assertGreaterThan(
            DailyAnchor::MAX_REST_SPREAD * 2,
            DailyAnchor::restSpread($free),
            'L\'ecart sans curseur doit rester manifeste, pas marginal.',
        );
    }

    /**
     * Le curseur des PM est plus rapide que celui des PV, et jamais gratuit.
     *
     * La symetrie du § 9 septies.2 : *les PV paient les coups recus, les PM
     * paient les gestes faits* — et on fait beaucoup plus de gestes qu'on ne
     * recoit de coups, donc le point de PM se rend plus vite. Les deux valeurs
     * exactes se recalibreront (§ 0.2) ; **l'ordre entre elles, non**.
     */
    public function testTheManaCursorIsFasterThanLifeAndNeverFree(): void
    {
        self::assertGreaterThan(0, ManaRegenManager::DEFAULT_REGEN_SECONDS);
        self::assertLessThan(
            LifeRegenManager::DEFAULT_REGEN_SECONDS,
            ManaRegenManager::DEFAULT_REGEN_SECONDS,
        );
    }

    /**
     * La journee autorise le nombre de rencontres que le canon lui prete.
     *
     * Le « ~16 combats » du § 6.4 n'est pas un chiffre pose : il se **derive**
     * des curseurs livres — 240 points d'energie rendus par jour, un tiers au
     * combat, une chasse a 5 points. Changer l'un des trois deplace la seconde
     * ancre, ce qui est exactement ce qu'on veut d'une ancre.
     */
    public function testTheDayYieldsTheEncounterCountTheCanonAssumes(): void
    {
        $budget = DailyAnchor::dailyEnergyBudget(ActionEnergyManager::DEFAULT_REGEN_SECONDS);

        self::assertSame(Player::DEFAULT_MAX_ACTION_ENERGY, $budget);
        self::assertSame(16, DailyAnchor::encountersPerDay($budget, HuntService::DEFAULT_COST));
    }

    /**
     * Une journee sans regeneration ne rend rien, et une rencontre gratuite ne
     * se compte pas.
     *
     * Les deux cas degeneres se lisent « la question ne se pose pas » plutot
     * que de produire une division par zero ou un nombre infini de rencontres.
     */
    public function testTheDegenerateCasesDoNotInventANumber(): void
    {
        self::assertSame(0, DailyAnchor::dailyEnergyBudget(0));
        self::assertSame(0, DailyAnchor::encountersPerDay(240, 0));
        self::assertSame(1.0, DailyAnchor::restSpread([]));
    }

    /**
     * Seuls les PM se reportent d'une rencontre a la suivante.
     *
     * C'est ce qui rend le registre melee structurellement different (§ 6.4) et
     * ce qu'ARC-04b a acte pour la distance : le carquois est **durable**, il
     * se vide dans la rencontre et se ramasse apres. Le jour ou une ressource
     * de melee ou de distance se reporterait, la seconde ancre aurait deux
     * colonnes de plus — et ce test le rappellerait.
     */
    public function testOnlySpellsCarryTheirResourceBetweenEncounters(): void
    {
        self::assertTrue(DailyAnchor::carriesOverBetweenEncounters(CombatRegister::Spell));
        self::assertFalse(DailyAnchor::carriesOverBetweenEncounters(CombatRegister::Melee));
        self::assertFalse(DailyAnchor::carriesOverBetweenEncounters(CombatRegister::Ranged));
    }
}
