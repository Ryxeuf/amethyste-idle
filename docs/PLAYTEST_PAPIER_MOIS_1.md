# Playtest sur papier — le premier mois

> **Session du 2026-07-29.** Simulation à froid du premier mois de jeu contre l'état
> **réellement livré** (plan foyers 17/17, rétention complète, pureté, chaîne de
> production, Vallons). But : vérifier que la colonne de progression tient ses promesses
> avant que des joueurs ne s'en chargent. Verdict global : **le design tient — avec
> deux frictions chiffrées qui méritent correction avant lancement**, et trois points
> de vigilance.

## 0. Chiffres d'entrée (vérifiés dans le code le 2026-07-29)

| Donnée | Valeur | Source |
|---|---|---|
| Barre d'énergie | 240, +1/6 min (barre pleine = 24 h) | `Player` |
| Récolte | **3** énergie | `GatherService::DEFAULT_COST` |
| Exploration | **5** | `ExploreService::DEFAULT_COST` |
| Chasse | **5** | `HuntService::DEFAULT_COST` |
| Événement de zone | **10** | `ZoneEventService::DEFAULT_COST` |
| Voyage | temps seul (`travel_seconds`, 4-15 min) — pas d'énergie | `ZoneTravelService` (vérifié par absence) |
| Craft | temps seul (`craftingTime`) | idem |
| Commission hebdo | 1/semaine, cibles 5-60, récompenses ~équivalentes (2 500 gils / 120 énergie / Tribut) | `weekly_commissions.yaml` |
| Assiduité | paliers 2/4/6 jours (800/1 800/3 500 gils + 60 é) | `weekly_attendance.yaml` |
| Grain de sédiment | 1/action qualifiante, plafond 60/j/foyer, rendements décroissants > 40 | BALANCE §23.1 |

**Trois profils simulés** : le **régulier** (150 é/jour, ~62 % de la barre — la référence
de BALANCE §22.5), l'**assidu** (240 é/jour), le **casual** (80 é/jour, 4 jours sur 7).
Plus une **guilde de 12 réguliers** coordonnés.

## 1. Semaine 1 — l'Acte I tient, largement

