<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

// Chemin vers le dossier des fixtures
$fixturesDir = dirname(__DIR__) . '/fixtures/area';
$outputFile = dirname(__DIR__) . '/fixtures/area_data.json';

// Vérifier si le dossier existe
if (!is_dir($fixturesDir)) {
    echo "Le dossier $fixturesDir n'existe pas.\n";
    exit(1);
}

// Récupérer tous les fichiers YAML du dossier
$yamlFiles = glob("$fixturesDir/*.yaml");

if (empty($yamlFiles)) {
    echo "Aucun fichier YAML trouvé dans $fixturesDir.\n";
    exit(1);
}

// Tableau pour stocker les données des areas
$areasData = [];

// Parcourir chaque fichier YAML
foreach ($yamlFiles as $yamlFile) {
    $filename = basename($yamlFile, '.yaml');

    // Ignorer le fichier template area.yaml
    if ($filename === 'area') {
        continue;
    }

    echo "Traitement du fichier $filename...\n";

    // Charger le contenu YAML
    $content = file_get_contents($yamlFile);
    $data = Yaml::parse($content);

    // Extraire les données de l'area
    $entityKey = array_key_first($data);
    $areaKey = array_key_first($data[$entityKey]);
    $areaData = $data[$entityKey][$areaKey];

    // Extraire les coordonnées du nom du fichier (map_x_y.yaml)
    preg_match('/map_(\d+)_(\d+)/', $filename, $matches);
    if (count($matches) === 3) {
        $x = $matches[1];
        $y = $matches[2];
        $mapRef = 'map_1'; // Référence à la carte par défaut

        // Construire les données de l'area
        $areasData[$mapRef][] = [
            'name' => $areaData['name'] ?? "Zone $x-$y",
            'slug' => $areaData['slug'] ?? "zone-$x-$y",
            'coordinates' => $areaData['coordinates'] ?? "$x.$y",
            'data' => isset($areaData['fullData']) ? json_decode($areaData['fullData'], true) : ['x' => $x, 'y' => $y],
        ];
    }
}

// Enregistrer les données au format JSON
if (!empty($areasData)) {
    file_put_contents($outputFile, json_encode($areasData, JSON_PRETTY_PRINT));
    echo "Les données ont été enregistrées dans $outputFile.\n";
} else {
    echo "Aucune donnée d'area n'a été trouvée.\n";
}
