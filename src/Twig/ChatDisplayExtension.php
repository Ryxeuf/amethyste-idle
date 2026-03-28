<?php

namespace App\Twig;

use App\Entity\App\GuildMember;
use App\Entity\App\Player;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ChatDisplayExtension extends AbstractExtension
{
    /** @var array<int, string|null> */
    private array $guildTagCache = [];

    /** @var array<int, string|null> */
    private array $guildColorCache = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('player_guild_tag', $this->getPlayerGuildTag(...)),
            new TwigFunction('player_guild_color', $this->getPlayerGuildColor(...)),
        ];
    }

    public function getPlayerGuildTag(Player $player): ?string
    {
        $playerId = $player->getId();

        if (\array_key_exists($playerId, $this->guildTagCache)) {
            return $this->guildTagCache[$playerId];
        }

        $guildMember = $this->em->getRepository(GuildMember::class)->findOneBy(['player' => $player]);

        $tag = $guildMember?->getGuild()->getTag();
        $this->guildTagCache[$playerId] = $tag;

        return $tag;
    }

    public function getPlayerGuildColor(Player $player): ?string
    {
        $playerId = $player->getId();

        if (\array_key_exists($playerId, $this->guildColorCache)) {
            return $this->guildColorCache[$playerId];
        }

        $guildMember = $this->em->getRepository(GuildMember::class)->findOneBy(['player' => $player]);

        $color = $guildMember?->getGuild()->getColor();
        $this->guildColorCache[$playerId] = $color;

        return $color;
    }
}
