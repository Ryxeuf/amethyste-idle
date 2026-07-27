# Design system — direction « Parchemin » + refonte de l'écran de zone

## Contexte

`assets/styles/app.css` a grossi à ~1500 lignes sans système : mêmes composants redéclarés, pas d'échelle typographique, pas de token de rareté. Les six écrans principaux souffraient du même symptôme — on ne sait pas où cliquer.

Cette PR ajoute un design system documenté, sous forme d'un document HTML autonome (`design/amethyste-design-system.html`), et six écrans mobiles maquettés avec.

## Direction retenue

**Parchemin** : encre sur papier, améthyste comme unique couleur de marque, raretés en sceaux.

- Surfaces : `#fbf8f0` papier, `#f4efe2` surface, `#26221c` encre, bordures `#ddd2ba` / `#cfc4ab`
- Typo : Cormorant Garamond (titres), Karla (texte), IBM Plex Mono (tous les chiffres)
- Marque : `#5b2fb0` plein, `#7c4ddb` actif, `#e6dcfb` fond
- Sémantique : `#a8341f` perte, `#1f7a49` gain, `#2b5ea8` mana

Trois règles de clarté :

1. **Une seule action primaire par écran**, en améthyste plein — tout le reste est texte ou bordure.
2. **Tout chiffre est en monospace**, aligné à droite, avec son delta juste à côté.
3. **Un état vide dit toujours quoi faire**, jamais juste « vide ».

Cibles tactiles à 44 px minimum partout.

## Écrans maquettés

Combat, Exploration, Compétences, Sac, Artisanat, Boutique — plus une section de refonte de l'écran de zone.

## Écran de zone — construit sur les données déclarées

L'écran de zone (section 07) ne contient **que** de la donnée de `config/game/zones/world_1.yaml` et des règles de `docs/PIVOT_PBBG.md` :

- table de rencontres de la Forêt des murmures affichée telle qu'elle est écrite (mob 45 / coffre 12 / filon 12 / PNJ 8 / rien 23) ;
- gils de coffre 5-25 le jour, 10-40 la nuit ; variance jour/nuit avec les mobs nocturnes déclarés (fantômes, squelettes) ;
- filons partagés avec capacité et respawn réels, présentés comme un stock collectif — donc une information sociale ;
- durées de voyage réelles depuis les `connections` (village 5 min, marais 5 min, mines 8 min) ;
- l'énergie comme régulateur des tentatives, le combat gratuit une fois la rencontre engagée.

Les seules valeurs non déclarées dans le repo — **coûts d'énergie et régénération**, question ouverte de `docs/BALANCE.md` — sont soulignées en pointillé dans la maquette, avec une légende qui l'explicite. Aucun autre chiffre n'est inventé.

## Trace de recherche conservée

La section 06 garde trois directions explorées **avant** relecture des règles du repo. Leurs chiffres sont inventés et l'une d'elles (« profondeur ») contredit le modèle d'énergie. Conservées comme trace, annotées comme telles. À supprimer avant merge si vous préférez un document propre.

## À corriger avant d'appliquer le DS au code

L'audit contre `docs/GAME_PRINCIPLES.md` et `CLAUDE.md` a relevé trois écarts qui touchent les écrans existants :

- [ ] **Mentions de niveau** — « Niv. 44 », « Zone 3 · Niv. 40-46 », « Forge niv. 6 », « requiert le niveau 46 » : il n'y a pas de niveau global (règle absolue). Remplacer par la difficulté de monstre et les rangs d'arbre.
- [ ] **Monnaie** — les écrans affichent « or », le modèle dit **gils**.
- [ ] **Sorts actifs dans l'arbre de compétences** — l'écran Compétences montre « Trait d'ombre · 25 PM » comme nœud d'arbre, alors que les arbres sont **passifs uniquement** et que les sorts actifs viennent des matérias (plus l'attaque de base de l'arme, toujours gratuite).

## Suite

Traduction des tokens en `@theme` Tailwind v4 pour remplacer `app.css`, une fois la direction validée.
