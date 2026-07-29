<?php

namespace App\GameEngine\Guild;

use App\Entity\App\Guild;
use App\Entity\App\GuildMember;
use App\Entity\App\Player;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Qui peut engager le tresor d'une guilde sur un acte de gouvernement.
 *
 * La regle est simple et tient en une phrase : **c'est l'autorite qui gouverne
 * deja la depense**. Restaurer un filon (FOY-12) ou doter un foyer d'un atelier
 * (FOY-13) sont des retraits du tresor ; rien ne justifierait qu'ils obeissent a
 * une regle plus permissive qu'un retrait ordinaire.
 *
 * Elle vit ici plutot que dans chacun de ces services parce qu'elle est **la
 * meme**. Le jour ou un troisieme acte de gouvernement arrive, il n'y aura pas
 * de troisieme facon de repondre a la question.
 *
 * Le refus rend un **motif** et non un message : chaque appelant nomme ses
 * propres clefs de traduction, et l'ecran ou l'on refuse dit ce qu'il refuse.
 */
class GuildSpendingAuthority
{
    public const REASON_NO_GUILD = 'no_guild';
    public const REASON_RANK_TOO_LOW = 'rank_too_low';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * La guilde au nom de laquelle ce joueur peut depenser, ou le motif du refus.
     *
     * @return array{0: ?Guild, 1: ?string} la guilde, ou `null` et un motif
     */
    public function resolve(Player $player): array
    {
        $membership = $this->entityManager->getRepository(GuildMember::class)->findOneBy(['player' => $player]);
        if (!$membership instanceof GuildMember) {
            return [null, self::REASON_NO_GUILD];
        }

        if (!$membership->getRank()->canWithdraw()) {
            return [null, self::REASON_RANK_TOO_LOW];
        }

        return [$membership->getGuild(), null];
    }
}
