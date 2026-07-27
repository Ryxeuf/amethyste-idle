<?php

declare(strict_types=1);

namespace App\Translation;

/**
 * Detection de texte francais code en dur dans les gabarits (tache 135).
 *
 * `TranslationCatalogAudit` repond a « une cle appelee est-elle definie ? ».
 * Elle ne voit pas le defaut inverse, plus courant : du texte ecrit directement
 * dans le gabarit, qui n'appelle **aucune** cle. Le joueur en anglais lit du
 * francais, et aucun outil ne s'en plaint.
 *
 * Le scan est volontairement heuristique — il cherche des mots francais
 * indiscutables dans les seuls endroits qu'un joueur lit :
 *
 * - les noeuds de texte (`>Payer le loyer<`) ;
 * - les attributs affiches (`title`, `placeholder`, `alt`, `aria-label`) ;
 * - les chaines passees a une macro Twig (`_self.gear_slot('head', ..., 'Tete')`).
 *
 * Les classes CSS, les identifiants et les commentaires ne sont pas lus.
 *
 * `templates/admin/` est hors perimetre : ces ecrans s'adressent a l'exploitant,
 * pas aux joueurs, et le plan i18n ne les couvre pas. `templates/old_game/` est
 * l'heritage d'avant le pivot, plus servi par aucune route.
 */
final class HardcodedTextScanner
{
    /**
     * Ecrans nes du pivot PBBG. Ils sont propres, et doivent le rester.
     */
    public const PIVOT_TEMPLATES = [
        'templates/game/index.html.twig',
        'templates/game/zone/index.html.twig',
        'templates/game/zone/world_map.html.twig',
        'templates/game/dungeon/list.html.twig',
        'templates/game/dungeon/show.html.twig',
    ];

    private const EXCLUDED_PREFIXES = ['templates/admin/', 'templates/old_game/'];

    /**
     * Mots francais sans equivalent anglais courant.
     *
     * Volontairement court : le but est zero faux positif, pas l'exhaustivite.
     * Ce qui echappe a cette liste echappe aussi au garde-fou — c'est le prix
     * d'un controle qui ne se declenche jamais a tort.
     */
    private const FRENCH_WORDS = [
        'le', 'la', 'les', 'des', 'du', 'une', 'vous', 'votre', 'vos', 'pour',
        'dans', 'avec', 'sont', 'aucun', 'aucune', 'cette', 'sur', 'par', 'qui',
        'que', 'sans', 'chaque', 'tous', 'toutes', 'vers', 'entre', 'depuis',
        'plus', 'est', 'aux', 'nos', 'notre', 'leur', 'ceux', 'celle', 'elles',
    ];

    /** Un mot portant un accent francais est francais sans discussion. */
    private const ACCENTED = '/[éèêëàâäçùûüîïôö]/iu';

    public function __construct(private readonly string $projectDir)
    {
    }

    /**
     * Textes codes en dur, indexes par gabarit.
     *
     * @return array<string, list<string>> chemin relatif => extraits trouves
     */
    public function scan(): array
    {
        $found = [];
        foreach ($this->templates() as $relative => $absolute) {
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
    public function scanTemplate(string $relative): array
    {
        $absolute = $this->projectDir . '/' . $relative;

        return is_file($absolute) ? $this->scanFile($absolute) : [];
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

        // Les commentaires Twig ne sont pas rendus : ils peuvent rester francais.
        $content = (string) preg_replace('/\{#.*?#\}/s', '', $content);

        $hits = [];

        // Noeuds de texte : ce qui separe deux balises, sans expression Twig.
        if (preg_match_all('/>([^<>{}]{3,})</', $content, $matches)) {
            foreach ($matches[1] as $text) {
                $hits[] = trim($text);
            }
        }

        // Attributs lus par le joueur ou par un lecteur d'ecran.
        if (preg_match_all('/(?:title|placeholder|alt|aria-label)="([^"{}]{3,})"/', $content, $matches)) {
            $hits = array_merge($hits, $matches[1]);
        }

        // Libelles passes a une macro : `_self.gear_slot('head', ..., 'Tete')`.
        if (preg_match_all("/_self\.\w+\([^)]*'([^']{3,})'\s*\)/", $content, $matches)) {
            $hits = array_merge($hits, $matches[1]);
        }

        return array_values(array_unique(array_filter(
            array_map(trim(...), $hits),
            $this->looksFrench(...),
        )));
    }

    private function looksFrench(string $text): bool
    {
        if ('' === $text) {
            return false;
        }
        if (preg_match(self::ACCENTED, $text)) {
            return true;
        }

        $words = preg_split('/[^a-zA-Z\']+/', mb_strtolower($text), -1, \PREG_SPLIT_NO_EMPTY) ?: [];

        return [] !== array_intersect($words, self::FRENCH_WORDS);
    }

    /**
     * @return array<string, string> chemin relatif => chemin absolu
     */
    private function templates(): array
    {
        $root = $this->projectDir . '/templates';
        if (!is_dir($root)) {
            return [];
        }

        $templates = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \RecursiveDirectoryIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            /* @var \SplFileInfo $file */
            if (!$file->isFile() || 'twig' !== $file->getExtension()) {
                continue;
            }
            $relative = substr($file->getPathname(), \strlen($this->projectDir) + 1);
            foreach (self::EXCLUDED_PREFIXES as $prefix) {
                if (str_starts_with($relative, $prefix)) {
                    continue 2;
                }
            }
            $templates[$relative] = $file->getPathname();
        }

        return $templates;
    }
}
