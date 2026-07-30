<?php

namespace App\GameEngine\Progression;

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
 */
class DomainCatalogDescriptions
{
    /**
     * @var array<string, array{teaches: string, equips: string}>|null
     */
    private ?array $entries = null;

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
     * @return array<string, array{teaches: string, equips: string}>
     *
     * @throws DomainCatalogDefinitionException
     */
    public function load(string $path): array
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

        return $this->normalize($raw, $path);
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
                throw new DomainCatalogDefinitionException(sprintf(
                    'Tree "%s" declares unknown field(s) "%s" in "%s": the catalogue says what a tree teaches, never its nodes.',
                    $slug,
                    implode('", "', $unknown),
                    $source,
                ));
            }

            $normalized[mb_strtolower(trim($slug))] = [
                'teaches' => $entry['teaches'],
                'equips' => $entry['equips'],
            ];
        }

        return $normalized;
    }
}
