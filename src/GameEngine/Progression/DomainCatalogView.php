<?php

namespace App\GameEngine\Progression;

use App\Dto\Domain\DomainCatalogCard;
use App\Entity\App\Player;
use App\Entity\Game\Domain;

/**
 * Assemble le catalogue public des arbres (ONB-09).
 *
 * Le catalogue est **complet et identique pour tout le monde** : il ne depend
 * ni du peuple, ni de la progression, ni de ce qui est deja ouvert. Le seul
 * effet du personnage courant est un marqueur « deja ouvert » — informatif,
 * jamais filtrant. Un catalogue qui se retrecirait ferait exactement ce que le
 * cadrage refuse : orienter.
 *
 * **Le catalogue omet, il ne ment pas.** Il est complet pour tout ce qui
 * s'atteint par le jeu ordinaire ; les arbres retrouves (DOM-10) n'y figurent
 * pas et n'existent, pour le joueur, qu'apres la rencontre. C'est pourquoi rien
 * ici n'affiche de compte total : un « 36 arbres » grave dans l'ecran
 * deviendrait un mensonge le jour ou un 37e apparaitrait hors liste.
 */
class DomainCatalogView
{
    public function __construct(
        private readonly DomainCatalog $catalog,
        private readonly DomainCatalogDescriptions $descriptions,
        private readonly DomainAccessManager $accessManager,
    ) {
    }

    /**
     * @return DomainCatalogCard[]
     */
    public function cards(?Player $player = null): array
    {
        $cards = [];
        foreach ($this->catalog->all() as $domain) {
            $cards[] = $this->card($domain, $player);
        }

        return $cards;
    }

    public function card(Domain $domain, ?Player $player = null): DomainCatalogCard
    {
        $entry = $this->descriptions->forSlug($domain->getSlug());
        $parchment = $this->catalog->parchmentFor($domain);

        return DomainCatalogCard::fromDomain(
            $domain,
            // Un arbre sans entree reste visible, avec une phrase neutre. Le
            // faire disparaitre serait pire que le decrire mal : il cesserait
            // d'exister pour le joueur, alors qu'il s'ouvre toujours.
            $entry['teaches'] ?? 'Un savoir dont le détail se découvre en ouvrant l\'arbre.',
            $entry['equips'] ?? 'À découvrir.',
            $parchment?->getName(),
            $parchment?->getPrice(),
            $player !== null && $this->accessManager->isOpen($player, $domain),
        );
    }

    /**
     * Les cartes regroupees par element, dans l'ordre de la roue.
     *
     * L'ordre est fixe et non alphabetique : la roue est une **lecture**, et
     * ranger le feu apres l'eau parce que « E vient avant F » la casserait.
     * Les arbres hors combat (recolte, artisanat) n'ont pas de registre et
     * suivent, groupes par leur element de metier.
     *
     * @return array<string, DomainCatalogCard[]>
     */
    public function cardsByElement(?Player $player = null): array
    {
        $order = ['fire', 'water', 'air', 'earth', 'metal', 'beast', 'light', 'dark', 'wood'];

        $grouped = [];
        foreach ($order as $element) {
            $grouped[$element] = [];
        }

        foreach ($this->cards($player) as $card) {
            // `none` et pas `other` : c'est la cle que le systeme de design
            // utilise deja pour la pastille sans element (`ds-elem-none`).
            $element = $card->element ?? 'none';
            $grouped[$element][] = $card;
        }

        return array_filter($grouped, static fn (array $cards): bool => $cards !== []);
    }
}
