<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Avatar;

use App\Service\Avatar\AvatarCatalogProvider;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;

class AvatarCatalogProviderTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/avatar_catalog_test_' . uniqid();
        mkdir($this->tmpDir . '/assets/styles/images/avatar/body', 0777, true);
        mkdir($this->tmpDir . '/assets/styles/images/avatar/hair', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testGetCatalogReturnsAllCategories(): void
    {
        $provider = $this->createProvider([]);

        $catalog = $provider->getCatalog();

        $this->assertArrayHasKey('body', $catalog);
        $this->assertArrayHasKey('hair', $catalog);
        $this->assertArrayHasKey('beard', $catalog);
        $this->assertArrayHasKey('facemark', $catalog);
        $this->assertArrayHasKey('gear', $catalog);
    }

    public function testGetCatalogScansBodyDirectory(): void
    {
        touch($this->tmpDir . '/assets/styles/images/avatar/body/human_m_light.png');
        touch($this->tmpDir . '/assets/styles/images/avatar/body/human_f_dark.png');

        $provider = $this->createProvider([]);

        $catalog = $provider->getCatalog();

        $this->assertCount(2, $catalog['body']);
        $slugs = array_column($catalog['body'], 'slug');
        $this->assertContains('human_f_dark', $slugs);
        $this->assertContains('human_m_light', $slugs);
    }

    public function testGetCatalogHandlesMissingDirectory(): void
    {
        $provider = $this->createProvider([]);

        $catalog = $provider->getCatalog();

        $this->assertSame([], $catalog['beard']);
        $this->assertSame([], $catalog['facemark']);
    }

    public function testGetCatalogIncludesGearSheets(): void
    {
        $gearSheets = [
            ['avatar_sheet' => '/assets/styles/images/avatar/gear/chest/iron_armor.png'],
        ];

        $provider = $this->createProvider($gearSheets);

        $catalog = $provider->getCatalog();

        $this->assertCount(1, $catalog['gear']);
        $this->assertSame('/assets/styles/images/avatar/gear/chest/iron_armor.png', $catalog['gear'][0]);
    }

    public function testGetAllSheetUrlsFlattensAllCategories(): void
    {
        touch($this->tmpDir . '/assets/styles/images/avatar/body/human_m_light.png');
        touch($this->tmpDir . '/assets/styles/images/avatar/hair/short_01.png');

        $gearSheets = [
            ['avatar_sheet' => '/gear/leather.png'],
        ];

        $provider = $this->createProvider($gearSheets);

        $urls = $provider->getAllSheetUrls();

        $this->assertCount(3, $urls);
    }

    /**
     * @param list<array{avatar_sheet: string}> $gearRows
     */
    private function createProvider(array $gearRows): AvatarCatalogProvider
    {
        $packages = $this->createMock(Packages::class);
        $packages->method('getUrl')->willReturnCallback(
            static fn (string $path): string => '/' . $path,
        );

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAllAssociative')->willReturn($gearRows);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);

        return new AvatarCatalogProvider($packages, $em, $this->tmpDir);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
        }

        rmdir($dir);
    }
}
