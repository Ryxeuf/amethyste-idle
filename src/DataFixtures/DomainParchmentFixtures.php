<?php

namespace App\DataFixtures;

use App\Entity\Game\Domain;
use App\Entity\Game\Item;
use App\Enum\BindType;
use App\GameEngine\Item\ItemEffectEncoder;
use App\GameEngine\Progression\FoundTreeCatalog;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Un parchemin par arbre (ONB-08).
 *
 * Les trois parchemins historiques (`life-domain-parchment`,
 * `miner-domain-parchment`, `herbalist-domain-parchment`) restent dans
 * `ItemFixtures` : ils sont references par slug depuis les quetes et par cle de
 * fixture depuis l'inventaire de demonstration, et les deplacer casserait ces
 * liens pour aucun gain. Seule leur **semantique** a change — ils ouvrent
 * desormais un arbre au lieu d'accorder une competence precise. Cette classe
 * livre les autres.
 *
 * Les quatre conditions non negociables du cadrage (GAME_ONBOARDING § 6.3) sont
 * portees par la **forme** de ces donnees, pas par du code :
 *
 * 1. **accessible a tout le monde** — aucun `requirements`, aucune condition de
 *    peuple, de faction ni de progression n'est posee ici, et il n'existe aucun
 *    champ pour en poser une ;
 * 2. **cumulables** — chaque parchemin ouvre son arbre et rien d'autre ;
 * 3. **ni unique ni limite** — `nb_usages` a 1 decrit la lecture d'un exemplaire,
 *    pas une rarete : le prix est fixe et identique pour tous ;
 * 4. **jamais sur le chemin critique de l'acte I** — les trois parchemins de
 *    l'acte I sont *donnes* (ONB-12), et ce sont precisement les trois qui ne
 *    sont pas dans cette classe.
 *
 * Le prix uniforme de 100 gils est **provisoire et assume** : le bareme des
 * parchemins non offerts est un gold sink a poser avec PLAN_PLAYER_ECONOMY.
 * Poser 100 partout aujourd'hui vaut mieux qu'inventer une echelle qui devrait
 * etre refaite — et surtout mieux que de laisser 33 arbres sans vendeur.
 */
class DomainParchmentFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly FoundTreeCatalog $foundTrees,
    ) {
    }

    /**
     * Prix unique, provisoire (cf. l'en-tete de classe).
     */
    public const PARCHMENT_PRICE = 100;

    /**
     * Les arbres dont le parchemin vit ailleurs, et ou.
     *
     * `healer` porte le slug historique `life-domain-parchment` : le renommer
     * casserait les recompenses de quete qui le designent.
     *
     * @var array<string, string> cle de domaine => slug du parchemin
     */
    public const LEGACY_PARCHMENTS = [
        'healer' => 'life-domain-parchment',
        'miner' => 'miner-domain-parchment',
        'herbalist' => 'herbalist-domain-parchment',
    ];

    /**
     * Cle de domaine => [nom FR, nom EN].
     *
     * Le nom dit ce qu'on ouvre, jamais ce qu'on y gagne : le catalogue (ONB-09)
     * est le seul endroit qui presente le contenu d'un arbre, et un parchemin
     * qui promettrait un effet ferait de l'achat une decision de build prise
     * sans l'ecran qui l'eclaire.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const PARCHMENTS = [
        'pyromancy' => ['Traité de pyromancie', 'Treatise on Pyromancy'],
        'berserker' => ['Chant de la fureur', 'Chant of Fury'],
        'artificer' => ['Carnet de l\'artificier', 'Artificer\'s Notebook'],
        'hydromancer' => ['Traité d\'hydromancie', 'Treatise on Hydromancy'],
        'tidecaller' => ['Livre des marées', 'Book of Tides'],
        'stormcaller' => ['Traité de foudromancie', 'Treatise on Storm Calling'],
        'archer' => ['Manuel de l\'archer', 'Archer\'s Manual'],
        'wanderer' => ['Carnet de route du vagabond', 'Wanderer\'s Road Journal'],
        'geomancer' => ['Traité de géomancie', 'Treatise on Geomancy'],
        'defender' => ['Manuel du défenseur', 'Defender\'s Manual'],
        'guardian' => ['Serment du gardien', 'Guardian\'s Oath'],
        'soldier' => ['Manuel du soldat', 'Soldier\'s Manual'],
        'knight' => ['Code du chevalier', 'Knight\'s Code'],
        'engineer' => ['Plans de l\'ingénieur', 'Engineer\'s Blueprints'],
        'hunter' => ['Carnet du chasseur', 'Hunter\'s Notebook'],
        'tamer' => ['Manuel du dompteur', 'Tamer\'s Manual'],
        'druid' => ['Herbier du druide', 'Druid\'s Herbarium'],
        'paladin' => ['Serment du paladin', 'Paladin\'s Oath'],
        'priest' => ['Bréviaire du prêtre', 'Priest\'s Breviary'],
        'inquisitor' => ['Registre de l\'inquisiteur', 'Inquisitor\'s Register'],
        'assassin' => ['Codes de l\'assassin', 'Assassin\'s Ciphers'],
        'necromancer' => ['Grimoire de nécromancie', 'Grimoire of Necromancy'],
        'warlock' => ['Grimoire du sorcier', 'Warlock\'s Grimoire'],
        'fisherman' => ['Almanach du pêcheur', 'Fisherman\'s Almanac'],
        'skinner' => ['Manuel du dépeceur', 'Skinner\'s Manual'],
        'lumberjack' => ['Traité des essences', 'Treatise on Timber'],
        'blacksmith' => ['Registre du forgeron', 'Blacksmith\'s Register'],
        'leatherworker' => ['Manuel du tanneur', 'Leatherworker\'s Manual'],
        'alchimist' => ['Formulaire de l\'alchimiste', 'Alchemist\'s Formulary'],
        'jeweller' => ['Carnet du joaillier', 'Jeweler\'s Notebook'],
        'cook' => ['Livre de recettes du cuisinier', 'Cook\'s Recipe Book'],
        'carpenter' => ['Traité du charpentier', 'Carpenter\'s Treatise'],
        'tailor' => ['Patrons du tailleur', 'Tailor\'s Patterns'],
    ];

    public function load(ObjectManager $manager): void
    {
        $this->loadFoundParchments($manager);

        foreach (self::PARCHMENTS as $domainKey => [$nameFr, $nameEn]) {
            /** @var Domain $domain */
            $domain = $this->getReference($domainKey, Domain::class);

            $item = new Item();
            $item->setName($nameFr);
            $item->setNameTranslations(['en' => $nameEn]);
            $item->setSlug(self::parchmentSlug($domainKey));
            $item->setDescription(sprintf("Ouvre l'arbre du %s. Ce qu'on y apprend reste à apprendre.", mb_strtolower($domain->getTitle())));
            $item->setDescriptionTranslations(['en' => sprintf('Opens the %s tree. What it teaches remains to be learned.', mb_strtolower($nameEn))]);
            $item->setType('stuff');
            $item->setEffect(json_encode([
                ItemEffectEncoder::KEY_ACTION => ItemEffectEncoder::ACTION_OPEN_DOMAIN,
                ItemEffectEncoder::KEY_SLUG => $domain->getSlug(),
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            $item->setPrice(self::PARCHMENT_PRICE);
            $item->setSpace(1);
            $item->setEnergyCost(0);
            $item->setNbUsages(1);
            $item->setCreatedAt(new \DateTime());
            $item->setUpdatedAt(new \DateTime());

            $manager->persist($item);
            $this->addReference($domainKey . '_domain_parchment', $item);
        }

        $manager->flush();
    }

    /**
     * Les parchemins **retrouves** (DOM-10) — et leur unique difference.
     *
     * Ils sont **lies** (`bind_on_pickup`), ce qui est l'unique exception aux
     * quatre conditions du parchemin de registre rappelees en tete de classe :
     * *ce qui circule entre joueurs est l'information, jamais l'objet*. Sans
     * cela, le premier decouvreur met le secret a l'hotel des ventes et il meurt
     * en deux jours.
     *
     * Deux autres differences suivent d'elles-memes : ils n'ont **pas de prix**
     * (aucune echoppe n'en vend — un carnet se donne), et ils sont declares dans
     * `found_trees.yaml` plutot qu'ici, parce que c'est la que vit la rencontre
     * qui les remet.
     */
    private function loadFoundParchments(ObjectManager $manager): void
    {
        foreach ($this->foundTrees->trees() as $domainKey => $tree) {
            /** @var Domain $domain */
            $domain = $this->getReference($domainKey, Domain::class);
            $parchment = $tree['parchment'];

            $item = new Item();
            $item->setName($parchment['name']);
            $item->setNameTranslations(['en' => $parchment['name_en']]);
            $item->setSlug($parchment['slug']);
            $item->setDescription($parchment['description']);
            $item->setDescriptionTranslations(['en' => $parchment['description_en']]);
            $item->setType('stuff');
            $item->setEffect(json_encode([
                ItemEffectEncoder::KEY_ACTION => ItemEffectEncoder::ACTION_OPEN_DOMAIN,
                ItemEffectEncoder::KEY_SLUG => $domain->getSlug(),
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            $item->setPrice(0);
            $item->setSpace(1);
            $item->setEnergyCost(0);
            $item->setNbUsages(1);
            $item->setBindType(BindType::BindOnPickup);
            $item->setCreatedAt(new \DateTime());
            $item->setUpdatedAt(new \DateTime());

            $manager->persist($item);
            $this->addReference($domainKey . '_found_parchment', $item);
        }
    }

    public static function parchmentSlug(string $domainKey): string
    {
        return self::LEGACY_PARCHMENTS[$domainKey] ?? str_replace('_', '-', $domainKey) . '-domain-parchment';
    }

    public function getDependencies(): array
    {
        return [
            DomainFixtures::class,
        ];
    }
}
