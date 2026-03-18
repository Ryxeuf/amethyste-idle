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
        // Utilisateur principal (Super Admin)
        $user = new User();
        $user->setEmail('remy@amethyste.game');
        $user->setUsername('remy');
        $user->setFirstName('Rémy');
        $user->setLastName('Mandon');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'test'));
        $user->setRoles(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN', 'ROLE_PLAYER']);
        $manager->persist($user);
        $this->addReference('user_remy', $user);

        // Admin
        $admin = new User();
        $admin->setEmail('admin@amethyste.game');
        $admin->setUsername('admin');
        $admin->setFirstName('Admin');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'test'));
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_PLAYER']);
        $admin->setCreatedAt(new \DateTime());
        $admin->setUpdatedAt(new \DateTime());
        $manager->persist($admin);
        $this->addReference('user_admin', $admin);

        // Game Designer
        $gameDesigner = new User();
        $gameDesigner->setEmail('designer@amethyste.game');
        $gameDesigner->setUsername('designer');
        $gameDesigner->setFirstName('Game Designer');
        $gameDesigner->setPassword($this->passwordHasher->hashPassword($gameDesigner, 'test'));
        $gameDesigner->setRoles(['ROLE_GAME_DESIGNER', 'ROLE_PLAYER']);
        $gameDesigner->setCreatedAt(new \DateTime());
        $gameDesigner->setUpdatedAt(new \DateTime());
        $manager->persist($gameDesigner);
        $this->addReference('user_designer', $gameDesigner);

        // World Builder
        $worldBuilder = new User();
        $worldBuilder->setEmail('builder@amethyste.game');
        $worldBuilder->setUsername('builder');
        $worldBuilder->setFirstName('World Builder');
        $worldBuilder->setPassword($this->passwordHasher->hashPassword($worldBuilder, 'test'));
        $worldBuilder->setRoles(['ROLE_WORLD_BUILDER', 'ROLE_PLAYER']);
        $worldBuilder->setCreatedAt(new \DateTime());
        $worldBuilder->setUpdatedAt(new \DateTime());
        $manager->persist($worldBuilder);
        $this->addReference('user_builder', $worldBuilder);

        // Moderateur
        $moderator = new User();
        $moderator->setEmail('moderator@amethyste.game');
        $moderator->setUsername('moderator');
        $moderator->setFirstName('Moderateur');
        $moderator->setPassword($this->passwordHasher->hashPassword($moderator, 'test'));
        $moderator->setRoles(['ROLE_MODERATOR', 'ROLE_PLAYER']);
        $moderator->setCreatedAt(new \DateTime());
        $moderator->setUpdatedAt(new \DateTime());
        $manager->persist($moderator);
        $this->addReference('user_moderator', $moderator);

        // Utilisateur demo
        $userDemo = new User();
        $userDemo->setEmail('demo@amethyste.fr');
        $userDemo->setUsername('demo');
        $userDemo->setRoles(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN', 'ROLE_PLAYER']);
        $userDemo->setPassword($this->passwordHasher->hashPassword($userDemo, 'test'));
        $userDemo->setCreatedAt(new \DateTime());
        $userDemo->setUpdatedAt(new \DateTime());
        $manager->persist($userDemo);
        $this->addReference('user_demo', $userDemo);

        // Utilisateur demo 2
        $userDemo2 = new User();
        $userDemo2->setEmail('demo2@amethyste.fr');
        $userDemo2->setUsername('demo2');
        $userDemo2->setRoles(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN', 'ROLE_PLAYER']);
        $userDemo2->setPassword($this->passwordHasher->hashPassword($userDemo2, 'test'));
        $userDemo2->setCreatedAt(new \DateTime());
        $userDemo2->setUpdatedAt(new \DateTime());
        $manager->persist($userDemo2);
        $this->addReference('user_demo_2', $userDemo2);

        $manager->flush();
    }
}
