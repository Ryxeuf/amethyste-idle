<?php

namespace App\DataFixtures;

use App\DataFixtures\Game\SkillFixtures;
use App\Entity\Game\Item;
use App\Entity\Game\Skill;
use App\Entity\Game\Spell;
use App\Enum\ItemRarity;
use App\GameEngine\Materia\MateriaBlueprint;
use App\GameEngine\Materia\MateriaDerivation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Le catalogue des materia — une par `unlock` distinct (MAT-03).
 *
 * Une materia ne s'ecrit pas, elle se derive (GAME_MATERIA §2.1) : cette
 * fixture ne contient **aucune entree de donnees**. Elle lit les nœuds
 * `actions.materia.unlock` des arbres charges en base, resout chaque sort,
 * et derive l'objet par `MateriaDerivation` (MAT-02). Plus aucun nœud
 * d'arbre ne promet ce qui n'existe pas — et un nœud qui citerait un sort
 * inconnu casse le chargement au lieu de mentir en silence.
 *
 * `domain` : l'arbre qui porte le nœud ; `null` des que plusieurs arbres
 * l'ouvrent — une materia n'appartient pas a un arbre, elle est *ouverte*
 * par lui.
 *
 * Reference de fixture : `materia_<slug du sort, underscores>` — la forme
 * que le butin (`MonsterItemFixtures`), les quetes et les inventaires de
 * demonstration citaient deja.
 */
class MateriaCatalogFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * Les materia qu'aucun nœud n'ouvre : vide depuis MAT-07, qui a raccroche
     * les sept dernieres au nœud terminal de l'arbre de leur element. La liste
     * reste le filet de securite du catalogue — une entree n'y survit que le
     * temps qu'un nœud l'ouvre (`MateriaCatalogTest::testOrphanListIsAccurate`),
     * et « aucune materia sans accord » est desormais un invariant teste.
     *
     * @var list<string>
     */
    public const ORPHAN_SPELLS = [];

    public function __construct(private readonly MateriaDerivation $derivation)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // Les unlocks des arbres, et les domaines qui les portent.
        /** @var array<string, array<int, \App\Entity\Game\Domain>> $domainsBySpell */
        $domainsBySpell = [];
        foreach ($manager->getRepository(Skill::class)->findAll() as $skill) {
            $actions = $skill->getActions() ?? [];
            $spellSlug = $actions['materia']['unlock'] ?? null;
            if (!\is_string($spellSlug) || $spellSlug === '') {
                continue;
            }
            $domainsBySpell[$spellSlug] ??= [];
            foreach ($skill->getDomains() as $domain) {
                $domainsBySpell[$spellSlug][$domain->getId()] = $domain;
            }
        }

        foreach (self::ORPHAN_SPELLS as $spellSlug) {
            $domainsBySpell[$spellSlug] ??= [];
        }

        ksort($domainsBySpell);

        foreach ($domainsBySpell as $spellSlug => $domains) {
            $spell = $manager->getRepository(Spell::class)->findOneBy(['slug' => $spellSlug]);
            if (null === $spell) {
                throw new \RuntimeException(sprintf('Un nœud d\'arbre ouvre la materia du sort "%s", qui n\'existe pas : le nœud ment (MAT-03).', $spellSlug));
            }

            $blueprint = $this->derivation->derive($spell);

            $item = new Item();
            $item->setName($blueprint->name);
            $item->setNameTranslations($blueprint->nameTranslations ?: null);
            $item->setDescription($blueprint->description);
            $item->setDescriptionTranslations($blueprint->descriptionTranslations ?: null);
            $item->setSlug($blueprint->slug);
            $item->setType(Item::TYPE_MATERIA);
            $item->setElement($blueprint->element);
            $item->setSpell($spell);
            $item->setPrice($blueprint->price);
            $item->setSpace(MateriaBlueprint::SPACE);
            $item->setEnergyCost($blueprint->energyCost);
            $item->setLevel($blueprint->tier);
            $item->setDomain(\count($domains) === 1 ? reset($domains) : null);
            // La rarete est une fonction du palier — la meme que
            // `ItemFixtures::inferRarity()` lit du prefixe de slug. Jamais
            // declaree en donnees (GAME_MATERIA §2.1).
            $item->setRarity(match ($blueprint->tier) {
                1 => ItemRarity::Uncommon,
                2 => ItemRarity::Rare,
                3 => ItemRarity::Epic,
                default => ItemRarity::Legendary,
            });
            $item->setCreatedAt(new \DateTime());
            $item->setUpdatedAt(new \DateTime());

            $manager->persist($item);
            $this->addReference('materia_' . str_replace('-', '_', $spellSlug), $item);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            SpellFixtures::class,
            SkillFixtures::class,
        ];
    }
}
