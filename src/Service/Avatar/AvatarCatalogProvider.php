<?php

declare(strict_types=1);

namespace App\Service\Avatar;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class AvatarCatalogProvider
{
    private const AVATAR_DIR = 'styles/images/avatar';
    private const CATEGORIES = ['body', 'hair', 'beard', 'facemark'];

    public function __construct(
        private readonly Packages $packages,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
    }

    /**
     * @return array{body: list<array{slug: string, sheet: string}>, hair: list<array{slug: string, sheet: string}>, beard: list<array{slug: string, sheet: string}>, facemark: list<array{slug: string, sheet: string}>, gear: list<string>}
     */
    public function getCatalog(): array
    {
        $catalog = [];

        foreach (self::CATEGORIES as $category) {
            $catalog[$category] = $this->scanCategory($category);
        }

        $catalog['gear'] = $this->getGearSheets();

        return $catalog;
    }

    /**
     * @return list<string> All unique sheet URLs (body + hair + beard + facemark + gear) for preloading
     */
    public function getAllSheetUrls(): array
    {
        $urls = [];

        foreach (self::CATEGORIES as $category) {
            foreach ($this->scanCategory($category) as $entry) {
                $urls[] = $entry['sheet'];
            }
        }

        foreach ($this->getGearSheets() as $gearSheet) {
            $urls[] = $gearSheet;
        }

        return $urls;
    }

    /**
     * @return list<array{slug: string, sheet: string}>
     */
    private function scanCategory(string $category): array
    {
        $dir = $this->projectDir . '/assets/' . self::AVATAR_DIR . '/' . $category;
        $sheets = [];

        if (!is_dir($dir)) {
            return $sheets;
        }

        $files = glob($dir . '/*.png');
        if ($files === false) {
            return $sheets;
        }

        sort($files);

        foreach ($files as $file) {
            $filename = basename($file);
            $slug = basename($file, '.png');
            $sheets[] = [
                'slug' => $slug,
                'sheet' => $this->packages->getUrl(self::AVATAR_DIR . '/' . $category . '/' . $filename),
            ];
        }

        return $sheets;
    }

    /**
     * @return list<string>
     */
    private function getGearSheets(): array
    {
        $rows = $this->entityManager->getConnection()->fetchAllAssociative(
            'SELECT DISTINCT avatar_sheet FROM game_items WHERE avatar_sheet IS NOT NULL ORDER BY avatar_sheet',
        );

        return array_map(
            static fn (array $row): string => (string) $row['avatar_sheet'],
            $rows,
        );
    }
}
