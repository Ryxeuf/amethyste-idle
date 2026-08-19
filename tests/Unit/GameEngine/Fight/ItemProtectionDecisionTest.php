<?php

namespace App\Tests\Unit\GameEngine\Fight;

use PHPUnit\Framework\TestCase;

/**
 * `Item::protection` est tranche : il devient la mitigation d'armure (ARC-20c-b).
 *
 * Le constat du plan : la colonne est lue par `EquipmentSetResolver`, affichee
 * sur la fiche d'inventaire (« +4 DEF »), et **par aucune formule de combat** —
 * *un chiffre affiche sans effet est un mensonge d'interface*. Deux issues
 * etaient possibles : la retirer, ou la brancher.
 *
 * **Decision : on la branche, et c'est ARC-19 qui la branche.** La mitigation
 * d'armure est la moitie que GAME_VITALITY § 9 renvoie explicitement a ARC-19
 * (fourchette mesuree de GAME_ITEMS § 2.2 : 30 % minimum, 50 % maximum, cible
 * ~40 %), et la decision 21 du canon dit pourquoi elle ne peut pas vivre
 * ailleurs : *la mitigation d'un tank vient de son armure, pas de son arbre* —
 * l'ecart par l'arbre seul est de x1,39 quand encaisser la part de quatre en
 * demanderait x4. Retirer la colonne aurait supprime le seul vehicule que
 * cette moitie possede.
 *
 * Ce test tient l'etat **présent** en cliquet : tant qu'ARC-19 n'est pas
 * livre, aucune formule de combat ne lit `protection` — le brancher a moitie
 * (une formule sur deux) serait pire que ne pas le brancher, c'est le defaut
 * qu'ARC-17b a trouve sur le garde-fou d'ARC-17a, qui visait `MobActionHandler`
 * quand le degat se calcule dans `SpellApplicator` et
 * `GroupDungeonCombatService`. **ARC-19 retournera ce test** au lieu de le
 * supprimer, comme ARC-17b et ARC-20b-a avant lui.
 */
class ItemProtectionDecisionTest extends TestCase
{
    /**
     * Les chemins ou le degat se calcule — les deux que le garde-fou d'ARC-17a
     * avait appris a viser, plus le calculateur lui-meme.
     *
     * @var list<string>
     */
    private const DAMAGE_PATHS = [
        'src/GameEngine/Fight/SpellApplicator.php',
        'src/GameEngine/Fight/Calculator/DamageCalculator.php',
        'src/GameEngine/Dungeon/GroupDungeonCombatService.php',
    ];

    public function testNoCombatFormulaReadsProtectionUntilArc19WiresIt(): void
    {
        foreach (self::DAMAGE_PATHS as $path) {
            $source = file_get_contents(\dirname(__DIR__, 4) . '/' . $path);
            self::assertIsString($source, $path);

            self::assertStringNotContainsString(
                'getProtection',
                $source,
                sprintf('%s lit Item::protection : la mitigation d\'armure est le travail d\'ARC-19, et il doit retourner ce test en la branchant partout a la fois.', $path),
            );
        }
    }
}
