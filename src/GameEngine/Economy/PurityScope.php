<?php

namespace App\GameEngine\Economy;

use App\Entity\Game\Item;

/**
 * Ce qui porte une bande de purete, et ce qui reste fongible (ECO-21).
 *
 * Le perimetre est le garde-fou central du jalon. Star Wars Galaxies a donne des
 * statistiques a *toutes* ses ressources et a transforme son artisanat en
 * tableur ; ici la ligne du cristal seule en porte, et **ce service est le seul
 * endroit qui en decide**. Semer la question dans les appelants la ferait
 * repondre differemment selon l'ecran, et un lot finirait par avoir une bande
 * ici et pas la.
 *
 * Le refus est un `null`, jamais une exception : demander la bande d'une botte
 * d'herbes est une question legitime dont la reponse est « elle n'en a pas ».
 */
class PurityScope
{
    /**
     * @var array{slug_prefixes: list<string>, excluded_slugs: list<string>, included_slugs: list<string>}|null
     */
    private ?array $scope = null;

    public function __construct(
        private readonly PurityDefinitionLoader $loader,
    ) {
    }

    public function coversItem(?Item $item): bool
    {
        return $item !== null && $this->coversSlug($item->getSlug());
    }

    public function coversSlug(string $slug): bool
    {
        $scope = $this->scope();

        if (\in_array($slug, $scope['excluded_slugs'], true)) {
            return false;
        }

        if (\in_array($slug, $scope['included_slugs'], true)) {
            return true;
        }

        foreach ($scope['slug_prefixes'] as $prefix) {
            if (str_starts_with($slug, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{slug_prefixes: list<string>, excluded_slugs: list<string>, included_slugs: list<string>}
     */
    private function scope(): array
    {
        if ($this->scope === null) {
            $this->scope = $this->loader->load()['scope'];
        }

        return $this->scope;
    }
}
