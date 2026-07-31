<?php

declare(strict_types=1);

namespace App\GameEngine\Wiki;

/**
 * Une page du wiki joueur (WIK-02).
 *
 * Le titre vient du premier `#` du document, jamais d'un tableau de libelles :
 * un sommaire qui annonce autre chose que ce que la page dit est le seul defaut
 * qui compte vraiment ici.
 */
final readonly class WikiPage
{
    public function __construct(
        public string $section,
        public string $slug,
        public string $title,
    ) {
    }

    public function route(): string
    {
        return '/wiki/' . $this->section . '/' . $this->slug;
    }
}
