<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Domain;
use App\Entity\Game\Item;
use App\Enum\CombatRegister;
use App\Enum\Element;
use App\GameEngine\Gear\WornPieceReader;
use App\GameEngine\Progression\EquipmentPortCatalog;
use App\GameEngine\Progression\SynergyCalculator;

/**
 * Les domaines qu'un build exprime (DOM-02).
 *
 * GAME_DOMAINS § 3 : « un domaine n'est actif en combat que si le build porte
 * une de ses sources — une materia de son element pour les ecoles de sort, une
 * arme de son registre pour les ecoles d'arme ».
 *
 * **La borne est materielle, jamais reglementaire.** Rien n'interdit de monter
 * les trente-six arbres ; ce qu'on porte decide de ce qui s'exprime. Un joueur
 * ne lit jamais un « interdit » : le monde repond a son equipement. C'est la
 * difference entre ce systeme et des classes, et elle tient a ce que la reponse
 * se calcule ici plutot que de se declarer quelque part.
 *
 * **Deux sources, une par famille de registre**, et ce n'est pas une commodite :
 * une ecole de sort s'exprime par la matiere qu'on sertit, une ecole d'arme par
 * l'arme qu'on tient. Demander les deux a chacune aurait rendu le pyromancien
 * dependant d'un baton qui n'existe pas dans son arbre.
 *
 * **Ce que ce service ne borne jamais** : les accords. Une matiere apprise reste
 * apprise, meme quand rien ne la porte — « le savoir n'est jamais borne, seule
 * l'expression l'est » (§ 1). `CombatSkillResolver::getUnlockedMateriaSpellSlugs`
 * ne passe donc pas par ici, et un test l'exige.
 */
class BuildDomainResolver
{
    /**
     * @var list<string>
     */
    private const WEAPON_LOCATIONS = [Item::GEAR_LOCATION_MAIN_WEAPON, Item::GEAR_LOCATION_SIDE_WEAPON];

    public function __construct(
        private readonly SynergyCalculator $synergyCalculator,
        private readonly WornPieceReader $wornPieces,
        private readonly EquipmentPortCatalog $portCatalog,
    ) {
    }

    /**
     * Le domaine s'exprime-t-il avec ce que le joueur porte ?
     *
     * **Ou avec ce qu'il porte pour son voisin** : depuis ARC-16, une
     * accointance active de forme `domain_expression` elargit ce qui exprime un
     * domaine — *ce qu'on porte pour l'un parle aussi pour l'autre*. C'est le
     * seul endroit du moteur ou une accointance agit, et elle n'y ajoute aucun
     * chiffre : elle permet a un passif deja paye de s'exprimer dans une tenue
     * qui, sans elle, l'aurait tu.
     */
    public function isActive(Player $player, Domain $domain): bool
    {
        if ($this->expressesItself($player, $domain)) {
            return true;
        }

        foreach ($this->synergyCalculator->getExpressionWidenings($player)[$domain->getId()] ?? [] as $neighbourId) {
            $neighbour = $this->domainById($player, $neighbourId);
            if ($neighbour !== null && $this->expressesItself($player, $neighbour)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ce que le build dit de ce domaine, sans le secours d'une accointance.
     */
    private function expressesItself(Player $player, Domain $domain): bool
    {
        $register = $domain->getRegister();
        if ($register === null) {
            // Un metier n'a pas de source de combat a porter : la question ne le
            // concerne pas, et repondre « non » l'exclurait d'un jeu auquel il ne
            // joue pas.
            return false;
        }

        if ($register === CombatRegister::Spell) {
            $element = $domain->getElement();

            return $element !== null && \in_array($element, $this->carriedElements($player), true);
        }

        return \in_array($register, $this->carriedRegisters($player), true);
    }

    /**
     * Le domaine voisin, retrouve parmi ceux que le joueur pratique.
     *
     * On ne va pas le chercher en base : une accointance n'est active que si le
     * joueur a de l'experience dans **les deux** domaines (le seuil de
     * `SynergyCalculator`), donc le voisin est forcement dans ses
     * `DomainExperience`. Le lire la evite une requete par domaine teste.
     */
    private function domainById(Player $player, int $id): ?Domain
    {
        foreach ($player->getDomainExperiences() as $experience) {
            $domain = $experience->getDomain();
            if ($domain->getId() === $id) {
                return $domain;
            }
        }

        return null;
    }

    /**
     * Les elements que les materia serties apportent.
     *
     * @return list<string>
     */
    public function carriedElements(Player $player): array
    {
        $elements = [];

        foreach ($this->equippedItems($player) as $playerItem) {
            foreach ($playerItem->getSlots() as $slot) {
                $materia = $slot->getItemSet();
                if ($materia === null || !$materia->isMateria()) {
                    continue;
                }

                $element = $materia->getGenericItem()->getElement();
                if ($element !== Element::None) {
                    $elements[] = $element->value;
                }
            }
        }

        return array_values(array_unique($elements));
    }

    /**
     * Les registres que les armes portees apportent (DOM-09).
     *
     * **Le registre d'une arme vient de sa famille, jamais de son domaine.** Il
     * se lisait sur `Item::domain->getRegister()`, c'est-a-dire sur un couple
     * `element x registre` (DOM-01) : le baton etant rattache au paladin
     * (lumiere x **melee**), *porter un baton activait la famille melee* — une
     * arme de lanceur de sorts faisait parler les passifs de corps a corps.
     *
     * C'est le meme defaut que l'echelle de port avait corrige du cote de
     * l'apprentissage, reste ouvert du cote de l'objet ; la reponse est la
     * meme : *c'est l'arme qui fixe le registre*, et la famille le declare
     * (`equipment_ports.yaml`). Une arme sans echelon de port — l'epee de bois
     * du debutant — n'apporte aucun registre, et c'est juste : elle n'appartient
     * a aucune ecole.
     *
     * @return list<CombatRegister>
     */
    public function carriedRegisters(Player $player): array
    {
        $registers = [];

        foreach ($this->equippedItems($player) as $playerItem) {
            $item = $playerItem->getGenericItem();
            if (!\in_array($item->getGearLocation(), self::WEAPON_LOCATIONS, true)) {
                continue;
            }

            $family = $this->wornPieces->familyOf($playerItem);
            $register = $family !== null ? $this->portCatalog->registerOfFamily($family) : null;
            if ($register !== null) {
                $registers[] = $register;
            }
        }

        return array_values(array_unique($registers, \SORT_REGULAR));
    }

    /**
     * @return iterable<PlayerItem>
     */
    private function equippedItems(Player $player): iterable
    {
        foreach ($player->getInventories() as $inventory) {
            foreach ($inventory->getItems() as $playerItem) {
                if ($playerItem->getGear() === 0) {
                    continue;
                }

                yield $playerItem;
            }
        }
    }
}
