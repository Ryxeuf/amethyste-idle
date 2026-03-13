Afficher l'etat d'avancement de la feuille de route du projet amethyste-idle.

1. Lire le fichier memoire de la feuille de route : `/home/debian/.claude/projects/-var-www/memory/roadmap_amethyste.md`
2. Pour chaque phase, verifier l'etat actuel en inspectant rapidement les fichiers cles :
   - Phase 1 (Sprites) : verifier `assets/lib/SpriteAnimator.js` et les sprites dans `map_pixi_controller.js`
   - Phase 2 (Boucle de jeu) : verifier le ticker continu et la camera dans `map_pixi_controller.js`
   - Phase 3 (Mobile) : verifier le responsive et touch dans `map_pixi_controller.js` et le template
   - Phase 4 (Dialogue PNJ) : verifier `assets/controllers/dialog_controller.js` et l'endpoint API
   - Phase 5 (Pipeline Tiled) : verifier `src/Command/TerrainImportCommand.php`
   - Phase 6 (Assets) : verifier la structure dans `assets/styles/images/`
   - Phase 7 (Performance) : verifier le culling, cache textures, etc.
3. Presenter un tableau recapitulatif avec le statut de chaque phase (fait / en cours / a faire)
4. Suggerer les prochaines etapes prioritaires
