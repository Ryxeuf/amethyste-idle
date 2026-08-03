<?php

namespace App\Security;

use App\Entity\User;

/**
 * Le point de decision unique de la verification d'e-mail (ONB-04).
 *
 * **Personne d'autre ne lit `User::isEmailVerified()`.** Toutes les portes —
 * le listener HTTP, la ligne du hub, l'ecran de porte, les rappels —
 * consultent ce service. Semer la question dans les appelants, c'est garantir
 * qu'un ecran finira par repondre autrement que le guichet
 * (`OnboardingVerificationContractTest` tient cette loi par le texte).
 *
 * La porte ne barre jamais le jeu : explorer, chasser, recolter, l'acte I en
 * entier, les boutiques PNJ restent ouverts. Elle barre ce qui **sort du
 * joueur vers les autres** — et notamment la livraison de commission, car une
 * ferme de comptes non verifies ne doit pas faire monter un foyer (la Crue
 * indexe le quota de grandes cites sur la population active).
 *
 * **Aucun blocage retroactif** : les comptes d'avant la porte sont marques
 * verifies par la migration qui la livre. La regle ne vit pas ici — un compte
 * est verifie ou ne l'est pas, et l'histoire de comment il l'est devenu
 * appartient a la migration.
 */
class EmailVerificationGate
{
    /**
     * Les portes fermees, dans l'ordre ou l'ecran les montre. La cle est le
     * canal declare par l'attribut `RequiresVerifiedEmail`, la valeur la cle
     * de traduction de son libelle.
     *
     * @var array<string, string>
     */
    public const CHANNELS = [
        'chat' => 'security.verification.channel.chat',
        'auction' => 'security.verification.channel.auction',
        'shop' => 'security.verification.channel.shop',
        'guild' => 'security.verification.channel.guild',
        'party' => 'security.verification.channel.party',
        'dungeon' => 'security.verification.channel.dungeon',
        'messages' => 'security.verification.channel.messages',
        'friends' => 'security.verification.channel.friends',
        'commission' => 'security.verification.channel.commission',
    ];

    public function isVerified(?User $user): bool
    {
        return $user !== null && $user->isEmailVerified();
    }

    /**
     * La porte est-elle ouverte pour ce compte ? Aujourd'hui la reponse ne
     * depend pas du canal — toutes les portes s'ouvrent d'un coup, au clic
     * dans l'e-mail — mais la signature le porte : c'est le contrat que les
     * appelants connaissent, et le jour ou une porte s'ouvrira autrement, un
     * seul endroit changera.
     */
    public function allows(?User $user, string $channel): bool
    {
        \assert(\array_key_exists($channel, self::CHANNELS), sprintf('Canal de porte inconnu : "%s".', $channel));

        return $this->isVerified($user);
    }
}
