<?php

namespace App\GameEngine\Progression;

use App\Enum\Element;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Ce que le catalogue dit de chaque arbre (ONB-09).
 *
 * Deux phrases par arbre, et pas une de plus : *ce qu'on y apprend* et *ce
 * qu'il permet d'equiper, en famille*. GAME_ONBOARDING § 6.2 fixe la frontiere,
 * et ce loader la tient par omission — **il n'existe aucun champ** pour la
 * liste des nœuds, les valeurs, les prerequis internes, le premier nœud ni la
 * specialisation terminale. Une donnee qui n'existe pas ne fuit pas.
 *
 * La case element x registre n'est pas relue ici : elle est portee par le
 * domaine depuis DOM-01, et la redeclarer ouvrirait une divergence muette entre
 * l'ecran et le moteur.
 *
 * **Une ligne par element s'y ajoute (ARC-13b-b)** : la trace que cet element
 * laisse sur ce qu'il frappe. Elle est rangee a part des arbres, et non
 * recopiee sur chacun d'eux, parce que la marque appartient a l'**element** —
 * 24 arbres partagent 8 marques, et les repeter serait 24 occasions de
 * diverger pour une information identique.
 */
class DomainCatalogDescriptions
{
    /**
     * @var array<string, array{teaches: string, equips: string}>|null
     */
    private ?array $entries = null;

    /**
     * @var array<string, string>|null
     */
    private ?array $elements = null;

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function defaultFile(): string
    {
        return $this->projectDir . '/config/game/domain_catalog.yaml';
    }

    /**
     * @return array{teaches: string, equips: string}|null
     */
    public function forSlug(string $domainSlug): ?array
    {
        return $this->entries()[mb_strtolower(trim($domainSlug))] ?? null;
    }

    /**
     * @return array<string, array{teaches: string, equips: string}>
     */
    public function all(): array
    {
        return $this->entries();
    }

    /**
     * La trace que cet element laisse — une phrase, jamais un chiffre (ARC-13b-b).
     */
    public function traceOfElement(?string $element): ?string
    {
        if ($element === null) {
            return null;
        }

        return $this->elements()[mb_strtolower(trim($element))] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function allElements(): array
    {
        return $this->elements();
    }

    /**
     * @return array<string, array{teaches: string, equips: string}>
     */
    private function entries(): array
    {
        if ($this->entries === null) {
            $this->entries = $this->load($this->defaultFile());
        }

        return $this->entries;
    }

    /**
     * @return array<string, string>
     */
    private function elements(): array
    {
        if ($this->elements === null) {
            $this->elements = $this->loadElements($this->defaultFile());
        }

        return $this->elements;
    }

    /**
     * @return array<string, string>
     *
     * @throws DomainCatalogDefinitionException
     */
    public function loadElements(string $path): array
    {
        return $this->normalizeElements($this->parse($path), $path);
    }

    /**
     * Les traces d'element, verifiees comme le reste : par omission.
     *
     * **Le bloc est facultatif** — un catalogue sans traces reste un catalogue
     * valide, et c'est ce qui permet a un test de le charger sans le decrire.
     * Ce qui ne l'est pas, c'est de le remplir a moitie : une cle qui n'est pas
     * un element du jeu est refusee, parce qu'une phrase rangee sous `fue`
     * n'apparaitrait nulle part et personne ne s'en apercevrait.
     *
     * @param array<array-key, mixed> $raw
     *
     * @return array<string, string>
     */
    public function normalizeElements(array $raw, string $source = '<array>'): array
    {
        $elements = $raw['elements'] ?? null;
        if ($elements === null) {
            return [];
        }

        if (!\is_array($elements)) {
            throw new DomainCatalogDefinitionException(sprintf('"elements" must be a mapping in "%s".', $source));
        }

        $normalized = [];
        foreach ($elements as $element => $trace) {
            if (!\is_string($element) || Element::tryFrom(mb_strtolower(trim($element))) === null) {
                throw new DomainCatalogDefinitionException(sprintf('"%s" is not an element of the game, in "%s".', \is_string($element) ? $element : \gettype($element), $source));
            }

            if (!\is_string($trace) || trim($trace) === '') {
                throw new DomainCatalogDefinitionException(sprintf('Element "%s" must say the trace it leaves, in "%s".', $element, $source));
            }

            $normalized[mb_strtolower(trim($element))] = $trace;
        }

        return $normalized;
    }

    /**
     * @return array<string, array{teaches: string, equips: string}>
     *
     * @throws DomainCatalogDefinitionException
     */
    public function load(string $path): array
    {
        return $this->normalize($this->parse($path), $path);
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws DomainCatalogDefinitionException
     */
    private function parse(string $path): array
    {
        if (!is_file($path)) {
            throw new DomainCatalogDefinitionException(sprintf('Domain catalogue not found: "%s".', $path));
        }

        try {
            $raw = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new DomainCatalogDefinitionException(sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        if (!\is_array($raw)) {
            throw new DomainCatalogDefinitionException(sprintf('Domain catalogue "%s" must be a mapping.', $path));
        }

        return $raw;
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return array<string, array{teaches: string, equips: string}>
     */
    public function normalize(array $raw, string $source = '<array>'): array
    {
        $domains = $raw['domains'] ?? null;
        if (!\is_array($domains) || $domains === []) {
            throw new DomainCatalogDefinitionException(sprintf('Domain catalogue "%s" must declare at least one tree.', $source));
        }

        $normalized = [];
        foreach ($domains as $slug => $entry) {
            if (!\is_string($slug) || trim($slug) === '') {
                throw new DomainCatalogDefinitionException(sprintf('Domain catalogue keys must be slugs in "%s".', $source));
            }

            if (!\is_array($entry) || !\is_string($entry['teaches'] ?? null) || !\is_string($entry['equips'] ?? null)) {
                throw new DomainCatalogDefinitionException(sprintf('Tree "%s" needs a "teaches" and an "equips" in "%s".', $slug, $source));
            }

            // Le catalogue omet, il ne ment pas : un champ inconnu est le
            // symptome d'une donnee qu'on essaie de faire passer par l'ecran
            // public, et c'est precisement ce que § 6.2 interdit.
            $unknown = array_diff(array_keys($entry), ['teaches', 'equips']);
            if ($unknown !== []) {
                throw new DomainCatalogDefinitionException(sprintf('Tree "%s" declares unknown field(s) "%s" in "%s": the catalogue says what a tree teaches, never its nodes.', $slug, implode('", "', $unknown), $source));
            }

            $normalized[mb_strtolower(trim($slug))] = [
                'teaches' => $entry['teaches'],
                'equips' => $entry['equips'],
            ];
        }

        return $normalized;
    }
}
