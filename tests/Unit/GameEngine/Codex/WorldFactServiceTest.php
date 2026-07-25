<?php

declare(strict_types=1);

namespace App\Tests\Unit\GameEngine\Codex;

use App\Entity\Game\CodexEntry;
use App\GameEngine\Codex\WorldFactService;
use App\Repository\CodexEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class WorldFactServiceTest extends TestCase
{
    public function testRecordCreatesPublicWorldFactWhenSlugUnknown(): void
    {
        $codexRepo = $this->createMock(CodexEntryRepository::class);
        $codexRepo->method('findBySlug')->with('premiere-saison')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($this->isInstanceOf(CodexEntry::class));
        $em->expects($this->once())->method('flush');

        $service = new WorldFactService($codexRepo, $em);

        $entry = $service->recordWorldFact(
            'premiere-saison',
            'La Première Saison',
            'Le monde a vu sa première saison se refermer.',
            'Les Gardiens',
            ['en' => 'The First Season'],
            ['en' => 'The world saw its first season come to a close.'],
        );

        self::assertSame('premiere-saison', $entry->getSlug());
        self::assertSame(CodexEntry::CATEGORY_WORLD_FACT, $entry->getCategory());
        self::assertSame(CodexEntry::UNLOCK_MANUAL, $entry->getUnlockType());
        self::assertNull($entry->getUnlockKey());
        self::assertSame('Les Gardiens', $entry->getCreditedGuildName());
        self::assertTrue($entry->isPublic());
        self::assertSame('The First Season', $entry->getLocalizedTitle('en'));
    }

    public function testRecordUpdatesExistingEntryBySlugWithoutDuplicating(): void
    {
        $existing = (new CodexEntry())
            ->setSlug('premiere-saison')
            ->setTitle('Ancien titre');

        $codexRepo = $this->createMock(CodexEntryRepository::class);
        $codexRepo->method('findBySlug')->with('premiere-saison')->willReturn($existing);

        $em = $this->createMock(EntityManagerInterface::class);
        // Persist la MEME instance : pas de doublon.
        $em->expects($this->once())->method('persist')->with($existing);
        $em->expects($this->once())->method('flush');

        $service = new WorldFactService($codexRepo, $em);

        $entry = $service->recordWorldFact('premiere-saison', 'Nouveau titre', 'Nouvelle description');

        self::assertSame($existing, $entry);
        self::assertSame('Nouveau titre', $entry->getTitle());
        self::assertNull($entry->getCreditedGuildName());
    }
}
