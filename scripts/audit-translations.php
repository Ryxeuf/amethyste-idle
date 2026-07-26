<?php

declare(strict_types=1);

/*
 * Audit des cles de traduction (tache 135, Sprint 12).
 *
 * Rapport en ligne de commande. La logique vit dans
 * `App\Translation\TranslationCatalogAudit`, partagee avec le garde-fou
 * `tests/Unit/Translation/TranslationCatalogAuditTest.php` : le script montre le
 * detail, le test empeche la regression.
 *
 * Sortie :
 *   - missing : cles utilisees mais absentes des deux catalogues
 *   - parity  : ecart FR-EN / EN-FR
 *
 * Usage : php scripts/audit-translations.php [--active-only]
 *         --active-only ignore templates/old_game/ (heritage pre-pivot).
 *
 * Sort en code 1 si au moins un ecart est trouve, 0 sinon.
 */

use App\Translation\TranslationCatalogAudit;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
$activeOnly = in_array('--active-only', $argv, true);

$audit = new TranslationCatalogAudit($root);

$missing = $audit->missingKeys($activeOnly);
$parity = $audit->parityGaps();

printf("Cles definies : FR=%d, EN=%d\n", count($audit->keys('fr')), count($audit->keys('en')));
printf("Cles utilisees (scan) : %d\n", count($audit->usedKeys($activeOnly)));
printf("Cles manquantes : %d%s\n", count($missing), $activeOnly ? ' (active-only)' : '');
foreach ($missing as $key => $files) {
    printf("  - %s  <-  %s\n", $key, implode(', ', array_slice($files, 0, 2)));
}

if ([] !== $parity['fr_only'] || [] !== $parity['en_only']) {
    printf("\nParite rompue :\n");
    printf("  FR seulement : %s\n", implode(', ', $parity['fr_only']) ?: '(aucune)');
    printf("  EN seulement : %s\n", implode(', ', $parity['en_only']) ?: '(aucune)');
}

exit([] === $missing && [] === $parity['fr_only'] && [] === $parity['en_only'] ? 0 : 1);
