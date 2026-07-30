<?php

namespace App\Tests\Unit\GameEngine\Tutorial;

use App\Entity\App\Player;
use App\Enum\TutorialStep;
use App\GameEngine\Tutorial\TutorialManager;
use App\Repository\PlayerQuestCompletedRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Un seul etat d'onboarding (ONB-14).
 *
 * **La dette D7, telle qu'elle se jouait.** `player.tutorial_step` avancait par
 * cinq abonnements a des evenements de jeu, l'arc `intro` avancait par ses
 * quetes, et rien ne les reliait. Un joueur pouvait terminer le tutoriel sans
 * avoir touche a l'arc — il suffisait de voyager, de tuer, de ramasser, de
 * rendre une quete quelconque et de fabriquer — puis obtenir le succes
 * `tutorial-complete` bien avant la vraie fin de l'acte I.
 *
 * Le remede n'est pas de synchroniser les deux : c'est de n'en garder qu'un.
 * **Deux etats ne peuvent plus diverger quand il n'y en a qu'un.**
 */
class TutorialManagerTest extends TestCase
{
    private PlayerQuestCompletedRepository&MockObject $completed;
    private TutorialManager $manager;

    protected function setUp(): void
    {
        $this->completed = $this->createMock(PlayerQuestCompletedRepository::class);
        $this->manager = new TutorialManager(
            $this->createMock(EntityManagerInterface::class),
            $this->completed,
        );
    }

    /**
     * L'etape se deduit de l'arc, elle ne se stocke pas.
     *
     * Les bornes sont les trois tours de la boucle, pas cinq etapes
     * arbitraires : l'arme (1-2), la materia (3-5), le metier (6-8), puis le
     * depart et l'expedition.
     */
    public function testTheStepIsDerivedFromTheArc(): void
    {
        foreach ([
            0 => TutorialStep::Weapon,
            1 => TutorialStep::Weapon,
            2 => TutorialStep::Materia,
            4 => TutorialStep::Materia,
            5 => TutorialStep::Trade,
            7 => TutorialStep::Trade,
            8 => TutorialStep::Departure,
            9 => TutorialStep::Expedition,
        ] as $done => $expected) {
            self::assertSame($expected, TutorialStep::fromCompletedSteps($done), sprintf('%d quete(s) terminee(s) devrait donner « %s ».', $done, $expected->name));
        }
    }

    /**
     * L'arc termine ferme le tutoriel, et rien d'autre ne le ferme.
     */
    public function testTheArcClosesTheTutorial(): void
    {
        self::assertNull(TutorialStep::fromCompletedSteps(TutorialStep::ARC_STEPS));
        self::assertNull(TutorialStep::fromCompletedSteps(TutorialStep::ARC_STEPS + 3), 'Un compte superieur — arc rejoue — doit rester termine.');
    }

    public function testAPlayerWhoHasDoneNothingIsInTheTutorial(): void
    {
        $this->completed->method('countCompletedInArc')->willReturn(0);

        $player = new Player();

        self::assertTrue($this->manager->isInTutorial($player));
        self::assertFalse($this->manager->isCompleted($player));
        self::assertSame(TutorialStep::Weapon, $this->manager->getCurrentStep($player));
    }

    public function testAFinishedArcLeavesNoStep(): void
    {
        $this->completed->method('countCompletedInArc')->willReturn(TutorialStep::ARC_STEPS);

        $player = new Player();

        self::assertTrue($this->manager->isCompleted($player));
        self::assertNull($this->manager->getCurrentStep($player));
    }

    /**
     * Le refus est le seul etat que l'arc n'exprime pas.
     *
     * Un joueur qui a passe le tutoriel l'a dit une fois pour toutes, et aucune
     * quete n'enregistre cela — c'est la seule raison pour laquelle une colonne
     * subsiste.
     */
    public function testRefusalOutranksProgress(): void
    {
        $this->completed->method('countCompletedInArc')->willReturn(3);

        $player = new Player();
        $player->skipOnboarding();

        self::assertTrue($this->manager->isCompleted($player));
        self::assertNull($this->manager->getCurrentStep($player));
    }

    public function testRefusalIsRecordedOnlyOnce(): void
    {
        $player = new Player();

        self::assertFalse($player->hasSkippedOnboarding());

        $player->skipOnboarding();
        $first = $player->getOnboardingSkippedAt();

        $player->skipOnboarding();

        self::assertSame($first, $player->getOnboardingSkippedAt(), 'Un second refus a reecrit la date : le geste n\'est plus idempotent.');
    }

    /**
     * Passer le tutoriel abandonne l'arc, et c'est le meme geste.
     *
     * C'etait la moitie la plus visible de D7 : le bandeau disparaissait, les
     * quetes restaient au journal, et le joueur gardait ouverte une chaine
     * qu'il venait explicitement de refuser.
     */
    public function testSkippingAbandonsTheArc(): void
    {
        $manager = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/GameEngine/Tutorial/TutorialManager.php');

        self::assertStringContainsString('$this->entityManager->remove($playerQuest)', $manager);
        self::assertStringContainsString('$player->skipOnboarding()', $manager);
    }

    /**
     * Plus aucun compteur parallele n'existe.
     *
     * Le verifier par la **forme** : un service qui recommencerait a ecrire un
     * avancement le ferait sans qu'aucun scenario ne s'en plaigne, puisque les
     * deux etats seraient d'accord le premier jour. C'est precisement ainsi que
     * D7 s'est installee.
     */
    public function testNoParallelCounterSurvives(): void
    {
        self::assertFileDoesNotExist(
            \dirname(__DIR__, 4) . '/src/GameEngine/Tutorial/TutorialProgressListener.php',
            'Le listener qui faisait avancer un second etat d\'onboarding est de retour.',
        );

        $writers = [];
        foreach ($this->phpSources() as $relative => $source) {
            if (str_contains($source, 'setTutorialStep(')) {
                $writers[] = $relative;
            }
        }

        self::assertSame([], $writers, 'Un second etat d\'onboarding est ecrit quelque part : il finira par contredire l\'arc.');
    }

    /**
     * @return array<string, string>
     */
    private function phpSources(): array
    {
        $root = \dirname(__DIR__, 4);
        $sources = [];

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/src', \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $sources[substr($file->getPathname(), \strlen($root) + 1)] = (string) file_get_contents($file->getPathname());
            }
        }

        return $sources;
    }
}
