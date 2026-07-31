<?php

declare(strict_types=1);

namespace App\GameEngine\Wiki;

/**
 * Le wiki joueur, lu depuis `docs/wiki/` (WIK-02).
 *
 * Les 25 pages livrees par WIK-01 vivent en markdown, versionnees avec le code.
 * C'est deliberе : une page de regles qui derive du jeu est pire qu'une page
 * absente, et la garder dans le depot la met sous revue en meme temps que ce
 * qu'elle decrit.
 *
 * **Le sommaire n'est pas ecrit, il est constate.** La structure vient des
 * dossiers (`01-commencer/`, `02-devenir/`…) et les titres des fichiers
 * eux-memes. Un sommaire recopie a la main serait faux le jour ou une page est
 * ajoutee sans lui — et personne ne s'en apercevrait, puisque rien ne casse.
 *
 * **Aucun chemin ne vient de la requete.** Une page est designee par un couple
 * (section, page) qui doit exister dans l'index construit au chargement : il
 * n'y a donc rien a assainir, parce qu'il n'y a rien a interpreter. C'est la
 * meme garantie que la liste blanche de `RoadmapController`, obtenue en lisant
 * le disque au lieu de l'enumerer.
 */
final class WikiLibrary
{
    /** Racine des pages, relative au projet. */
    public const ROOT = 'docs/wiki';

    /** Page d'accueil du wiki. */
    public const HOME = 'README.md';

    /** @var array<string, WikiSection>|null */
    private ?array $sections = null;

    public function __construct(private readonly string $projectDir)
    {
    }

    /**
     * Les sections, dans l'ordre de leurs prefixes numeriques.
     *
     * @return array<string, WikiSection>
     */
    public function sections(): array
    {
        if (null !== $this->sections) {
            return $this->sections;
        }

        $sections = [];
        foreach ($this->directories() as $directory) {
            $slug = basename($directory);
            $pages = [];

            foreach ($this->markdownFiles($directory) as $file) {
                $page = basename($file, '.md');
                $pages[$page] = new WikiPage($slug, $page, $this->titleOf($file));
            }

            if ([] !== $pages) {
                $sections[$slug] = new WikiSection($slug, $this->sectionTitle($slug), $pages);
            }
        }

        return $this->sections = $sections;
    }

    /**
     * Le markdown d'une page, ou `null` si le couple n'existe pas.
     *
     * Le `null` est la reponse a une adresse inventee : le controleur en fait
     * un 404, et aucun chemin issu de la requete n'a touche le disque.
     */
    public function page(string $section, string $page): ?string
    {
        $known = $this->sections()[$section]->pages[$page] ?? null;
        if (null === $known) {
            return null;
        }

        return $this->read($this->root() . '/' . $section . '/' . $page . '.md');
    }

    /**
     * Le sommaire (`README.md`), qui sert de page d'accueil.
     */
    public function home(): string
    {
        return $this->read($this->root() . '/' . self::HOME) ?? '';
    }

    /**
     * Reecrit les liens entre pages en routes du wiki.
     *
     * Les pages se citent en chemins relatifs (`../01-commencer/energie.md`),
     * ce qui les rend lisibles telles quelles dans un depot. A l'ecran, ces
     * liens meneraient vers un fichier qui n'est pas servi : la reecriture est
     * donc la condition pour que le wiki soit navigable, pas une commodite.
     *
     * Un lien qui ne correspond a aucune page connue est **laisse tel quel** —
     * il sera visiblement casse, et `WikiLibraryTest` le fait rougir en CI
     * avant qu'un joueur ne le rencontre.
     */
    public function rewriteLinks(string $markdown, ?string $fromSection = null): string
    {
        return (string) preg_replace_callback(
            '/\]\(([^)]+\.md)(#[^)]*)?\)/',
            function (array $matches) use ($fromSection): string {
                $target = $this->resolve((string) $matches[1], $fromSection);
                $anchor = $matches[2] ?? '';

                return null === $target ? $matches[0] : '](' . $target . $anchor . ')';
            },
            $markdown,
        );
    }

