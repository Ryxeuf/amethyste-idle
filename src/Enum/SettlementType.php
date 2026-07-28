<?php

namespace App\Enum;

/**
 * Identite d'un foyer (FOY-01).
 *
 * Le type ne se choisit pas : il se **deduit** de l'indice dominant. Une zone
 * ou l'on se bat devient un Bastion, une zone ou l'on commerce un Comptoir —
 * sans qu'aucune guilde n'ait rien a decider.
 *
 * Il ne s'installe qu'a partir du Hameau, et seulement si le dominant tient son
 * avance une maree entiere ([BALANCE.md § 23.4](../../docs/BALANCE.md)). Sans
 * cette hysteresis, le type clignoterait au gre des semaines et la ville
 * n'aurait pas d'identite — c'est le risque nomme dans PLAN_SETTLEMENTS.
 */
enum SettlementType: string
{
    case Trading = 'trading';
    case Bastion = 'bastion';
    case Athenaeum = 'athenaeum';
    case Sanctuary = 'sanctuary';

    public function label(): string
    {
        return match ($this) {
            self::Trading => 'Comptoir',
            self::Bastion => 'Bastion',
            self::Athenaeum => 'Athenee',
            self::Sanctuary => 'Sanctuaire',
        };
    }

    /**
     * Indice de sediment dont ce type est la consequence.
     */
    public function index(): SettlementIndex
    {
        return match ($this) {
            self::Trading => SettlementIndex::Trade,
            self::Bastion => SettlementIndex::War,
            self::Athenaeum => SettlementIndex::Lore,
            self::Sanctuary => SettlementIndex::Rite,
        };
    }

    public static function fromIndex(SettlementIndex $index): self
    {
        return match ($index) {
            SettlementIndex::Trade => self::Trading,
            SettlementIndex::War => self::Bastion,
            SettlementIndex::Lore => self::Athenaeum,
            SettlementIndex::Rite => self::Sanctuary,
        };
    }
}
