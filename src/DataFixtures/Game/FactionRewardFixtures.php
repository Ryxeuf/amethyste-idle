<?php

namespace App\DataFixtures\Game;

use App\Entity\Game\FactionReward;
use App\Enum\FactionRewardForm;
use App\Enum\ReputationTier;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Les recompenses de palier — et la porte de chaque maison (FAC-09).
 *
 * **Ce que la mesure a trouve.** Sur les 12 recompenses livrees, 3 sont des
 * remises (la Guilde des Marchands) et 9 sont des bonus de statistiques : hors
 * de la seule maison hors tension, **l'echelle ne contenait rien d'autre que
 * des statistiques**. FAC-01 les ayant bornees au patron, un Exalte chez les
 * Chevaliers qui porte d'autres couleurs recevait, pour une echelle entiere,
 * exactement rien — et la **Fonderie n'avait aucune recompense du tout**.
 *
 * Les cinq portes reparent d'abord cela : *cinq recompenses d'exaltation, cinq
 * quartiers de lore, zero puissance verticale* (GAME_WORLD § 12.5). Chaque
 * maison a desormais au moins une recompense **laterale**, et elle est la meme
 * pour toutes — ce qui rend la symetrie lisible avant que les echelles par
 * maison ne la nuancent (FAC-09b→e).
 */
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
            ['faction' => 'faction_marchands', 'tier' => ReputationTier::Ami, 'type' => FactionRewardForm::Discount, 'label' => 'Remise marchande', 'label_en' => 'Merchant Discount', 'description' => 'Réduction de 10% dans toutes les boutiques.', 'description_en' => '10% discount in all shops.', 'data' => ['percent' => 10]],
            ['faction' => 'faction_marchands', 'tier' => ReputationTier::Honore, 'type' => FactionRewardForm::Discount, 'label' => 'Remise honorifique', 'label_en' => 'Honorary Discount', 'description' => 'Réduction de 20% dans toutes les boutiques.', 'description_en' => '20% discount in all shops.', 'data' => ['percent' => 20]],
            ['faction' => 'faction_marchands', 'tier' => ReputationTier::Exalte, 'type' => FactionRewardForm::Discount, 'label' => 'Tarif privilégié', 'label_en' => 'Privileged Pricing', 'description' => 'Réduction de 30% dans toutes les boutiques.', 'description_en' => '30% discount in all shops.', 'data' => ['percent' => 30]],

            // Ordre des Chevaliers
            ['faction' => 'faction_chevaliers', 'tier' => ReputationTier::Ami, 'type' => FactionRewardForm::Patronage, 'label' => 'Bénédiction du chevalier', 'label_en' => "Knight's Blessing", 'description' => '+5% de dégâts physiques.', 'description_en' => '+5% physical damage.', 'data' => ['stat' => 'damage', 'percent' => 5]],
            ['faction' => 'faction_chevaliers', 'tier' => ReputationTier::Honore, 'type' => FactionRewardForm::Patronage, 'label' => 'Bouclier de l\'ordre', 'label_en' => 'Shield of the Order', 'description' => '+10% de points de vie maximum.', 'description_en' => '+10% maximum hit points.', 'data' => ['stat' => 'life', 'percent' => 10]],
            ['faction' => 'faction_chevaliers', 'tier' => ReputationTier::Exalte, 'type' => FactionRewardForm::Patronage, 'label' => 'Champion de l\'ordre', 'label_en' => 'Champion of the Order', 'description' => '+15% de dégâts physiques et +10% de précision.', 'description_en' => '+15% physical damage and +10% accuracy.', 'data' => ['stat' => 'damage', 'percent' => 15, 'extra_stat' => 'hit', 'extra_percent' => 10]],

            // Cercle des Mages
            ['faction' => 'faction_mages', 'tier' => ReputationTier::Ami, 'type' => FactionRewardForm::Patronage, 'label' => 'Savoir arcanique', 'label_en' => 'Arcane Lore', 'description' => '+5% de dégâts magiques.', 'description_en' => '+5% magical damage.', 'data' => ['stat' => 'damage', 'percent' => 5]],
            ['faction' => 'faction_mages', 'tier' => ReputationTier::Honore, 'type' => FactionRewardForm::Patronage, 'label' => 'Résonance magique', 'label_en' => 'Magical Resonance', 'description' => '+10% d\'efficacité des soins.', 'description_en' => '+10% healing effectiveness.', 'data' => ['stat' => 'heal', 'percent' => 10]],
            ['faction' => 'faction_mages', 'tier' => ReputationTier::Exalte, 'type' => FactionRewardForm::Patronage, 'label' => 'Archimage honoraire', 'label_en' => 'Honorary Archmage', 'description' => '+15% de dégâts magiques et +10% de soins.', 'description_en' => '+15% magical damage and +10% healing.', 'data' => ['stat' => 'damage', 'percent' => 15, 'extra_stat' => 'heal', 'extra_percent' => 10]],

            // Confrérie des Ruelles
            ['faction' => 'faction_ombres', 'tier' => ReputationTier::Ami, 'type' => FactionRewardForm::Patronage, 'label' => 'Instinct du voleur', 'label_en' => "Thief's Instinct", 'description' => '+5% de chance de critique.', 'description_en' => '+5% critical hit chance.', 'data' => ['stat' => 'critical', 'percent' => 5]],
            ['faction' => 'faction_ombres', 'tier' => ReputationTier::Honore, 'type' => FactionRewardForm::Patronage, 'label' => 'Pas de l\'ombre', 'label_en' => 'Shadow Step', 'description' => '+10% de vitesse et +5% de critique.', 'description_en' => '+10% speed and +5% critical hit chance.', 'data' => ['stat' => 'critical', 'percent' => 5, 'extra_stat' => 'speed', 'extra_percent' => 10]],
            ['faction' => 'faction_ombres', 'tier' => ReputationTier::Exalte, 'type' => FactionRewardForm::Patronage, 'label' => 'Maître assassin', 'label_en' => 'Master Assassin', 'description' => '+15% de critique et +10% de précision.', 'description_en' => '+15% critical hit chance and +10% accuracy.', 'data' => ['stat' => 'critical', 'percent' => 15, 'extra_stat' => 'hit', 'extra_percent' => 10]],

            // -----------------------------------------------------------------
            // FAC-09 — les cinq portes
            // -----------------------------------------------------------------
            // Une par maison, toutes a Exalte, toutes de forme `access`. La zone
            // porte la garde (`requires_reputation` dans `zones.yaml`) ; cette
            // ligne-ci ne la duplique pas, elle **l'annonce** : l'ecran des
            // factions doit pouvoir dire ce qu'un palier ouvre sans interroger
            // le graphe des zones.
            ['faction' => 'faction_marchands', 'tier' => ReputationTier::Exalte, 'type' => FactionRewardForm::Access, 'label' => 'La Grande Halle', 'label_en' => 'The Great Hall', 'description' => 'La bourse des maisons marchandes, sous le Fanal : la ou les prix se decident.', 'description_en' => 'The trading floor of the merchant houses, beneath the Beacon: where prices are decided.', 'data' => ['zone' => 'la-grande-halle']],
            ['faction' => 'faction_chevaliers', 'tier' => ReputationTier::Exalte, 'type' => FactionRewardForm::Access, 'label' => 'La Salle du Serment', 'label_en' => 'The Hall of the Oath', 'description' => 'La ou le texte original est grave : pourquoi le sang ne se depose pas.', 'description_en' => 'Where the original text is carved: why blood does not settle.', 'data' => ['zone' => 'la-salle-du-serment']],
            ['faction' => 'faction_mages', 'tier' => ReputationTier::Exalte, 'type' => FactionRewardForm::Access, 'label' => 'Le Scriptorium', 'label_en' => 'The Scriptorium', 'description' => 'La ou le Repertoire s\'ecrit : ce que le monde a lu, et ce qu\'il lui reste a retrouver.', 'description_en' => 'Where the Repertoire is written: what the world has read, and what it has left to recover.', 'data' => ['zone' => 'le-scriptorium']],
            ['faction' => 'faction_ombres', 'tier' => ReputationTier::Exalte, 'type' => FactionRewardForm::Access, 'label' => 'La Cour des Miracles', 'label_en' => 'The Court of Miracles', 'description' => 'Qui tient vraiment les ruelles — et depuis quand.', 'description_en' => 'Who really holds the alleys — and for how long.', 'data' => ['zone' => 'la-cour-des-miracles']],
            ['faction' => 'faction_fonderie', 'tier' => ReputationTier::Exalte, 'type' => FactionRewardForm::Access, 'label' => 'Le Grand Fourneau', 'label_en' => 'The Great Furnace', 'description' => 'Ce que la Fonderie fond quand personne ne regarde.', 'description_en' => 'What the Foundry melts when no one is watching.', 'data' => ['zone' => 'le-grand-fourneau']],
        ];

        foreach ($rewards as $data) {
            $reward = new FactionReward();
            $reward->setFaction($this->getReference($data['faction'], \App\Entity\Game\Faction::class));
            $reward->setRequiredTier($data['tier']);
            $reward->setForm($data['type']);
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
