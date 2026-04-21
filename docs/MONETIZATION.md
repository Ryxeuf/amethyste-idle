# Politique de monetisation — Amethyste

> **Principe fondateur** : Amethyste est un jeu **100% gratuit** (Free-to-Play) et **sans Pay-to-Win**.
> Le joueur payant n'a jamais plus de puissance, plus de stats ou plus d'acces au contenu qu'un joueur gratuit.
> Il a : **plus de style, plus de confort, plus d'expression de soi**.

---

## Regles non negociables

### Ce qu'on ne vend JAMAIS

- XP, points de talent ou progression acceleree
- Or (monnaie du jeu) ou conversion monnaie premium → or
- Materias, equipement avec stats, consommables de combat
- Materiaux de craft ou de recolte
- Emplacements de materia supplementaires
- Respec gratuit payant (le respec a un cout or fixe pour tous)
- Revive en combat
- Zones, donjons ou quetes exclusifs aux payants
- Loot boxes a recompenses gameplay
- Boost de loot, boost d'XP, boost de drop rate
- Avantage en combat sous quelque forme que ce soit

### Pas de dark patterns

- Pas de timer agressif ni de popup pressant
- Pas d'offre "80% qui finit dans 10 minutes"
- Prix affiches en euros, pas uniquement en monnaie premium
- Pas de frustration volontaire pour vendre la solution

---

## Piliers de monetisation

### 1. Dons / Soutien (MVP — actif)

Systeme de don libre via plateformes externes :
- **Ko-fi** : don ponctuel ou recurrent
- **GitHub Sponsors** : sponsoring mensuel

Les dons ne donnent **aucun avantage en jeu**. Recompense symbolique possible :
- Badge "Mecene" sur le profil public
- Titre cosmétique `<Mecene>` affiche au-dessus du personnage

Page accessible via `/game/support`.

### 2. Cosmetiques (coeur du modele futur, ~50% des revenus)

Tout ce qui est visuel et n'apporte aucune stat :

| Type | Description |
|------|-------------|
| Skins d'armes/armures | Overlay visuel sur l'equipement equipe |
| Teintures (dyes) | Recolorer les pieces d'armure individuellement |
| Skins de materia | Particules colorees/uniques au lancement d'un sort |
| Animations personnalisees | Attaque critique, victoire, mort, emotes |
| Montures cosmetiques | Apparence differente, meme bonus de vitesse |
| Pets decoratifs | Compagnon qui suit le joueur, sans effet gameplay |
| Titres | Affiches au-dessus du personnage |

**Implementation technique** : entites `CosmeticItem`, `PlayerCosmetic`, `PlayerCosmeticLoadout`,
decouplees de l'inventaire reel.

### 3. Confort / Quality of Life (~20%)

Aucun confort payant ne doit etre **bloquant**. Les bases gratuites restent genereuses.

| Fonctionnalite | Gratuit | Premium |
|----------------|---------|---------|
| Slots de personnage | 1 | +2 supplementaires |
| Slots de banque | N slots | +N slots (plafonne) |
| Presets d'equipement | 2 | 5 |
| Presets de materia | 1 | 3 |
| Wardrobe (skins sauvegardes) | 3 tenues | Illimite |
| Marchand ambulant | Non | Oui (vendre hors ville) |

### 4. Pass saisonnier (~15%)

Saison de 2-3 mois avec 2 pistes :

- **Piste gratuite** : materiaux, or, quelques cosmetiques simples.
  Un joueur F2P ne doit jamais sentir qu'on lui vole du contenu.
- **Piste premium** : cosmetiques exclusifs de la saison, titres, teintures, emotes.
  Pas de recompenses gameplay (stats, materia, equipement).

Quetes de saison PvE cooperatives (tuer X mobs, completer Y donjons).

**Anti-FOMO** : les recompenses gameplay du pass restent acquises hors saison.
Seuls les cosmetiques saisonniers sont limites dans le temps.

### 5. Statut Fondateur / Mecene (~10%)

- **Pack Fondateur** (achat unique, alpha/beta) :
  - Titre exclusif `<Fondateur>` a vie
  - Monture cosmetique exclusive
  - Teinture unique
  - +1 slot de personnage
  - Acces anticipé aux test-serveurs (pas de contenu exclusif)

- **Abonnement optionnel** (ex: 5€/mois) :
  - Acces au pass saisonnier inclus
  - Salon de guilde decore "Mecene"
  - Skin de monture mensuel
  - **Pas** de bonus XP, loot ou stats

### 6. Housing / Personnalisation de guilde (~5%)

Quand la feature existe (cf. `PLAN_GUILD_CITY_CONTROL.md`) :
- Maison/salle de guilde de base **gratuite** et fonctionnelle
- Meubles, decors, musiques d'ambiance, skins de batiments → boutique
- **Jamais** de meubles qui donnent un buff gameplay

---

## Systeme de monnaies

| Monnaie | Obtention | Usage |
|---------|-----------|-------|
| **Or** (gils) | Gameplay uniquement (drop, quetes, HDV, craft) | HDV, PNJ marchands, craft, respec, reparation |
| **Amethystes** (premium) | Achat reel + petites quantites via pass gratuit / succes | Boutique cosmetique, slots confort, pass premium |

### Regles strictes

- **Aucune conversion** Amethystes → Or directe
- Les items achetes en Amethystes sont **lies au compte** (non echangeables, non vendables a l'HDV)
- Pas de "tokens" convertibles (modele WoW/EVE)
- Petite quantite d'Amethystes gratuites via pass/succes (~50/saison) → le joueur F2P peut s'offrir 1 cosmetique par saison

---

## Protection de l'economie (HDV)

- Taxe de vente HDV identique pour tous (5%)
- Cosmetiques premium lies au compte
- Pas de tokens convertibles
- Anti-RMT : pas de mecanisme facilitant le Real Money Trading

---

## Transparence

- Page `/game/support` publique expliquant la politique
- Prix affiches en euros
- Taux d'obtention affiches si coffres cosmetiques (avec pitie garantie)
- Ce document (`MONETIZATION.md`) fait reference

---

## Roadmap d'implementation

| Phase | Contenu | Effort |
|-------|---------|--------|
| M-01 | Page de support + dons externes (Ko-fi, GitHub Sponsors) | S |
| M-02 | Entite `CosmeticItem` + boutique readonly + page `/shop` | S |
| M-03 | Systeme de monnaie Amethystes (attribut sur User) | S |
| M-04 | Integration PSP (Stripe) en staging | M |
| M-05 | `PlayerCosmetic` + `CosmeticLoadout` + overlay visuel | M |
| M-06 | Teintures + wardrobe | M |
| M-07 | Slot personnage supplementaire achetable | S |
| M-08 | Pass saisonnier — piste gratuite (MVP) | M |
| M-09 | Pass saisonnier — piste premium | M |
| M-10 | Pack Fondateur | S |

---

## Metriques cibles

- Taux de conversion F2P → payant : 2-5%
- ARPPU (revenu moyen par payant/mois) : 5-15€
- Objectif : 80% du revenu provient de cosmetiques, 0% de P2W