    /**
     * Route d'un lien relatif, ou `null` s'il ne designe aucune page connue.
     */
    public function resolve(string $link, ?string $fromSection = null): ?string
    {
        $parts = array_values(array_filter(
            explode('/', str_replace('\\', '/', $link)),
            static fn (string $part): bool => '' !== $part && '.' !== $part && '..' !== $part,
        ));

        if ([] === $parts) {
            return null;
        }

        $file = array_pop($parts);
        if (self::HOME === $file) {
            return '/wiki';
        }

        // Une page qui en cite une autre du meme chapitre ecrit son nom nu :
        // c'est ainsi qu'on lit un depot, et c'est la moitie des liens du wiki.
        $section = array_pop($parts) ?? $fromSection;
        $page = basename($file, '.md');

        if (null === $section || !isset($this->sections()[$section]->pages[$page])) {
            return null;
        }

        return '/wiki/' . $section . '/' . $page;
    }

    /**
     * Tous les liens markdown internes du wiki, avec leur page d'origine.
     *
     * Sert au garde-fou : une page citee qui n'existe pas est un mensonge de
     * plus dans un document dont le seul interet est de ne pas mentir.
     *
     * @return list<array{from: string, section: ?string, link: string}>
     */
    public function internalLinks(): array
    {
        $links = [];

        foreach ($this->allFiles() as $relative => $absolute) {
            $section = str_contains($relative, '/') ? explode('/', $relative)[0] : null;
            preg_match_all('/\]\(([^)]+\.md)(?:#[^)]*)?\)/', (string) $this->read($absolute), $matches);
            foreach ($matches[1] as $link) {
                $links[] = ['from' => $relative, 'section' => $section, 'link' => (string) $link];
            }
        }

        return $links;
    }

    /**
     * Toutes les pages du wiki : chemin relatif => chemin absolu.
     *
     * @return array<string, string>
     */
    public function allFiles(): array
    {
        $files = [self::HOME => $this->root() . '/' . self::HOME];

        foreach ($this->directories() as $directory) {
            foreach ($this->markdownFiles($directory) as $file) {
                $files[basename($directory) . '/' . basename($file)] = $file;
            }
        }

        return $files;
    }

    private function root(): string
    {
        return $this->projectDir . '/' . self::ROOT;
    }

    /**
     * @return list<string>
     */
    private function directories(): array
    {
        $directories = array_filter((array) glob($this->root() . '/*'), 'is_dir');
        // `sort` reindexe : le tableau est deja une liste au retour.
        sort($directories);

        return $directories;
    }

    /**
     * @return list<string>
     */
    private function markdownFiles(string $directory): array
    {
        $files = (array) glob($directory . '/*.md');
        sort($files);

        return $files;
    }

    /**
     * Le titre d'une page : son premier `#`, ou son nom de fichier a defaut.
     *
     * Prendre le titre dans le document plutot que dans un tableau de libelles
     * evite le seul defaut qui compte ici — un sommaire qui annonce autre chose
     * que ce que la page dit.
     */
    private function titleOf(string $file): string
    {
        foreach (explode("\n", (string) $this->read($file)) as $line) {
            if (str_starts_with($line, '# ')) {
                return trim(substr($line, 2));
            }
        }

        return $this->humanize(basename($file, '.md'));
    }

    /**
     * Le libelle d'un chapitre, lu dans le sommaire.
     *
     * `README.md` porte deja « ## 7. Les regles d'or » — avec ses accents et
     * son apostrophe. Le deriver du nom de dossier rendrait « Regles dor », et
     * un wiki qui ecorche ses propres titres de chapitre perd la seule chose
     * qu'il vend : le soin. Le rapprochement se fait sur le numero de prefixe,
     * seul lien stable entre le dossier et sa ligne de sommaire.
     */
    private function sectionTitle(string $slug): string
    {
        $number = (int) $slug;
        if ($number > 0) {
            preg_match('/^## ' . $number . '\\. (.+)$/m', $this->home(), $matches);
            if (isset($matches[1])) {
                return trim((string) $matches[1]);
            }
        }

        return $this->humanize($slug);
    }

    /**
     * `03-le-monde` => `Le monde`.
     */
    private function humanize(string $slug): string
    {
        $words = (string) preg_replace('/^\d+-/', '', $slug);

        return ucfirst(str_replace('-', ' ', $words));
    }

    private function read(string $file): ?string
    {
        if (!is_file($file)) {
            return null;
        }

        $content = file_get_contents($file);

        return false === $content ? null : $content;
    }
}
