# Fixtures pour les Areas

Ce dossier contient les fixtures pour les areas de la carte.

## Structure des fichiers

- `area/` : Dossier contenant les fichiers YAML des areas
  - `area.yaml` : Template pour les areas
  - `map_x_y.yaml` : Données pour chaque area (où x et y sont les coordonnées)
- `area_data.json` : Fichier JSON généré contenant toutes les données des areas

## Génération du fichier JSON

Pour générer le fichier JSON à partir des fichiers YAML, exécutez le script suivant :

```bash
php scripts/concat_area_fixtures.php
```

Ce script va :
1. Parcourir tous les fichiers YAML du dossier `fixtures/area/`
2. Extraire les données de chaque area
3. Générer un fichier JSON `fixtures/area_data.json` contenant toutes les données

## Chargement des fixtures

Pour charger les fixtures, utilisez la commande Doctrine :

```bash
php bin/console doctrine:fixtures:load --append
```

L'option `--append` permet de ne pas supprimer les données existantes.

## Structure du fichier JSON

Le fichier JSON généré a la structure suivante :

```json
{
  "map_1": [
    {
      "name": "Zone 0-0",
      "slug": "zone-0-0",
      "coordinates": "0.0",
      "data": { ... }
    },
    {
      "name": "Zone 0-1",
      "slug": "zone-0-1",
      "coordinates": "0.1",
      "data": { ... }
    },
    ...
  ]
}
```

Chaque area est associée à une map (par défaut "map_1") et contient les propriétés suivantes :
- `name` : Nom de l'area
- `slug` : Slug de l'area
- `coordinates` : Coordonnées de l'area au format "x.y"
- `data` : Données supplémentaires de l'area 