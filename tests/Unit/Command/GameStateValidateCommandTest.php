<?php

namespace App\Tests\Unit\Command;

use App\Command\GameStateValidateCommand;
use App\GameEngine\Debug\GameStateValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class GameStateValidateCommandTest extends TestCase
{
    private GameStateValidator&MockObject $validator;
    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(GameStateValidator::class);

        $command = new GameStateValidateCommand($this->validator);
        $app = new Application();
        $app->add($command);
        $this->tester = new CommandTester($app->find('app:game:validate'));
    }

    public function testCommandSucceedsWhenNoAnomalies(): void
    {
        $this->validator->method('validateAll')
            ->willReturn([
                'ghost_fights' => [],
                'fights_without_living_mobs' => [],
                'orphaned_player_items' => [],
                'stale_active_quests' => [],
                'players_out_of_bounds' => [],
                'negative_domain_experience' => [],
                'equipped_items_wrong_location' => [],
            ]);

        $this->tester->execute([]);

        $this->assertSame(0, $this->tester->getStatusCode());
        $this->assertStringContainsString('Aucune anomalie', $this->tester->getDisplay());
    }

    public function testCommandFailsWhenAnomaliesDetected(): void
    {
        $this->validator->method('validateAll')
            ->willReturn([
                'ghost_fights' => ['Player #1 "Alice" references fight #999 which does not exist'],
                'fights_without_living_mobs' => [],
                'orphaned_player_items' => [],
                'stale_active_quests' => [],
                'players_out_of_bounds' => [],
                'negative_domain_experience' => [],
                'equipped_items_wrong_location' => [],
            ]);

        $this->tester->execute([]);

        $this->assertSame(1, $this->tester->getStatusCode());
        $this->assertStringContainsString('1 anomalie(s)', $this->tester->getDisplay());
        $this->assertStringContainsString('Alice', $this->tester->getDisplay());
    }

    public function testCommandDisplaysAllCheckLabels(): void
    {
        $this->validator->method('validateAll')
            ->willReturn([
                'ghost_fights' => [],
                'fights_without_living_mobs' => [],
                'orphaned_player_items' => [],
                'stale_active_quests' => [],
                'players_out_of_bounds' => [],
                'negative_domain_experience' => [],
                'equipped_items_wrong_location' => [],
            ]);

        $this->tester->execute([]);

        $display = $this->tester->getDisplay();
        $this->assertStringContainsString('combat inexistant', $display);
        $this->assertStringContainsString('mobs vivants', $display);
        $this->assertStringContainsString('orphelins', $display);
        $this->assertStringContainsString('completees', $display);
        $this->assertStringContainsString('limites', $display);
        $this->assertStringContainsString('Experience de domaine', $display);
        $this->assertStringContainsString('Items equipes hors inventaire', $display);
    }

    public function testCheckOptionRunsSingleCheck(): void
    {
        $this->validator->method('runCheck')
            ->with('ghost_fights')
            ->willReturn([]);

        $this->tester->execute(['--check' => 'ghost_fights']);

        $this->assertSame(0, $this->tester->getStatusCode());
    }

    public function testCheckOptionRejectsUnknownCheck(): void
    {
        $this->tester->execute(['--check' => 'unknown_check']);

        $this->assertSame(1, $this->tester->getStatusCode());
        $this->assertStringContainsString('Unknown check', $this->tester->getDisplay());
    }
}
