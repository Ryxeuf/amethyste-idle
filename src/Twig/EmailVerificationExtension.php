<?php

namespace App\Twig;

use App\Entity\User;
use App\Security\EmailVerificationGate;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * La porte de verification vue des gabarits (ONB-04).
 *
 * La ligne du hub et l'ecran de porte ne lisent jamais `User` directement :
 * ils demandent ici, et ce service delegue au point de decision unique
 * (`EmailVerificationGate`). Un gabarit qui repondrait tout seul finirait par
 * repondre autrement que le guichet.
 */
final class EmailVerificationExtension extends AbstractExtension
{
    public function __construct(
        private readonly Security $security,
        private readonly EmailVerificationGate $gate,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('email_verification_pending', $this->isPending(...)),
        ];
    }

    /**
     * Vrai quand le compte connecte attend encore sa verification — c'est la
     * condition de la ligne du hub, et d'elle seule.
     */
    public function isPending(): bool
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return !$this->gate->isVerified($user);
    }
}
