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
            ['faction' => 'faction_marchands', 'tier' => ReputationTier::Ami, 'type' => 'discount', 'label' => 'Remise marchande', 'description' => 'Réduction de 10% dans toutes les boutiques.', 'data' => ['percent' => 10]],
            ['faction' => 'faction_marchands', 'tier' => ReputationTier::Honore, 'type' => 'discount', 'label' => 'Remise honorifique', 'description' => 'Réduction de 20% dans toutes les boutiques.', 'data' => ['percent' => 20]],
            ['faction' => 'faction_marchands', 'tier' => ReputationTier::Exalte, 'type' => 'discount', 'label' => 'Tarif privilégié', 'description' => 'Réduction de 30% dans toutes les boutiques.', 'data' => ['percent' => 30]],

            // Ordre des Chevaliers
            ['faction' => 'faction_chevaliers', 'tier' => ReputationTier::Ami, 'type' => 'stat_bonus', 'label' => 'Bénédiction du chevalier', 'description' => '+5% de dégâts physiques.', 'data' => ['stat' => 'damage', 'percent' => 5]],
            ['faction' => 'faction_chevaliers', 'tier' => ReputationTier::Honore, 'type' => 'stat_bonus', 'label' => 'Bouclier de l\'ordre', 'description' => '+10% de points de vie maximum.', 'data' => ['stat' => 'life', 'percent' => 10]],
            ['faction' => 'faction_chevaliers', 'tier' => ReputationTier::Exalte, 'type' => 'stat_bonus', 'label' => 'Champion de l\'ordre', 'description' => '+15% de dégâts physiques et +10% de précision.', 'data' => ['stat' => 'damage', 'percent' => 15, 'extra_stat' => 'hit', 'extra_percent' => 10]],

            // Cercle des Mages
            ['faction' => 'faction_mages', 'tier' => ReputationTier::Ami, 'type' => 'stat_bonus', 'label' => 'Savoir arcanique', 'description' => '+5% de dégâts magiques.', 'data' => ['stat' => 'damage', 'percent' => 5]],
            ['faction' => 'faction_mages', 'tier' => ReputationTier::Honore, 'type' => 'stat_bonus', 'label' => 'Résonance magique', 'description' => '+10% d\'efficacité des soins.', 'data' => ['stat' => 'heal', 'percent' => 10]],
            ['faction' => 'faction_mages', 'tier' => ReputationTier::Exalte, 'type' => 'stat_bonus', 'label' => 'Archimage honoraire', 'description' => '+15% de dégâts magiques et +10% de soins.', 'data' => ['stat' => 'damage', 'percent' => 15, 'extra_stat' => 'heal', 'extra_percent' => 10]],

            // Confrérie des Ombres
            ['faction' => 'faction_ombres', 'tier' => ReputationTier::Ami, 'type' => 'stat_bonus', 'label' => 'Instinct du voleur', 'description' => '+5% de chance de critique.', 'data' => ['stat' => 'critical', 'percent' => 5]],
            ['faction' => 'faction_ombres', 'tier' => ReputationTier::Honore, 'type' => 'stat_bonus', 'label' => 'Pas de l\'ombre', 'description' => '+10% de vitesse et +5% de critique.', 'data' => ['stat' => 'critical', 'percent' => 5, 'extra_stat' => 'speed', 'extra_percent' => 10]],
            ['faction' => 'faction_ombres', 'tier' => ReputationTier::Exalte, 'type' => 'stat_bonus', 'label' => 'Maître assassin', 'description' => '+15% de critique et +10% de précision.', 'data' => ['stat' => 'critical', 'percent' => 15, 'extra_stat' => 'hit', 'extra_percent' => 10]],
        ];

        foreach ($rewards as $data) {
            $reward = new FactionReward();
            $reward->setFaction($this->getReference($data['faction']));
            $reward->setRequiredTier($data['tier']);
            $reward->setRewardType($data['type']);
            $reward->setLabel($data['label']);
            $reward->setDescription($data['description']);
            $reward->setRewardData($data['data']);
            $reward->setCreatedAt(new \DateTime());
            $reward->setUpdatedAt(new \DateTime());

            $manager->persist($reward);
        }

        $manager->flush();
    }
}
