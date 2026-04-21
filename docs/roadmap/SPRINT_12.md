## Sprint 12 — Technique & i18n

> **2 taches** | Priorite : **Basse** | Origine : Vague 10, Piste C
> Objectif : preparer l'infrastructure pour la montee en charge et l'internationalisation.
> Prerequis : Sprints 1-6 recommandes (contenu stable avant scaling)

---

### 134 — Load testing & scaling (M | ★★)
> Prerequis : ∅
- [x] Script k6/Locust pour simuler 100+ joueurs simultanes — infrastructure k6 (`scripts/load-test/`) + scenario `guest-browsing` (home, login, register, demo, /health, /metrics). Ramp-up/plateau/ramp-down configurable, thresholds p95<800ms + <1% erreurs, export JSON pour CI. Documentation dans `scripts/load-test/README.md`.
- [ ] Identification goulots d'etranglement (DB, Mercure, FrankenPHP)
- [ ] Optimisations : connection pooling, cache Redis, horizontal scaling plan
- [ ] Objectif : 200 joueurs simultanes sans degradation

### 135 — Localisation i18n (M | ★)
> Prerequis : ∅
> Avancement : sous-phases 1 (selecteur de langue securise), 2a (parite UI FR/EN atteinte), 3a (infrastructure multilingue des noms d'items) et 3b (cablage du filter Twig `localized_name` dans les templates shop/inventaire/bestiaire) livrees.
- [x] Extraction des chaines via Symfony Translation — base existante sous `translations/messages.{fr,en}.json` (format JSON arborescent, deja exploite par `|trans`). Non re-extrait en XLIFF (choix projet de conserver JSON).
- [~] Traduction EN prioritaire (UI, items, quetes, dialogues) — sous-phase 2a livree 2026-04-21
  - [x] **2a — Parite de cles FR/EN sur l'UI** (2026-04-21) : audit systematique de `messages.{fr,en}.json` via flatten + diff ensembliste revele 19 cles presentes en FR mais absentes en EN. Ajout cible : `game.nav.bestiary` + `game.nav.craft` (entrees de navigation) et l'integralite du namespace `game.bestiary.*` (17 cles : `title`, `subtitle`, `discovered`, `total_kills`, `kills`, `tier_weaknesses`, `tier_loot`, `tier_title`, `weaknesses`, `loot_table`, `loot_probability`, `next_tier`, `empty`, `empty_hint`, `boss`, `level`, `difficulty`). Resultat : 432 cles FR = 432 cles EN, ecart nul. Impact immediat : la page `/game/bestiary` et les liens de navigation ne tombent plus sur le fallback de cle brute en anglais.
  - [ ] **2b** — audit des cles utilisees mais absentes de `messages.*.json` (via `bin/console debug:translation en`)
  - [ ] **2c** — traduction EN des items, quetes, dialogues PNJ (contenu de jeu, necessite systeme dedie cf. sous-phase 4)
- [x] Selecteur de langue dans les parametres joueur — `framework.yaml` declare `enabled_locales: ['fr', 'en']` ; `LocaleController` valide strictement la locale contre cette whitelist (fallback `default_locale` si inconnue), bloque les payloads exotiques via contrainte regex de route, verifie que le referer pointe vers le meme host avant redirection. `/game/settings` expose un `<select>` branche a un controller Stimulus `locale-switcher` qui redirige vers `/change-locale/{locale}` au changement ; l'option selectionnee reflete la locale courante (`app.request.locale`). Tests unitaires (`LocaleControllerTest`, 6 cas) couvrent les chemins valide / invalide / payload malveillant / referer safe-or-not.
- [~] Contenu de jeu multilingue (noms items, descriptions sorts) — sous-phase 3a livree 2026-04-21
  - [x] **3a — Infrastructure multilingue pour les noms d'items** (2026-04-21) : colonne JSON `name_translations` ajoutee sur `game_items` (migration `Version20260421ItemNameTranslations`, idempotente). Entite `Item` etendue avec `getLocalizedName(?string $locale): string` (fallback gracieux sur `$this->name` si locale nulle/vide, colonne nulle, locale absente ou valeur blanche), `getNameTranslations(): array` (defaut `[]`) et `setNameTranslations(?array $translations): Item` avec normalisation (cles/valeurs vides filtrees, stockage compact en `null` si aucune entree valide). Tests `ItemLocalizationTest` (7 cas). Aucun template ni controller modifie : les sous-phases 3b / 3c cableront progressivement.
  - [x] **3b — Cablage dans les templates/helpers** (2026-04-21) : nouveau filter Twig `localized_name` (extension `App\Twig\ItemLocalizationExtension`) qui applique `Item::getLocalizedName` avec la locale courante recuperee depuis `RequestStack` (fallback transparent sur `Item::name` si RequestStack vide, Item null ou traduction manquante). Templates cables avec `{{ item|localized_name }}` : `templates/game/shop/index.html.twig` (listes acheter/vendre), `templates/game/inventory/items_list.html.twig`, `templates/game/inventory/materia/_slot_select.html.twig`, `templates/game/inventory/materia/_slot_select_embed.html.twig`, `templates/game/bestiary/index.html.twig` (table de loot affichee aux paliers de decouverte). Les controllers `Game\Inventory\ItemsController` et `Game\Inventory\MateriaController` (qui construisent des tableaux associatifs pour leurs templates) recuperent la locale via `Request::getLocale()` et appellent `Item::getLocalizedName($locale)` directement. Les attributs `data-item-name` et `data-item-eq-name` (utilises par le JS de recherche) conservent volontairement `item.name` non traduit pour preserver la semantique de cle. Tests `ItemLocalizationExtensionTest` (5 cas : traduction matchee, fallback base name, RequestStack vide, Item null, enregistrement du filter). Une fois la sous-phase 3c (fixtures EN pour les noms d'items) livree, chaque paire `{fr, en}` sera automatiquement affichee dans les 5 templates cables sans redeploiement de template.
  - [ ] **3c** — fixtures EN pour les noms d'items existants (boucle par tier / rarete, priorite aux items visibles en debut de jeu)
  - [ ] **3d** — extension a `Item.description` (meme pattern, colonne `description_translations`)
  - [ ] **3e** — extension aux entites `Spell`, `Quest`, `Monster`, `Pnj` (noms + descriptions/dialogues)

---

### Definition of Done

- [ ] Load test passe avec 200 joueurs simultanes
- [ ] Traduction anglaise complete de l'interface
- [ ] Selecteur de langue fonctionnel
