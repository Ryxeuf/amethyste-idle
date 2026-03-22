<?php

namespace App\DataFixtures\Game;

use App\Entity\Game\Faction;
use App\Entity\Game\FactionReward;
use App\Enum\ReputationTier;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class FactionRewardFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $rewards = [
            // Guilde des Marchands
            [
                'faction' => 'faction_marchands',
                'tier' => ReputationTier::Ami,
                'type' => FactionReward::TYPE_DISCOUNT,
                'name' => 'Remise marchande',
                'description' => 'Les boutiques des Marchands accordent une réduction de 10% sur tous les achats.',
                'data' => ['discount_percent' => 10],
            ],
            [
                'faction' => 'faction_marchands',
                'tier' => ReputationTier::Honore,
                'type' => FactionReward::TYPE_RECIPE_UNLOCK,
                'name' => 'Recettes secrètes des Marchands',
                'description' => 'Accès aux recettes de craft exclusives de la Guilde des Marchands.',
                'data' => ['recipes' => ['merchant_elixir', 'merchant_bag']],
            ],
            [
                'faction' => 'faction_marchands',
                'tier' => ReputationTier::Exalte,
                'type' => FactionReward::TYPE_ITEM,
                'name' => 'Bourse dorée des Marchands',
                'description' => 'Une bourse enchantée qui augmente les gains de Gils de 15%.',
                'data' => ['item_slug' => 'merchant_golden_purse', 'bonus' => 'gils_bonus_15'],
            ],

            // Ordre des Chevaliers
            [
                'faction' => 'faction_chevaliers',
                'tier' => ReputationTier::Ami,
                'type' => FactionReward::TYPE_ITEM,
                'name' => 'Écu de l\'Ordre',
                'description' => 'Un bouclier honorifique de l\'Ordre des Chevaliers, symbole de votre dévouement.',
                'data' => ['item_slug' => 'knight_shield'],
            ],
            [
                'faction' => 'faction_chevaliers',
                'tier' => ReputationTier::Honore,
                'type' => FactionReward::TYPE_RECIPE_UNLOCK,
                'name' => 'Forges de l\'Ordre',
                'description' => 'Accès aux recettes d\'armures renforcées forgées par les maîtres de l\'Ordre.',
                'data' => ['recipes' => ['knight_plate', 'knight_sword']],
            ],
            [
                'faction' => 'faction_chevaliers',
                'tier' => ReputationTier::Exalte,
                'type' => FactionReward::TYPE_ZONE_ACCESS,
                'name' => 'Salle d\'armes secrète',
                'description' => 'Accès à la salle d\'armes secrète de l\'Ordre, remplie d\'équipements légendaires.',
                'data' => ['zone' => 'knight_armory'],
            ],

            // Cercle des Mages
            [
                'faction' => 'faction_mages',
                'tier' => ReputationTier::Ami,
                'type' => FactionReward::TYPE_RECIPE_UNLOCK,
                'name' => 'Grimoire d\'alchimie',
                'description' => 'Recettes de potions améliorées enseignées par le Cercle des Mages.',
                'data' => ['recipes' => ['arcane_potion', 'mana_crystal']],
            ],
            [
                'faction' => 'faction_mages',
                'tier' => ReputationTier::Honore,
                'type' => FactionReward::TYPE_ITEM,
                'name' => 'Orbe des Arcanes',
                'description' => 'Un orbe magique qui renforce les sorts élémentaires de 10%.',
                'data' => ['item_slug' => 'arcane_orb', 'bonus' => 'spell_damage_10'],
            ],
            [
                'faction' => 'faction_mages',
                'tier' => ReputationTier::Exalte,
                'type' => FactionReward::TYPE_ZONE_ACCESS,
                'name' => 'Bibliothèque interdite',
                'description' => 'Accès à la bibliothèque interdite du Cercle, où reposent les materia les plus puissantes.',
                'data' => ['zone' => 'mage_library'],
            ],

            // Confrérie des Ombres
            [
                'faction' => 'faction_ombres',
                'tier' => ReputationTier::Ami,
                'type' => FactionReward::TYPE_ITEM,
                'name' => 'Cape des Ombres',
                'description' => 'Une cape enchantée qui augmente la vitesse de 5%.',
                'data' => ['item_slug' => 'shadow_cloak', 'bonus' => 'speed_5'],
            ],
            [
                'faction' => 'faction_ombres',
                'tier' => ReputationTier::Honore,
                'type' => FactionReward::TYPE_DISCOUNT,
                'name' => 'Marché noir',
                'description' => 'Accès au marché noir de la Confrérie avec 15% de réduction sur les objets rares.',
                'data' => ['discount_percent' => 15],
            ],
            [
                'faction' => 'faction_ombres',
                'tier' => ReputationTier::Exalte,
                'type' => FactionReward::TYPE_RECIPE_UNLOCK,
                'name' => 'Arts empoisonnés',
                'description' => 'Recettes de poisons et lames empoisonnées de la Confrérie.',
                'data' => ['recipes' => ['shadow_poison', 'assassin_blade']],
            ],
        ];

        foreach ($rewards as $data) {
            $reward = new FactionReward();
            $reward->setFaction($this->getReference($data['faction'], Faction::class));
            $reward->setRequiredTier($data['tier']);
            $reward->setRewardType($data['type']);
            $reward->setName($data['name']);
            $reward->setDescription($data['description']);
            $reward->setRewardData($data['data']);
            $reward->setCreatedAt(new \DateTime());
            $reward->setUpdatedAt(new \DateTime());

            $manager->persist($reward);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            FactionFixtures::class,
        ];
    }
}
