<?php

namespace App\DataFixtures\Game;

use App\DataFixtures\DomainFixtures;
use App\DataFixtures\SpellFixtures;
use App\Entity\Game\Domain;
use App\Entity\Game\Item;
use App\Entity\Game\Skill;
use App\Entity\Game\Spell;
use App\Enum\Element;
use App\Enum\ItemRarity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class ItemFixtures extends Fixture implements DependentFixtureInterface
{
    private const FIXTURES_DIR = __DIR__ . '/../../../fixtures/game/item';

    public function load(ObjectManager $manager): void
    {
        $finder = new Finder();
        $finder->files()->in(self::FIXTURES_DIR)->name('*.yaml')->notName('item.yaml');

        foreach ($finder as $file) {
            $content = Yaml::parseFile($file->getRealPath());

            if (!isset($content['Api\Entity\Game\Item']) && !isset($content['App\Entity\Game\Item'])) {
                continue;
            }

            $items = isset($content['Api\Entity\Game\Item']) ? $content['Api\Entity\Game\Item'] : $content['App\Entity\Game\Item'];

            foreach ($items as $rawReference => $data) {
                if ($rawReference === 'item (template)') {
                    continue;
                }

                // Strip " (extends ...)" suffix from reference name
                $reference = preg_replace('/\s*\(extends\s+.*\)$/', '', $rawReference);

                $item = new Item();

                if (isset($data['id'])) {
                    // Utiliser la méthode de réflexion pour définir l'ID si nécessaire
                    $reflectionClass = new \ReflectionClass(Item::class);
                    $property = $reflectionClass->getProperty('id');
                    $property->setAccessible(true);
                    $property->setValue($item, (int) $data['id']);
                }

                if (isset($data['name'])) {
                    $item->setName((string) $data['name']);
                }

                if (isset($data['description'])) {
                    $item->setDescription((string) $data['description']);
                }

                if (isset($data['type'])) {
                    $item->setType((string) $data['type']);
                }

                if (isset($data['slug'])) {
                    $item->setSlug((string) $data['slug']);
                }

                if (isset($data['level'])) {
                    $item->setLevel((int) $data['level']);
                }

                if (isset($data['effect'])) {
                    // Utiliser la méthode de réflexion pour définir l'effet
                    $reflectionClass = new \ReflectionClass(Item::class);
                    $property = $reflectionClass->getProperty('effect');
                    $property->setAccessible(true);
                    $property->setValue($item, $data['effect']);
                }

                if (isset($data['nbUsages'])) {
                    $item->setNbUsages((int) $data['nbUsages']);
                }

                if (isset($data['domain']) && $data['domain'] !== null) {
                    $domainReference = substr($data['domain'], 1); // Remove the @ symbol
                    $item->setDomain($this->getReference($domainReference, Domain::class));
                }

                if (isset($data['spell']) && $data['spell'] !== null) {
                    $spellReference = substr($data['spell'], 1); // Remove the @ symbol
                    $item->setSpell($this->getReference($spellReference, Spell::class));
                }

                if (isset($data['boundToPlayer'])) {
                    $item->setBoundToPlayer((bool) $data['boundToPlayer']);
                }

                if (isset($data['gear_location'])) {
                    $item->setGearLocation((string) $data['gear_location']);
                }

                if (isset($data['price'])) {
                    $item->setPrice((int) $data['price']);
                }

                if (isset($data['protection'])) {
                    $item->setProtection((int) $data['protection']);
                }

                if (isset($data['element'])) {
                    $elementEnum = Element::tryFrom((string) $data['element']);
                    if ($elementEnum !== null) {
                        $item->setElement($elementEnum);
                    }
                }

                if (isset($data['rarity'])) {
                    $item->setRarity(ItemRarity::tryFrom((string) $data['rarity']));
                }

                if (isset($data['space'])) {
                    $item->setSpace((int) $data['space']);
                }

                if (isset($data['energyCost'])) {
                    $item->setEnergyCost((int) $data['energyCost']);
                }

                if (isset($data['value'])) {
                    $item->setValue((int) $data['value']);
                }

                if (isset($data['toolType'])) {
                    $item->setToolType((string) $data['toolType']);
                }

                if (isset($data['toolTier'])) {
                    $item->setToolTier((int) $data['toolTier']);
                }

                if (isset($data['durability'])) {
                    $item->setDurability((int) $data['durability']);
                }

                if (isset($data['materia_slots'])) {
                    $item->setMateriaSlots((int) $data['materia_slots']);
                }

                $manager->persist($item);
                $this->addReference($reference, $item);
            }
        }

        // Second pass to set requirements after all items are created
        foreach ($finder as $file) {
            $content = Yaml::parseFile($file->getRealPath());

            if (!isset($content['Api\Entity\Game\Item']) && !isset($content['App\Entity\Game\Item'])) {
                continue;
            }

            $items = isset($content['Api\Entity\Game\Item']) ? $content['Api\Entity\Game\Item'] : $content['App\Entity\Game\Item'];

            foreach ($items as $rawReference => $data) {
                if ($rawReference === 'item (template)' || !isset($data['requirements'])) {
                    continue;
                }

                $reference = preg_replace('/\s*\(extends\s+.*\)$/', '', $rawReference);
                $item = $this->getReference($reference, Item::class);

                foreach ($data['requirements'] as $requirementRef) {
                    $requirementName = substr($requirementRef, 1); // Remove the @ symbol
                    $item->addRequirement($this->getReference($requirementName, Skill::class));
                }
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            DomainFixtures::class,
            SpellFixtures::class,
            SkillFixtures::class,
        ];
    }
}
