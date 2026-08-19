<?php

namespace App\Tests\Unit\GameEngine\Fight;

use PHPUnit\Framework\TestCase;

/**
 * La mitigation d'armure est branchee — et elle ne passe pas par
 * `Item::protection` (ARC-19, retourne le cliquet d'ARC-20c-b).
 *
 * ARC-20c-b avait tranche « on le branche, et c'est ARC-19 qui le branche »,
 * et tenait en cliquet le fait qu'aucune formule de combat ne lisait encore la
 * colonne. **Ouvrir le jalon a mesure le contraire de ce qu'on supposait** :
 *
 *  - `protection` est **nulle sur les quinze pieces de la grille de reference**
 *    — celle qu'OBJ-03 a verrouillee (starter, t2-*, t3-*), c'est-a-dire
 *    precisement celle que portent les builds du simulateur ;
 *  - elle est **incoherente entre lignes** la ou elle existe : le cuir de
 *    palier 2 totalise exactement autant que la plaque du meme palier (37
 *    contre 37), donc l'adosser a la mitigation aurait rendu le cuir aussi
 *    protecteur que la plaque.
 *
 * ***Une valeur qui vaut zero la ou on la lirait le plus n'est pas un vehicule,
 * c'est un ornement.*** Le canon, lui, n'a jamais parle en points de defense :
 * il acte « plaque 40 %, cuir 20 %, tissu 0 % » — des **lignes**. La loi suit
 * le canon (`ArmorMitigationLaw`), et l'alignement de la colonne sur les lignes
 * reste du **contenu** (OBJ), tenu en cliquet ici.
 *
 * *Un cliquet qui bouge parce qu'on mesure enfin est le contraire d'un cliquet
 * qui bouge en silence* — la meme discipline qu'ARC-20c-a.
 */
class ItemProtectionDecisionTest extends TestCase
{
    /**
     * Les chemins ou le degat se calcule — les trois que le cliquet d'ARC-20c-b
     * nommait, et qu'ARC-19 devait brancher **tous a la fois**.
     *
     * @var list<string>
     */
    private const DAMAGE_PATHS = [
        'src/GameEngine/Fight/SpellApplicator.php',
        'src/GameEngine/Dungeon/GroupDungeonCombatService.php',
    ];

    /**
     * **La mitigation est branchee partout ou le degat se calcule.**.
     *
     * Brancher a moitie serait pire que ne pas brancher : c'est le defaut
     * qu'ARC-17b avait trouve sur le garde-fou d'ARC-17a, qui visait
     * `MobActionHandler` quand le degat se calcule ailleurs.
     */
    public function testEveryDamagePathAppliesArmorMitigation(): void
    {
        foreach (self::DAMAGE_PATHS as $path) {
            $source = file_get_contents(\dirname(__DIR__, 4) . '/' . $path);
            self::assertIsString($source, $path);

            self::assertStringContainsString(
                'armorMitigation',
                $source,
                sprintf('%s ne mitige pas : la moitie que le canon refuse a l\'arbre y disparait.', $path),
            );
        }
    }

    /**
     * `Item::protection` reste hors des formules — et c'est desormais une
     * **decision mesuree**, plus une attente.
     *
     * Le jour ou OBJ alignera la colonne sur les lignes, ce test dira ce qu'il
     * faut relire ; l'effacer laisserait croire que la question ne s'est jamais
     * posee.
     */
    public function testProtectionPointsStayOutOfTheFormulas(): void
    {
        foreach (array_merge(self::DAMAGE_PATHS, ['src/GameEngine/Fight/Calculator/DamageCalculator.php']) as $path) {
            $source = file_get_contents(\dirname(__DIR__, 4) . '/' . $path);
            self::assertIsString($source, $path);

            self::assertStringNotContainsString(
                'getProtection',
                $source,
                sprintf('%s lit Item::protection : la colonne est nulle sur la grille de reference et n\'oppose pas les lignes — la mitigation ne peut pas s\'y adosser.', $path),
            );
        }
    }
}
