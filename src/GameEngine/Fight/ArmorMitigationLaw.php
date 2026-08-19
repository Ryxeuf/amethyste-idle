<?php

namespace App\GameEngine\Fight;

/**
 * Ce qu'une ligne d'armure retire aux coups recus (ARC-19).
 *
 * GAME_ARCHETYPES § 2.2, acte le 2026-08-01 : **plaque 40 %, cuir 20 %, tissu
 * 0 %**. C'est la moitie que le canon refuse de laisser a l'arbre, et il le dit
 * avec des nombres : par l'arbre seul (`life` plafonne a 20 pb, `guard` a 15),
 * un tank a **x1,39** les points de vie effectifs d'un porteur de tissu — *ce
 * qui n'est pas un ecart, c'est une nuance*. Avec la plaque, l'ecart devient
 * **x2,3 / x1,6 / x1**.
 *
 * > ***La mitigation d'un tank vient de son armure, pas de son arbre.***
 *
 * ## Pourquoi la ligne, et non les points de defense
 *
 * `Item::protection` semblait le vehicule naturel — il est deja affiche sur la
 * fiche d'inventaire. **La mesure dit le contraire** : la colonne est **nulle
 * sur les quinze pieces de la grille de reference** (celle qu'OBJ-03 a
 * verrouillee, et que les builds du simulateur portent), et incoherente
 * ailleurs — le cuir de palier 2 totalise exactement autant que la plaque du
 * meme palier. *Une valeur qui vaut zero la ou on la lirait le plus n'est pas
 * un vehicule, c'est un ornement.* Le canon, lui, parle de **lignes** et jamais
 * de points : « plaque 40 %, cuir 20 %, tissu 0 % ». La loi suit le canon, et
 * l'alignement de la colonne est du contenu (OBJ), tenu en cliquet nomme.
 *
 * ## Les deux bornes
 *
 * La part la plus haute est **50 %**, et elle sort d'un calcul et non d'un avis
 * (§ 2.2) : c'est le point ou la mitigation du tank annule exactement sa
 * lenteur — quatorze tours contre six. Au-dela il encaisse moins que l'archer
 * *tout en survivant mieux*, et redevient le meilleur choix partout. La plus
 * basse est **28 %**, le minimum sous lequel l'aggro bornee ne passe plus
 * (§ 13.4). La cible retenue, 40 %, est au centre de cette fourchette etroite.
 *
 * ## La couverture
 *
 * Une part s'obtient en **portant la ligne**, pas en possedant une piece : la
 * mitigation se moyenne sur les sept emplacements d'armure du jeu, et un
 * emplacement vide — ou tenu par une piece qui n'appartient a aucune ligne —
 * compte pour zero. Sans cette regle, une seule epauliere de plaque vaudrait
 * une armure complete.
 */
final class ArmorMitigationLaw
{
    /**
     * Les parts par ligne, telles que le canon les acte.
     *
     * Les cles sont celles de l'echelle de port (`equipment_ports.yaml`) : la
     * ligne d'une piece se lit la ou elle se lit deja pour tout le reste
     * (ARC-16b), jamais dans une table parallele.
     *
     * @var array<string, float>
     */
    public const LINE_SHARES = [
        'plate' => 0.40,
        'leather' => 0.20,
        'cloth' => 0.0,
    ];

    /**
     * La borne haute opposable : au-dela, le solo casse (§ 2.2).
     */
    public const MAX_SHARE = 0.50;

    /**
     * Le minimum sous lequel l'aggro bornee ne passe plus (§ 13.4).
     *
     * Il ne borne rien ici — c'est un **seuil de lecture**, celui qui dit si la
     * ligne la plus protectrice du jeu peut porter le transfert d'ARC-18d.
     */
    public const AGGRO_FLOOR = 0.28;

    /**
     * Les emplacements d'armure sur lesquels la couverture se moyenne.
     *
     * Sept, et ce sont ceux du vestiaire (GAME_ITEMS § 2.1 : la grille 3
     * paliers x 7 formes). Les armes, les bijoux et les munitions n'en sont
     * pas : ils ne protegent rien, et les compter diluerait la mitigation d'un
     * tank par le nombre de bagues qu'il porte.
     *
     * @var list<string>
     */
    public const ARMOR_SLOTS = ['head', 'chest', 'hand', 'belt', 'leg', 'foot', 'shoulder'];

    /**
     * La part qu'une ligne retire, ou zero si elle n'en est pas une.
     *
     * Le bouclier n'a **pas** de part : il est une ligne de l'echelle de port,
     * mais ce qu'il apporte se joue ailleurs (le levier `guard` sous condition
     * `shield`, ARC-12). Lui en donner une le compterait deux fois.
     */
    public static function shareOfLine(?string $line): float
    {
        return self::LINE_SHARES[$line] ?? 0.0;
    }

    /**
     * La part mitigee d'une tenue, moyennee sur les emplacements d'armure.
     *
     * @param list<string|null> $wornLines la ligne de chaque piece portee, dans
     *                                     un emplacement d'armure
     */
    public static function shareFor(array $wornLines): float
    {
        if ($wornLines === []) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($wornLines as $line) {
            $total += self::shareOfLine($line);
        }

        return min(self::MAX_SHARE, $total / \count(self::ARMOR_SLOTS));
    }

    /**
     * Ce qui reste d'un coup une fois l'armure servie.
     *
     * **La mitigation s'applique apres la garde et avant le bouclier**, a la
     * meme place que `guard` (§ 4) et pour la meme raison : c'est ce que le
     * corps de la cible oppose, quand le bouclier est un tampon exterieur. Les
     * deux se multiplient plutot que de s'additionner — additionner des
     * reductions les ferait atteindre 100 %, et une cible invulnerable n'est
     * plus une cible.
     */
    public static function mitigated(int $damage, float $share): int
    {
        if ($damage <= 0 || $share <= 0.0) {
            return max(0, $damage);
        }

        return max(0, (int) round($damage * (1.0 - min(self::MAX_SHARE, $share))));
    }
}
