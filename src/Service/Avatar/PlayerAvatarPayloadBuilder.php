<?php

declare(strict_types=1);

namespace App\Service\Avatar;

use App\Entity\App\Player;
use App\Helper\GearHelper;

class PlayerAvatarPayloadBuilder
{
    private const AVATAR_BASE_PATH = '/assets/styles/images/avatar';

    private const GEAR_LAYER_ORDER = [
        'foot',
        'leg',
        'chest',
        'belt',
        'shoulder',
        'hand',
        'side_weapon',
        'main_weapon',
    ];

    public function __construct(
        private readonly AvatarHashGenerator $hashGenerator,
        private readonly GearHelper $gearHelper,
        private readonly ItemAvatarSheetResolver $itemAvatarSheetResolver,
    ) {
    }

    /**
     * @return array{renderMode: string, avatarHash: string, avatar: array{baseSheet: string, layers: list<array{sheet: string, tint?: int}>}}|null
     */
    public function build(Player $player): ?array
    {
        if (!$player->hasAvatar()) {
            return null;
        }

        $appearance = $this->extractAppearance($player);
        $layers = $this->buildVisibleLayers($appearance);

        $hash = $this->hashGenerator->generate(
            $appearance,
            array_map(
                static fn (array $layer): string => $layer['sheet'],
                $layers,
            ),
        );

        return [
            'renderMode' => 'avatar',
            'avatarHash' => $hash,
            'avatar' => [
                'baseSheet' => self::AVATAR_BASE_PATH . '/body/' . $appearance['body'] . '.png',
                'layers' => $layers,
            ],
        ];
    }

    /**
     * @return array{renderMode: string, avatarHash: string, avatar: array{baseSheet: string, layers: list<array{sheet: string, tint?: int}>}}|null
     */
    public function buildForMapEntity(Player $player): ?array
    {
        if (!$player->hasAvatar()) {
            return null;
        }

        $appearance = $this->extractAppearance($player);
        $layers = $this->buildAppearanceLayers($appearance);

        $hash = $this->hashGenerator->generate(
            $appearance,
            array_map(
                static fn (array $layer): string => $layer['sheet'],
                $layers,
            ),
            'avatar-map-v1',
        );

        return [
            'renderMode' => 'avatar',
            'avatarHash' => $hash,
            'avatar' => [
                'baseSheet' => self::AVATAR_BASE_PATH . '/body/' . $appearance['body'] . '.png',
                'layers' => $layers,
            ],
        ];
    }

    /**
     * @param array{body: string, hair: string|null, hairColor: string|null, beard: string|null, beardColor: string|null, faceMark: string|null} $appearance
     *
     * @return list<array{sheet: string, tint?: int}>
     */
    private function buildAppearanceLayers(array $appearance): array
    {
        $layers = [];

        if (!empty($appearance['hair'])) {
            $layer = ['sheet' => self::AVATAR_BASE_PATH . '/hair/' . $appearance['hair'] . '.png'];
            if (!empty($appearance['hairColor'])) {
                $layer['tint'] = self::hexToInt($appearance['hairColor']);
            }
            $layers[] = $layer;
        }

        if (!empty($appearance['beard'])) {
            $layer = ['sheet' => self::AVATAR_BASE_PATH . '/beard/' . $appearance['beard'] . '.png'];
            if (!empty($appearance['beardColor'])) {
                $layer['tint'] = self::hexToInt($appearance['beardColor']);
            }
            $layers[] = $layer;
        }

        if (!empty($appearance['faceMark'])) {
            $layers[] = ['sheet' => self::AVATAR_BASE_PATH . '/facemark/' . $appearance['faceMark'] . '.png'];
        }

        return $layers;
    }

    /**
     * @return array{body: string, hair: string|null, hairColor: string|null, beard: string|null, beardColor: string|null, faceMark: string|null}
     */
    private function extractAppearance(Player $player): array
    {
        $raw = $player->getAvatarAppearance() ?? [];

        return [
            'body' => $raw['body'] ?? 'human_m_light',
            'hair' => $raw['hair'] ?? null,
            'hairColor' => $raw['hairColor'] ?? null,
            'beard' => $raw['beard'] ?? null,
            'beardColor' => $raw['beardColor'] ?? null,
            'faceMark' => $raw['faceMark'] ?? null,
        ];
    }

    /**
     * @param array{body: string, hair: string|null, hairColor: string|null, beard: string|null, beardColor: string|null, faceMark: string|null} $appearance
     *
     * @return list<array{sheet: string, tint?: int}>
     */
    private function buildVisibleLayers(array $appearance): array
    {
        $layers = [];

        foreach (self::GEAR_LAYER_ORDER as $location) {
            $layer = $this->getGearLayer($location);
            if ($layer !== null) {
                $layers[] = $layer;
            }
        }

        if (!empty($appearance['hair'])) {
            $layer = ['sheet' => self::AVATAR_BASE_PATH . '/hair/' . $appearance['hair'] . '.png'];
            if (!empty($appearance['hairColor'])) {
                $layer['tint'] = self::hexToInt($appearance['hairColor']);
            }
            $layers[] = $layer;
        }

        if (!empty($appearance['beard'])) {
            $layer = ['sheet' => self::AVATAR_BASE_PATH . '/beard/' . $appearance['beard'] . '.png'];
            if (!empty($appearance['beardColor'])) {
                $layer['tint'] = self::hexToInt($appearance['beardColor']);
            }
            $layers[] = $layer;
        }

        if (!empty($appearance['faceMark'])) {
            $layers[] = ['sheet' => self::AVATAR_BASE_PATH . '/facemark/' . $appearance['faceMark'] . '.png'];
        }

        $headLayer = $this->getGearLayer('head');
        if ($headLayer !== null) {
            $layers[] = $headLayer;
        }

        return $layers;
    }

    /**
     * @return array{sheet: string}|null
     */
    private function getGearLayer(string $location): ?array
    {
        $equipped = $this->gearHelper->getEquippedGearByLocation($location);
        if ($equipped === null) {
            return null;
        }

        $sheet = $this->itemAvatarSheetResolver->resolve($equipped->getGenericItem());
        if ($sheet === null) {
            return null;
        }

        return ['sheet' => $sheet];
    }

    private static function hexToInt(string $hex): int
    {
        return (int) hexdec(ltrim($hex, '#'));
    }
}
