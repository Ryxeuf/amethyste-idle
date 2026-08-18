<?php

namespace App\GameEngine\Progression;

use App\Enum\CombatLever;
use App\Enum\CombatRegister;

/**
 * Le convertisseur unique : des points de budget vers un effet (ARC-03).
 *
 * GAME_ARCHETYPES § 4 : *« le taux de change vit dans **un seul** convertisseur »*.
 * C'est la moitie de la regle qui rend l'equilibrage verifiable — l'autre moitie
 * etant qu'un levier occupe **une place et une seule** dans la formule. Chaque
 * consommateur (degats, critique, jet de touche, statuts) lit les leviers qui le
 * concernent, mais aucun ne convertit lui-meme : sinon deux endroits du moteur
 * finiraient par ne plus donner le meme chiffre pour le meme nœud.
 *
 * **Ce que cette classe ne fait pas.** Elle n'applique rien : elle traduit. La
 * consommation dans la formule est ARC-03b, et la palette qui dit *qui a le droit
 * d'acheter quoi* est ARC-01. Ici on ne repond qu'a une question — *ce nœud paie
 * N points de budget sur ce levier, qu'est-ce que ca vaut ?*
 */
class CombatLeverScale
{
    /**
     * @var array<string, array{place: string, cap: int, bounded: bool, unit: ?string, per_point: ?float, by_register: ?array<string, array{unit: string, per_point: float, resource: string}>}>|null
     */
    private ?array $definitions = null;

    public function __construct(
        private readonly CombatLeverDefinitionLoader $loader,
    ) {
    }

    /**
     * Ce qu'un investissement achete, dans l'unite du levier.
     *
     * Le registre n'est requis que pour les leviers qui se lisent par registre
     * (`thrift`, `wind`) : leur effet porte sur **la ressource du registre**
     * (§ 2), pas sur les PM par principe. Le demander partout obligerait chaque
     * appelant a en trouver un la ou il n'en existe pas — un passif de vie n'a
     * pas de registre (§ 4.2).
     *
     * @throws CombatLeverDefinitionException si l'investissement depasse le plafond du levier,
     *                                        ou si un levier a lecture par registre est interroge sans registre
     */
    public function effectOf(CombatLever $lever, int $budgetPoints, ?CombatRegister $register = null): float
    {
        // ARC-18b — **le convertisseur convertit, il ne juge pas ce qu'on lui
        // donne.** Une premiere redaction refusait ici tout total negatif, avec
        // le message « a *node* cannot invest -6 budget points » : la phrase
        // dit d'elle-meme ou la regle appartient — on n'**achete** pas une
        // puissance negative, et c'est vrai a l'ecriture d'un nœud
        // (`SkillLeverReader` refuse deja les points <= 0). Ce n'est pas vrai a
        // la conversion d'un **total**, qui peut avoir ete diminue depuis.
        //
        // Le refus etait donc au mauvais endroit, et il coutait deux
        // mecaniques : la posture (ARC-18b), qui donne sur un levier ce qu'elle
        // retire sur un autre, et surtout **le pacte** (ARC-15) — *la seule
        // mecanique du canon qui rende un personnage mesurablement plus faible
        // quelque part*, dont le malus etait lu, valide, compte au budget, et
        // n'atteignait jamais la formule. Invisible jusqu'ici parce qu'aucun
        // nœud livre ne porte de levier.
        //
        // Le plafond, lui, se lit en valeur absolue : il borne l'**ampleur**
        // d'un deplacement, dans un sens comme dans l'autre.
        $cap = $this->capOf($lever);
        if (abs($budgetPoints) > $cap) {
            throw new CombatLeverDefinitionException(sprintf('"%s" is capped at %d budget points per tree, %d asked.', $lever->value, $cap, $budgetPoints));
        }

        return $budgetPoints * $this->perPointOf($lever, $register);
    }

    /**
     * Ce qu'un seul point de budget achete.
     */
    public function perPointOf(CombatLever $lever, ?CombatRegister $register = null): float
    {
        $definition = $this->definitionOf($lever);

        if ($definition['by_register'] === null) {
            return (float) $definition['per_point'];
        }

        return $this->readingOf($lever, $register)['per_point'];
    }

    public function unitOf(CombatLever $lever, ?CombatRegister $register = null): string
    {
        $definition = $this->definitionOf($lever);

        if ($definition['by_register'] === null) {
            return (string) $definition['unit'];
        }

        return $this->readingOf($lever, $register)['unit'];
    }

    /**
     * La ressource sur laquelle le levier porte, quand il en porte une.
     *
     * `null` pour les treize leviers qui ne se lisent pas par registre : ils ne
     * touchent aucune ressource, ils touchent une valeur.
     */
    public function resourceOf(CombatLever $lever, CombatRegister $register): ?string
    {
        if ($this->definitionOf($lever)['by_register'] === null) {
            return null;
        }

        return $this->readingOf($lever, $register)['resource'];
    }

    /**
     * Ce levier se lit-il differemment selon le registre ?
     */
    public function readsItsRegister(CombatLever $lever): bool
    {
        return $this->definitionOf($lever)['by_register'] !== null;
    }

    /**
     * La place du levier dans la formule — unique par construction.
     */
    public function placeOf(CombatLever $lever): string
    {
        return $this->definitionOf($lever)['place'];
    }

    public function capOf(CombatLever $lever): int
    {
        return $this->definitionOf($lever)['cap'];
    }

    /**
     * Le levier est-il soumis a la double borne element x registre (DOM-01) ?
     *
     * `life` et `recovery` ne le sont pas (§ 4.2) : les points de vie ne sont
     * pas un geste, et les borner ferait varier la barre de vie d'un tour a
     * l'autre selon le geste choisi.
     */
    public function isBounded(CombatLever $lever): bool
    {
        return $this->definitionOf($lever)['bounded'];
    }

    /**
     * @return array{place: string, cap: int, bounded: bool, unit: ?string, per_point: ?float, by_register: ?array<string, array{unit: string, per_point: float, resource: string}>}
     */
    private function definitionOf(CombatLever $lever): array
    {
        $this->definitions ??= $this->loader->load();

        return $this->definitions[$lever->value];
    }

    /**
     * @return array{unit: string, per_point: float, resource: string}
     */
    private function readingOf(CombatLever $lever, ?CombatRegister $register): array
    {
        if ($register === null) {
            throw new CombatLeverDefinitionException(sprintf('"%s" reads the resource of its register (GAME_ARCHETYPES §2): a register is required to convert it.', $lever->value));
        }

        /** @var array<string, array{unit: string, per_point: float, resource: string}> $readings */
        $readings = $this->definitionOf($lever)['by_register'];

        return $readings[$register->value];
    }
}
