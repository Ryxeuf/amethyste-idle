<?php

namespace App\DataFixtures;

use App\Entity\Game\Domain;
use App\Enum\CombatRegister;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DomainFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // DOM-01 — le `register` fait de chaque domaine de combat une **case**
        // element x registre (GAME_DOMAINS § 2). C'est le domaine qui porte la
        // borne, jamais le nœud : les 130 passifs livres se typent d'un coup,
        // sans qu'aucune decision ne se prenne competence par competence.
        //
        // Les domaines de recolte et d'artisanat n'ont **pas** de registre, et
        // c'est la lettre du canon : leurs passifs sont bornes a leur metier,
        // c'est-a-dire au domaine lui-meme. Un `null` dit « hors combat ».
        $domains = [
            // Feu
            'pyromancy' => ['title' => 'Pyromancien', 'element' => 'fire', 'register' => CombatRegister::Spell, 'title_translations' => ['en' => 'Pyromancer']],
            'berserker' => ['title' => 'Berserker', 'element' => 'fire', 'register' => CombatRegister::Melee, 'title_translations' => ['en' => 'Berserker']],
            'artificer' => ['title' => 'Artificier', 'element' => 'fire', 'register' => CombatRegister::Ranged, 'title_translations' => ['en' => 'Artificer']],
            // Eau
            'hydromancer' => ['title' => 'Hydromancien', 'element' => 'water', 'register' => CombatRegister::Spell, 'title_translations' => ['en' => 'Hydromancer']],
            'healer' => ['title' => 'Guérisseur', 'element' => 'water', 'register' => CombatRegister::Spell, 'title_translations' => ['en' => 'Healer']],
            'tidecaller' => ['title' => 'Marémancien', 'element' => 'water', 'register' => CombatRegister::Spell, 'title_translations' => ['en' => 'Tidecaller']],
            // Air
            'stormcaller' => ['title' => 'Foudromancien', 'element' => 'air', 'register' => CombatRegister::Spell, 'title_translations' => ['en' => 'Stormcaller']],
            'archer' => ['title' => 'Archer', 'element' => 'air', 'register' => CombatRegister::Ranged, 'title_translations' => ['en' => 'Archer']],
            'wanderer' => ['title' => 'Vagabond', 'element' => 'air', 'register' => CombatRegister::Melee, 'title_translations' => ['en' => 'Wanderer']],
            // Terre
            'geomancer' => ['title' => 'Géomancien', 'element' => 'earth', 'register' => CombatRegister::Spell, 'title_translations' => ['en' => 'Geomancer']],
            'defender' => ['title' => 'Défenseur', 'element' => 'earth', 'register' => CombatRegister::Melee, 'title_translations' => ['en' => 'Defender']],
            'guardian' => ['title' => 'Gardien', 'element' => 'earth', 'register' => CombatRegister::Melee, 'title_translations' => ['en' => 'Guardian']],
            // Métal
            'soldier' => ['title' => 'Soldat', 'element' => 'metal', 'register' => CombatRegister::Melee, 'title_translations' => ['en' => 'Soldier']],
            'knight' => ['title' => 'Chevalier', 'element' => 'metal', 'register' => CombatRegister::Melee, 'title_translations' => ['en' => 'Knight']],
            'engineer' => ['title' => 'Ingénieur', 'element' => 'metal', 'register' => CombatRegister::Ranged, 'title_translations' => ['en' => 'Engineer']],
            // Bête
            'hunter' => ['title' => 'Chasseur', 'element' => 'beast', 'register' => CombatRegister::Ranged, 'title_translations' => ['en' => 'Hunter']],
            'tamer' => ['title' => 'Dompteur', 'element' => 'beast', 'register' => CombatRegister::Melee, 'title_translations' => ['en' => 'Tamer']],
            'druid' => ['title' => 'Druide', 'element' => 'beast', 'register' => CombatRegister::Spell, 'title_translations' => ['en' => 'Druid']],
            // Lumière
            'paladin' => ['title' => 'Paladin', 'element' => 'light', 'register' => CombatRegister::Melee, 'title_translations' => ['en' => 'Paladin']],
            'priest' => ['title' => 'Prêtre', 'element' => 'light', 'register' => CombatRegister::Spell, 'title_translations' => ['en' => 'Priest']],
            'inquisitor' => ['title' => 'Inquisiteur', 'element' => 'light', 'register' => CombatRegister::Melee, 'title_translations' => ['en' => 'Inquisitor']],
            // Ombre
            'assassin' => ['title' => 'Assassin', 'element' => 'dark', 'register' => CombatRegister::Melee, 'title_translations' => ['en' => 'Assassin']],
            'necromancer' => ['title' => 'Nécromancien', 'element' => 'dark', 'register' => CombatRegister::Spell, 'title_translations' => ['en' => 'Necromancer']],
            'warlock' => ['title' => 'Sorcier', 'element' => 'dark', 'register' => CombatRegister::Spell, 'title_translations' => ['en' => 'Warlock']],
            // Récolte
            'miner' => ['title' => 'Mineur', 'element' => 'earth', 'title_translations' => ['en' => 'Miner']],
            'herbalist' => ['title' => 'Herboriste', 'element' => 'beast', 'title_translations' => ['en' => 'Herbalist']],
            'fisherman' => ['title' => 'Pêcheur', 'element' => 'water', 'title_translations' => ['en' => 'Fisherman']],
            'skinner' => ['title' => 'Dépeceur', 'element' => 'beast', 'title_translations' => ['en' => 'Skinner']],
            // ZON-34 — la cinquieme recolte. Element bois : le domaine prend
            // l'element de ce qu'il coupe, comme le mineur prend la terre.
            'lumberjack' => ['title' => 'Bûcheron', 'element' => 'wood', 'title_translations' => ['en' => 'Lumberjack']],
            // Craft
            'blacksmith' => ['title' => 'Forgeron', 'element' => 'metal', 'title_translations' => ['en' => 'Blacksmith']],
            'leatherworker' => ['title' => 'Tanneur', 'element' => 'beast', 'title_translations' => ['en' => 'Leatherworker']],
            'alchimist' => ['title' => 'Alchimiste', 'element' => 'water', 'title_translations' => ['en' => 'Alchemist']],
            'jeweller' => ['title' => 'Joaillier', 'element' => 'earth', 'title_translations' => ['en' => 'Jeweler']],
            // ECO-29 — le debouche de la peche et des vivres. Element eau : le
            // metier prend l'element de ce qu'il cuit le plus.
            'cook' => ['title' => 'Cuisinier', 'element' => 'water', 'title_translations' => ['en' => 'Cook']],
            // ECO-30 — le debouche de la ligne du bois. Meme element que le
            // bucheron : le metier prend celui de sa matiere, comme le forgeron
            // prend le metal.
            'carpenter' => ['title' => 'Charpentier', 'element' => 'wood', 'title_translations' => ['en' => 'Carpenter']],
            // ECO-31 — celui qui habille les lanceurs de sorts. Element air : le
            // tissu est ce qui pese le moins, et le metier prend l'element de ce
            // qu'il travaille.
            'tailor' => ['title' => 'Tailleur', 'element' => 'air', 'title_translations' => ['en' => 'Tailor']],
        ];

        foreach ($domains as $key => $data) {
            $domain = new Domain();
            $domain->setTitle($data['title']);
            $domain->setElement($data['element']);
            $domain->setRegister($data['register'] ?? null);
            if (isset($data['title_translations']) && is_array($data['title_translations'])) {
                $domain->setTitleTranslations($data['title_translations']);
            }
            $domain->setRandomSeed(rand(1, 1000));
            $domain->setGraphHeight(rand(5, 10));
            $domain->setCreatedAt(new \DateTime());
            $domain->setUpdatedAt(new \DateTime());

            $manager->persist($domain);
            $this->addReference($key, $domain);
        }

        $manager->flush();
    }
}