Jour 1 : réveil au Fanal, acte d'intro (~10 actions guidées, coût mixte ≈ 40 é),
première matéria accordée à 0 point, lettre du foyer d'attache. Il reste **200 é** — le
débutant explore, récolte, meurt une fois, revient. Jours 2-7 : premier domaine de
combat + premier métier ; le kit T1 à emplacement libre se vérifie. **Aucun goulot** :
même le casual finit l'Acte I dans sa semaine. Les promesses de GAME_PROGRESSION
(matéria jour 1, arme dotée, foyer d'attache identifié) passent toutes. ✅

## 2. Le test du lundi — la charge est cognitive, pas énergétique

Le lundi porte six systèmes : quotidiennes, Commission, défi de guilde, commande de
guilde, chantier du foyer, assiduité (+ l'Affleurement caché). La simulation montre que
**cinq des six comptent des gestes que le joueur fait déjà** :

- La Commission est tirée **dans ses domaines travaillés** → ses récoltes normales comptent.
- Le chantier compte l'activité **dans la zone** ; la Commission peut pointer dessus (convergence RET-02/05, vérifiée en config).
- Le défi et la commande de guilde comptent l'activité de tous.
- L'assiduité compte les jours, pas les actions.

**Bilan énergétique du lundi d'un régulier : ~0 énergie supplémentaire** — sauf la
**livraison** de la Commission (un voyage, 5-15 min de temps réel, 0 é). Le design de
« l'unique rotation » fonctionne. **Mais la charge cognitive est réelle** : six choses à
lire le lundi matin, aujourd'hui éparpillées sur quatre écrans. → Vigilance V1 (§6).

## 3. Le test des horizons (règle : quelque chose finit, quelque chose reste)

| Horizon | Ça finit ? | Ça reste ? | Verdict |
|---|---|---|---|
| Session | la barre se vide | matière, grains déposés | ✅ |
| Jour | quotidiennes | XP domaine, améthyste | ✅ |
| Semaine | Commission livrée, paliers d'assiduité | choix de récompense, réputation, chantier avancé | ✅ — l'horizon réparé par RET |
| Marée | l'arc se résout, le rang tombe | journal de monde, rang de foyer | ✅ |
| An | — | — | ✅ pour l'assidu (éveil, Métropole) ; **vide pour le casual, et c'est acceptable** |

## 4. La guilde de 12 et le premier Bourg — l'échéancier tient… pour un Comptoir

12 réguliers focalisés sur les Vallons (nés en Ruine) : ~40 grains/jour/joueur en
récolte (120 é — dans le budget de 150), flux 480/j, décroissance 2 %/j →
stock(24 j) ≈ 9 200 > seuil Bourg 8 000. **Le premier Bourg tombe vers J21-24, comme
promis** (BALANCE §23.3), pile pour la marée de la Première Pierre. ✅

**Mais** le même calcul pour une guilde de *chasseurs* casse — voir F2.

## 5. Les deux frictions chiffrées

### F1 — La promesse des « 80 actions par jour » est fausse en l'état

GAME_PROGRESSION §1 (et le cadrage entier) répète : *« une action ≈ 3 énergie, donc ~80
actions/jour »*. Les coûts réels : récolte 3, mais **exploration et chasse 5, événement
10**. Un récolteur fait 80 actions ; un explorateur **48** ; un profil mixte ~55-65.

Conséquences : les repères « 20 récoltes/jour sur un filon » (calibrage) restent bons,
mais tout raisonnement en « actions » côté combat/exploration surestime de ~40 %.
**Recommandation : garder les coûts différenciés** (c'est du bon design — l'exploration
paie plus, elle coûte plus) **et corriger la doc** : « 240 énergie ≈ 50 à 80 gestes
selon leur nature ». À répercuter dans GAME_PROGRESSION §1 et §5.

### F2 — Le sédiment au grain uniforme favorise les Comptoirs de ~40 %

BALANCE §23.1 : 1 kill = 1 grain `war`, 1 récolte = 1 grain `trade`. Or un kill coûte
**5 é** et une récolte **3 é**. À budget égal, un guerrier dépose **30 grains/jour** là
où un récolteur en dépose **40-50**. Refait sur la guilde de 12 : une guilde de
chasseurs plafonne à ~360 grains/j → stock(28 j) ≈ 7 700 < 8 000 — **elle rate le Bourg
dans la marée là où la guilde de récolteurs l'a**. Les Bastions monteront
structurellement plus lentement que les Comptoirs, et l'indice `war` mondial sera
chroniquement le plus bas (la rotation des marées tirera Battue sur Battue — symptôme
visible du biais).

**Recommandation : pondérer le grain par l'énergie du geste** — 1 grain par ~3 é
dépensée : récolte 1, kill 1,7, événement 3,3 (paramètres dans `settlements.yaml`, table
de dépôt déjà déclarative). Une ligne de config, l'équité des quatre types de foyer.

## 6. Trois points de vigilance (pas des défauts, des choses à surveiller)

- **V1 — Le tableau de bord du lundi.** La charge du lundi est lisible seulement si un
  écran unique la porte (quotidiennes + Commission + chantier + défi + assiduité). C'est
  aujourd'hui le **jalon UI le plus rentable du jeu** — il n'existe pas encore en tant
  que jalon. À ouvrir.
- **V2 — Le voyage clôt la session.** Sessions de 10-15 min, voyages de 5-15 min
  asynchrones : le bon pattern est « je lance mon voyage en fin de session ». L'UI
  devrait le suggérer (un rappel « barre vide → où aller demain ? »). Micro-UX, gros
  confort.
- **V3 — L'énergie de combat (PM).** La simulation papier ne peut pas vérifier la
  régénération des PM entre combats : un chasseur en chaîne est-il limité par ses PM
  avant son énergie d'action ? **À vérifier en jeu** dès que possible — c'est le seul
  système que le papier ne voit pas.

## 7. Ce que le papier valide sans réserve

La doctrine matéria jour 1 (verrouillée par l'intro), le plancher T1 (jamais bloqué),
l'unique rotation du lundi (0 é de surcharge), la convergence Commission/chantier,
l'échéancier du premier Bourg pour une guilde marchande, l'assiduité sans culpabilité
(le casual à 4 jours touche son palier 2 et vit bien), la grâce des deux premières
marées, et la règle des horizons à tous les étages.

**Prochain playtest recommandé** : le même exercice sur le **mois 2-3** (l'Acte III —
« on compte sur moi ») quand les factions (FAC) seront livrées : c'est le passage
critique des semaines 3-6, et il dépend de systèmes qui viennent d'arriver.

---

## Suites (2026-07-29, validees)

- **F1 consignee** : GAME_PROGRESSION §1 et §5 corriges (50-80 gestes selon leur nature) ;
  la passe d'equilibrage post-arbres est ouverte en BALANCE §24.1 (les arbres apporteront
  des reductions de cout/temps qui re-agrandiront le budget).
- **F2 consignee** : le grain est pondere par l'energie du geste (BALANCE §23.1 — 1 grain
  par ~3 energie ; kill = 1,7, evenement = 3,3).
- **V3 jalonnee** : l'equilibrage des combats (regen des PM, couts des sorts, duree des
  combats) est ouvert en BALANCE §24.2 — a instrumenter en jeu.
