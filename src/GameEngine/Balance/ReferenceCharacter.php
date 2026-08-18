<?php

namespace App\GameEngine\Balance;

use App\Enum\CombatRegister;
use App\Enum\DomainRole;

/**
 * Ce qu'un build de reference vaut au moment d'entrer en rencontre (ARC-17c-b).
 *
 * `ReferenceBuild` (ARC-17c-a) dit **ce qu'un personnage a appris** : des points
 * de budget par levier et des slugs de gestes. Ce n'est pas encore quelqu'un qui
 * peut jouer un tour — un levier n'est pas un chiffre de combat, et un slug n'est
 * pas un degat.
 *
 * Cette classe est l'etat intermediaire : **le build converti dans les unites de
 * la formule**. Une barre de vie, un geste et ce qu'il coute, des taux. Rien de
 * plus, et surtout rien qui vienne d'ailleurs que des donnees — le canon (§ 0.2)
 * range les constantes ecrites a la main parmi les choses qui se periment.
 *
 * **Pourquoi separer la fiche du simulateur.** Parce que la fiche se lit :
 * elle est le seul endroit ou l'on peut constater qu'un guerisseur frappe a 3 et
 * un archer a 17 avant qu'un seul tour ne soit joue. Un simulateur qui derivait
 * ses chiffres en jouant les rendrait invisibles, et une mesure dont on ne peut
 * pas lire les entrees ne s'explique pas.
 */
final readonly class ReferenceCharacter
{
    /**
     * @param string $label           l'arbre et sa branche
     * @param int    $maxLife         points de vie, base plus le levier `life`
     * @param int    $maxResource     la ressource du registre, 0 quand il n'en a pas
     * @param int    $gestureDamage   ce que le geste retire, leviers compris
     * @param int    $gestureCost     ce que le geste coute dans la ressource du registre
     * @param int    $fallbackDamage  ce que le personnage retire quand il n'a plus de quoi payer
     * @param float  $hitRate         chance de toucher, en pourcentage
     * @param float  $criticalRate    chance de critique, en pourcentage
     * @param float  $criticalPower   multiplicateur du critique
     * @param float  $guardMultiplier ce qui reste des degats subis apres `guard`
     * @param float  $dodgeRate       chance d'eviter entierement, en pourcentage
     * @param float  $recoveryPerTurn part des PV maximum rendue en fin de tour
     * @param float  $resourcePerTurn ce que `wind` rend par tour, dans la ressource
     */
    public function __construct(
        public string $label,
        public DomainRole $role,
        public CombatRegister $register,
        public int $maxLife,
        public int $maxResource,
        public string $gestureSlug,
        public int $gestureDamage,
        public int $gestureCost,
        public int $fallbackDamage,
        public float $hitRate,
        public float $criticalRate,
        public float $criticalPower,
        public float $guardMultiplier,
        public float $dodgeRate,
        public float $recoveryPerTurn,
        public float $resourcePerTurn,
    ) {
    }

    /**
     * Le degat **attendu** d'un tour, jets compris.
     *
     * Une esperance et non un tirage : voir `EncounterSimulator`, qui explique
     * pourquoi le simulateur ne tire pas de des. La precision et le critique s'y
     * multiplient dans l'ordre ou la formule les applique — toucher d'abord,
     * critiquer ensuite —, si bien que deplacer `hit` ou `critical` dans le
     * convertisseur deplace cette valeur sans qu'on ait rien a reecrire ici.
     */
    public function expectedDamagePerTurn(): float
    {
        return $this->expectedDamageOf($this->gestureDamage);
    }

    /**
     * Ce que le personnage retire quand il n'a plus de quoi payer son geste.
     *
     * **Un tour sans ressource n'est pas un tour perdu** : l'attaque de base
     * reste gratuite (regle absolue n° 10), et c'est elle qui decide de ce
     * qu'un lanceur a sec vaut encore. La faire valoir zero rendrait le
     * guerisseur immortel-mais-inoffensif au lieu de le rendre lent, ce qui
     * n'est pas la meme mesure.
     */
    public function expectedFallbackDamagePerTurn(): float
    {
        return $this->expectedDamageOf($this->fallbackDamage);
    }

    private function expectedDamageOf(int $damage): float
    {
        $hit = max(0.0, min(100.0, $this->hitRate)) / 100.0;
        $critical = max(0.0, min(100.0, $this->criticalRate)) / 100.0;

        return $damage * $hit * (1.0 + $critical * ($this->criticalPower - 1.0));
    }

    /**
     * Ce que ce personnage rend de vie en fin de tour (levier `recovery`).
     */
    public function recoveryPerTurnPoints(): float
    {
        return $this->maxLife * max(0.0, $this->recoveryPerTurn) / 100.0;
    }

    /**
     * La case de la grille — la meme lecture que `ReferenceBuild::cell()`.
     */
    public function cell(): string
    {
        return sprintf('%s x %s', $this->role->value, $this->register->value);
    }

    /**
     * Cette ressource se vide-t-elle au point d'arreter le personnage ?
     *
     * Un registre sans ressource (la melee paie en tours, ARC-04a) rend `false`
     * : il ne peut pas tomber a sec, et lui chercher une limite de ressource
     * ferait dire au simulateur qu'un soldat s'arrete de frapper.
     */
    public function spendsAResource(): bool
    {
        return $this->maxResource > 0 && $this->gestureCost > 0;
    }
}
