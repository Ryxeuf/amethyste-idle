<?php

namespace App\GameEngine\Balance;

use App\Enum\CombatRegister;
use App\Enum\DomainRole;

/**
 * Un build de reference : ce qu'un personnage porte quand son arbre est fini
 * (ARC-17c).
 *
 * GAME_ARCHETYPES § 9 sexies. Le simulateur doit comparer les fonctions sur la
 * meme ligne, et pour cela il lui faut des personnages. Le canon previent
 * comment **ne pas** les obtenir :
 *
 * > **Ecrits en dur, ils se perimeraient au premier changement de fixture — et
 * > c'est exactement ce qu'on cherche a detecter.**
 *
 * Un build est donc **derive** d'un arbre reel : sa fonction et son registre
 * viennent du domaine, ses leviers de ses nœuds, ses gestes de ses accords. Rien
 * n'est recopie ; deplacer un nœud deplace le build.
 *
 * **Une branche fait un build, pas un arbre.** La fourche (ARC-14) existe
 * precisement pour que deux personnages du meme arbre ne soient pas le meme
 * personnage : les mesurer ensemble reviendrait a moyenner ce que la fourche
 * separe, et a rendre invisible le seul choix que l'arbre offre.
 */
final readonly class ReferenceBuild
{
    /**
     * @param array<string, int> $leverBudget points de budget par levier, teinte comprise
     * @param list<string>       $accords     slugs des sorts que l'arbre ouvre
     */
    public function __construct(
        public string $domainTitle,
        public string $treeKey,
        public string $branch,
        public string $branchLabel,
        public DomainRole $role,
        public CombatRegister $register,
        public string $element,
        public array $leverBudget,
        public array $accords,
    ) {
    }

    /**
     * Le nom sous lequel la table croisee le lit — *l'arbre et sa branche*.
     *
     * Jamais le seul nom d'arbre : deux lignes qui porteraient « Pyromancien »
     * ne se distingueraient pas, et c'est justement leur difference qu'on
     * mesure.
     */
    public function label(): string
    {
        return sprintf('%s / %s', $this->domainTitle, $this->branchLabel);
    }

    /**
     * Ce que ce build depense en tout — il doit valoir le budget de l'arbre.
     */
    public function totalBudget(): int
    {
        return array_sum($this->leverBudget);
    }

    /**
     * La case de la grille que ce build occupe (§ 5, les trois axes).
     */
    public function cell(): string
    {
        return sprintf('%s x %s', $this->role->value, $this->register->value);
    }
}
