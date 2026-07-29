<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Security;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * ONB-01 — l'inscription.
 *
 * Chaque test part d'une adresse IP distincte : le limiteur de 5 comptes par
 * heure est partage par tout le suite sinon, et l'ordre d'execution deciderait
 * du resultat.
 */
class RegistrationControllerTest extends WebTestCase
{
    private string $lastEmail = '';

    public function testRegistrationPageIsReachableWithoutAccount(): void
    {
        $client = $this->clientFromIp('203.0.113.10');
        $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#registration_form_email');
    }

    public function testAccountIsCreatedThenSentToCharacterCreation(): void
    {
        $client = $this->clientFromIp('203.0.113.11');
        $email = $this->uniqueEmail();

        $this->submitRegistration($client, $email, 'un-mot-de-passe-tres-long');

        $this->assertResponseRedirects('/game/character/create');

        $user = $this->findUser($email);
        $this->assertInstanceOf(User::class, $user);
        $this->assertNotSame('', $user->getPassword(), 'Le mot de passe doit etre hache et stocke.');
        $this->assertContains('ROLE_PLAYER', $user->getRoles());
    }

    /**
     * Decision A1 : le compte nait non verifie, et cela ne l'empeche pas de jouer.
     */
    public function testFreshAccountIsNotEmailVerified(): void
    {
        $client = $this->clientFromIp('203.0.113.12');
        $email = $this->uniqueEmail();

        $this->submitRegistration($client, $email, 'un-mot-de-passe-tres-long');

        $user = $this->findUser($email);
        $this->assertInstanceOf(User::class, $user);
        $this->assertNull($user->getEmailVerifiedAt());
        $this->assertFalse($user->isEmailVerified());
    }

    public function testAlreadyRegisteredEmailIsRejected(): void
    {
        $client = $this->clientFromIp('203.0.113.13');

        $this->submitRegistration($client, 'demo@amethyste.fr', 'un-mot-de-passe-tres-long');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#registration_form_email');
    }

    public function testEmailUniquenessIgnoresCase(): void
    {
        $client = $this->clientFromIp('203.0.113.14');

        $this->submitRegistration($client, 'DEMO@Amethyste.fr', 'un-mot-de-passe-tres-long');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#registration_form_email');
    }

    public function testTooShortPasswordIsRejected(): void
    {
        $client = $this->clientFromIp('203.0.113.15');
        $short = str_repeat('a', RegistrationFormType::PASSWORD_MIN_LENGTH - 1);

        $this->submitRegistration($client, $this->uniqueEmail(), $short);

        $this->assertResponseIsSuccessful();
        $this->assertNull($this->findUser($this->lastEmail));
    }

    public function testTermsMustBeAccepted(): void
    {
        $client = $this->clientFromIp('203.0.113.16');
        $email = $this->uniqueEmail();

        $this->submitRegistration($client, $email, 'un-mot-de-passe-tres-long', agreeTerms: false);

        $this->assertResponseIsSuccessful();
        $this->assertNull($this->findUser($email));
    }

    public function testSixthAccountFromTheSameAddressIsRefused(): void
    {
        $client = $this->clientFromIp('203.0.113.17');

        for ($i = 0; $i < 5; ++$i) {
            $this->submitRegistration($client, $this->uniqueEmail(), 'un-mot-de-passe-tres-long');
            $this->assertResponseRedirects('/game/character/create', message: sprintf('Compte %d refuse trop tot.', $i + 1));
            $this->logOut($client);
        }

        $this->submitRegistration($client, $this->uniqueEmail(), 'un-mot-de-passe-tres-long');
        $this->assertResponseStatusCodeSame(429);
    }

    private function clientFromIp(string $ip): KernelBrowser
    {
        return static::createClient([], ['REMOTE_ADDR' => $ip]);
    }

    private function uniqueEmail(): string
    {
        return $this->lastEmail = sprintf('onb01-%s@example.test', bin2hex(random_bytes(8)));
    }

    private function submitRegistration(
        KernelBrowser $client,
        string $email,
        string $password,
        bool $agreeTerms = true,
    ): void {
        $client->request('GET', '/register');

        $values = [
            'registration_form[email]' => $email,
            'registration_form[plainPassword]' => $password,
        ];

        if ($agreeTerms) {
            $values['registration_form[agreeTerms]'] = '1';
        }

        $client->submitForm('registration_submit', $values);
    }

    private function logOut(KernelBrowser $client): void
    {
        $client->getCookieJar()->clear();
    }

    private function findUser(string $email): ?User
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        return $entityManager->getRepository(User::class)->findOneBy(['email' => mb_strtolower($email)]);
    }
}
