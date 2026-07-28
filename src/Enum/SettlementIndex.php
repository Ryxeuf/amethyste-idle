<?php

namespace App\Enum;

/**
 * Les quatre indices de sediment d'un foyer (FOY-01).
 *
 * Un foyer n'a pas un compteur mais quatre, qui **decroissent independamment**
 * (emprunt aux indices d'activite d'EVE, cf. GAME_INSPIRATIONS §2.4). Le
 * **rang** se lit sur leur somme, le **type** sur le dominant.
 *
 * C'est ce qui permet a deux villes de meme rang de ne pas se ressembler : la
 * meme somme obtenue par la chasse ou par la quete ne fait pas la meme ville.
 */
enum SettlementIndex: string
{
    case Trade = 'trade';
    case War = 'war';
    case Lore = 'lore';
    case Rite = 'rite';

    public function label(): string
    {
        return match ($this) {
            self::Trade => 'Negoce',
            self::War => 'Guerre',
            self::Lore => 'Savoir',
            self::Rite => 'Rite',
        };
    }

    /**
     * Ce qui alimente l'indice, en clair — pour l'ecran de zone (FOY-04) : un
     * joueur doit pouvoir lire *comment* faire monter sa ville.
     */
    public function fedBy(): string
    {
        return match ($this) {
            self::Trade => 'Recolte, peche, depecage, artisanat et ventes',
            self::War => 'Combats, donjons et assauts de boss',
            self::Lore => 'Quetes, premieres visites et entrees de Codex',
            self::Rite => 'Materia lue et participation aux marees',
        };
    }
}
