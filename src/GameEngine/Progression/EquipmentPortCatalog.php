<?php

namespace App\GameEngine\Progression;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * L'echelle de port des armes, telle qu'elle est declaree (ONB-20b).
 *
 * Ajouter une famille d'arme, c'est ajouter un bloc dans
 * `config/game/equipment_ports.yaml` — jamais toucher une classe.
 *
 * **Le loader refuse une famille enseignee par un seul arbre.** Un domaine
 * porte une borne `element x registre` (DOM-01) : une famille rattachee a un
 * arbre unique impose donc son element par la bande, ce que la regle (c) du
 * cadrage interdit — *« on n'achete jamais un element pour porter une arme »*.
 * C'etait exactement le defaut herite (la hache derriere le berserker, feu ;
 * le baton derriere le paladin, lumiere), et le refuser ici empeche de le
 * reintroduire sans s'en apercevoir.
 */
class EquipmentPortCatalog
{
    /**
     * @var array<string, array{label: string, taught_by: list<string>, rung1: array{reference: string, slug: string, title: string, free: bool}, rung2: string, rung3: string}>|null
     */
    private ?array $families = null;

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function defaultFile(): string
    {
        return $this->projectDir . '/config/game/equipment_ports.yaml';
    }

    /**
     * @return array<string, array{label: string, line: string, taught_by: list<string>, rung1: array{reference: string, slug: string, title: string, free: bool}, rung2: string, rung3: string}>
     */
    public function families(): array
    {
        if ($this->families === null) {
            $this->families = $this->load($this->defaultFile());
        }

        return $this->families;
    }

    /**
     * Les echelons 1 enseignes par un arbre, par reference de fixture.
     *
     * C'est le **kit de port** que l'ouverture d'un arbre livre immediatement.
     *
     * @return list<string>
     */
    public function rungOneReferencesTaughtBy(string $domainKey): array
    {
        $references = [];
        foreach ($this->families() as $family) {
            if (\in_array($domainKey, $family['taught_by'], true)) {
                $references[] = $family['rung1']['reference'];
            }
        }

        return $references;
    }

    /**
     * Les slugs de competence des echelons 1, tous arbres confondus.
     *
     * @return list<string>
     */
    public function rungOneSlugs(): array
    {
        $slugs = [];
        foreach ($this->families() as $family) {
            $slugs[] = $family['rung1']['slug'];
        }

        return $slugs;
    }

