<?php

namespace App\GameEngine\Economy;

use App\Entity\Game\Item;
use App\Enum\Element;

/**
 * L'affinite elementaire d'une ressource — la loi 10 (ZON-36).
 *
 * « Chaque ressource est la materialisation mineure d'un flux »
 * (GAME_WORLD § 2.2, decline en GAME_ZONES § 3 ter). La loi enonce une
 * **derivation**, pas une table : l'affinite vient de la ligne de recolte, et
 * se corrige quand la matiere est marquee par son lieu — la signature de sa
 * zone source — ou quand elle nomme elle-meme son flux.
 *
 * Ce service est le seul endroit qui repond a la question, pour la meme raison
 * que `PurityScope` est le seul a dire ce qui porte une bande : semer la
 * derivation dans les appelants la ferait repondre differemment selon l'ecran,
 * et une matiere finirait par etre Feu ici et Terre la.
 *
 * Deux refus distincts, et les confondre serait une perte d'information :
 *
 * - une matiere **hors perimetre** (une epee, une potion, une buche de decor)
 *   n'est pas une ressource — la question ne se pose pas ;
 * - l'**amethyste** est une ressource dont la reponse est « aucune ». Elle est
 *   le substrat dans lequel les flux se deposent, pas un flux.
 *
 * Les deux rendent `null` a `affinityOf()`, et `covers()` les separe.
 */
class ResourceAffinityCatalog
{
    /**
     * @var array{lines: array<string, list<string>>, line_slugs: array<string, list<string>>, corrections: array<string, Element>, without_affinity: list<string>, excluded: list<string>}|null
     */
    private ?array $table = null;

    public function __construct(
        private readonly ResourceAffinityDefinitionLoader $loader,
    ) {
    }

    /**
     * La matiere est-elle une ressource au sens de la loi 10 ?
     *
     * Repondre « oui » pour l'amethyste est volontaire : elle est couverte, et
     * son affinite est `null`.
     */
    public function covers(string $slug): bool
    {
        $table = $this->table();

        if (\in_array($slug, $table['excluded'], true)) {
            return false;
        }

        if (\in_array($slug, $table['without_affinity'], true)) {
            return true;
        }

        if (isset($table['corrections'][$slug])) {
            return true;
        }

        return $this->lineOf($slug) !== null;
    }

    /**
     * L'affinite d'une matiere, ou `null` — hors perimetre comme pour le
     * substrat. `covers()` distingue les deux.
     */
    public function affinityOf(string $slug): ?Element
    {
        $table = $this->table();

        if (\in_array($slug, $table['excluded'], true)) {
            return null;
        }

        if (\in_array($slug, $table['without_affinity'], true)) {
            return null;
        }

        return $table['corrections'][$slug] ?? $this->lineOf($slug);
    }

    public function affinityOfItem(?Item $item): ?Element
    {
        return $item === null ? null : $this->affinityOf($item->getSlug());
    }

    /**
     * L'affinite que la **ligne** donne a une matiere, avant correction.
     *
     * Exposee parce que la difference entre les deux est exactement ce que le
     * jalon decide : une correction qui rend la meme valeur que la ligne n'est
     * pas une decision, c'est du bruit dans une table qui doit se compter.
     */
    public function lineOf(string $slug): ?Element
    {
        $table = $this->table();

        foreach ($table['line_slugs'] as $line => $slugs) {
            if (\in_array($slug, $slugs, true)) {
                return Element::from($line);
            }
        }

        foreach ($table['lines'] as $line => $prefixes) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($slug, $prefix)) {
                    return Element::from($line);
                }
            }
        }

        return null;
    }

    /**
     * Les corrections declarees, dans l'ordre du fichier.
     *
     * @return array<string, Element>
     */
    public function corrections(): array
    {
        return $this->table()['corrections'];
    }

    /**
     * Les matieres declarees sans affinite — le substrat.
     *
     * @return list<string>
     */
    public function withoutAffinity(): array
    {
        return $this->table()['without_affinity'];
    }

    /**
     * @return array{lines: array<string, list<string>>, line_slugs: array<string, list<string>>, corrections: array<string, Element>, without_affinity: list<string>, excluded: list<string>}
     */
    private function table(): array
    {
        if ($this->table === null) {
            $this->table = $this->loader->load();
        }

        return $this->table;
    }
}
