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
        $user->setRoles(['ROLE_USER']);
        $user->setRoles(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN', 'ROLE_PLAYER']);

        // Persistance de l'utilisateur
        $manager->persist($user);
        $this->addReference('user_remy', $user);

        // Utilisateur demo
        $userDemo = new User();
        $userDemo->setEmail('demo@amethyste.fr');
        $userDemo->setUsername('demo');
        $userDemo->setRoles(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN', 'ROLE_PLAYER']);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $userDemo,
            'test'
        );
        $userDemo->setPassword($hashedPassword);
        $userDemo->setCreatedAt(new \DateTime());
        $userDemo->setUpdatedAt(new \DateTime());

        $manager->persist($userDemo);
        $this->addReference('user_demo', $userDemo);

        // Utilisateur demo 2
        $userDemo2 = new User();
        $userDemo2->setEmail('demo2@amethyste.fr');
        $userDemo2->setUsername('demo2');
        $userDemo2->setRoles(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN', 'ROLE_PLAYER']);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $userDemo2,
            'test'
        );
        $userDemo2->setPassword($hashedPassword);
        $userDemo2->setCreatedAt(new \DateTime());
        $userDemo2->setUpdatedAt(new \DateTime());

        $manager->persist($userDemo2);
        $this->addReference('user_demo_2', $userDemo2);

        $manager->flush();
    }
}
