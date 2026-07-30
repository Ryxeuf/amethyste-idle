<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Mob;
use App\Entity\Game\Monster;
use App\Enum\TrainingMode;
use PHPUnit\Framework\TestCase;

/**
 * Les deux garanties du mannequin (ONB-11).
 *
 * Elles ne se valent pas :
 *
 * - **le premier ne frappe jamais**, donc perdre est impossible et l'on peut
 *   afficher toute l'interface sans qu'un joueur qui lit se fasse tuer ;
 * - **le second ne peut pas tuer**, ce qui n'est pas la meme chose : il frappe,
 *   la barre descend, et c'est ce qui apprend a quoi servent les soins.
 *
 * Et une troisieme, structurelle : **un mannequin n'est pas une rencontre**.
 * C'est elle qui permet d'enseigner le combat au Fanal sans lever son
 * `safe: true` — « ici, rien ne mord » reste vrai.
 */
class TrainingDummyTest extends TestCase
{
    public function testARealMonsterIsNotADummy(): void
    {
        self::assertFalse($this->monster(null)->isTrainingDummy());
        self::assertNull($this->monster(null)->getTrainingMode());
    }

    public function testTheFirstDummyNeverStrikes(): void
    {
        self::assertFalse(TrainingMode::Inert->strikes());
    }

    /**
     * Le second frappe, et c'est le point : sans coup recu, rien n'apprend a
     * quoi servent les soins. Ce qui le rend sur n'est pas de rater, c'est de
     * ne pas pouvoir tuer.
     */
    public function testTheSecondDummyStrikes(): void
    {
        self::assertTrue(TrainingMode::Capped->strikes());
    }

    public function testBothAreDummies(): void
    {
        foreach (TrainingMode::cases() as $mode) {
            self::assertTrue($this->monster($mode)->isTrainingDummy(), sprintf('« %s » n\'est pas reconnu comme mannequin.', $mode->value));
        }
    }

    /**
     * Le plancher a 1 point de vie est attache a **l'agresseur**, pas a la cible.
     *
     * C'est ce qui empeche la clemence de fuir : un joueur qui sort du Fanal
     * meurt comme tout le monde, des le premier loup. Si le plancher regardait
     * la cible — « ce joueur est en acte I » — il faudrait ensuite decider quand
     * il cesse de s'appliquer, et on trouverait un jour un debutant invincible.
     */
    public function testTheFloorLooksAtTheAttackerNotTheTarget(): void
    {
        $applicator = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/GameEngine/Fight/SpellApplicator.php');

        self::assertMatchesRegularExpression(
            '/isStruckByTrainingDummy\(CharacterInterface \$sender\)/',
            $applicator,
            'Le plancher ne regarde plus l\'agresseur : il pourrait rendre un joueur invincible hors du Fanal.',
        );
        self::assertStringContainsString(
            '$sender instanceof Mob && $sender->getMonster()->isTrainingDummy()',
            $applicator,
            'Le plancher ne se limite plus aux coups d\'un mannequin.',
        );
    }

    /**
     * Un mannequin n'entre dans **aucun** tirage de rencontre.
     *
     * Le filtre est pose au depot, pas chez les appelants : explorer et chasser
     * partent tous deux de la, et un appelant a venir en heritera sans avoir a
     * connaitre la regle. Le verifier ici plutot que dans chaque service evite
     * qu'un troisieme chemin naisse sans garde-fou.
     */
    public function testNoEncounterDrawEverReachesADummy(): void
    {
        $repository = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/Repository/MobRepository.php');

        $drawQueries = substr_count($repository, 'monster.trainingMode IS NULL');

        self::assertSame(2, $drawQueries, implode("\n", [
            'Les deux requetes de rencontre doivent exclure les mannequins :',
            '`findAvailableInZone` (exploration) et `findAvailableInZoneForMonster` (chasse ciblee).',
            'Sans cela, un mannequin deviendrait une proie — et le Fanal, une zone ou quelque chose mord.',
        ]));
    }

    /**
     * Le lanceur refuse un vrai monstre.
     *
     * Il contourne le tirage de rencontre : l'ouvrir a autre chose qu'un
     * mannequin reviendrait a poser un combat en zone sure, ce que le
     * `safe: true` existe pour interdire.
     */
    public function testTheLauncherRefusesARealMonster(): void
    {
        $launcher = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/GameEngine/Fight/TrainingFightLauncher.php');

        self::assertStringContainsString('if (!$dummy->isTrainingDummy())', $launcher);
        self::assertStringContainsString('InvalidArgumentException', $launcher);
    }

    /**
     * Un mannequin n'habite nulle part.
     *
     * S'il peuplait une zone, il apparaitrait dans la liste des proies, dans le
     * bestiaire et dans les compteurs de presence — et il faudrait alors
     * expliquer partout pourquoi il n'est pas une rencontre.
     */
    public function testADummyBelongsToNoZone(): void
    {
        $launcher = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/GameEngine/Fight/TrainingFightLauncher.php');

        self::assertStringContainsString('$mob->setZone(null);', $launcher);
    }

    /**
     * Les deux mannequins sont livres, et ce sont les seuls.
     */
    public function testExactlyTwoDummiesAreShipped(): void
    {
        $fixtures = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/DataFixtures/MonsterFixtures.php');

        self::assertSame(1, substr_count($fixtures, 'TrainingMode::Inert'), 'Il faut un mannequin qui ne frappe pas, et un seul.');
        self::assertSame(1, substr_count($fixtures, 'TrainingMode::Capped'), 'Il faut un mannequin qui riposte, et un seul.');
    }

    private function monster(?TrainingMode $mode): Monster
    {
        $monster = new Monster();
        $monster->setSlug('sujet');
        $monster->setName('Sujet');
        $monster->setLife(10);
        $monster->setTrainingMode($mode);

        return $monster;
    }

    /**
     * Garde-fou du garde-fou : le mob de test porte bien son monstre.
     */
    public function testTheFixtureHelperBuildsAUsableMob(): void
    {
        $mob = new Mob();
        $mob->setMonster($this->monster(TrainingMode::Inert));

        self::assertTrue($mob->getMonster()->isTrainingDummy());
    }
}
