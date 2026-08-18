<?php

namespace App\Tests\Integration\Progression;

use App\Entity\App\Player;
use App\Entity\Game\Skill;
use App\GameEngine\Balance\VitalityLaw;
use App\GameEngine\Progression\CombatBranchCatalog;
use App\GameEngine\Progression\SkillLeverReader;
use App\GameEngine\Progression\VitalityTier;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Le Socle, et ce qu'il n'a pas le droit d'etre (ARC-20b).
 *
 * GAME_VITALITY § 8, invariants 1 a 6 et 11. La regle qui tient tout le jalon :
 *
 * > ***La progression verticale ne doit jamais etre un choix ; seule la
 * > differenciation l'est.***
 *
 * C'est elle qui rend le levier `life` **facultatif** — sans le Socle, il
 * deviendrait obligatoire dans les 24 arbres et le budget de 50 pb n'en
 * compterait plus que 30.
 */
class VitalitySocleTest extends AbstractIntegrationTestCase
{
    /**
     * **Le maximum, jamais la somme.**.
     *
     * La seule forme qui survive a « le savoir n'est jamais borne » : un nœud
     * additif a +100 PV donnerait **+3 200 PV** au joueur qui a mene les
     * 32 arbres — le defaut exact de `Skill::life`, plat et cumulatif.
     *
     * Consequence a assumer : **ouvrir un dixieme arbre ne rend pas plus
     * resistant**. C'est voulu — sinon la barre recompenserait le nombre
     * d'arbres ouverts, c'est-a-dire le temps passe.
     */
    public function testTheBarIsAMaximumNeverASum(): void
    {
        $player = new Player();
        $player->getSkills()->clear();

        $player->addSkill($this->socle(2));
        $player->addSkill($this->socle(2));
        $player->addSkill($this->socle(1));

        self::assertSame(2, VitalityTier::tierOf($player), 'Les Socles se cumulent : la barre recompense le nombre d\'arbres.');
        self::assertSame(VitalityLaw::barFor(2), VitalityTier::barOf($player));
    }

    /**
     * **Le plancher est inconditionnel.**.
     *
     * Un personnage qui sort du tunnel, ou qui ne mene que des arbres de
     * metier, a le palier 1 sans rien avoir appris : ***on ne peut pas se
     * retrouver sans barre de vie***.
     */
    public function testTheFloorIsUnconditional(): void
    {
        $player = new Player();
        $player->getSkills()->clear();

        self::assertSame(VitalityLaw::FIRST_TIER, VitalityTier::tierOf($player));
        self::assertSame(VitalityLaw::floor(), VitalityTier::barOf($player));
    }

    /**
     * **Un Socle est une porte : gratuit, sans levier, sans geste.**.
     *
     * *Gratuit parce qu'il n'est pas une recompense* — le faire payer en points
     * en ferait un peage, et en budget la taxe de PoE : un cout que les
     * 24 arbres acquitteraient tous, donc qui ne differencierait rien.
     */
    public function testASocleCostsNothingAndGrantsNothingElse(): void
    {
        /** @var SkillLeverReader $reader */
        $reader = self::getContainer()->get(SkillLeverReader::class);

        $socles = 0;
        foreach ($this->em->getRepository(Skill::class)->findAll() as $skill) {
            if (!VitalityTier::isSocle($skill)) {
                continue;
            }

            ++$socles;
            self::assertSame(0, $skill->getRequiredPoints(), $skill->getSlug());
            self::assertSame([], $reader->grantsOf($skill), sprintf('%s accorde un levier : ce n\'est plus une porte.', $skill->getSlug()));
            self::assertSame(0, $skill->getLife(), sprintf('%s ecrit encore une statistique plate.', $skill->getSlug()));

            $actions = $skill->getActions();
            self::assertArrayNotHasKey('materia', $actions, sprintf('%s ouvre un geste.', $skill->getSlug()));
        }

        self::assertGreaterThan(0, $socles, 'Aucun Socle en base : la barre ne monte nulle part.');
    }

    /**
     * **Les 24 arbres de combat ont leurs trois Socles**, et eux seuls.
     *
     * En cliquet : la couverture peut grandir, jamais retrecir. Un arbre de
     * metier n'en recoit aucun — *la barre se gagne en combat, et un forgeron
     * garde le plancher*.
     */
    public function testEveryCombatTreeCarriesItsThreeSocles(): void
    {
        $trees = (new CombatBranchCatalog(\dirname(__DIR__, 3)))->trees();

        $byDomain = [];
        foreach ($this->em->getRepository(Skill::class)->findAll() as $skill) {
            $tier = VitalityTier::of($skill);
            if ($tier === null) {
                continue;
            }

            foreach ($skill->getDomains() as $domain) {
                $byDomain[$domain->getTitle()][] = $tier;
            }
        }

        self::assertCount(
            \count($trees),
            $byDomain,
            sprintf('%d arbres portent un Socle pour %d arbres de combat.', \count($byDomain), \count($trees))
        );

        foreach ($byDomain as $title => $tiers) {
            sort($tiers);
            self::assertSame([1, 2, 3], $tiers, sprintf('%s n\'a pas ses trois Socles.', $title));
        }
    }

    /**
     * **`Skill::life` ne fait plus monter la barre.**.
     *
     * Il etait plat (donc ineequilibrable), cumulatif (donc explosif), et ecrit
     * **en dur** dans `Player::maxLife` a chaque apprentissage — la meme fuite
     * que les echelons de port de l'ecart n° 5, qui ecrivaient une statistique
     * la ou ils ne devaient qu'ouvrir une porte.
     */
    public function testTheFlatLifeBonusNoLongerRaisesTheBar(): void
    {
        $source = file_get_contents(\dirname(__DIR__, 3) . '/src/GameEngine/Progression/SkillAcquiring.php');
        self::assertIsString($source);

        self::assertStringNotContainsString(
            '$skill->getLife()',
            $source,
            'Apprendre une competence ajoute encore un bonus plat de vie : le Socle et l\'ancien systeme se cumulent.'
        );
    }

    private function socle(int $tier): Skill
    {
        $skill = new Skill();
        $skill->setTitle(sprintf('Socle %d', $tier));
        $skill->setSlug(sprintf('socle-test-%d-%s', $tier, bin2hex(random_bytes(4))));
        $skill->setActions([VitalityTier::ACTION_KEY => ['tier' => $tier]]);

        return $skill;
    }
}
