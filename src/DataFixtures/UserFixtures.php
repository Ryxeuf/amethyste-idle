<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Création de l'utilisateur par défaut
        $user = new User();
        $user->setEmail('remy@amethyste.game');
        $user->setUsername('remy');
        $user->setFirstName('Rémy');
        $user->setLastName('Mandon');

        // Hashage du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            'test'
        );
        $user->setPassword($hashedPassword);
        
        // Attribution des rôles (optionnel)
        $user->setRoles(['ROLE_USER']);
        
        // Persistance de l'utilisateur
        $manager->persist($user);
        $manager->flush();
    }
} 