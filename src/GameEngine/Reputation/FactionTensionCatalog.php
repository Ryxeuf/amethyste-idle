<?php

namespace App\GameEngine\Reputation;

use App\Enum\ReputationTier;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * L'axe doctrinal, lu dans `config/game/factions.yaml` (FAC-01).
 *
 * GAME_WORLD § 6.4 a : deux paires opposees, une faction hors tension, et une
 * decote qui ne mord qu'au-dela d'Ami. Le catalogue est declaratif — ajouter
 * une faction, c'est ajouter une ligne dans le fichier, jamais toucher une
 * classe.
 *
 * **Ce que le loader refuse, et pourquoi.** Une faction dans deux paires
 * rendrait l'axe illisible : un gain retirerait chez deux opposes, et le joueur
 * verrait fondre une reputation sans comprendre laquelle il a trahie. Une
 * faction a la fois neutre et opposee dit deux choses contraires. Un palier
 * inconnu ferait mordre la decote a un seuil arbitraire. Aucun de ces defauts
 * ne se voit a l'execution : ils produisent des nombres, pas des erreurs.
 *
 * **Ce que le loader accepte volontiers** : une paire dont un membre n'existe
 * pas encore comme entite. `fonderie` arrive avec FAC-04 ; la tension est
 * declaree des maintenant et reste **inerte** jusque-la. Exiger que la faction
 * existe obligerait a se souvenir de revenir ici le jour venu — et personne ne
 * s'en souvient.
 */
class FactionTensionCatalog
{
    /**
     * @var array{
     *   pairs: list<array{left: string, right: string, axis: string}>,
     *   neutral: list<string>,
     *   beyond_tier: ReputationTier,
     *   percent: int,
     *   patronage_tier: ReputationTier,
     *   forbidden_in_combat: bool
     * }|null
     */
    private ?array $definition = null;

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function defaultFile(): string
    {
        return $this->projectDir . '/config/game/factions.yaml';
    }

    /**
     * L'oppose d'une faction sur l'axe, ou `null` si elle est hors tension.
     */
    public function opponentOf(string $slug): ?string
    {
        foreach ($this->definition()['pairs'] as $pair) {
            if ($pair['left'] === $slug) {
                return $pair['right'];
            }
            if ($pair['right'] === $slug) {
                return $pair['left'];
            }
        }

        return null;
    }

    /**
     * Le nom de l'axe qui porte une faction (« Extraire / Preserver »).
     */
    public function axisOf(string $slug): ?string
    {
        foreach ($this->definition()['pairs'] as $pair) {
            if ($pair['left'] === $slug || $pair['right'] === $slug) {
                return $pair['axis'];
            }
        }

        return null;
    }

    /**
     * @return list<array{left: string, right: string, axis: string}>
     */
    public function pairs(): array
    {
        return $this->definition()['pairs'];
    }

    /**
     * @return list<string>
     */
    public function neutralFactions(): array
    {
        return $this->definition()['neutral'];
    }

    /**
     * Le palier a partir duquel la tension mord.
     */
    public function beyondTier(): ReputationTier
    {
        return $this->definition()['beyond_tier'];
    }

    public function offsetPercent(): int
    {
        return $this->definition()['percent'];
    }

    /**
     * Le plancher de la decote : le miroir du palier ou elle commence.
     *
     * « On ne peut pas renoncer a plus que ce qu'on aurait pu donner. » La
     * valeur n'est pas ecrite dans le fichier — elle est derivee, pour qu'un
     * deplacement du palier deplace le plancher avec lui.
     */
    public function offsetFloor(): int
    {
        return -$this->beyondTier()->threshold();
    }

    /**
     * Le palier a partir duquel on peut porter les couleurs d'une faction.
     */
    public function patronageTier(): ReputationTier
    {
        return $this->definition()['patronage_tier'];
    }

    public function patronageForbiddenInCombat(): bool
    {
        return $this->definition()['forbidden_in_combat'];
    }

    /**
     * La part d'un gain qui passe au-dela du palier, et donc ce que l'oppose
     * perd.
     *
     * **Toute l'arithmetique de la tension tient ici**, sans base de donnees :
     * c'est ce qui permet de la verifier cas par cas. Le calcul porte sur la
     * **part du gain au-dela du seuil**, jamais sur le gain entier — sans quoi
     * un joueur a 1 999 points perdrait chez l'oppose pour un gain qui le laisse
     * a Ami tout juste.
     *
     * Un gain nul ou negatif ne retire rien : la reputation ne descend que par
     * le geste oppose, et un non-geste n'est pas un geste.
     */
    public function offsetFor(int $reputationBefore, int $gain): int
    {
        if ($gain <= 0) {
            return 0;
        }

        $threshold = $this->beyondTier()->threshold();
        $after = $reputationBefore + $gain;

        $beyond = $after - max($threshold, $reputationBefore);
        if ($beyond <= 0) {
            return 0;
        }

        return (int) floor($beyond * $this->offsetPercent() / 100);
    }

    /**
     * @return array{
     *   pairs: list<array{left: string, right: string, axis: string}>,
     *   neutral: list<string>,
     *   beyond_tier: ReputationTier,
     *   percent: int,
     *   patronage_tier: ReputationTier,
     *   forbidden_in_combat: bool
     * }
     */
    private function definition(): array
    {
        if ($this->definition === null) {
            $this->definition = $this->load($this->defaultFile());
        }

        return $this->definition;
    }

