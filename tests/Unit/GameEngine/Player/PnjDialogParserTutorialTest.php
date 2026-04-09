<?php

namespace App\Tests\Unit\GameEngine\Player;

use App\Entity\App\Player;
use App\Enum\TutorialStep;
use App\GameEngine\Player\PnjDialogParser;
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
    private PnjDialogParser $parser;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->parser = new PnjDialogParser($this->em, $this->playerHelper);
    }

    public function testTutorialStepConditionMatchesCurrent(): void
    {
        $player = $this->createPlayer(TutorialStep::Combat->value);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $dialog = [
            [
                'text' => '',
                'conditional_next' => [
                    ['next' => 1, 'next_condition' => ['tutorial_step' => [TutorialStep::Movement->value]]],
                    ['next' => 2, 'next_condition' => ['tutorial_step' => [TutorialStep::Combat->value]]],
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
        $player = $this->createPlayer(TutorialStep::Craft->value);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $dialog = [
            [
                'text' => '',
                'conditional_next' => [
                    ['next' => 1, 'next_condition' => ['tutorial_step' => [TutorialStep::Movement->value]]],
                    ['next' => 2, 'next_condition' => ['tutorial_step' => [TutorialStep::Combat->value]]],
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
        $player = $this->createPlayer(TutorialStep::Quests->value);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $dialog = [
            [
                'text' => '',
                'conditional_next' => [
                    ['next' => 1, 'next_condition' => ['tutorial_step' => [
                        TutorialStep::Quests->value,
                        TutorialStep::Craft->value,
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
        $player = $this->createPlayer(null);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $dialog = [
            [
                'text' => '',
                'conditional_next' => [
                    ['next' => 1, 'next_condition' => ['tutorial_step' => [TutorialStep::Movement->value]]],
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
        $player = $this->createPlayer(TutorialStep::Movement->value);
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
                    ['next' => 1, 'next_condition' => ['tutorial_step' => [TutorialStep::Movement->value]]],
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

    private function createPlayer(?int $tutorialStep): Player
    {
        $player = new Player();
        $player->setName('TestPlayer');
        $player->setTutorialStep($tutorialStep);

        return $player;
    }
}
