<?php

namespace App\DataFixtures\Game;

use App\Entity\Game\Quest;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Finder;

class QuestFixtures extends Fixture
{
    private const FIXTURES_DIR = __DIR__ . '/../../../fixtures/game/quest';

    public function load(ObjectManager $manager): void
    {
        $finder = new Finder();
        $finder->files()->in(self::FIXTURES_DIR)->name('*.yaml');

        foreach ($finder as $file) {
            $content = Yaml::parseFile($file->getRealPath());
            
            if (!isset($content['Api\Entity\Game\Quest']) && !isset($content['App\Entity\Game\Quest'])) {
                continue;
            }

            $quests = isset($content['Api\Entity\Game\Quest']) ? $content['Api\Entity\Game\Quest'] : $content['App\Entity\Game\Quest'];
            
            foreach ($quests as $reference => $data) {
                if ($reference === 'quest (template)') {
                    continue;
                }
                
                // Ignorer les quêtes commentées
                if (strpos($reference, '#') === 0) {
                    continue;
                }
                
                $quest = new Quest();
                
                if (isset($data['name'])) {
                    $quest->setName((string)$data['name']);
                }
                
                if (isset($data['description'])) {
                    $quest->setDescription((string)$data['description']);
                }
                
                if (isset($data['requirements'])) {
                    $quest->setRequirements($data['requirements']);
                }
                
                if (isset($data['rewards'])) {
                    $quest->setRewards($data['rewards']);
                }
                
                $manager->persist($quest);
                $this->addReference($reference, $quest);
            }
        }
        
        $manager->flush();
    }
} 