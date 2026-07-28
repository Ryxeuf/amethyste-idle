# Le calendrier des marées — l'an 1

> **Statut : acté le 2026-07-28.** Décline la fabrique de marées
> ([GAME_WORLD.md](GAME_WORLD.md) §8) en **partition de première année** : 13 marées de
> 28 jours. En amont : GAME_PRINCIPLES §3 (narration saisonnière, hybride D9), la règle
> des horizons (GAME_PROGRESSION §2 — chaque marée *clôt* quelque chose et *laisse*
> quelque chose), le chiffrage des foyers (BALANCE §23 — le 1er Bourg est l'événement
> de la première marée), la doctrine fondre/lire (§12.2) et le Répertoire (§12.3).
> Jalons : vague 2 de [roadmap/PLAN_NARRATIVE.md](roadmap/PLAN_NARRATIVE.md) (NAR-15+).

## 0. Le principe : une partition, pas un script

Un calendrier figé de 13 épisodes trahirait la fabrique (« les marées conséquence
doivent être déclenchées par ce que les joueurs ont fait le mois d'avant »). L'an 1 est
donc une **partition à trois voix** :

1. **La colonne vertébrale** — 5 marées **canon**, écrites, à date à peu près fixe :
   elles portent l'histoire du serveur (l'éveil, le premier Bourg, l'axe doctrinal, le
   premier signe du Reflux, l'anniversaire). C'est le tiers canon de la règle D9/D12.
2. **La rotation** — les créneaux libres tirent un **gabarit** rejouable (§2). Règle de
   tirage : la rotation choisit le gabarit dont **l'indice de sédiment mondial est le
   plus faible** (la somme par indice sur tous les foyers) — le monde équilibre son
   propre régime : trop de guerre ? la marée suivante nourrit le commerce ou le savoir.
3. **Les conséquences** — des marées **déclenchées**, jamais datées : elles préemptent
   le créneau de rotation quand leur condition est vraie (§3). Priorité de tirage :
   conséquence déclenchée > colonne vertébrale datée > rotation.

Chaque marée, quelle que soit sa voix, respecte le gabarit livré (amorce → montée →
climax → résolution) et la règle des horizons : le tableau de chaque marée dit **ce
qu'elle clôt** et **ce qu'elle laisse**.

## 1. La colonne vertébrale — cinq marées canon

### M1 — L'Éveil *(livrée — SeasonArcFixtures)*
L'acte d'introduction à l'échelle du serveur : les cloches sonnent, la pression grandit,
la Faille, l'accalmie. **Clôt** : l'ouverture du monde. **Laisse** : le journal de monde
s'ouvre — la première page du serveur.

### M2 — La Première Pierre *(canon)*
La strate qui affleure : le monde attend qu'on bâtisse. La Concorde proclame qu'une
place de **Bourg** est ouverte (l'échelle de la Crue expliquée en fiction) ; le
chiffrage veut que ce soit l'affaire d'une marée pour une guilde de 12 (BALANCE §23.3).
Beats : l'annonce → les chantiers rivaux (jauges publiques) → la dernière semaine (le
coude à coude) → la consécration. **Clôt** : la course au premier marché de foyer.
**Laisse** : le premier Bourg du serveur, **le nom de la guilde bâtisseuse au journal**
— la première trace politique. *(La grâce des deux premières marées — §23.7 — couvre
M1-M2 : personne ne régresse pendant qu'on apprend à bâtir.)*

### M4 — Le Procès de la Fonderie *(canon)*
L'axe doctrinal s'ouvre officiellement. Un incident (un quartier sans lumière, une veine
éreintée — au choix de l'état du monde) met la Fonderie en accusation publique. Pendant
la marée, chaque **fonte** et chaque **lecture** compte double au dossier ; le climax est
une audience à ciel ouvert ; la résolution proclame la **première dominante doctrinale du
serveur** (le ratio fondre/lire de la marée). **Clôt** : le procès — sans coupable.
**Laisse** : la doctrine du serveur inscrite au journal, l'atelier de doctrine du foyer
vainqueur (FOY-13), et le Programme du Cercle qui démarre.

### M8 — La Marée Basse *(canon — le méta-arc avance d'un cran)*
Le premier signe du **Reflux** (GAME_WORLD §7) : une nuit, la marée ne remonte pas —
partout, l'améthyste rend une bande plus bas que d'habitude, et un **Effacé** est vu
hors de la Cité ensevelie pour la première fois. Rien n'est expliqué. **Clôt** : rien —
c'est le point : une marée qui ne se résout pas *entièrement* est la signature du
méta-arc. **Laisse** : une entrée de Codex scellée (« la mer s'est retirée d'un pas »),
la première peur. *(Jamais plus d'un cran de méta-arc par an — §7.3 : le retournement
ne se livre pas tôt.)*

### M13 — Le Grand Inventaire *(canon — l'anniversaire)*
Le serveur lit son propre journal. La Concorde recense : les foyers montés, les noms
gravés, ce que le Répertoire a retrouvé, ce qui a été fondu et ce qui a été lu — la
marée est une **rétrospective jouable** (défis qui rejouent les moments de l'année,
récompenses d'assiduité annuelle en paliers, jamais en série). **Clôt** : l'an 1.
**Laisse** : le monument de l'an 1 (un objet de zone au Fanal, différent selon ce que
le serveur a réellement fait), et l'amorce de l'an 2.

## 2. La rotation — les gabarits rejouables

Les six de la fabrique (§8) plus deux nouveaux, un par indice de sédiment :

| Gabarit | Indice nourri | Ce qui remonte | Ce que ça laisse |
|---|---|---|---|
| La Marée d'Ambre | `lore` | un jour de l'âge précédent, aux Dunes | butin d'ambre, entrées de Codex |
| La Fonte | `lore`/`rite` | l'été d'avant le gel, au Silence | cartes du Silence, herbes rares |
| Le Chœur | `rite` | deux veines en phase | le lieu-dit du Chœur repéré (Forêt) |
| La Contrefaçon | `trade` | les Ruelles inondent le marché | le marché gris révélé à qui l'a trouvé |
| **La Grande Battue** *(nouveau)* | `war` | une faune d'un autre âge déborde d'une zone | le tableau des primes des Chevaliers, trophées de bestiaire |
| **La Foire Franche** *(nouveau)* | `trade` | la grande foire itinérante s'installe dans un foyer | un étal éphémère par artisan, la première fièvre des prix — précurseur des caravanes |
| La Pâleur | — | *conséquence* (§3) | — |
| L'Appel de la Crue | — | *conséquence* (§3) | — |

La règle de tirage (« l'indice mondial le plus faible ») fait le reste : un serveur de
guerriers verra venir des foires, un serveur de marchands des battues. **Le monde
prescrit ce qui lui manque** — c'est l'indice décroissant d'EVE appliqué au calendrier.

## 3. Les conséquences — déclenchées, jamais datées

| Marée | Condition de déclenchement | Enjeu |
|---|---|---|
| **La Pâleur** | ≥ N filons pâlis en fin de marée (FOY-11 — la sur-extraction avérée du mois d'avant) | empêcher un foyer de tomber ; restauration au trésor |
| **L'Appel de la Crue** | un palier de population franchi (40/80/120… — §13.4) **ou** un quota libéré par régression | la course de nœuds entre guildes, sans un coup échangé |

Elles préemptent le prochain créneau de rotation. Si les deux sont vraies, la Pâleur
passe d'abord (la conséquence *négative* ne doit jamais attendre — c'est elle qui
enseigne). Aux populations de lancement, l'Appel se déclenchera vraisemblablement une à
deux fois dans l'an 1 (le passage de 40 actifs) : c'est voulu, pas garanti.

## 4. L'an 1 vraisemblable, en une ligne

À ~50 joueurs quotidiens : **Éveil → Première Pierre → [rotation] → Procès → [rotation ×3,
dont probablement un Appel à 40+ actifs] → Marée Basse → [rotation ×4, Pâleur si le
serveur a trop pressé ses veines] → Grand Inventaire.** Treize marées, cinq écrites,
le reste choisi par ce que le serveur fait — la boucle plutôt que le calendrier.

## 5. Ce que ce document ne décide pas

- **Le contenu détaillé des beats** de M2/M4/M8/M13 (dialogues, quêtes d'événement,
  récompenses) : c'est l'exécution, vague 2 du plan narration (NAR-15+), un jalon par
  marée écrite, gabarits `GameEvent` + `Quest.gameEvent` livrés.
- **Les seuils** (N filons pâlis, volumes de la Foire) : paramètres, BALANCE au moment
  des jalons.
- **L'an 2** : le Grand Inventaire l'amorce ; sa partition s'écrira avec les leçons de
  l'an 1 — et le deuxième cran du Reflux.
