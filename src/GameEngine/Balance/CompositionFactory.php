<?php

namespace App\GameEngine\Balance;

use App\Enum\DomainRole;

/**
 * Les quatre compositions de groupe du § 9 octies (ARC-17c-d).
 *
 * *Avec ou sans tank, croise avec ou sans soigneur* — et le croisement est tout
 * l'objet : c'est la seule facon de repondre a la question du § 7 bis, ***aucun
 * role n'est necessaire***. Mesurer une seule composition ne dirait rien ; c'est
 * l'ecart entre les quatre qui dit si un role est un gout ou un peage.
 *
 * **Les compositions sont derivees, jamais ecrites.** La regle est la meme que
 * pour les builds (ARC-17c-a) : une liste en dur se perimerait au premier arbre
 * converti par ARC-08. On tire donc chaque place dans les builds disponibles, et
 * la place qu'on retire est **remplacee par un assaut** — pas laissee vide. Un
 * groupe de trois contre une rencontre calibree pour quatre mesurerait le nombre
 * de membres et non la composition.
 */
final class CompositionFactory
{
    /** La taille d'un groupe de donjon (`GroupDungeonRun`). */
    public const GROUP_SIZE = 4;

    public function __construct(
        private readonly ReferenceBuildFactory $buildFactory,
        private readonly ReferenceCharacterFactory $characterFactory,
    ) {
    }

    /**
     * Les quatre compositions, chacune sous son libelle.
     *
     * Une composition manque quand aucun build ne tient l'une de ses places —
     * elle est alors **absente de la table** plutot que remplacee en silence.
     * ARC-08 la fera apparaitre.
     *
     * @return array<string, list<ReferenceCharacter>>
     */
    public function all(): array
    {
        $byRole = $this->charactersByRole();

        $compositions = [];
        foreach ([[true, true], [false, true], [true, false], [false, false]] as [$withTank, $withHealer]) {
            $label = sprintf('%s tank / %s soigneur', $withTank ? 'avec' : 'sans', $withHealer ? 'avec' : 'sans');

            $group = $this->compose($byRole, $withTank, $withHealer);
            if (null !== $group) {
                $compositions[$label] = $group;
            }
        }

        return $compositions;
    }

    /**
     * @param array<string, list<ReferenceCharacter>> $byRole
     *
     * @return list<ReferenceCharacter>|null
     */
    private function compose(array $byRole, bool $withTank, bool $withHealer): ?array
    {
        $assault = $byRole[DomainRole::Assault->value] ?? [];
        $control = $byRole[DomainRole::Control->value] ?? [];

        if ([] === $assault || [] === $control) {
            return null;
        }

        $wanted = [
            $withTank ? DomainRole::Bulwark->value : DomainRole::Assault->value,
            $withHealer ? DomainRole::Upkeep->value : DomainRole::Assault->value,
            DomainRole::Assault->value,
            DomainRole::Control->value,
        ];

        $group = [];
        $taken = [];
        foreach ($wanted as $role) {
            $pool = $byRole[$role] ?? [];
            if ([] === $pool) {
                return null;
            }

            // On prend un build different a chaque place quand le vivier le
            // permet : quatre fois le meme personnage mesurerait un solo joue
            // quatre fois.
            $index = ($taken[$role] ?? 0) % \count($pool);
            $taken[$role] = ($taken[$role] ?? 0) + 1;
            $group[] = $pool[$index];
        }

        return $group;
    }

    /**
     * @return array<string, list<ReferenceCharacter>>
     */
    private function charactersByRole(): array
    {
        $byRole = [];
        foreach ($this->buildFactory->all() as $build) {
            $byRole[$build->role->value][] = $this->characterFactory->of($build);
        }

        return $byRole;
    }
}
