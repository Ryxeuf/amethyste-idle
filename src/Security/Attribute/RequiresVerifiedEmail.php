<?php

namespace App\Security\Attribute;

/**
 * Marque une action fermee jusqu'a la verification de l'e-mail (ONB-04).
 *
 * La verification ne barre pas le jeu, elle barre **tout ce qui sort du
 * joueur vers les autres** (GAME_ONBOARDING §3.2) : chat, hotel des ventes,
 * echoppe, guilde, groupe, donjon, messages, amis, livraison de commission.
 *
 * L'attribut ne decide rien : il declare. La decision appartient a
 * `EmailVerificationGate` — le point unique —, et l'aiguillage au listener
 * `EmailVerificationGateListener`. Fermer une porte de plus, c'est poser cet
 * attribut sur l'action et ajouter la ligne au contrat de test.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
final class RequiresVerifiedEmail
{
    public function __construct(
        /**
         * Le nom de la porte, pour l'ecran qui explique ce qui est verrouille.
         */
        public readonly string $channel,
    ) {
    }
}
