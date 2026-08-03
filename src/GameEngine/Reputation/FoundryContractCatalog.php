<?php

namespace App\GameEngine\Reputation;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Le pool des contrats d'approvisionnement, lu dans
 * `config/game/foundry_contracts.yaml` (FAC-05).
 *
 * Declaratif comme le reste de la maison : ajouter un contrat possible, c'est
 * ajouter une ligne. Ce que le loader refuse : un contrat sans matiere (il ne
 * demanderait rien), un volume ou un prix nul (un contrat gratuit dans un
 * sens ou dans l'autre), et une essence nulle — le paiement de la Fonderie
 * est **mixte** par doctrine, c'est ce qui fait du contrat le second robinet
 * d'essence apres la fonte.
 */
class FoundryContractCatalog
{
    /**
     * @var list<array{item: string, volume: int, gils_per_unit: int, essence: int}>|null
     */
    private ?array $contracts = null;

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function defaultFile(): string
    {
        return $this->projectDir . '/config/game/foundry_contracts.yaml';
    }

    /**
     * @return list<array{item: string, volume: int, gils_per_unit: int, essence: int}>
     */
    public function contracts(): array
    {
        if ($this->contracts === null) {
            $this->contracts = $this->load($this->defaultFile());
        }

        return $this->contracts;
    }

    /**
     * @return list<array{item: string, volume: int, gils_per_unit: int, essence: int}>
     *
     * @throws FactionTensionDefinitionException
     */
    public function load(string $path): array
    {
        if (!is_file($path)) {
            throw new FactionTensionDefinitionException(sprintf('Foundry contract pool not found: "%s".', $path));
        }

        try {
            $raw = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new FactionTensionDefinitionException(sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        if (!\is_array($raw)) {
            throw new FactionTensionDefinitionException(sprintf('Foundry contract pool "%s" must be a mapping.', $path));
        }

        return $this->normalize($raw, $path);
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return list<array{item: string, volume: int, gils_per_unit: int, essence: int}>
     */
    public function normalize(array $raw, string $source = '<array>'): array
    {
        $rawContracts = $raw['contracts'] ?? null;
        if (!\is_array($rawContracts) || [] === $rawContracts) {
            throw new FactionTensionDefinitionException(sprintf('Pool "%s" must declare at least one contract.', $source));
        }

        $contracts = [];
        foreach (array_values($rawContracts) as $contract) {
            if (!\is_array($contract)) {
                throw new FactionTensionDefinitionException(sprintf('Each contract of "%s" must be a mapping.', $source));
            }

            $item = $contract['item'] ?? null;
            if (!\is_string($item) || trim($item) === '') {
                throw new FactionTensionDefinitionException(sprintf('A contract of "%s" needs an "item" slug.', $source));
            }

            foreach (['volume', 'gils_per_unit', 'essence'] as $field) {
                $value = $contract[$field] ?? null;
                if (!\is_int($value) || $value < 1) {
                    // Un volume nul ne demande rien, un prix nul ne paie pas,
                    // et une essence nulle ferait un paiement simple la ou la
                    // doctrine veut du mixte.
                    throw new FactionTensionDefinitionException(sprintf('The "%s" of contract "%s" in "%s" must be a positive integer.', $field, $item, $source));
                }
            }

            $contracts[] = [
                'item' => $item,
                'volume' => $contract['volume'],
                'gils_per_unit' => $contract['gils_per_unit'],
                'essence' => $contract['essence'],
            ];
        }

        return $contracts;
    }
}
