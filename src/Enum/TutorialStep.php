<?php

namespace App\Enum;

enum TutorialStep: int
{
    case Movement = 0;
    case Combat = 1;
    case Inventory = 2;
    case Quests = 3;
    case Craft = 4;

    public function label(): string
    {
        return match ($this) {
            self::Movement => 'Deplacement',
            self::Combat => 'Combat',
            self::Inventory => 'Inventaire',
            self::Quests => 'Quetes',
            self::Craft => 'Artisanat',
        };
    }

    public function objective(): string
    {
        return match ($this) {
            self::Movement => 'Deplacez-vous sur la carte en cliquant sur une case adjacente.',
            self::Combat => 'Combattez un monstre et remportez la victoire !',
            self::Inventory => 'Recuperez votre butin apres un combat.',
            self::Quests => 'Completez votre premiere quete.',
            self::Craft => 'Fabriquez votre premier objet a l\'atelier.',
        };
    }

    public function stepNumber(): int
    {
        return $this->value + 1;
    }

    public static function totalSteps(): int
    {
        return count(self::cases());
    }

    public function next(): ?self
    {
        return self::tryFrom($this->value + 1);
    }
}