    /**
     * @return array{
     *   pairs: list<array{left: string, right: string, axis: string}>,
     *   neutral: list<string>,
     *   beyond_tier: ReputationTier,
     *   percent: int,
     *   patronage_tier: ReputationTier,
     *   forbidden_in_combat: bool
     * }
     *
     * @throws FactionTensionDefinitionException
     */
    public function load(string $path): array
    {
        if (!is_file($path)) {
            throw new FactionTensionDefinitionException(sprintf('Faction axis catalogue not found: "%s".', $path));
        }

        try {
            $raw = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new FactionTensionDefinitionException(sprintf('Invalid YAML in "%s": %s', $path, $e->getMessage()), 0, $e);
        }

        if (!\is_array($raw)) {
            throw new FactionTensionDefinitionException(sprintf('Faction axis catalogue "%s" must be a mapping.', $path));
        }

        return $this->normalize($raw, $path);
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return array{
     *   pairs: list<array{left: string, right: string, axis: string}>,
     *   neutral: list<string>,
     *   beyond_tier: ReputationTier,
     *   percent: int,
     *   patronage_tier: ReputationTier,
     *   forbidden_in_combat: bool
     * }
     */
    public function normalize(array $raw, string $source = '<array>'): array
    {
        $pairs = [];
        $seen = [];

        foreach ($this->asList($raw['tension_pairs'] ?? [], 'tension_pairs', $source) as $pair) {
            if (!\is_array($pair)) {
                throw new FactionTensionDefinitionException(sprintf('Each tension pair of "%s" must be a mapping.', $source));
            }

            $left = $pair['left'] ?? null;
            $right = $pair['right'] ?? null;
            $axis = $pair['axis'] ?? null;

            if (!\is_string($left) || !\is_string($right) || !\is_string($axis) || trim($axis) === '') {
                throw new FactionTensionDefinitionException(sprintf('A tension pair of "%s" needs "left", "right" and "axis".', $source));
            }
            if ($left === $right) {
                throw new FactionTensionDefinitionException(sprintf('Faction "%s" is opposed to itself in "%s".', $left, $source));
            }

            foreach ([$left, $right] as $slug) {
                if (isset($seen[$slug])) {
                    // Deux oppositions rendraient l'axe illisible : un gain
                    // retirerait chez deux factions, et rien ne dirait laquelle
                    // a ete trahie.
                    throw new FactionTensionDefinitionException(sprintf('Faction "%s" appears in two tension pairs of "%s": the axis must stay readable.', $slug, $source));
                }
                $seen[$slug] = true;
            }

            $pairs[] = ['left' => $left, 'right' => $right, 'axis' => $axis];
        }

        $neutral = [];
        foreach ($this->asList($raw['neutral'] ?? [], 'neutral', $source) as $slug) {
            if (!\is_string($slug) || trim($slug) === '') {
                throw new FactionTensionDefinitionException(sprintf('Neutral factions of "%s" must be slugs.', $source));
            }
            if (isset($seen[$slug])) {
                throw new FactionTensionDefinitionException(sprintf('Faction "%s" is both neutral and opposed in "%s".', $slug, $source));
            }
            $neutral[] = $slug;
        }

        $tension = $raw['tension'] ?? null;
        if (!\is_array($tension)) {
            throw new FactionTensionDefinitionException(sprintf('Catalogue "%s" must declare a "tension" block.', $source));
        }

        $percent = $tension['percent'] ?? null;
        if (!\is_int($percent) || $percent < 0 || $percent > 100) {
            throw new FactionTensionDefinitionException(sprintf('The tension percent of "%s" must be an integer between 0 and 100.', $source));
        }

        $patronage = $raw['patronage'] ?? null;
        if (!\is_array($patronage)) {
            throw new FactionTensionDefinitionException(sprintf('Catalogue "%s" must declare a "patronage" block.', $source));
        }

        return [
            'pairs' => $pairs,
            'neutral' => $neutral,
            'beyond_tier' => $this->tier($tension['beyond_tier'] ?? null, 'tension.beyond_tier', $source),
            'percent' => $percent,
            'patronage_tier' => $this->tier($patronage['required_tier'] ?? null, 'patronage.required_tier', $source),
            'forbidden_in_combat' => (bool) ($patronage['forbidden_in_combat'] ?? true),
        ];
    }

    /**
     * @return list<mixed>
     */
    private function asList($value, string $key, string $source): array
    {
        if (!\is_array($value)) {
            throw new FactionTensionDefinitionException(sprintf('"%s" of "%s" must be a list.', $key, $source));
        }

        return array_values($value);
    }

    private function tier($value, string $key, string $source): ReputationTier
    {
        $tier = \is_string($value) ? ReputationTier::tryFrom($value) : null;
        if ($tier === null) {
            // Un palier inconnu ferait mordre la decote a un seuil arbitraire —
            // un nombre plausible, jamais une erreur.
            throw new FactionTensionDefinitionException(sprintf('"%s" of "%s" does not name a reputation tier.', $key, $source));
        }

        return $tier;
    }
}
