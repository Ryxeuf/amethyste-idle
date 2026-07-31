<?php

declare(strict_types=1);

namespace App\GameEngine\Wiki;

/**
 * Un chapitre du wiki joueur (WIK-02).
 *
 * Il n'est pas declare : il **est** un dossier de `docs/wiki/`. Le prefixe
 * numerique du nom donne l'ordre, et disparait du libelle — ajouter une page
 * n'oblige donc a toucher aucune liste.
 */
final readonly class WikiSection
{
    /**
     * @param array<string, WikiPage> $pages indexees par slug, dans l'ordre du dossier
     */
    public function __construct(
        public string $slug,
        public string $title,
        public array $pages,
    ) {
    }
}
