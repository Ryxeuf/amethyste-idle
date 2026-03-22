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
        $todoFile = $projectDir . '/docs/ROADMAP_TODO.md';

        $doneContent = file_exists($doneFile) ? (string) file_get_contents($doneFile) : '';
        $todoContent = file_exists($todoFile) ? (string) file_get_contents($todoFile) : '';

        $doneHtml = $this->markdownParser->toHtml($doneContent);
        $todoHtml = $this->markdownParser->toHtml($todoContent);
        $todoStats = $this->markdownParser->parseStats($todoContent);

        $doneSections = (int) preg_match_all('/^## .+✅/m', $doneContent);

        $doneFileMtime = file_exists($doneFile) ? filemtime($doneFile) : false;
        $todoFileMtime = file_exists($todoFile) ? filemtime($todoFile) : false;

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
}
