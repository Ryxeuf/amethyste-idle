<?php

namespace App\Tests\Unit\GameEngine\Player;

use App\Entity\App\Player;
use App\Enum\TutorialStep;
use App\GameEngine\Player\PnjDialogParser;
use App\GameEngine\Tutorial\TutorialManager;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for tutorial_step and tutorial_completed conditions in PnjDialogParser.
 */
class PnjDialogParserTutorialTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private PlayerHelper&MockObject $playerHelper;
    private TutorialManager&MockObject $tutorialManager;
    private PnjDialogParser $parser;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->tutorialManager = $this->createMock(TutorialManager::class);
        $this->parser = new PnjDialogParser($this->em, $this->playerHelper, $this->tutorialManager);
    }

    public function testTutorialStepConditionMatchesCurrent(): void
    {
        $player = new Player();
        $this->tutorialManager->method('getCurrentStep')->willReturn(TutorialStep::Materia);
        $this->tutorialManager->method('isCompleted')->willReturn(false);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $dialog = [
            [
                'text' => '',
                'conditional_next' => [
                    ['next' => 1, 'next_condition' => ['tutorial_step' => [TutorialStep::Weapon->value]]],
                    ['next' => 2, 'next_condition' => ['tutorial_step' => [TutorialStep::Materia->value]]],
                    ['next' => 3],
                ],
            ],
            ['text' => 'Movement'],
            ['text' => 'Combat'],
            ['text' => 'Default'],
        ];

        $result = $this->parser->parseDialog($dialog);

        $this->assertSame(2, $result[0]['next']);
    }

    public function testTutorialStepConditionNoMatchFallsThrough(): void
    {
        $player = new Player();
        $this->tutorialManager->method('getCurrentStep')->willReturn(TutorialStep::Expedition);
        $this->tutorialManager->method('isCompleted')->willReturn(false);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $dialog = [
            [
                'text' => '',
                'conditional_next' => [
                    ['next' => 1, 'next_condition' => ['tutorial_step' => [TutorialStep::Weapon->value]]],
                    ['next' => 2, 'next_condition' => ['tutorial_step' => [TutorialStep::Materia->value]]],
                    ['next' => 3],
                ],
            ],
            ['text' => 'Movement'],
            ['text' => 'Combat'],
            ['text' => 'Default'],
        ];

        $result = $this->parser->parseDialog($dialog);

        $this->assertSame(3, $result[0]['next']);
    }

    public function testTutorialStepAcceptsMultipleValues(): void
    {
        $player = new Player();
        $this->tutorialManager->method('getCurrentStep')->willReturn(TutorialStep::Departure);
        $this->tutorialManager->method('isCompleted')->willReturn(false);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $dialog = [
            [
                'text' => '',
                'conditional_next' => [
                    ['next' => 1, 'next_condition' => ['tutorial_step' => [
                        TutorialStep::Departure->value,
                        TutorialStep::Expedition->value,
                    ]]],
                    ['next' => 2],
                ],
            ],
            ['text' => 'Late tutorial'],
            ['text' => 'Default'],
        ];

        $result = $this->parser->parseDialog($dialog);

        $this->assertSame(1, $result[0]['next']);
    }

    public function testTutorialCompletedConditionWhenDone(): void
    {
        $player = new Player();
        $this->tutorialManager->method('getCurrentStep')->willReturn(null);
        $this->tutorialManager->method('isCompleted')->willReturn(true);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $dialog = [
            [
                'text' => '',
                'conditional_next' => [
                    ['next' => 1, 'next_condition' => ['tutorial_step' => [TutorialStep::Weapon->value]]],
                    ['next' => 2, 'next_condition' => ['tutorial_completed' => true]],
                    ['next' => 3],
                ],
            ],
            ['text' => 'Movement'],
            ['text' => 'Completed'],
            ['text' => 'Default'],
        ];

        $result = $this->parser->parseDialog($dialog);

        $this->assertSame(2, $result[0]['next']);
    }

    public function testTutorialCompletedConditionWhenInProgress(): void
    {
        $player = new Player();
        $this->tutorialManager->method('getCurrentStep')->willReturn(TutorialStep::Weapon);
        $this->tutorialManager->method('isCompleted')->willReturn(false);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $dialog = [
            [
                'text' => '',
                'conditional_next' => [
                    ['next' => 1, 'next_condition' => ['tutorial_completed' => true]],
                    ['next' => 2],
                ],
            ],
            ['text' => 'Completed'],
            ['text' => 'Default'],
        ];

        $result = $this->parser->parseDialog($dialog);

        $this->assertSame(2, $result[0]['next']);
    }

    public function testTutorialStepConditionWithNoPlayer(): void
    {
        $this->playerHelper->method('getPlayer')->willReturn(null);

        $dialog = [
            [
                'text' => '',
                'conditional_next' => [
                    ['next' => 1, 'next_condition' => ['tutorial_step' => [TutorialStep::Weapon->value]]],
                    ['next' => 2, 'next_condition' => ['tutorial_completed' => true]],
                    ['next' => 3],
                ],
            ],
            ['text' => 'Movement'],
            ['text' => 'Completed'],
            ['text' => 'Default'],
        ];

        $result = $this->parser->parseDialog($dialog);

        $this->assertSame(3, $result[0]['next']);
    }

}
