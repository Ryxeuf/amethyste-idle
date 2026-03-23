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

        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $doneFile = $projectDir . '/docs/ROADMAP_DONE.md';

        $doneContent = file_exists($doneFile) ? (string) file_get_contents($doneFile) : '';
        [$todoContent, $todoFileMtime] = $this->loadAggregatedRoadmapTodo($projectDir);

        $doneHtml = $this->markdownParser->toHtml($doneContent);
        $todoHtml = $this->markdownParser->toHtml($todoContent);
        $todoStats = $this->markdownParser->parseStats($todoContent);

        $doneSections = (int) preg_match_all('/^## .+✅/m', $doneContent);

        $doneFileMtime = file_exists($doneFile) ? filemtime($doneFile) : false;

        return $this->render('admin/roadmap/index.html.twig', [
            'tab' => $tab,
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
     * @return array{0: string, 1: int|false} [markdown, max mtime of parts]
     */
    private function loadAggregatedRoadmapTodo(string $projectDir): array
    {
        $roadmapDir = $projectDir . '/docs/roadmap';
        $parts = [
            $roadmapDir . '/ROADMAP_TODO_INDEX.md',
            $roadmapDir . '/ROADMAP_TODO_VAGUE_01.md',
            $roadmapDir . '/ROADMAP_TODO_VAGUE_02.md',
            $roadmapDir . '/ROADMAP_TODO_VAGUE_03.md',
            $roadmapDir . '/ROADMAP_TODO_VAGUE_04.md',
            $roadmapDir . '/ROADMAP_TODO_VAGUE_05.md',
            $roadmapDir . '/ROADMAP_TODO_VAGUE_06.md',
        ];

        $chunks = [];
        $maxMtime = false;
        foreach ($parts as $path) {
            if (!is_readable($path)) {
                continue;
            }
            $chunks[] = (string) file_get_contents($path);
            $mt = filemtime($path);
            if ($mt !== false && ($maxMtime === false || $mt > $maxMtime)) {
                $maxMtime = $mt;
            }
        }

        if ($chunks !== []) {
            return [implode("\n\n---\n\n", $chunks), $maxMtime];
        }

        $fallback = $projectDir . '/docs/ROADMAP_TODO.md';

        return [
            file_exists($fallback) ? (string) file_get_contents($fallback) : '',
            file_exists($fallback) ? filemtime($fallback) : false,
        ];
    }
}
