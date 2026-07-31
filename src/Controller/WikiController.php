<?php

namespace App\Controller;

use App\GameEngine\Wiki\WikiLibrary;
use App\Service\MarkdownParser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Le wiki joueur, public (WIK-02).
 *
 * **Public, et c'est le point.** Les regles d'un jeu se lisent avant d'y jouer :
 * un wiki derriere l'authentification ne sert qu'a ceux qui n'en ont deja plus
 * besoin. Le contenu est celui de `docs/wiki/`, versionne avec le code — une
 * page qui derive du jeu est pire qu'une page absente.
 *
 * **Aucun chemin ne vient de la requete.** Les deux segments d'URL servent de
 * clefs dans un index construit en lisant le disque : une adresse inventee ne
 * trouve rien et rend un 404, sans qu'aucune chaine de la requete ait touche le
 * systeme de fichiers. Meme garantie que la liste blanche du controleur de
 * roadmap, obtenue en constatant plutot qu'en enumerant.
 */
#[Route('/wiki', name: 'app_wiki_')]
class WikiController extends AbstractController
{
    public function __construct(
        private readonly WikiLibrary $library,
        private readonly MarkdownParser $markdownParser,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(): Response
    {
        return $this->render('wiki/index.html.twig', [
            'sections' => $this->library->sections(),
            'current' => null,
            'title' => null,
            'content' => $this->markdownParser->toHtml(
                $this->library->rewriteLinks($this->library->home()),
            ),
        ]);
    }

    #[Route('/{section}/{page}', name: 'page', requirements: ['section' => '[a-z0-9-]+', 'page' => '[a-z0-9-]+'])]
    public function page(string $section, string $page): Response
    {
        $markdown = $this->library->page($section, $page);
        if (null === $markdown) {
            throw $this->createNotFoundException('Cette page du wiki n\'existe pas.');
        }

        return $this->render('wiki/index.html.twig', [
            'sections' => $this->library->sections(),
            'current' => $this->library->sections()[$section]->pages[$page],
            'title' => $this->library->sections()[$section]->pages[$page]->title,
            'content' => $this->markdownParser->toHtml($this->library->rewriteLinks($markdown, $section)),
        ]);
    }
}
