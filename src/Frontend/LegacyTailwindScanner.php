<?php

declare(strict_types=1);

namespace App\Frontend;

/**
 * Detection des classes Tailwind d'avant la v4 dans les gabarits et les assets.
 *
 * Le projet compile en Tailwind 4, mais a charge longtemps, en plus, le CDN
 * Tailwind 2.2.19. Or les declarations hors calque l'emportent sur celles d'un
 * `@layer` : c'est donc la v2 qui servait tous les noms de classe communs, et
 * l'interface tournait en v2 sans que personne l'ait decide.
 *
 * Le CDN retire, deux familles de defauts sont apparues — aucune visible dans un
 * diff, aucune detectee par un test :
 *
 * 1. Les classes **supprimees** en v4 (`bg-opacity-50`) ne peignent plus rien.
 *    L'ecran ne casse pas : il perd juste sa transparence, et un voile de modale
 *    devient un aplat opaque.
 * 2. Les classes **renommees** dont l'ancien nom reste valide avec un autre sens.
 *    `outline-none` posait un contour transparent de 2 px — visible en contraste
 *    force, donc utile au clavier ; en v4 il supprime le contour pour de bon.
 *
 * Ce scan gele le resultat de la migration. Il ne juge pas du gout : il refuse
 * les noms dont la v4 a change le sens ou qu'elle ne connait plus.
 *
 * `templates/old_game/` est l'heritage d'avant le pivot, plus servi par aucune
 * route ; `public/` est du compile.
 */
final class LegacyTailwindScanner
{
    /**
     * Nom herite => ce qu'il faut ecrire, et pourquoi.
     *
     * @var array<string, string>
     */
    public const REPLACEMENTS = [
        'bg-opacity' => 'bg-<couleur>/<n> — les utilitaires d\'opacite ont disparu en v4',
        'text-opacity' => 'text-<couleur>/<n> — les utilitaires d\'opacite ont disparu en v4',
        'border-opacity' => 'border-<couleur>/<n> — les utilitaires d\'opacite ont disparu en v4',
        'ring-opacity' => 'ring-<couleur>/<n> — les utilitaires d\'opacite ont disparu en v4',
        'divide-opacity' => 'divide-<couleur>/<n> — les utilitaires d\'opacite ont disparu en v4',
        'placeholder-opacity' => 'placeholder-<couleur>/<n> — les utilitaires d\'opacite ont disparu en v4',
        'flex-shrink' => 'shrink — renomme en v3, seul nom valide en v4',
        'flex-grow' => 'grow — renomme en v3, seul nom valide en v4',
        'overflow-ellipsis' => 'text-ellipsis — renomme en v3',
        'decoration-slice' => 'box-decoration-slice — renomme en v3',
        'decoration-clone' => 'box-decoration-clone — renomme en v3',
        'bg-gradient-to' => 'bg-linear-to-* — l\'idiome v4, qui interpole en oklab',
        'outline-none' => 'outline-hidden — en v4, `outline-none` supprime le contour au lieu de le rendre transparent, et le repere de focus disparait en contraste force',
    ];

    /**
     * Suffixe attendu apres le nom herite, par famille.
     *
     * Devine le moins possible : `bg-opacity-50` finit par un nombre,
     * `bg-gradient-to-br` par une direction, `flex-shrink-0` par un `-0`
     * facultatif. Un motif par forme evite les faux negatifs silencieux.
     *
     * @var array<string, string>
     */
    private const SUFFIXES = [
        'bg-opacity' => '-\d+',
        'text-opacity' => '-\d+',
        'border-opacity' => '-\d+',
        'ring-opacity' => '-\d+',
        'divide-opacity' => '-\d+',
        'placeholder-opacity' => '-\d+',
        'bg-gradient-to' => '-[a-z]{1,2}',
        'flex-shrink' => '(?:-0)?',
        'flex-grow' => '(?:-0)?',
        'overflow-ellipsis' => '',
        'decoration-slice' => '',
        'decoration-clone' => '',
        'outline-none' => '',
    ];

    private const EXCLUDED = ['templates/old_game/'];

    public function __construct(private readonly string $projectDir)
    {
    }

    /**
     * Occurrences trouvees, indexees par fichier.
     *
     * @return array<string, list<string>> chemin relatif => noms herites cites
     */
    public function scan(): array
    {
        $found = [];
        foreach ($this->files() as $relative => $absolute) {
            $hits = $this->scanFile($absolute);
            if ([] !== $hits) {
                $found[$relative] = $hits;
            }
        }
        ksort($found);

        return $found;
    }

    /**
     * @return list<string>
     */
    private function scanFile(string $absolute): array
    {
        $content = file_get_contents($absolute);
        if (false === $content) {
            return [];
        }

        $hits = [];
        foreach (array_keys(self::REPLACEMENTS) as $legacy) {
            // Un nom de classe : precede d'une frontiere, eventuellement prefixe
            // de variantes (`focus:`, `sm:hover:`), suivi du suffixe de sa
            // famille — et jamais d'un `:`, pour ne pas confondre la classe
            // `flex-shrink` avec la propriete CSS `flex-shrink: 0`.
            $pattern = sprintf(
                '/(?<![\w-])(?:[a-z-]+:)*%s%s(?![\w-])(?!\s*:)/',
                preg_quote($legacy, '/'),
                self::SUFFIXES[$legacy],
            );

            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[0] as $match) {
                    $hits[] = $match;
                }
            }
        }

        return array_values(array_unique($hits));
    }

    /**
     * @return array<string, string> chemin relatif => chemin absolu
     */
    private function files(): array
    {
        $files = [];
        foreach (['templates', 'assets'] as $root) {
            $base = $this->projectDir . '/' . $root;
            if (!is_dir($base)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($base, \RecursiveDirectoryIterator::SKIP_DOTS),
            );
            foreach ($iterator as $file) {
                /* @var \SplFileInfo $file */
                if (!$file->isFile() || !\in_array($file->getExtension(), ['twig', 'js'], true)) {
                    continue;
                }
                $relative = substr($file->getPathname(), \strlen($this->projectDir) + 1);
                foreach (self::EXCLUDED as $prefix) {
                    if (str_starts_with($relative, $prefix)) {
                        continue 2;
                    }
                }
                $files[$relative] = $file->getPathname();
            }
        }
        ksort($files);

        return $files;
    }
}
