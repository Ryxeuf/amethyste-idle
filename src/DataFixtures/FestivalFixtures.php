<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\App\Festival;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class FestivalFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getFestivalsData() as $key => $data) {
            $festival = new Festival();
            $festival->setName($data['name']);
            $festival->setSlug($data['slug']);
            $festival->setDescription($data['description']);
            $festival->setSeason($data['season']);
            $festival->setStartDay($data['start_day']);
            $festival->setEndDay($data['end_day']);
            $festival->setRewards($data['rewards']);
            $festival->setActive(true);
            $festival->setCreatedAt(new \DateTime());
            $festival->setUpdatedAt(new \DateTime());

            if (isset($data['name_translations']) && \is_array($data['name_translations'])) {
                $festival->setNameTranslations($data['name_translations']);
            }
            if (isset($data['description_translations']) && \is_array($data['description_translations'])) {
                $festival->setDescriptionTranslations($data['description_translations']);
            }

            $manager->persist($festival);
            $this->addReference($key, $festival);
        }

        $manager->flush();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getFestivalsData(): array
    {
        return [
            'festival_spring' => [
                'name' => 'Fete du Renouveau',
                'slug' => 'spring-renewal',
                'description' => 'Le printemps eveille la nature. Les recoltes sont plus abondantes et les herbes rares fleurissent dans toutes les regions.',
                'name_translations' => ['en' => 'Renewal Festival'],
                'description_translations' => ['en' => 'Spring awakens nature. Harvests are more bountiful and rare herbs bloom across every region.'],
                'season' => 'spring',
                'start_day' => 5,
                'end_day' => 12,
                'rewards' => [
                    'harvest_bonus' => 1.5,
                    'xp_gathering' => 1.25,
                ],
            ],
            'festival_summer' => [
                'name' => 'Solstice de Flamme',
                'slug' => 'summer-solstice',
                'description' => 'Le soleil est a son zenith. Les guerriers brulent d\'ardeur et les degats de feu sont amplifies.',
                'name_translations' => ['en' => 'Flame Solstice'],
                'description_translations' => ['en' => 'The sun stands at its zenith. Warriors burn with fervor and fire damage is amplified.'],
                'season' => 'summer',
                'start_day' => 10,
                'end_day' => 17,
                'rewards' => [
                    'fire_damage_bonus' => 1.3,
                    'xp_combat' => 1.25,
                ],
            ],
            'festival_autumn' => [
                'name' => 'Moisson des Ames',
                'slug' => 'autumn-harvest',
                'description' => 'Les voiles entre les mondes s\'amenuisent. Les monstres lachent plus de butin et les esprits genereux recompensent les artisans.',
                'name_translations' => ['en' => 'Soul Harvest'],
                'description_translations' => ['en' => 'The veils between worlds grow thin. Monsters drop more loot and generous spirits reward artisans.'],
                'season' => 'autumn',
                'start_day' => 15,
                'end_day' => 22,
                'rewards' => [
                    'drop_bonus' => 1.5,
                    'craft_bonus' => 1.25,
                ],
            ],
            'festival_winter' => [
                'name' => 'Nuit Eternelle',
                'slug' => 'winter-night',
                'description' => 'Le froid mordant enveloppe le monde. Les aventuriers les plus braves gagnent plus d\'experience en surmontant l\'adversite.',
                'name_translations' => ['en' => 'Eternal Night'],
                'description_translations' => ['en' => 'A biting cold blankets the world. The bravest adventurers earn more experience overcoming adversity.'],
                'season' => 'winter',
                'start_day' => 20,
                'end_day' => 27,
                'rewards' => [
                    'xp_bonus' => 1.5,
                    'gold_bonus' => 1.25,
                ],
            ],
        ];
    }
}
