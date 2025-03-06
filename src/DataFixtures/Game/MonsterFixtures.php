<?php

namespace App\DataFixtures\Game;

use App\Entity\Game\Monster;
use App\Entity\Game\Spell;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Finder;
use App\DataFixtures\SpellFixtures;

class MonsterFixtures extends Fixture implements DependentFixtureInterface
{
    private const FIXTURES_DIR = __DIR__ . '/../../../fixtures/game/monster';

    public function load(ObjectManager $manager): void
    {
        $finder = new Finder();
        $finder->files()->in(self::FIXTURES_DIR)->name('*.yaml')->notName('monster.yaml');

        // Si le fichier monster.yaml contient des données, on le traite également
        $monsterYamlPath = self::FIXTURES_DIR . '/monster.yaml';
        if (file_exists($monsterYamlPath)) {
            $content = Yaml::parseFile($monsterYamlPath);
            
            if (isset($content['Api\Entity\Game\Monster']) || isset($content['App\Entity\Game\Monster'])) {
                $monsters = isset($content['Api\Entity\Game\Monster']) ? $content['Api\Entity\Game\Monster'] : $content['App\Entity\Game\Monster'];
                
                foreach ($monsters as $reference => $data) {
                    if ($reference === 'monster (template)') {
                        continue;
                    }
                    
                    $monster = new Monster();
                    
                    if (isset($data['name'])) {
                        $monster->setName((string)$data['name']);
                    }
                    
                    if (isset($data['slug'])) {
                        $monster->setSlug((string)$data['slug']);
                    }
                    
                    if (isset($data['life'])) {
                        $monster->setLife((int)$data['life']);
                    }
                    
                    if (isset($data['speed'])) {
                        $monster->setSpeed((int)$data['speed']);
                    }
                    
                    if (isset($data['hit'])) {
                        $monster->setHit((int)$data['hit']);
                    }
                    
                    if (isset($data['attack'])) {
                        $attackReference = substr($data['attack'], 1); // Remove the @ symbol
                        $monster->setAttack($this->getReference($attackReference, Spell::class));
                    }
                    
                    $manager->persist($monster);
                    $this->addReference($reference, $monster);
                }
            }
        }
        
        // Traitement des autres fichiers YAML dans le dossier
        foreach ($finder as $file) {
            $content = Yaml::parseFile($file->getRealPath());
            
            if (!isset($content['Api\Entity\Game\Monster']) && !isset($content['App\Entity\Game\Monster'])) {
                continue;
            }

            $monsters = isset($content['Api\Entity\Game\Monster']) ? $content['Api\Entity\Game\Monster'] : $content['App\Entity\Game\Monster'];
            
            foreach ($monsters as $reference => $data) {
                if ($reference === 'monster (template)') {
                    continue;
                }
                
                $monster = new Monster();
                
                if (isset($data['name'])) {
                    $monster->setName((string)$data['name']);
                }
                
                if (isset($data['slug'])) {
                    $monster->setSlug((string)$data['slug']);
                }
                
                if (isset($data['life'])) {
                    $monster->setLife((int)$data['life']);
                }
                
                if (isset($data['speed'])) {
                    $monster->setSpeed((int)$data['speed']);
                }
                
                if (isset($data['hit'])) {
                    $monster->setHit((int)$data['hit']);
                }
                
                if (isset($data['attack'])) {
                    $attackReference = substr($data['attack'], 1); // Remove the @ symbol
                    $monster->setAttack($this->getReference($attackReference, Spell::class));
                }
                
                $manager->persist($monster);
                $this->addReference($reference, $monster);
            }
        }
        
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            SpellFixtures::class,
        ];
    }
} 