<?php

namespace App\GameEngine\Gear;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\GameEngine\Progression\EquipmentPortCatalog;
use App\Helper\GearHelper;

/**
 * Ce qu'un personnage porte, lu sur l'echelle de port (ARC-19).
 *
 * La question « de quelle famille est cette piece ? » avait deja un lecteur —
 * `BuildConditionEvaluator` (ARC-16b) —, mais il la posait **pour lui seul** :
 * la regle vivait dans une methode privee, hors de portee de tout autre
 * appelant. La mitigation d'armure pose exactement la meme question, et
 * l'ecrire une seconde fois aurait rejoue le defaut nomme par ARC-08a :
 * ***une regle recopiee derive de son original en silence***.
 *
 * La reponse vient de l'echelle de port et d'elle seule : **une piece est de la
 * famille dont elle exige un echelon**. Une piece qui n'en exige aucun — le kit
 * de depart, le palier 1 des armures — n'a pas de famille : *la doctrine gate
 * l'evolution, jamais l'arrivee*, et ce qui se porte sans rien appris ne
 * temoigne d'aucun choix de build.
 */
class WornPieceReader
{
    /**
     * Les emplacements herites qui designent le meme endroit du corps.
     *
     * `legs`/`leg` et `feet`/`foot` cohabitent en donnees depuis avant la
     * grille d'OBJ-03. Les normaliser ici plutot que dans chaque appelant
     * evite qu'une paire de bottes compte pour un emplacement chez l'un et
     * pour rien chez l'autre.
     *
     * @var array<string, string>
     */
    private const SLOT_ALIASES = ['legs' => 'leg', 'feet' => 'foot'];

    public function __construct(
        private readonly EquipmentPortCatalog $portCatalog,
        private readonly GearHelper $gearHelper,
    ) {
    }

    /**
     * La famille d'une piece — celle dont elle exige un echelon de port.
     */
    public function familyOf(PlayerItem $item): ?string
    {
        foreach ($item->getGenericItem()->getRequirements() as $requirement) {
            $family = $this->portCatalog->familyOfPortSkill($requirement->getSlug());
            if ($family !== null) {
                return $family;
            }
        }

        return null;
    }

    /**
     * Les pieces portees, tous emplacements confondus.
     *
     * @return iterable<PlayerItem>
     */
    public function equippedItems(Player $player): iterable
    {
        foreach ($player->getInventories() as $inventory) {
            if (!$inventory->isBag()) {
                continue;
            }

            foreach ($inventory->getItems() as $playerItem) {
                if ($this->gearHelper->isEquipped($playerItem)) {
                    yield $playerItem;
                }
            }
        }
    }

    /**
     * La ligne d'armure de chaque emplacement d'armure occupe.
     *
     * Une entree par piece portee **sur un emplacement d'armure**, et `null`
     * pour celles qui n'appartiennent a aucune famille : la moyenne de
     * `ArmorMitigationLaw` a besoin de savoir qu'un emplacement est occupe sans
     * rien mitiger, ce qu'une liste filtree ne dirait pas.
     *
     * @param list<string> $armorSlots les emplacements qui comptent
     *
     * @return list<string|null>
     */
    public function armorLinesWornBy(Player $player, array $armorSlots): array
    {
        $families = $this->portCatalog->families();
        $lines = [];

        foreach ($this->equippedItems($player) as $item) {
            $slot = (string) $item->getGenericItem()->getGearLocation();
            $slot = self::SLOT_ALIASES[$slot] ?? $slot;

            if (!\in_array($slot, $armorSlots, true)) {
                continue;
            }

            $family = $this->familyOf($item);
            $lines[] = ($family !== null && ($families[$family]['line'] ?? null) === 'armor') ? $family : null;
        }

        return $lines;
    }
}
