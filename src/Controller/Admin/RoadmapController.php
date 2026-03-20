<?php

namespace App\Controller\Admin;

use App\Service\MarkdownParser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/roadmap', name: 'admin_roadmap_')]
#[IsGranted('ROLE_ADMIN')]
final class RoadmapController extends AbstractController
{
    private readonly string $projectDir;

    public function __construct(
        private readonly MarkdownParser $markdownParser,
        KernelInterface $kernel,
    ) {
        $this->projectDir = $kernel->getProjectDir();
    }

    #[Route('', name: 'index', defaults: ['tab' => 'todo'])]
    #[Route('/done', name: 'done', defaults: ['tab' => 'done'])]
    #[Route('/todo', name: 'todo', defaults: ['tab' => 'todo'])]
    public function index(string $tab): Response
    {
        $doneFile = $this->projectDir . '/docs/ROADMAP_DONE.md';
        $todoFile = $this->projectDir . '/docs/ROADMAP_TODO.md';

        $doneContent = file_exists($doneFile) ? (string) file_get_contents($doneFile) : '';
        $todoContent = file_exists($todoFile) ? (string) file_get_contents($todoFile) : '';

        $doneHtml = $this->markdownParser->toHtml($doneContent);
        $todoHtml = $this->markdownParser->toHtml($todoContent);
        $todoStats = $this->markdownParser->parseStats($todoContent);

        // Count completed sections in done file (H2 with ✅)
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
}
