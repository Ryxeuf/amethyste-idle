<?php

namespace App\GameEngine\Progression;

use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use App\Enum\CombatLever;
use App\Enum\DomainRole;

/**
 * Les regles du pacte qui portent sur l'arbre, pas sur le nœud (ARC-15).
 *
 * GAME_ARCHETYPES § 6.5. `SkillLeverReader` tient ce qu'un nœud peut dire de
 * lui-meme — le cran, le levier, le poids. Ce qui reste ne se voit qu'en
 * regardant l'arbre entier, et c'est la partie facile a degenerer :
 *
 *  - **un seul pacte par arbre**, et **au palier 3 seulement** — c'est une
 *    signature, pas un outil ;
 *  - **le malus est hors de la palette de la fonction** — l'assaut paie en
 *    survie, l'encaisse paie en degats ; un assaut qui paierait en `pierce`
 *    echangerait de la monnaie contre elle-meme ;
 *  - **le nœud de pacte est une feuille** : aucun autre nœud ne l'exige. Un
 *    arbre ou le pacte est sur le chemin du capstone n'offre pas un choix, il
 *    pose un peage.
 *
 * La classe **rend les manquements** plutot que de lever : un arbre se juge en
 * bloc, et une exception au premier ecart cacherait les suivants.
 */
class PactRule
{
    /**
     * Le palier auquel un pacte se pose, et le seul.
     *
     * Le pacte **remplace** un passif de fourche (tranche le 2026-08-01) : un
     * dix-neuvieme nœud aurait donne a un arbre a pacte plus de contenu que
     * les vingt-trois autres, et plus rien n'aurait ete comparable.
     */
    public const TIER = 3;

    public function __construct(
        private readonly SkillLeverReader $reader,
        private readonly DomainRoleDefinitionLoader $roles,
    ) {
    }

    /**
     * Ce qui cloche dans les pactes de cet arbre — vide si tout tient.
     *
     * @param iterable<Skill> $skills les nœuds de l'arbre
     *
     * @return list<string>
     */
    public function violationsOf(Domain $domain, iterable $skills): array
    {
        $violations = [];
        $pactNodes = [];

        foreach ($skills as $skill) {
            foreach ($this->reader->grantsOf($skill) as $grant) {
                if (!$grant->isPact()) {
                    continue;
                }

                $pactNodes[] = $skill;

                $pact = $grant->pact;
                if ($pact !== null && $this->isInPalette($domain, $pact->lever)) {
                    $role = $domain->getRole();
                    $violations[] = sprintf(
                        '%s : le malus porte sur "%s", qui est dans la palette de %s — un arbre ne paie pas dans sa propre monnaie.',
                        $skill->getSlug(),
                        $pact->lever->value,
                        $role instanceof DomainRole ? $role->value : 'sa fonction',
                    );
                }

                if ($this->isRequiredByAnother($skill, $skills)) {
                    $violations[] = sprintf(
                        '%s : un autre nœud l\'exige. Un pacte sur le chemin du capstone n\'offre pas un choix, il pose un peage.',
                        $skill->getSlug(),
                    );
                }
            }
        }

        if (\count($pactNodes) > 1) {
            $violations[] = sprintf(
                '%s porte %d pactes. Un seul par arbre : c\'est une signature, pas un outil.',
                $domain->getTitle(),
                \count($pactNodes),
            );
        }

        return $violations;
    }

    /**
     * Ce levier est-il dans la palette de la fonction de cet arbre ?
     *
     * Le principal **et** les secondaires : payer dans l'un ou l'autre revient
     * au meme, puisque les deux sont ce que la fonction achete.
     */
    private function isInPalette(Domain $domain, CombatLever $lever): bool
    {
        $role = $domain->getRole();
        if (!$role instanceof DomainRole) {
            return false;
        }

        $palette = $this->roles->load()['roles'][$role->value] ?? null;
        if ($palette === null) {
            return false;
        }

        return $lever->value === $palette['primary'] || \in_array($lever->value, $palette['secondary'], true);
    }

    /**
     * @param iterable<Skill> $skills
     */
    private function isRequiredByAnother(Skill $pactNode, iterable $skills): bool
    {
        foreach ($skills as $other) {
            if ($other === $pactNode) {
                continue;
            }

            foreach ($other->getRequirements() as $requirement) {
                if ($requirement === $pactNode) {
                    return true;
                }
            }
        }

        return false;
    }
}
