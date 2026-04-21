<?php

declare(strict_types=1);

/*
 * Audit des cles de traduction (tache 135-2b, Sprint 12).
 *
 * Scanne templates/ et src/ pour detecter les cles utilisees via `|trans`
 * (Twig) et `->trans(` (PHP) puis les compare a translations/messages.{fr,en}.json.
 *
 * Sortie :
 *   - missing : cles utilisees mais absentes des deux fichiers de traduction
 *   - orphan  : cles definies mais apparemment jamais utilisees (informatif, heuristique)
 *   - parity  : ecart FR-EN / EN-FR (doit etre vide depuis 135-2a)
 *
 * Usage : php scripts/audit-translations.php [--active-only]
 *         --active-only ignore templates/old_game/ (legacy).
 *
 * Sort en code 1 si au moins une cle active est manquante, 0 sinon.
 */

$root = dirname(__DIR__);
$activeOnly = in_array('--active-only', $argv, true);

/** @return array<string, mixed> */
$loadJson = static function (string $path): array {
    $raw = file_get_contents($path);
    if (false === $raw) {
        fwrite(STDERR, "Impossible de lire $path\n");
        exit(2);
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        fwrite(STDERR, "JSON invalide dans $path\n");
        exit(2);
    }

    return $data;
};

/** @return list<string> */
$flatten = static function (array $data, string $prefix = '') use (&$flatten): array {
    $keys = [];
    foreach ($data as $k => $v) {
        $nk = '' === $prefix ? (string) $k : $prefix.'.'.$k;
        if (is_array($v)) {
            $keys = array_merge($keys, $flatten($v, $nk));
        } else {
            $keys[] = $nk;
        }
    }

    return $keys;
};

$fr = $loadJson($root.'/translations/messages.fr.json');
$en = $loadJson($root.'/translations/messages.en.json');
$frKeys = array_flip($flatten($fr));
$enKeys = array_flip($flatten($en));
$defined = $frKeys + $enKeys;

$twigPattern = '/[\'"]([a-z][a-z0-9_]*(?:\.[a-z0-9_]+)+)[\'"]\s*\|\s*trans\b/i';
$phpPattern = '/->\s*trans\s*\(\s*[\'"]([a-z][a-z0-9_]*(?:\.[a-z0-9_]+)+)[\'"]/i';

$used = [];
$scanDirs = ['templates', 'src'];
foreach ($scanDirs as $dir) {
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$dir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iter as $file) {
        /** @var SplFileInfo $file */
        if (!$file->isFile()) {
            continue;
        }
        $path = $file->getPathname();
        $ext = $file->getExtension();
        if (!in_array($ext, ['twig', 'php'], true)) {
            continue;
        }
        if ($activeOnly && str_contains($path, '/templates/old_game/')) {
            continue;
        }
        $content = file_get_contents($path);
        if (false === $content) {
            continue;
        }
        $pattern = 'twig' === $ext ? $twigPattern : $phpPattern;
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[1] as $key) {
                $used[$key][] = substr($path, strlen($root) + 1);
            }
        }
    }
}

$missing = [];
foreach ($used as $key => $files) {
    if (!isset($defined[$key])) {
        $missing[$key] = array_values(array_unique($files));
    }
}
ksort($missing);

$parityFrOnly = array_keys(array_diff_key($frKeys, $enKeys));
$parityEnOnly = array_keys(array_diff_key($enKeys, $frKeys));

printf("Cles definies : FR=%d, EN=%d\n", count($frKeys), count($enKeys));
printf("Cles utilisees (scan) : %d\n", count($used));
printf("Cles manquantes : %d%s\n", count($missing), $activeOnly ? ' (active-only)' : '');
foreach ($missing as $key => $files) {
    printf("  - %s  <-  %s\n", $key, implode(', ', array_slice($files, 0, 2)));
}

if ([] !== $parityFrOnly || [] !== $parityEnOnly) {
    printf("\nParite rompue :\n");
    printf("  FR seulement : %s\n", implode(', ', $parityFrOnly) ?: '(aucune)');
    printf("  EN seulement : %s\n", implode(', ', $parityEnOnly) ?: '(aucune)');
}

exit([] === $missing && [] === $parityFrOnly && [] === $parityEnOnly ? 0 : 1);