    /**
     * La famille d'arme dont ce slug de competence est un echelon (ONB-12a).
     *
     * Sert a repondre a « ceci est-il une epee ? » sans table parallele : c'est
     * deja l'echelle qui le sait, puisque porter une epee passe par ses
     * echelons et par eux seuls.
     *
     * Les echelons 2 et 3 sont declares par **reference de fixture** — le
     * rewiring de `SkillFixtures` en a besoin. Leur slug s'en deduit par la
     * convention du projet (`_` → `-`), que `EquipmentPortLadderTest` verifie
     * echelon par echelon : si elle cassait, la famille deviendrait
     * introuvable en silence, et un objectif de port ne se terminerait jamais.
     */
    public function familyOfPortSkill(string $skillSlug): ?string
    {
        foreach ($this->families() as $key => $family) {
            $rungs = [
                $family['rung1']['slug'],
                str_replace('_', '-', $family['rung2']),
                str_replace('_', '-', $family['rung3']),
            ];

            if (\in_array($skillSlug, $rungs, true)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * La famille dont ce slug est l'echelon **3**, et lui seul (ARC-16b).
     *
     * L'accointance `access_discount` ne remise que le dernier barreau — *« l'echelon 3
     * de port de l'arc coute un palier de moins »* —, jamais l'entree (gratuite par
     * regle) ni l'echelon 2. Repondre ici plutot que chez le lecteur garde la
     * convention slug/reference (`_` → `-`) en un seul endroit.
     */
    public function familyOfRungThree(string $skillSlug): ?string
    {
        foreach ($this->families() as $key => $family) {
            if ($skillSlug === str_replace('_', '-', $family['rung3'])) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @return array<string, array{label: string, line: string, taught_by: list<string>, rung1: array{reference: string, slug: string, title: string, free: bool}, rung2: string, rung3: string}>
     *
     * @throws EquipmentPortDefinitionException
     */
    public function load(string $path): array
    {
        if (!is_file($path)) {
            throw new EquipmentPortDefinitionException(sprintf('Equipment port ladder not found: "%s".', $path));
        }

        try {
            $raw = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new EquipmentPortDefinitionException(sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        if (!\is_array($raw)) {
            throw new EquipmentPortDefinitionException(sprintf('Equipment port ladder "%s" must be a mapping.', $path));
        }

        return $this->normalize($raw, $path);
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return array<string, array{label: string, line: string, taught_by: list<string>, rung1: array{reference: string, slug: string, title: string, free: bool}, rung2: string, rung3: string}>
     */
    public function normalize(array $raw, string $source = '<array>'): array
    {
        $families = $raw['families'] ?? null;
        if (!\is_array($families) || $families === []) {
            throw new EquipmentPortDefinitionException(sprintf('Equipment port ladder "%s" must declare at least one family.', $source));
        }

        $normalized = [];
        foreach ($families as $key => $family) {
            if (!\is_string($key) || trim($key) === '') {
                throw new EquipmentPortDefinitionException(sprintf('Family keys must be slugs in "%s".', $source));
            }

            if (!\is_array($family) || !\is_string($family['label'] ?? null)) {
                throw new EquipmentPortDefinitionException(sprintf('Family "%s" needs a label in "%s".', $key, $source));
            }

            $taughtBy = $family['taught_by'] ?? null;
            if (!\is_array($taughtBy) || \count($taughtBy) < 2) {
                throw new EquipmentPortDefinitionException(sprintf('Family "%s" must be taught by at least two trees in "%s": a single tree would impose its element.', $key, $source));
            }

            $rungOne = $family['rung1'] ?? null;
            if (!\is_array($rungOne)
                || !\is_string($rungOne['reference'] ?? null)
                || !\is_string($rungOne['slug'] ?? null)
                || !\is_string($rungOne['title'] ?? null)) {
                throw new EquipmentPortDefinitionException(sprintf('Family "%s" needs a rung1 with a reference, a slug and a title in "%s".', $key, $source));
            }

            if (($rungOne['free'] ?? false) !== true) {
                throw new EquipmentPortDefinitionException(sprintf('The first rung of family "%s" must be free in "%s": it is the entry node of its trees, not a toll.', $key, $source));
            }

            if (!\is_string($family['rung2'] ?? null) || !\is_string($family['rung3'] ?? null)) {
                throw new EquipmentPortDefinitionException(sprintf('Family "%s" needs a rung2 and a rung3 in "%s".', $key, $source));
            }

            // ONB-20b-b : la ligne dit d'ou viennent les echelons superieurs —
            // les armes reutilisent des nœuds historiques, les armures n'en
            // avaient aucun et les leurs sont generes. Une valeur inconnue est
            // refusee : une ligne mal orthographiee produirait une echelle
            // silencieusement sans effet.
            $line = $family['line'] ?? 'weapon';
            if (!\in_array($line, ['weapon', 'armor'], true)) {
                throw new EquipmentPortDefinitionException(sprintf('Family "%s" declares an unknown line "%s" in "%s": weapon or armor.', $key, \is_scalar($line) ? (string) $line : \gettype($line), $source));
            }

            $normalized[$key] = [
                'label' => $family['label'],
                'line' => $line,
                'taught_by' => array_values(array_map('strval', $taughtBy)),
                'rung1' => [
                    'reference' => $rungOne['reference'],
                    'slug' => $rungOne['slug'],
                    'title' => $rungOne['title'],
                    'free' => true,
                ],
                'rung2' => $family['rung2'],
                'rung3' => $family['rung3'],
            ];
        }

        return $normalized;
    }
}
