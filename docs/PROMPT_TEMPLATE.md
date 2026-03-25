# Template de Prompt — Amethyste-Idle

> Template optimisé pour formuler des demandes de développement à Claude Code sur ce projet.
> Copier la structure ci-dessous et remplir les sections pertinentes.

---

## Template

```markdown
## Objectif
[1-2 phrases : quoi faire et pourquoi, en termes de valeur utilisateur]

## Contexte technique
[Lister les fichiers/entités/controllers directement concernés avec leurs chemins.
Mentionner la stack pertinente (Twig, Stimulus, Turbo, Tailwind, etc.).
Indiquer ce qui EXISTE DÉJÀ et peut être réutilisé — éviter de recréer.]

## Spécification fonctionnelle

### 1. [Sous-feature A]
- Comportement attendu (cas nominal)
- Cas limites (vide, erreur, max atteint)
- Données affichées (quels champs, quel format)

### 2. [Sous-feature B]
- ...

### 3. [Sous-feature N]
- ...

## UX/UI
- Interactions : clic, hover, tap mobile
- Pattern responsive à suivre (tooltip desktop / bottom-sheet mobile / etc.)
- Animations ou feedback visuel attendus
- Cohérence avec le design existant (préciser les classes CSS, couleurs, patterns à réutiliser)

## Contraintes
- [ ] Mobile-first, responsive
- [ ] Cohérent avec le thème existant (dark, glass-morphism, rareté)
- [ ] Turbo Frames pour rechargement partiel (pas de full reload)
- [ ] Commits atomiques (un par sous-feature testable)
- [ ] Docker obligatoire pour toutes les commandes
- [ ] Pas de bundler (AssetMapper uniquement)
- [Ajouter toute contrainte spécifique]

## Hors périmètre
[Ce qu'il ne faut PAS faire — évite les dérives de scope]

## Référence visuelle (optionnel)
[Description d'une référence UI connue, ex: "Comme le menu materia de FF7 Remake"
ou lien vers screenshot/mockup]
```

---

## Pourquoi cette structure

| Section | Rôle |
|---------|------|
| **Contexte technique** | Évite que Claude cherche à tâtons ou réinvente l'existant |
| **Sous-features numérotées** | Permet des commits atomiques naturels |
| **UX/UI séparé** | Clarifie le rendu attendu sans polluer le fonctionnel |
| **Contraintes** | Rappelle les règles du CLAUDE.md applicables |
| **Hors périmètre** | Bloque le sur-engineering |

---

## Exemple rempli

```markdown
## Objectif
Améliorer l'interface d'équipement pour rendre les slots de materia pleinement fonctionnels,
visuels et interactifs, inspirés du système FF7.

## Contexte technique
- L'équipement est affiché dans `templates/game/inventory/equipment/_list.html.twig`
- Les entités clés : `Entity/Game/Item.php` (materiaSlots, element),
  `Entity/App/PlayerItem.php` (slots, experience), `Entity/App/Slot.php` (element, linkedSlot, item_set)
- Les controllers existent déjà : `MateriaSlotController` (GET), `SetMateriaController` (POST),
  `UnsetMateriaController` (POST)
- Le template `materia/_slot_select.html.twig` gère déjà la sélection de materia
- Les orbs materia sont stylisées via `.materia-orb-{element}` dans `app.css`
  (sprite atlas `materias_32.png`)
- Stack frontend : Twig + Tailwind 4.1 + Stimulus.js + Turbo Frames

## Spécification fonctionnelle

### 1. Affichage des slots sur la fiche équipement
- Sur chaque carte d'équipement, afficher "3/5 slots occupés"
- Orbes visuelles par slot :
  - Vide : cercle creux, bordure grise, pulsation
  - Occupé : orbe avec couleur/sprite de la materia
  - Élémentaire : bordure couleur de l'élément du slot
  - Liés (linkedSlot) : trait/arc entre les deux orbes

### 2. Tooltip/Panel détaillé des materia sockettées
- Clic/tap sur slot occupé : nom, élément, niveau (1-5), XP/palier suivant
- Sort associé et ses effets
- Bonus actifs : match élémentaire (+25% XP), synergie linked (+15% dégâts)

### 3. Actions sur les slots
- Slot vide → "Sertir" → sélecteur materia (route existante)
- Slot occupé → "Retirer" / "Remplacer"
- Turbo Frames pour rechargement partiel

### 4. Indicateurs visuels de bonus
- Glow doré : match élémentaire
- Glow cyan : synergie linked active
- Badge niveau sur l'orbe (1-5)

## UX/UI
- Tooltip desktop (hover), bottom-sheet mobile (tap) — pattern existant `inventory_controller.js`
- Cohérent : dark theme, glass-morphism, couleurs de rareté
- Réutiliser `.materia-orb`, `.equip-slot`

## Contraintes
- [x] Mobile-first, responsive
- [x] Turbo Frames (pas de full reload)
- [x] Commits atomiques
- [x] Docker obligatoire
- [x] Respecter validations de `MateriaGearSetter`

## Hors périmètre
- Pas de modification du backend materia (entities, game engine)
- Pas de nouveau système de craft/fusion de materia
```
