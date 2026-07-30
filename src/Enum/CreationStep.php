<?php

namespace App\Enum;

/**
 * Les quatre pas du tunnel d'entree (ONB-05).
 *
 * GAME_ONBOARDING § 3 : **compte → nom → peuple → visage**, un ecran par pas,
 * une decision par ecran, une phrase de fiction par ecran.
 *
 * Ce que le tunnel remplace : deux formulaires administratifs d'affilee. Le
 * second demandait le nom, le peuple et l'apparence **sur le meme ecran**, ce
 * qui presente trois decisions de nature differente comme une seule corvee — et
 * fait rater la seule qui porte quelque chose (le peuple, § 4.5).
 *
 * **Aucun pas ne demande une decision de build** : ni metier, ni element, ni
 * arme, ni destination. Le peuple porte une capacite qui touche ce qu'on
 * **sait**, jamais ce qu'on **produit** ; le reste s'apprend en jouant. C'est la
 * decision A8, et le contrat de test la tient.
 */
enum CreationStep: string
{
    /** Le compte — hors du controleur de personnage, c'est `/register`. */
    case Account = 'account';

    case Name = 'name';
    case People = 'people';
    case Face = 'face';

    /**
     * Les pas que le tunnel de personnage sert lui-meme.
     *
     * Le compte en est exclu : il precede la session de jeu, et l'y inclure
     * ferait croire qu'on peut y revenir en arriere.
     *
     * @return list<self>
     */
    public static function characterSteps(): array
    {
        return [self::Name, self::People, self::Face];
    }

    /**
     * Rang affiche, compte du premier pas — celui du compte.
     *
     * La barre de progression compte **quatre** pas et non trois : le joueur a
     * bien franchi le premier, et le lui retirer donnerait l'impression que le
     * tunnel recommence.
     */
    public function position(): int
    {
        return match ($this) {
            self::Account => 1,
            self::Name => 2,
            self::People => 3,
            self::Face => 4,
        };
    }

    public static function total(): int
    {
        return \count(self::cases());
    }

    public function previous(): ?self
    {
        return match ($this) {
            self::Account, self::Name => null,
            self::People => self::Name,
            self::Face => self::People,
        };
    }

    public function next(): ?self
    {
        return match ($this) {
            self::Account => self::Name,
            self::Name => self::People,
            self::People => self::Face,
            self::Face => null,
        };
    }

    public function titleKey(): string
    {
        return 'game.character.tunnel.' . $this->value . '.title';
    }

    public function fictionKey(): string
    {
        return 'game.character.tunnel.' . $this->value . '.fiction';
    }
}
