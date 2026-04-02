<?php

declare(strict_types=1);

namespace App\Service\Avatar;

use App\Entity\App\Player;
use App\Entity\Game\Item;
use App\Entity\App\PlayerItem;

/**
 * Builder MVP adapté à amethyste-idle.
 *
 * Objectif :
 * - partir du Player
 * - récupérer son apparence JSON
 * - récupérer les items équipés visibles
 * - construire un payload simple pour /api/map/entities
 *
 * À brancher ensuite sur ton vrai système d'équipement (bitmask / slots).
 */
final class PlayerAvatarPayloadBuilder
{
    public function __construct(
        private readonly AvatarHashGenerator $hashGenerator,
    ) {
    }

    public function build(Player $player, array $equippedItems = []): array
    {
        $appearance = $this->extractAppearance($player);
        $layers = $this->buildVisibleLayers($appearance, $equippedItems);

        $hash = $this->hashGenerator->generate(
            $appearance,
            array_map(
                static fn (array $layer): string => (string) ($layer['sheet'] ?? ''),
                $layers,
            ),
        );

        return [
            'renderMode' => 'avatar',
            'avatarHash' => $hash,
            'avatar' => [
                'baseSheet' => $appearance['bodySheet'] ?? '/assets/styles/images/avatar/base/human_m_light_01.png',
                'layers' => $layers,
            ],
        ];
    }

    private function extractAppearance(Player $player): array
    {
        /**
         * MVP :
         * une fois les colonnes ajoutées sur Player, remplacer ce bloc
         * par un vrai getAvatarAppearance()/getAvatarHash()/etc.
         */
        return [
            'bodySheet' => '/assets/styles/images/avatar/base/human_m_light_01.png',
            'hairSheet' => '/assets/styles/images/avatar/hair/short_01.png',
            'hairTint' => 0xd6b25e,
            'beardSheet' => null,
            'beardTint' => null,
            'faceMarkSheet' => null,
        ];
    }

    /**
     * @param PlayerItem[] $equippedItems
     */
    private function buildVisibleLayers(array $appearance, array $equippedItems): array
    {
        $layers = [];

        if (!empty($appearance['hairSheet'])) {
            $layers[] = [
                'sheet' => $appearance['hairSheet'],
                'tint' => $appearance['hairTint'] ?? null,
            ];
        }

        if (!empty($appearance['beardSheet'])) {
            $layers[] = [
                'sheet' => $appearance['beardSheet'],
                'tint' => $appearance['beardTint'] ?? null,
            ];
        }

        if (!empty($appearance['faceMarkSheet'])) {
            $layers[] = [
                'sheet' => $appearance['faceMarkSheet'],
            ];
        }

        foreach ($equippedItems as $playerItem) {
            $genericItem = $playerItem->getGenericItem();
            if (!$genericItem->isGear() && !$genericItem->isTool()) {
                continue;
            }

            $sheet = $this->resolveSheetFromItem($genericItem);
            if ($sheet === null) {
                continue;
            }

            $layers[] = [
                'sheet' => $sheet,
            ];
        }

        return $layers;
    }

    private function resolveSheetFromItem(Item $item): ?string
    {
        $gearLocation = $item->getGearLocation();

        return match ($gearLocation) {
            Item::GEAR_LOCATION_HEAD => '/assets/styles/images/avatar/gear/head/iron_helm_01.png',
            Item::GEAR_LOCATION_CHEST => '/assets/styles/images/avatar/gear/chest/iron_armor_01.png',
            Item::GEAR_LOCATION_BELT => '/assets/styles/images/avatar/gear/belt/belt_01.png',
            Item::GEAR_LOCATION_LEG => '/assets/styles/images/avatar/gear/leg/plate_legs_01.png',
            Item::GEAR_LOCATION_FOOT => '/assets/styles/images/avatar/gear/foot/boots_01.png',
            Item::GEAR_LOCATION_SHOULDER => '/assets/styles/images/avatar/gear/shoulder/shoulder_iron_01.png',
            Item::GEAR_LOCATION_MAIN_WEAPON => '/assets/styles/images/avatar/gear/main_weapon/sword_iron_01.png',
            Item::GEAR_LOCATION_SIDE_WEAPON => '/assets/styles/images/avatar/gear/side_weapon/shield_wood_01.png',
            default => null,
        };
    }
}
