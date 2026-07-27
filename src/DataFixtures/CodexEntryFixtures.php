<?php

namespace App\DataFixtures;

use App\Entity\Game\CodexEntry;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Entrees de Codex de depart (NAR-05). Chaque entree se debloque par un
 * declencheur declaratif (visite de zone, kill de boss, fin d'arc). Les faits
 * de monde publics (`world_fact`) seront ajoutes par la narration saisonniere.
 */
class CodexEntryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $entries = [
            [
                'slug' => 'region-foret-des-murmures',
                'category' => CodexEntry::CATEGORY_REGION,
                'title' => 'La Forêt des Murmures',
                'title_translations' => ['en' => 'The Whispering Forest'],
                'description' => "On raconte que les arbres de la Forêt des Murmures retiennent la voix des disparus. Peu s'y aventurent, et moins encore en reviennent inchangés.",
                'description_translations' => ['en' => 'They say the trees of the Whispering Forest hold the voices of the lost. Few venture in, and fewer still return unchanged.'],
                'unlockType' => CodexEntry::UNLOCK_ZONE_VISIT,
                'unlockKey' => 'foret-des-murmures',
            ],
            [
                'slug' => 'region-mines-profondes',
                'category' => CodexEntry::CATEGORY_REGION,
                'title' => 'Les Mines Profondes',
                'title_translations' => ['en' => 'The Deep Mines'],
                'description' => 'Creusées bien avant la mémoire des vivants, les Mines Profondes recèlent des filons rares — et des échos qui ne devraient pas répondre.',
                'description_translations' => ['en' => 'Dug long before living memory, the Deep Mines hold rare veins — and echoes that should not answer back.'],
                'unlockType' => CodexEntry::UNLOCK_ZONE_VISIT,
                'unlockKey' => 'mines-profondes',
            ],
            [
                // Le hub etait la seule des trois zones de depart sans entree de
                // region : le joueur y passait ses premieres heures sans que le
                // Codex n'en garde trace.
                'slug' => 'region-village-de-lumiere',
                'category' => CodexEntry::CATEGORY_REGION,
                'title' => 'Le Village de Lumière',
                'title_translations' => ['en' => 'The Village of Light'],
                'description' => "Un cercle de murs bas, une forge qui ne s'éteint pas et des jardins de temple où poussent le thym et la lavande. Le Village de Lumière n'est pas grand ; il est simplement le seul endroit du monde connu où l'on dort sans monter la garde.",
                'description_translations' => ['en' => 'A ring of low walls, a forge that never goes out, and temple gardens where thyme and lavender grow. The Village of Light is not large; it is simply the only place in the known world where one sleeps without standing watch.'],
                'unlockType' => CodexEntry::UNLOCK_ZONE_VISIT,
                'unlockKey' => 'village-de-lumiere',
            ],
            [
                'slug' => 'bestiary-gardien-foret',
                'category' => CodexEntry::CATEGORY_BESTIARY_LORE,
                'title' => 'Le Gardien de la Forêt',
                'title_translations' => ['en' => 'The Forest Guardian'],
                'description' => "Colosse de bois et de mousse, le Gardien veille sur le cœur de la forêt. Le vaincre, dit-on, c'est hériter d'un fragment de sa vigilance.",
                'description_translations' => ['en' => 'A colossus of wood and moss, the Guardian watches over the forest heart. To defeat it, they say, is to inherit a shard of its vigilance.'],
                'unlockType' => CodexEntry::UNLOCK_BOSS_KILL,
                'unlockKey' => 'forest_guardian',
            ],
            [
                // Pendant minier du Gardien : les deux zones hostiles de depart
                // ont desormais chacune leur boss de zone et sa page de Codex.
                'slug' => 'bestiary-seigneur-forge',
                'category' => CodexEntry::CATEGORY_BESTIARY_LORE,
                'title' => 'Le Seigneur de la Forge',
                'title_translations' => ['en' => 'The Forge Lord'],
                'description' => "Les mineurs jurent qu'il fut un homme, et qu'il descendit trop bas chercher un métal qui n'a pas de nom. Ce qui bat encore dans sa poitrine n'est plus un cœur : c'est un feu qu'on n'a jamais su éteindre.",
                'description_translations' => ['en' => 'The miners swear he was once a man, and that he went too deep looking for a metal with no name. What still beats in his chest is no longer a heart: it is a fire no one ever learned to put out.'],
                'unlockType' => CodexEntry::UNLOCK_BOSS_KILL,
                'unlockKey' => 'forge_lord',
            ],
            [
                'slug' => 'chronique-de-l-eveil',
                'category' => CodexEntry::CATEGORY_BESTIARY_LORE,
                'title' => "Chronique de l'Éveil",
                'title_translations' => ['en' => 'Chronicle of the Awakening'],
                'description' => "Sans souvenir ni nom, vous avez ouvert les yeux au Village de Lumière. La Sage vous a guidé jusqu'au Cristal d'Améthyste — première pierre d'un chemin qui reste à écrire.",
                'description_translations' => ['en' => 'Without memory or name, you woke in the Village of Light. The Wise One guided you to the Amethyst Crystal — the first stone of a path yet to be written.'],
                'unlockType' => CodexEntry::UNLOCK_ARC_COMPLETED,
                'unlockKey' => 'intro',
            ],
            [
                // Fait de monde public (NAR-07), horodate. Les faits crédités à une
                // guilde seront générés par les résolutions de saison canon (NAR-11/12).
                'slug' => 'fondation-du-village-de-lumiere',
                'category' => CodexEntry::CATEGORY_WORLD_FACT,
                'title' => 'La Fondation du Village de Lumière',
                'title_translations' => ['en' => 'The Founding of the Village of Light'],
                'description' => "Bien avant l'Éveil des sans-mémoire, des colons dressèrent le Village de Lumière comme un fanal contre les ténèbres environnantes. Il demeure le premier refuge de tout nouvel arrivant.",
                'description_translations' => ['en' => 'Long before the awakening of the memoryless, settlers raised the Village of Light as a beacon against the surrounding dark. It remains the first refuge of every newcomer.'],
                'unlockType' => CodexEntry::UNLOCK_MANUAL,
                'unlockKey' => null,
            ],
        ];

        foreach ($entries as $data) {
            $entry = new CodexEntry();
            $entry->setSlug($data['slug']);
            $entry->setCategory($data['category']);
            $entry->setTitle($data['title']);
            if (isset($data['title_translations']) && \is_array($data['title_translations'])) {
                $entry->setTitleTranslations($data['title_translations']);
            }
            $entry->setDescription($data['description']);
            if (isset($data['description_translations']) && \is_array($data['description_translations'])) {
                $entry->setDescriptionTranslations($data['description_translations']);
            }
            $entry->setUnlockType($data['unlockType']);
            $entry->setUnlockKey($data['unlockKey']);
            $entry->setCreatedAt(new \DateTime());
            $entry->setUpdatedAt(new \DateTime());

            $manager->persist($entry);
            $this->addReference('codex_' . $data['slug'], $entry);
        }

        $manager->flush();
    }
}
