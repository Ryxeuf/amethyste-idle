<?php

namespace App\DataFixtures\Game;

use App\Entity\Game\Item;
use App\Entity\Game\Domain;
use App\Entity\Game\Spell;
use App\Entity\Game\Skill;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Finder;
use App\DataFixtures\DomainFixtures;
use App\DataFixtures\SpellFixtures;

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
            
            foreach ($items as $reference => $data) {
                if ($reference === 'item (template)') {
                    continue;
                }
                
                $item = new Item();
                
                if (isset($data['id'])) {
                    // Utiliser la méthode de réflexion pour définir l'ID si nécessaire
                    $reflectionClass = new \ReflectionClass(Item::class);
                    $property = $reflectionClass->getProperty('id');
                    $property->setAccessible(true);
                    $property->setValue($item, (int)$data['id']);
                }
                
                if (isset($data['name'])) {
                    $item->setName((string)$data['name']);
                }
                
                if (isset($data['description'])) {
                    $item->setDescription((string)$data['description']);
                }
                
                if (isset($data['type'])) {
                    $item->setType((string)$data['type']);
                }
                
                if (isset($data['slug'])) {
                    $item->setSlug((string)$data['slug']);
                }
                
                if (isset($data['level'])) {
                    $item->setLevel((int)$data['level']);
                }
                
                if (isset($data['effect'])) {
                    // Utiliser la méthode de réflexion pour définir l'effet
                    $reflectionClass = new \ReflectionClass(Item::class);
                    $property = $reflectionClass->getProperty('effect');
                    $property->setAccessible(true);
                    $property->setValue($item, $data['effect']);
                }
                
                if (isset($data['nbUsages'])) {
                    $item->setNbUsages((int)$data['nbUsages']);
                }
                
                if (isset($data['domain']) && $data['domain'] !== null) {
                    $domainReference = substr($data['domain'], 1); // Remove the @ symbol
                    $item->setDomain($this->getReference($domainReference, Domain::class));
                }
                
                if (isset($data['spell']) && $data['spell'] !== null) {
                    $spellReference = substr($data['spell'], 1); // Remove the @ symbol
                    $item->setSpell($this->getReference($spellReference, Spell::class));
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
            
            foreach ($items as $reference => $data) {
                if ($reference === 'item (template)' || !isset($data['requirements'])) {
                    continue;
                }
                
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