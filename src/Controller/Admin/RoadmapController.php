<?php

namespace App\Controller\Admin;

use App\Service\MarkdownParser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/roadmap', name: 'admin_roadmap_')]
#[IsGranted('ROLE_GAME_DESIGNER')]
class RoadmapController extends AbstractController
{
    /** @var array<string, string> cle part => nom de fichier */
    private const ROADMAP_TODO_PART_FILES = [
        'index' => 'ROADMAP_TODO_INDEX.md',
        'sprint_1' => 'SPRINT_01.md',
        'sprint_2' => 'SPRINT_02.md',
        'sprint_3' => 'SPRINT_03.md',
        'sprint_4' => 'SPRINT_04.md',
        'sprint_5' => 'SPRINT_05.md',
        'sprint_6' => 'SPRINT_06.md',
        'sprint_7' => 'SPRINT_07.md',
        'sprint_8' => 'SPRINT_08.md',
        'sprint_9' => 'SPRINT_09.md',
        'sprint_10' => 'SPRINT_10.md',
        'sprint_11' => 'SPRINT_11.md',
        'sprint_12' => 'SPRINT_12.md',
    ];

    private const TODO_PART_LABELS = [
        'all' => 'Tout',
        'index' => 'Index',
        'sprint_1' => 'Sprint 1',
        'sprint_2' => 'Sprint 2',
        'sprint_3' => 'Sprint 3',
        'sprint_4' => 'Sprint 4',
        'sprint_5' => 'Sprint 5',
        'sprint_6' => 'Sprint 6',
        'sprint_7' => 'Sprint 7',
        'sprint_8' => 'Sprint 8',
        'sprint_9' => 'Sprint 9',
        'sprint_10' => 'Sprint 10',
        'sprint_11' => 'Sprint 11',
        'sprint_12' => 'Sprint 12',
    ];

    /** @var list<string> */
    private const ALLOWED_TODO_PARTS = ['all', 'index', 'sprint_1', 'sprint_2', 'sprint_3', 'sprint_4', 'sprint_5', 'sprint_6', 'sprint_7', 'sprint_8', 'sprint_9', 'sprint_10', 'sprint_11', 'sprint_12'];

    public function __construct(
        private readonly MarkdownParser $markdownParser,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $tab = $request->query->getString('tab', 'todo');
        if (!\in_array($tab, ['todo', 'done'], true)) {
            $tab = 'todo';
        }

        $todoPart = $request->query->getString('part', 'all');
        if (!\in_array($todoPart, self::ALLOWED_TODO_PARTS, true)) {
            $todoPart = 'all';
        }

        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $doneFile = $projectDir . '/docs/ROADMAP_DONE.md';

        $doneContent = file_exists($doneFile) ? (string) file_get_contents($doneFile) : '';
        $bundle = $this->loadRoadmapTodoBundle($projectDir);

        if ($todoPart === 'all') {
            $todoContent = $bundle['aggregated'];
            $todoFileMtime = $bundle['maxMtime'];
        } else {
            $todoContent = $bundle['byPart'][$todoPart] ?? '';
            $todoFileMtime = $this->roadmapTodoSinglePartMtime($projectDir, $todoPart);
        }

        $doneHtml = $this->markdownParser->toHtml($doneContent);
        $todoHtml = $this->markdownParser->toHtml($todoContent);
        $todoStats = $this->markdownParser->parseStats($todoContent);

        $doneSections = (int) preg_match_all('/^## .+✅/m', $doneContent);

        $doneFileMtime = file_exists($doneFile) ? filemtime($doneFile) : false;

        $todoPartSummaries = $this->buildTodoPartSummaries($bundle);

        return $this->render('admin/roadmap/index.html.twig', [
            'tab' => $tab,
            'todoPart' => $todoPart,
            'todoPartSummaries' => $todoPartSummaries,
            'doneHtml' => $doneHtml,
            'todoHtml' => $todoHtml,
            'todoStats' => $todoStats,
            'doneSections' => $doneSections,
            'doneFileDate' => $doneFileMtime !== false ? $doneFileMtime : null,
            'todoFileDate' => $todoFileMtime !== false ? $todoFileMtime : null,
        ]);
    }

    #[Route('/done', name: 'done')]
    public function done(): Response
    {
        return $this->redirectToRoute('admin_roadmap_index', ['tab' => 'done']);
    }

    #[Route('/todo', name: 'todo')]
    public function todo(): Response
    {
        return $this->redirectToRoute('admin_roadmap_index', ['tab' => 'todo']);
    }

    /**
     * @return array{byPart: array<string, string>, aggregated: string, maxMtime: int|false}
     */
    private function loadRoadmapTodoBundle(string $projectDir): array
    {
        $roadmapDir = $projectDir . '/docs/roadmap';
        /** @var array<string, string> $byPart */
        $byPart = [];
        $maxMtime = false;

        foreach (self::ROADMAP_TODO_PART_FILES as $key => $filename) {
            $path = $roadmapDir . '/' . $filename;
            if (is_readable($path)) {
                $byPart[$key] = (string) file_get_contents($path);
                $mt = filemtime($path);
                if ($mt !== false && ($maxMtime === false || $mt > $maxMtime)) {
                    $maxMtime = $mt;
                }
            } else {
                $byPart[$key] = '';
            }
        }

        $ordered = [];
        foreach (array_keys(self::ROADMAP_TODO_PART_FILES) as $key) {
            if (($byPart[$key] ?? '') !== '') {
                $ordered[] = $byPart[$key];
            }
        }

        $aggregated = $ordered !== [] ? implode("\n\n---\n\n", $ordered) : '';

        if ($aggregated === '') {
            $fallback = $projectDir . '/docs/ROADMAP_TODO.md';
            if (is_readable($fallback)) {
                $aggregated = (string) file_get_contents($fallback);
                $mt = filemtime($fallback);
                $maxMtime = $mt !== false ? $mt : false;
            }
        }

        return [
            'byPart' => $byPart,
            'aggregated' => $aggregated,
            'maxMtime' => $maxMtime,
        ];
    }

    private function roadmapTodoSinglePartMtime(string $projectDir, string $part): int|false
    {
        $filename = self::ROADMAP_TODO_PART_FILES[$part] ?? null;
        if ($filename === null) {
            return false;
        }
        $path = $projectDir . '/docs/roadmap/' . $filename;

        return is_readable($path) ? filemtime($path) : false;
    }

    /**
     * @param array{byPart: array<string, string>, aggregated: string, maxMtime: int|false} $bundle
     *
     * @return list<array{key: string, label: string, total: int, done: int, remaining: int}>
     */
    private function buildTodoPartSummaries(array $bundle): array
    {
        $out = [];
        $aggStats = $this->markdownParser->parseStats($bundle['aggregated']);
        $out[] = [
            'key' => 'all',
            'label' => self::TODO_PART_LABELS['all'],
            'total' => $aggStats['total'],
            'done' => $aggStats['done'],
            'remaining' => $aggStats['total'] - $aggStats['done'],
        ];

        foreach (array_keys(self::ROADMAP_TODO_PART_FILES) as $key) {
            $content = $bundle['byPart'][$key] ?? '';
            $st = $this->markdownParser->parseStats($content);
            $out[] = [
                'key' => $key,
                'label' => self::TODO_PART_LABELS[$key],
                'total' => $st['total'],
                'done' => $st['done'],
                'remaining' => $st['total'] - $st['done'],
            ];
        }

        return $out;
    }
}
