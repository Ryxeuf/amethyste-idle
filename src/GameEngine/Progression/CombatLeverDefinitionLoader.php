<?php

namespace App\GameEngine\Progression;

use App\Enum\CombatLever;
use App\Enum\CombatRegister;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Chargement + validation du vocabulaire des leviers (ARC-03).
 *
 * Le fichier est **obligatoire** et **complet** : les quinze leviers de
 * `CombatLever` y sont, ou la lecture echoue. Un levier absent ne serait pas un
 * levier sans effet, ce serait un levier qu'un arbre peut acheter et dont
 * personne ne sait ce qu'il achete.
 *
 * Quatre refus structurels, un par regle du canon (GAME_ARCHETYPES § 4) :
 *
 * - **une place, et une seule** — c'est le critere d'admission. Deux leviers a
 *   la meme place dans la formule sont un seul levier sous deux noms, et c'est
 *   la porte ouverte aux empilements qui font exploser un equilibrage ;
 * - **le vocabulaire est ferme** — une entree hors de l'enum est refusee, pas
 *   ignoree : le canon dit qu'ajouter un levier est une decision de moteur ;
 * - **un taux non nul** — un levier a 0 par point est un nœud qui ne fait rien,
 *   ce qu'aucun test de contenu ne saurait attraper ;
 * - **les trois registres ou aucun** — un levier qui se lit par registre (§ 2)
 *   les couvre tous les trois. En couvrir deux rendrait un levier inaccessible
 *   a un registre entier sans que rien ne le dise, ce qui est exactement l'ecart
 *   n° 13 corrige le 2026-08-01.
 */
class CombatLeverDefinitionLoader
{
    /**
     * Les unites que le moteur sait lire.
     *
     * Fermees pour la meme raison que les leviers : une unite inconnue serait
     * un effet dont la formule ne sait rien faire.
     *
     * @var list<string>
     */
    public const UNITS = ['percent', 'point', 'percent_of_max', 'resource_per_turn'];

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function defaultFile(): string
    {
        return $this->projectDir . '/config/game/combat_levers.yaml';
    }

