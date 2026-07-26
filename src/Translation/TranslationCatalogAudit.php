<?php

declare(strict_types=1);

namespace App\Translation;

/**
 * Audit des catalogues de traduction (tache 135, Sprint 12).
 *
 * Deux questions, deux methodes :
 *
 * - `parityGaps()` — une cle definie dans un seul catalogue. Le joueur qui a
 *   choisi l'autre langue voit la cle brute a l'ecran.
 * - `missingKeys()` — une cle appelee par un template ou un controller, definie
 *   nulle part. Symfony renvoie alors la cle elle-meme, sans erreur : la page
 *   s'affiche, avec `game.inventory.paper_doll_label` en guise de texte.
 *
 * Aucun des deux ne casse quoi que ce soit a l'execution. C'est precisement ce
 * qui les rend durables : sans garde-fou, ils s'accumulent en silence.
 *
 * La logique vit ici — et non dans `scripts/audit-translations.php` — pour que
 * le script en ligne de commande **et** le test de non-regression partagent la
 * meme implementation.
 */
final class TranslationCatalogAudit
{
    private const SCANNED_DIRS = ['templates', 'src'];

    /** Cles citees dans un template Twig : `'game.foo.bar'|trans`. */
    private const TWIG_PATTERN = '/[\'"]([a-z][a-z0-9_]*(?:\.[a-z0-9_]+)+)[\'"]\s*\|\s*trans\b/i';

    /** Cles citees depuis PHP : `->trans('game.foo.bar')`. */
    private const PHP_PATTERN = '/->\s*trans\s*\(\s*[\'"]([a-z][a-z0-9_]*(?:\.[a-z0-9_]+)+)[\'"]/i';

    public function __construct(private readonly string $projectDir)
    {
    }

    /**
     * Cles d'un catalogue, aplaties en notation pointee.
     *
     * @return list<string>
     */
    public function keys(string $locale): array
    {
        return $this->flatten($this->catalog($locale));
    }

    /**
     * Cles definies dans une seule langue.
     *
     * @return array{fr_only: list<string>, en_only: list<string>}
     */
    public function parityGaps(): array
    {
        $fr = array_flip($this->keys('fr'));
        $en = array_flip($this->keys('en'));

        return [
            'fr_only' => array_keys(array_diff_key($fr, $en)),
            'en_only' => array_keys(array_diff_key($en, $fr)),
        ];
    }

    /**
     * Cles appelees par le code mais definies dans aucun catalogue.
     *
     * @param bool $activeOnly ignore `templates/old_game/` (heritage pre-pivot)
     *
     * @return array<string, list<string>> cle => fichiers appelants
     */
    public function missingKeys(bool $activeOnly = true): array
    {
        $defined = array_flip($this->keys('fr')) + array_flip($this->keys('en'));

        $missing = [];
        foreach ($this->usedKeys($activeOnly) as $key => $files) {
            if (!isset($defined[$key])) {
                $missing[$key] = $files;
            }
        }
        ksort($missing);

        return $missing;
    }

    /**
     * Cles citees par le code, indexees par fichier appelant.
     *
     * @return array<string, list<string>> cle => chemins relatifs
     */
    public function usedKeys(bool $activeOnly = true): array
    {
        $used = [];
        foreach (self::SCANNED_DIRS as $dir) {
            $root = $this->projectDir . '/' . $dir;
            if (!is_dir($root)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \RecursiveDirectoryIterator::SKIP_DOTS),
            );
            foreach ($iterator as $file) {
                /* @var \SplFileInfo $file */
                if (!$file->isFile()) {
                    continue;
                }
                $extension = $file->getExtension();
                if (!\in_array($extension, ['twig', 'php'], true)) {
                    continue;
                }
                $path = $file->getPathname();
                if ($activeOnly && str_contains($path, '/templates/old_game/')) {
                    continue;
                }
                $content = file_get_contents($path);
                if (false === $content) {
                    continue;
                }
                $content = $this->stripComments($content, $extension);
                $pattern = 'twig' === $extension ? self::TWIG_PATTERN : self::PHP_PATTERN;
                if (!preg_match_all($pattern, $content, $matches)) {
                    continue;
                }
                $relative = substr($path, \strlen($this->projectDir) + 1);
                foreach ($matches[1] as $key) {
                    $used[$key][$relative] = true;
                }
            }
        }

        return array_map(static fn (array $files): array => array_keys($files), $used);
    }

    /**
     * Retire les commentaires avant le scan.
     *
     * Sans cela, documenter une cle dans un docbloc suffit a la declarer
     * « utilisee » — ce fichier s'est lui-meme signale a sa premiere execution.
     * Le cout d'un faux positif est un build rouge pour quelqu'un qui n'a fait
     * qu'ecrire un commentaire.
     */
    private function stripComments(string $content, string $extension): string
    {
        if ('twig' === $extension) {
            return (string) preg_replace('/\{#.*?#\}/s', '', $content);
        }

        $stripped = '';
        foreach (token_get_all($content) as $token) {
            if (\is_array($token)) {
                if (\in_array($token[0], [\T_COMMENT, \T_DOC_COMMENT], true)) {
                    continue;
                }
                $stripped .= $token[1];
            } else {
                $stripped .= $token;
            }
        }

        return $stripped;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function catalog(string $locale): array
    {
        $path = $this->projectDir . '/translations/messages.' . $locale . '.json';
        $raw = file_get_contents($path);
        if (false === $raw) {
            throw new \RuntimeException(sprintf('Catalogue de traduction illisible : %s', $path));
        }

        $data = json_decode($raw, true);
        if (!\is_array($data)) {
            throw new \RuntimeException(sprintf('JSON invalide dans %s', $path));
        }

        return $data;
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return list<string>
     */
    private function flatten(array $data, string $prefix = ''): array
    {
        $keys = [];
        foreach ($data as $name => $value) {
            $key = '' === $prefix ? (string) $name : $prefix . '.' . $name;
            if (\is_array($value)) {
                $keys = array_merge($keys, $this->flatten($value, $key));
            } else {
                $keys[] = $key;
            }
        }

        return $keys;
    }
}
