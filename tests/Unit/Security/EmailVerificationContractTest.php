<?php

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Security\Attribute\RequiresVerifiedEmail;
use App\Security\EmailVerificationGate;
use PHPUnit\Framework\TestCase;

/**
 * Le contrat de la porte de verification (ONB-04).
 *
 * Trois lois, tenues par le texte et la reflexion :
 *
 * 1. **Un seul point de decision** — personne ne lit l'etat de verification
 *    hors du sous-systeme : ni un controleur, ni un gabarit, ni un moteur.
 * 2. **Chaque porte est fermee** — la liste canonique du cadrage (§3.2) est
 *    verifiee action par action, attribut par attribut.
 * 3. **Aucun blocage retroactif** — la migration qui livre la porte marque
 *    verifies les comptes d'avant elle.
 */
class EmailVerificationContractTest extends TestCase
{
    /**
     * Les fichiers autorises a connaitre l'etat de verification. Tout le
     * reste passe par `EmailVerificationGate` — y compris les gabarits, via
     * `EmailVerificationExtension` qui delegue a la porte.
     */
    private const ALLOWED_READERS = [
        'src/Entity/User.php',
        'src/Security/EmailVerificationGate.php',
        'src/Security/EmailVerificationManager.php',
        'src/Repository/UserRepository.php',
    ];

    /**
     * La liste canonique des portes (GAME_ONBOARDING §3.2) : chaque action
     * fermee, et le canal qu'elle declare. Fermer une porte de plus = poser
     * l'attribut ET ajouter la ligne ici.
     */
    private const GATED_ACTIONS = [
        [\App\Controller\Game\ChatController::class, 'send', 'chat'],
        [\App\Controller\Api\V1\ChatController::class, 'send', 'chat'],
        [\App\Controller\Game\MessageController::class, 'send', 'messages'],
        [\App\Controller\Game\FriendController::class, 'sendRequest', 'friends'],
        [\App\Controller\Game\FriendController::class, 'accept', 'friends'],
        [\App\Controller\Game\GuildController::class, 'create', 'guild'],
        [\App\Controller\Game\GuildController::class, 'invite', 'guild'],
        [\App\Controller\Game\GuildController::class, 'acceptInvitation', 'guild'],
        [\App\Controller\Game\PartyController::class, 'create', 'party'],
        [\App\Controller\Game\PartyController::class, 'invite', 'party'],
        [\App\Controller\Game\PartyController::class, 'acceptInvitation', 'party'],
        [\App\Controller\Game\GroupDungeonController::class, 'launch', 'dungeon'],
        [\App\Controller\Game\PlayerShopController::class, 'open', 'shop'],
        [\App\Controller\Game\PlayerShopController::class, 'stock', 'shop'],
        [\App\Controller\Game\PlayerShopController::class, 'buy', 'shop'],
        [\App\Controller\Game\AuctionController::class, 'sell', 'auction'],
        [\App\Controller\Game\AuctionController::class, 'bid', 'auction'],
        [\App\Controller\Game\AuctionController::class, 'buy', 'auction'],
        [\App\Controller\Game\WeeklyCommissionController::class, 'deliver', 'commission'],
    ];

    /**
     * Loi 1 — le point de decision unique. `isEmailVerified`,
     * `getEmailVerifiedAt` et `setEmailVerifiedAt` n'apparaissent que dans le
     * sous-systeme de verification. La migration et les tests sont hors
     * perimetre : ils constatent, ils ne decident pas.
     */
    public function testNobodyReadsVerificationStateOutsideTheGate(): void
    {
        $root = \dirname(__DIR__, 3);
        $offenders = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root . '/src', \RecursiveDirectoryIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            /* @var \SplFileInfo $file */
            if (!$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), \strlen($root) + 1));
            if (\in_array($relative, self::ALLOWED_READERS, true)) {
                continue;
            }
            $content = (string) file_get_contents($file->getPathname());
            if (preg_match('/isEmailVerified|EmailVerifiedAt/', $content)) {
                $offenders[] = $relative;
            }
        }

        self::assertSame([], $offenders, sprintf(
            "Ces fichiers lisent l'etat de verification hors du point unique :\n%s\nPasser par EmailVerificationGate.",
            implode("\n", $offenders),
        ));
    }

    /**
     * Loi 2 — chaque porte de la liste canonique est fermee par l'attribut,
     * avec le bon canal.
     */
    public function testEveryCanonicalGateIsClosed(): void
    {
        foreach (self::GATED_ACTIONS as [$class, $method, $channel]) {
            $reflection = new \ReflectionMethod($class, $method);
            $attributes = $reflection->getAttributes(RequiresVerifiedEmail::class);

            self::assertNotEmpty($attributes, sprintf('%s::%s doit porter #[RequiresVerifiedEmail].', $class, $method));
            self::assertSame($channel, $attributes[0]->newInstance()->channel, sprintf('%s::%s declare le mauvais canal.', $class, $method));
        }
    }

    /**
     * Chaque canal declare dans une porte existe dans la table de l'ecran —
     * une porte fermee que l'ecran ne sait pas nommer serait une panne, pas
     * une porte.
     */
    public function testEveryDeclaredChannelHasALabel(): void
    {
        foreach (self::GATED_ACTIONS as [$class, $method, $channel]) {
            self::assertArrayHasKey($channel, EmailVerificationGate::CHANNELS);
        }
    }

    /**
     * Loi 3 — aucun blocage retroactif : la migration qui livre la porte
     * marque verifies tous les comptes d'avant elle.
     */
    public function testTheMigrationGrandfathersEveryExistingAccount(): void
    {
        $migration = (string) file_get_contents(
            \dirname(__DIR__, 3) . '/migrations/Version20260803HEmailVerification.php',
        );

        self::assertStringContainsString(
            'UPDATE "users" SET email_verified_at = NOW() WHERE email_verified_at IS NULL',
            $migration,
            'La porte ne vaut que pour les comptes nes apres elle.',
        );
    }

    /**
     * La porte elle-meme : fermee sans compte, fermee non verifie, ouverte
     * verifie — et le meme verdict pour tous les canaux.
     */
    public function testTheGateAnswersTheSameForEveryChannel(): void
    {
        $gate = new EmailVerificationGate();

        $unverified = new User();
        $unverified->setEmail('nouveau@exemple.fr');

        $verified = new User();
        $verified->setEmail('verifie@exemple.fr');
        $verified->setEmailVerifiedAt(new \DateTimeImmutable());

        foreach (array_keys(EmailVerificationGate::CHANNELS) as $channel) {
            self::assertFalse($gate->allows(null, $channel));
            self::assertFalse($gate->allows($unverified, $channel));
            self::assertTrue($gate->allows($verified, $channel));
        }
    }
}
