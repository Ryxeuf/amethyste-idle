<?php

declare(strict_types=1);

namespace App\Service\Avatar;

use App\Entity\Game\Item;

/**
 * Derive le chemin du sprite sheet avatar (format 8x8) d'un item d'equipement.
 *
 * Priorite :
 *   1. `Item::getAvatarSheet()` s'il est defini (override explicite en DB/fixtures)
 *   2. Convention `/{avatar_base}/{gear_directory}/{slug}.png` selon `gearLocation`
 *   3. null si l'item ne produit pas de layer visible (neck, finger, materia, resource...)
 */
final class ItemAvatarSheetResolver
{
    private const AVATAR_BASE_PATH = '/assets/styles/images/avatar';

    private const GEAR_LOCATION_DIRECTORY = [
        Item::GEAR_LOCATION_HEAD => 'head',
        Item::GEAR_LOCATION_CHEST => 'chest',
        Item::GEAR_LOCATION_LEG => 'leg',
        Item::GEAR_LOCATION_FOOT => 'foot',
        Item::GEAR_LOCATION_HAND => 'hand',
        Item::GEAR_LOCATION_BELT => 'belt',
        Item::GEAR_LOCATION_SHOULDER => 'shoulder',
        Item::GEAR_LOCATION_MAIN_WEAPON => 'weapon_main',
        Item::GEAR_LOCATION_SIDE_WEAPON => 'weapon_side',
    ];

    public function resolve(Item $item): ?string
    {
        $explicit = $item->getAvatarSheet();
        if ($explicit !== null && $explicit !== '') {
            return $explicit;
        }

        if (!$item->isGear()) {
            return null;
        }

        $location = $item->getGearLocation();
        if ($location === null || !isset(self::GEAR_LOCATION_DIRECTORY[$location])) {
            return null;
        }

        $slug = $item->getSlug();
        if ($slug === null || $slug === '') {
            return null;
        }

        return sprintf(
            '%s/%s/%s.png',
            self::AVATAR_BASE_PATH,
            self::GEAR_LOCATION_DIRECTORY[$location],
            $slug,
        );
    }
}