    /**
     * @return array<string, array{place: string, cap: int, bounded: bool, unit: ?string, per_point: ?float, by_register: ?array<string, array{unit: string, per_point: float, resource: string}>}>
     *
     * @throws CombatLeverDefinitionException si le fichier est absent, illisible ou invalide
     */
    public function load(?string $path = null): array
    {
        $path ??= $this->defaultFile();

        if (!is_file($path)) {
            throw new CombatLeverDefinitionException(sprintf('Combat levers config not found: "%s".', $path));
        }

        try {
            $raw = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new CombatLeverDefinitionException(sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        if (!\is_array($raw)) {
            throw new CombatLeverDefinitionException(sprintf('Combat levers config "%s" must be a mapping.', $path));
        }

        return $this->normalize($raw, $path);
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return array<string, array{place: string, cap: int, bounded: bool, unit: ?string, per_point: ?float, by_register: ?array<string, array{unit: string, per_point: float, resource: string}>}>
     */
    public function normalize(array $raw, string $source = '<array>'): array
    {
        $levers = $raw['levers'] ?? null;
        if (!\is_array($levers) || $levers === []) {
            throw new CombatLeverDefinitionException(sprintf('Combat levers config "%s" must declare a non-empty "levers" mapping.', $source));
        }

        $expected = array_map(static fn (CombatLever $lever): string => $lever->value, CombatLever::cases());

        $unknown = array_diff(array_keys($levers), $expected);
        if ($unknown !== []) {
            throw new CombatLeverDefinitionException(sprintf('Combat levers config "%s" declares levers outside the closed vocabulary: %s.', $source, implode(', ', $unknown)));
        }

        $missing = array_diff($expected, array_keys($levers));
        if ($missing !== []) {
            throw new CombatLeverDefinitionException(sprintf('Combat levers config "%s" is missing: %s.', $source, implode(', ', $missing)));
        }

        $normalized = [];
        $places = [];
        foreach ($expected as $name) {
            $definition = $this->normalizeLever($levers[$name], $name, $source);

            $place = $definition['place'];
            if (isset($places[$place])) {
                throw new CombatLeverDefinitionException(sprintf('Combat levers config "%s": "%s" and "%s" share the formula slot "%s". Two levers in the same slot are one lever under two names.', $source, $places[$place], $name, $place));
            }
            $places[$place] = $name;

            $normalized[$name] = $definition;
        }

        return $normalized;
    }

    /**
     * @return array{place: string, cap: int, bounded: bool, unit: ?string, per_point: ?float, by_register: ?array<string, array{unit: string, per_point: float, resource: string}>}
     */
    private function normalizeLever(mixed $raw, string $name, string $source): array
    {
        if (!\is_array($raw)) {
            throw new CombatLeverDefinitionException(sprintf('Combat levers config "%s": lever "%s" must be a mapping.', $source, $name));
        }

        $place = $raw['place'] ?? null;
        if (!\is_string($place) || trim($place) === '') {
            throw new CombatLeverDefinitionException(sprintf('Combat levers config "%s": lever "%s" must declare a non-empty "place" — the slot it occupies in the combat formula.', $source, $name));
        }

        $cap = $raw['cap'] ?? null;
        if (!\is_int($cap) || $cap <= 0) {
            throw new CombatLeverDefinitionException(sprintf('Combat levers config "%s": lever "%s" must declare a positive integer "cap".', $source, $name));
        }

        $bounded = $raw['bounded'] ?? null;
        if (!\is_bool($bounded)) {
            throw new CombatLeverDefinitionException(sprintf('Combat levers config "%s": lever "%s" must declare "bounded" (true / false) — whether the element x register bound applies (DOM-01).', $source, $name));
        }

        $byRegister = $raw['by_register'] ?? null;
        if ($byRegister !== null) {
            if (isset($raw['per_point']) || isset($raw['unit'])) {
                throw new CombatLeverDefinitionException(sprintf('Combat levers config "%s": lever "%s" declares both "by_register" and a flat rate. A lever reads its register or it does not.', $source, $name));
            }

            return [
                'place' => $place,
                'cap' => $cap,
                'bounded' => $bounded,
                'unit' => null,
                'per_point' => null,
                'by_register' => $this->normalizeByRegister($byRegister, $name, $source),
            ];
        }

        return [
            'place' => $place,
            'cap' => $cap,
            'bounded' => $bounded,
            'unit' => $this->unit($raw['unit'] ?? null, $name, $source),
            'per_point' => $this->perPoint($raw['per_point'] ?? null, $name, $source),
            'by_register' => null,
        ];
    }

    /**
     * @return array<string, array{unit: string, per_point: float, resource: string}>
     */
    private function normalizeByRegister(mixed $raw, string $name, string $source): array
    {
        if (!\is_array($raw)) {
            throw new CombatLeverDefinitionException(sprintf('Combat levers config "%s": lever "%s" has a malformed "by_register".', $source, $name));
        }

        $registers = array_map(static fn (CombatRegister $r): string => $r->value, CombatRegister::cases());

        $unknown = array_diff(array_keys($raw), $registers);
        if ($unknown !== []) {
            throw new CombatLeverDefinitionException(sprintf('Combat levers config "%s": lever "%s" reads unknown registers: %s.', $source, $name, implode(', ', $unknown)));
        }

        $missing = array_diff($registers, array_keys($raw));
        if ($missing !== []) {
            throw new CombatLeverDefinitionException(sprintf('Combat levers config "%s": lever "%s" is unreadable in register(s) %s — a lever covers the three registers or none.', $source, $name, implode(', ', $missing)));
        }

        $readings = [];
        foreach ($registers as $register) {
            $reading = $raw[$register];
            if (!\is_array($reading)) {
                throw new CombatLeverDefinitionException(sprintf('Combat levers config "%s": lever "%s" has a malformed reading for register "%s".', $source, $name, $register));
            }

            $resource = $reading['resource'] ?? null;
            if (!\is_string($resource) || trim($resource) === '') {
                throw new CombatLeverDefinitionException(sprintf('Combat levers config "%s": lever "%s" must name the resource it reads in register "%s".', $source, $name, $register));
            }

            $readings[$register] = [
                'unit' => $this->unit($reading['unit'] ?? null, $name . '.' . $register, $source),
                'per_point' => $this->perPoint($reading['per_point'] ?? null, $name . '.' . $register, $source),
                'resource' => $resource,
            ];
        }

        return $readings;
    }

    private function unit(mixed $value, string $name, string $source): string
    {
        if (!\is_string($value) || !\in_array($value, self::UNITS, true)) {
            throw new CombatLeverDefinitionException(sprintf('Combat levers config "%s": lever "%s" must declare a "unit" among %s.', $source, $name, implode(', ', self::UNITS)));
        }

        return $value;
    }

    private function perPoint(mixed $value, string $name, string $source): float
    {
        if (!\is_int($value) && !\is_float($value)) {
            throw new CombatLeverDefinitionException(sprintf('Combat levers config "%s": lever "%s" must declare a numeric "per_point".', $source, $name));
        }

        if ((float) $value === 0.0) {
            throw new CombatLeverDefinitionException(sprintf('Combat levers config "%s": lever "%s" buys nothing per budget point. A lever at zero is a node that does nothing.', $source, $name));
        }

        return (float) $value;
    }
}
