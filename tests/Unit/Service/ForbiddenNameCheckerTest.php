<?php

namespace App\Tests\Unit\Service;

use App\Service\ForbiddenNameChecker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ForbiddenNameCheckerTest extends TestCase
{
    private ForbiddenNameChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new ForbiddenNameChecker();
    }

    #[DataProvider('forbiddenNamesProvider')]
    public function testForbiddenNamesAreRejected(string $name): void
    {
        $this->assertTrue($this->checker->isForbidden($name), "Name '$name' should be forbidden");
    }

    /** @return iterable<string, array{string}> */
    public static function forbiddenNamesProvider(): iterable
    {
        yield 'system impersonation' => ['Admin'];
        yield 'system impersonation uppercase' => ['ADMIN'];
        yield 'system impersonation mixed' => ['AdMiN'];
        yield 'moderator' => ['Moderateur'];
        yield 'gamemaster' => ['GameMaster'];
        yield 'staff' => ['Staff'];
        yield 'reserved gm' => ['gm'];
        yield 'reserved npc' => ['NPC'];
        yield 'reserved bot' => ['bot'];
        yield 'reserved test' => ['test'];
        yield 'offensive french' => ['connard'];
        yield 'offensive english' => ['fuck'];
        yield 'leet speak' => ['$h1t'];
        yield 'leet speak numbers' => ['4dm1n'];
        yield 'spaced out' => ['f u c k'];
        yield 'with hyphens' => ['ad-min'];
        yield 'embedded offensive' => ['Darkfucker'];
        yield 'discrimination' => ['nazi'];
    }

    #[DataProvider('allowedNamesProvider')]
    public function testAllowedNamesAreAccepted(string $name): void
    {
        $this->assertFalse($this->checker->isForbidden($name), "Name '$name' should be allowed");
    }

    /** @return iterable<string, array{string}> */
    public static function allowedNamesProvider(): iterable
    {
        yield 'normal name' => ['Aldric'];
        yield 'fantasy name' => ['Elara'];
        yield 'with hyphen' => ['Jean-Pierre'];
        yield 'with space' => ['Dark Knight'];
        yield 'accented' => ['Thérèse'];
        yield 'short name' => ['Zyx'];
        yield 'japanese-style' => ['Akira'];
        yield 'dwarven name' => ['Thorin'];
    }
}
