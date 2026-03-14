<?php

namespace App\DataStorage;

use App\Dto\Map\MapModelLight;
use App\Entity\App\Map;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class MapStorage
{
    private const MAP_INFOS_PATTERN = 'map_%s_%s';
    private const MAP_TAG_PATTERN = 'tag_%s_%s';

    private string $dataPath;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        $this->dataPath = $this->projectDir . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'map' . DIRECTORY_SEPARATOR;
    }

    public function storeMapInfos(Map $map, string $content): string
    {
        $model = new MapModelLight($map);

        return $this->storeMap(self::MAP_INFOS_PATTERN, [$model->id, $model->versionHash], $content);
    }

    public function storeMapTag(Map $map, string $content): string
    {
        $model = new MapModelLight($map);

        return $this->storeMap(self::MAP_TAG_PATTERN, [$model->id, $model->versionHash], $content);
    }

    private function storeMap(string $pattern, array $params, string $content): string
    {
        $fs = new Filesystem();
        $mapFileName = sprintf($pattern, ...$params);
        $filePath = $this->dataPath . $mapFileName;
        $fs->dumpFile($filePath, $content);

        return $filePath;
    }

    public function getMap(int $id): ?array
    {
        if (null !== $content = $this->retrieveMap($id, self::MAP_INFOS_PATTERN)) {
            return json_decode($content, true);
        }

        return null;
    }

    public function getMapTag(int $id): ?array
    {
        if (null !== $content = $this->retrieveMap($id, self::MAP_TAG_PATTERN)) {
            return json_decode($content, true);
        }

        return null;
    }

    protected function retrieveMap(int $mapId, string $pattern): ?string
    {
        $finder = new Finder();
        $finder->files()->in($this->dataPath);
        $matchingPattern = '/' . sprintf($pattern, $mapId, '\d{14}') . '$/';

        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                preg_match_all($matchingPattern, $file->getRelativePathname(), $pregMatch);
                if (!empty($pregMatch[0])) {
                    return $file->getContents();
                }
            }
        }

        return null;
    }
}
