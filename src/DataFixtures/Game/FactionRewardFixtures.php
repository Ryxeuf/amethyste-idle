<?php

namespace App\DataFixtures\Game;

use App\Entity\Game\FactionReward;
use App\Enum\ReputationTier;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class FactionRewardFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [
            FactionFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $rewards = [
            // Guilde des Marchands
            ['faction' => 'faction_marchands', 'tier' => ReputationTier::Ami, 'type' => 'discount', 'label' => 'Remise marchande', 'label_en' => 'Merchant Discount', 'description' => 'Réduction de 10% dans toutes les boutiques.', 'description_en' => '10% discount in all shops.', 'data' => ['percent' => 10]],
            ['faction' => 'faction_marchands', 'tier' => ReputationTier::Honore, 'type' => 'discount', 'label' => 'Remise honorifique', 'label_en' => 'Honorary Discount', 'description' => 'Réduction de 20% dans toutes les boutiques.', 'description_en' => '20% discount in all shops.', 'data' => ['percent' => 20]],
            ['faction' => 'faction_marchands', 'tier' => ReputationTier::Exalte, 'type' => 'discount', 'label' => 'Tarif privilégié', 'label_en' => 'Privileged Pricing', 'description' => 'Réduction de 30% dans toutes les boutiques.', 'description_en' => '30% discount in all shops.', 'data' => ['percent' => 30]],

            // Ordre des Chevaliers
            ['faction' => 'faction_chevaliers', 'tier' => ReputationTier::Ami, 'type' => 'stat_bonus', 'label' => 'Bénédiction du chevalier', 'label_en' => "Knight's Blessing", 'description' => '+5% de dégâts physiques.', 'description_en' => '+5% physical damage.', 'data' => ['stat' => 'damage', 'percent' => 5]],
            ['faction' => 'faction_chevaliers', 'tier' => ReputationTier::Honore, 'type' => 'stat_bonus', 'label' => 'Bouclier de l\'ordre', 'label_en' => 'Shield of the Order', 'description' => '+10% de points de vie maximum.', 'description_en' => '+10% maximum hit points.', 'data' => ['stat' => 'life', 'percent' => 10]],
            ['faction' => 'faction_chevaliers', 'tier' => ReputationTier::Exalte, 'type' => 'stat_bonus', 'label' => 'Champion de l\'ordre', 'label_en' => 'Champion of the Order', 'description' => '+15% de dégâts physiques et +10% de précision.', 'description_en' => '+15% physical damage and +10% accuracy.', 'data' => ['stat' => 'damage', 'percent' => 15, 'extra_stat' => 'hit', 'extra_percent' => 10]],

            // Cercle des Mages
            ['faction' => 'faction_mages', 'tier' => ReputationTier::Ami, 'type' => 'stat_bonus', 'label' => 'Savoir arcanique', 'label_en' => 'Arcane Lore', 'description' => '+5% de dégâts magiques.', 'description_en' => '+5% magical damage.', 'data' => ['stat' => 'damage', 'percent' => 5]],
            ['faction' => 'faction_mages', 'tier' => ReputationTier::Honore, 'type' => 'stat_bonus', 'label' => 'Résonance magique', 'label_en' => 'Magical Resonance', 'description' => '+10% d\'efficacité des soins.', 'description_en' => '+10% healing effectiveness.', 'data' => ['stat' => 'heal', 'percent' => 10]],
            ['faction' => 'faction_mages', 'tier' => ReputationTier::Exalte, 'type' => 'stat_bonus', 'label' => 'Archimage honoraire', 'label_en' => 'Honorary Archmage', 'description' => '+15% de dégâts magiques et +10% de soins.', 'description_en' => '+15% magical damage and +10% healing.', 'data' => ['stat' => 'damage', 'percent' => 15, 'extra_stat' => 'heal', 'extra_percent' => 10]],

            // Confrérie des Ombres
            ['faction' => 'faction_ombres', 'tier' => ReputationTier::Ami, 'type' => 'stat_bonus', 'label' => 'Instinct du voleur', 'label_en' => "Thief's Instinct", 'description' => '+5% de chance de critique.', 'description_en' => '+5% critical hit chance.', 'data' => ['stat' => 'critical', 'percent' => 5]],
            ['faction' => 'faction_ombres', 'tier' => ReputationTier::Honore, 'type' => 'stat_bonus', 'label' => 'Pas de l\'ombre', 'label_en' => 'Shadow Step', 'description' => '+10% de vitesse et +5% de critique.', 'description_en' => '+10% speed and +5% critical hit chance.', 'data' => ['stat' => 'critical', 'percent' => 5, 'extra_stat' => 'speed', 'extra_percent' => 10]],
            ['faction' => 'faction_ombres', 'tier' => ReputationTier::Exalte, 'type' => 'stat_bonus', 'label' => 'Maître assassin', 'label_en' => 'Master Assassin', 'description' => '+15% de critique et +10% de précision.', 'description_en' => '+15% critical hit chance and +10% accuracy.', 'data' => ['stat' => 'critical', 'percent' => 15, 'extra_stat' => 'hit', 'extra_percent' => 10]],
        ];

        foreach ($rewards as $data) {
            $reward = new FactionReward();
            $reward->setFaction($this->getReference($data['faction'], \App\Entity\Game\Faction::class));
            $reward->setRequiredTier($data['tier']);
            $reward->setRewardType($data['type']);
            $reward->setLabel($data['label']);
            $reward->setLabelTranslations(['en' => $data['label_en']]);
            $reward->setDescription($data['description']);
            $reward->setDescriptionTranslations(['en' => $data['description_en']]);
            $reward->setRewardData($data['data']);
            $reward->setCreatedAt(new \DateTime());
            $reward->setUpdatedAt(new \DateTime());

            $manager->persist($reward);
        }

        $manager->flush();
    }
}
