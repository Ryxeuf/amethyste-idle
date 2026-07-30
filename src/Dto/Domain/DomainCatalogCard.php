<?php

namespace App\Dto\Domain;

use App\Entity\Game\Domain;
use App\Enum\CombatRegister;

/**
 * Une case du catalogue des arbres (ONB-09).
 *
 * Ce que ce type **porte** est exactement ce que GAME_ONBOARDING § 6.2 autorise
 * le catalogue a dire. Ce qu'il ne porte pas — nœuds, valeurs, prerequis,
 * premier nœud, specialisation terminale — n'est pas une omission d'ecriture :
 * le type n'a aucune propriete pour l'accueillir, donc aucun gabarit ne peut
 * l'afficher par accident.
 */
final class DomainCatalogCard
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly ?string $element,
        public readonly ?CombatRegister $register,
        public readonly string $teaches,
        public readonly string $equips,
        // Le parchemin qui l'ouvre. `null` designe un arbre inatteignable —
        // `DomainParchmentContractTest` interdit ce cas dans les donnees
        // livrees, mais le type doit pouvoir le representer pour qu'un ecran
        // le dise plutot que de planter.
        public readonly ?string $parchmentName,
        public readonly ?int $parchmentPrice,
        // Ouvert pour le personnage courant. Purement informatif : le
        // catalogue affiche exactement la meme chose dans les deux cas.
        public readonly bool $opened,
    ) {
    }

    /**
     * La famille du catalogue : les trois registres de combat, ou le metier.
     *
     * Le catalogue se lit comme la roue element x registre (§ 6.2) ; les
     * recoltes et les artisanats n'ont pas de registre (DOM-01, `null` veut
     * dire « hors combat ») et forment leurs propres colonnes.
     */
    public function family(): string
    {
        return $this->register?->value ?? 'craft';
    }

    public static function fromDomain(
        Domain $domain,
        string $teaches,
        string $equips,
        ?string $parchmentName,
        ?int $parchmentPrice,
        bool $opened,
    ): self {
        return new self(
            (int) $domain->getId(),
            $domain->getTitle(),
            $domain->getElement(),
            $domain->getRegister(),
            $teaches,
            $equips,
            $parchmentName,
            $parchmentPrice,
            $opened,
        );
    }
}
