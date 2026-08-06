<?php

namespace App\GameEngine\Progression;

use App\Entity\App\Player;
use App\Entity\App\PlayerCombatBranch;
use App\Entity\Game\Domain;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Choisir sa branche dans un arbre de combat, et en changer (ARC-14b).
 *
 * GAME_ARCHETYPES § 6.1 bis. Le catalogue (ARC-14a) dit **quelles** branches
 * existent ; ce service dit **ce qu'un joueur en a fait**, et il est le seul a
 * l'ecrire — la meme discipline que `PurityPricer` ou `EmailVerificationGate`.
 *
 * **Ce qu'il refuse, et pourquoi ce sont les bons refus** :
 *
 *  - une branche que le catalogue ne connait pas — sinon un nœud conditionne a
 *    une chaine mal orthographiee serait **a jamais inapprenable**, et le
 *    joueur chercherait un prerequis qui n'existe pas ;
 *  - un arbre sans fourche — les vingt arbres qu'ARC-08 doit encore forker
 *    n'ont rien a trancher, et laisser choisir une branche qui ne mene nulle
 *    part serait pire que refuser.
 *
 * **Ce qu'il ne refuse pas** : changer d'avis. Le respec de branche existe
 * (DOM-04) et se paie ; ce service en tient la trace, jamais le prix — un
 * renoncement qu'on peut defaire sans trace n'en est pas un, mais un
 * renoncement qu'on ne peut pas defaire du tout est un piege.
 */
class CombatBranchManager
{
    public function __construct(
        private readonly CombatBranchCatalog $catalog,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Ce joueur peut-il prendre cette branche dans cet arbre ?
     */
    public function canChoose(Domain $domain, string $branch): bool
    {
        $tree = $this->catalog->keyForLabel($domain->getTitle());

        return $tree !== null && $this->catalog->hasBranch($tree, $branch);
    }

    /**
     * Poser — ou deplacer — le choix de branche.
     *
     * Retourne `null` si la branche n'existe pas : le refus est une reponse,
     * jamais une exception. Un ecran doit pouvoir dire « cette branche n'existe
     * pas » sans que la requete tombe.
     */
    public function choose(Player $player, Domain $domain, string $branch): ?PlayerCombatBranch
    {
        if (!$this->canChoose($domain, $branch)) {
            return null;
        }

        $choice = $player->getCombatBranchFor($domain);
        if ($choice === null) {
            $choice = new PlayerCombatBranch($player, $domain, $branch);
            $player->addCombatBranch($choice);
        } else {
            $choice->switchTo($branch);
        }

        $this->entityManager->persist($choice);
        $this->entityManager->flush();

        return $choice;
    }

    /**
     * Ce a quoi le joueur renonce en prenant cette branche.
     *
     * L'ecran doit le dire **avant** le choix, et pas seulement nommer ce qu'on
     * gagne : une fourche dont on ne lit qu'un cote n'est pas un choix, c'est
     * un bouton. Le geste de l'autre branche est ce qui rend le renoncement
     * concret — mesure au § 9 bis, ce sont les accords qui separent les
     * branches, pas les passifs.
     *
     * @return array{label: string, accord: string}|null
     */
    public function forgoneBy(Domain $domain, string $branch): ?array
    {
        $tree = $this->catalog->keyForLabel($domain->getTitle());
        if ($tree === null) {
            return null;
        }

        foreach ($this->catalog->branchesOf($tree) as $key => $other) {
            if ($key !== $branch) {
                return ['label' => $other['label'], 'accord' => $other['accord']];
            }
        }

        return null;
    }
}
