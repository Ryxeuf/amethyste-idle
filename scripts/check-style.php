<?php

/*
 * Garde de style local — rejoue hors Docker neuf regles de la CI PHP-CS-Fixer.
 *
 * Ce script n'a AUCUNE dependance (ni vendor/, ni Composer, ni Docker) : il
 * s'appuie uniquement sur `token_get_all()`. Il ne remplace pas
 * `vendor/bin/php-cs-fixer` — il attrape avant le push les neuf regles qui
 * cassent le plus souvent la CI, quand l'environnement de travail n'a pas
 * de conteneur PHP.
 *
 * Usage :
 *   php scripts/check-style.php                 # src/ tests/ config/
 *   php scripts/check-style.php src/Entity      # un dossier
 *   php scripts/check-style.php a.php b.php     # des fichiers
 *   git diff --name-only --diff-filter=ACM | grep '\.php$' | xargs php scripts/check-style.php
 *
 * Sortie : une ligne `fichier:ligne: REGLE message` par violation, code de
 * sortie 1 si au moins une violation est trouvee.
 *
 * Ce bloc s'ecrit `/*` et non `/**` a dessein : un phpdoc suivi d'une ligne
 * vide est refuse par `no_blank_lines_after_phpdoc`, et le coller a la
 * constante ferait passer une notice de fichier pour la documentation de
 * `DEFAULT_PATHS`. C'est la huitieme regle, apprise en cassant la CI avec ce
 * fichier meme. La neuvieme — l'alignement des colonnes d'un bloc de `@param` —
 * a ete apprise de la meme facon, deux chantiers plus tard.
 */

/*
 * Perimetre par defaut : celui du finder de `.php-cs-fixer.dist.php`, tout le
 * depot moins ses exclusions. `scripts/` en faisait partie et pas de la
 * premiere version de ce fichier — c'est ainsi que le garde a laisse passer
 * une violation qui vivait dans le garde lui-meme.
 */

const DEFAULT_PATHS = ['.'];

const EXCLUDED_DIRS = ['var', 'vendor', 'node_modules', 'public/assets', 'data', '.git'];

/** Ponctuations acceptees par phpdoc_summary en fin de resume. */
const SUMMARY_END = ['.', '?', '!', '。'];

$paths = array_slice($argv, 1);
$onlyRules = [];

foreach ($paths as $i => $path) {
    if (str_starts_with($path, '--rules=')) {
        $onlyRules = explode(',', substr($path, 8));
        unset($paths[$i]);
    }
}

$paths = array_values($paths);

if ([] === $paths) {
    $paths = DEFAULT_PATHS;
}

$root = dirname(__DIR__);
$files = [];

foreach ($paths as $path) {
    $absolute = str_starts_with($path, '/') ? $path : $root . '/' . $path;
    // Sans normalisation, `.` produit des chemins « /racine/./data/... » dont le
    // segment relatif commence par « ./ » : les exclusions ne mordraient pas.
    $absolute = realpath($absolute) ?: $absolute;

    if (is_file($absolute)) {
        $files[] = $absolute;
        continue;
    }

    if (!is_dir($absolute)) {
        fwrite(STDERR, sprintf("Chemin introuvable : %s\n", $path));
        exit(2);
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absolute, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if (!$file->isFile() || 'php' !== $file->getExtension()) {
            continue;
        }

        $relative = str_starts_with($file->getPathname(), $root . '/') ? substr($file->getPathname(), \strlen($root) + 1) : $file->getPathname();

        foreach (EXCLUDED_DIRS as $excluded) {
            if (str_starts_with($relative, $excluded . '/')) {
                continue 2;
            }
        }

        $files[] = $file->getPathname();
    }
}

$files = array_values(array_unique($files));
sort($files);

$violations = [];

foreach ($files as $file) {
    $source = file_get_contents($file);

    if (false === $source) {
        continue;
    }

    $relative = str_starts_with($file, $root . '/') ? substr($file, \strlen($root) + 1) : $file;

    foreach (inspect($source) as $violation) {
        if ([] !== $onlyRules && !\in_array($violation['rule'], $onlyRules, true)) {
            continue;
        }

        $violations[] = sprintf('%s:%d: %s %s', $relative, $violation['line'], $violation['rule'], $violation['message']);
    }
}

foreach ($violations as $violation) {
    echo $violation, "\n";
}

printf("\n%d fichier(s) analyse(s), %d violation(s).\n", \count($files), \count($violations));

exit([] === $violations ? 0 : 1);

/**
 * Applique les neuf regles a un fichier et retourne la liste des violations.
 *
 * @return list<array{rule: string, line: int, message: string}>
 */
function inspect(string $source): array
{
    $tokens = token_get_all($source);
    $lines = preg_split('/\R/', $source) ?: [];

    return array_merge(
        checkSingleQuote($tokens),
        checkSingleLineThrow($tokens),
        checkRedundantNullsafeCoalesce($tokens),
        checkOrderedImports($tokens),
        checkPhpdoc($tokens),
        checkBlankLineAfterPhpdoc($tokens),
        checkVarDocBeforeReturn($tokens),
        checkBlankLines($lines),
    );
}

/**
 * single_quote : une chaine sans variable ni echappement s'ecrit en quotes simples.
 *
 * @param list<array{0: int, 1: string, 2: int}|string> $tokens
 *
 * @return list<array{rule: string, line: int, message: string}>
 */
function checkSingleQuote(array $tokens): array
{
    $violations = [];

    foreach ($tokens as $token) {
        if (!\is_array($token) || T_CONSTANT_ENCAPSED_STRING !== $token[0]) {
            continue;
        }

        $value = $token[1];

        if (!str_starts_with($value, '"')) {
            continue;
        }

        $inner = substr($value, 1, -1);

        // Une simple quote dans la chaine, un echappement ou une interpolation
        // rendent la conversion non triviale : php-cs-fixer s'abstient aussi.
        if (str_contains($inner, "'") || str_contains($inner, '\\') || str_contains($inner, '$')) {
            continue;
        }

        $violations[] = [
            'rule' => 'single_quote',
            'line' => $token[2],
            'message' => sprintf('la chaine %s doit utiliser des quotes simples.', mb_substr($value, 0, 40)),
        ];
    }

    return $violations;
}

/**
 * single_line_throw : `throw new X(...)` tient sur une seule ligne.
 *
 * @param list<array{0: int, 1: string, 2: int}|string> $tokens
 *
 * @return list<array{rule: string, line: int, message: string}>
 */
function checkSingleLineThrow(array $tokens): array
{
    $violations = [];
    $count = \count($tokens);

    for ($i = 0; $i < $count; ++$i) {
        $token = $tokens[$i];

        if (!\is_array($token) || T_THROW !== $token[0]) {
            continue;
        }

        $line = $token[2];
        $depth = 0;

        for ($j = $i + 1; $j < $count; ++$j) {
            $current = $tokens[$j];

            if ('(' === $current || '[' === $current) {
                ++$depth;
                continue;
            }

            if (')' === $current || ']' === $current) {
                --$depth;
                continue;
            }

            if (0 === $depth && (';' === $current || ',' === $current)) {
                break;
            }

            if (\is_array($current) && str_contains($current[1], "\n")) {
                $violations[] = [
                    'rule' => 'single_line_throw',
                    'line' => $line,
                    'message' => 'le throw doit tenir sur une seule ligne.',
                ];
                break;
            }
        }
    }

    return $violations;
}

/**
 * Un `?->` sur une expression qui ne peut pas etre nulle : PHPStan le refuse.
 *
 * Le garde ne connait pas les types : il n'attrape que le cas ou la
 * non-nullabilite est certaine sans analyse — `$this?->`. Les autres cas
 * (`$service?->method()` sur un service injecte) restent du ressort de PHPStan
 * dans la CI.
 *
 * @param list<array{0: int, 1: string, 2: int}|string> $tokens
 *
 * @return list<array{rule: string, line: int, message: string}>
 */
function checkRedundantNullsafeCoalesce(array $tokens): array
{
    $violations = [];
    $count = \count($tokens);

    for ($i = 0; $i < $count; ++$i) {
        $token = $tokens[$i];

        if (!\is_array($token) || T_NULLSAFE_OBJECT_OPERATOR !== $token[0]) {
            continue;
        }

        $previous = $tokens[$i - 1] ?? null;

        if (\is_array($previous) && T_VARIABLE === $previous[0] && '$this' === $previous[1]) {
            $violations[] = [
                'rule' => 'nullsafe_on_non_nullable',
                'line' => $token[2],
                'message' => '`$this` ne peut pas etre nul : utiliser `->` plutot que `?->`.',
            ];
        }
    }

    return $violations;
}

/**
 * ordered_imports : tri alphabetique sur le chemin COMPLET, `\` valant un espace.
 *
 * @param list<array{0: int, 1: string, 2: int}|string> $tokens
 *
 * @return list<array{rule: string, line: int, message: string}>
 */
function checkOrderedImports(array $tokens): array
{
    $count = \count($tokens);
    /** @var array<string, list<array{name: string, line: int}>> $groups */
    $groups = [];

    for ($i = 0; $i < $count; ++$i) {
        $token = $tokens[$i];

        if (\is_array($token) && \in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM, T_FUNCTION], true)) {
            break; // Les imports precedent toujours la premiere declaration.
        }

        if (!\is_array($token) || T_USE !== $token[0]) {
            continue;
        }

        $kind = 'class';
        $name = '';
        $line = $token[2];

        for ($j = $i + 1; $j < $count; ++$j) {
            $current = $tokens[$j];

            if (';' === $current) {
                $i = $j;
                break;
            }

            if ('{' === $current || ',' === $current) {
                // Import groupe ou multiple : hors perimetre du garde.
                $name = '';
                $i = $j;
                break;
            }

            if (\is_array($current)) {
                if (T_FUNCTION === $current[0]) {
                    $kind = 'function';
                    continue;
                }

                if (T_CONST === $current[0]) {
                    $kind = 'const';
                    continue;
                }

                if (T_WHITESPACE === $current[0] || T_COMMENT === $current[0]) {
                    continue;
                }

                if (T_AS === $current[0]) {
                    break;
                }

                $name .= $current[1];
            }
        }

        if ('' !== $name) {
            $groups[$kind][] = ['name' => $name, 'line' => $line];
        }
    }

    $violations = [];

    foreach ($groups as $kind => $imports) {
        for ($i = 1; $i < \count($imports); ++$i) {
            $previous = $imports[$i - 1]['name'];
            $current = $imports[$i]['name'];

            if (importOrderKey($previous) > importOrderKey($current)) {
                $violations[] = [
                    'rule' => 'ordered_imports',
                    'line' => $imports[$i]['line'],
                    'message' => sprintf('`use %s%s` doit preceder `use %s`.', 'class' === $kind ? '' : $kind . ' ', $current, $previous),
                ];
            }
        }
    }

    return $violations;
}

/**
 * Clef de tri d'un import : php-cs-fixer remplace `\` par un espace, puis compare sans casse.
 */
function importOrderKey(string $name): string
{
    return strtolower(str_replace('\\', ' ', ltrim($name, '\\')));
}

/**
 * phpdoc_summary (resume ponctue) et phpdoc_align (description de tag sur une seule ligne).
 *
 * @param list<array{0: int, 1: string, 2: int}|string> $tokens
 *
 * @return list<array{rule: string, line: int, message: string}>
 */
function checkPhpdoc(array $tokens): array
{
    $violations = [];

    foreach ($tokens as $token) {
        if (!\is_array($token) || T_DOC_COMMENT !== $token[0]) {
            continue;
        }

        $startLine = $token[2];
        $raw = preg_split('/\R/', $token[1]) ?: [];

        if (1 === \count($raw)) {
            continue; // php-cs-fixer laisse les docblocks d'une ligne intacts.
        }

        $content = [];

        foreach ($raw as $offset => $line) {
            $trimmed = trim($line);
            $trimmed = preg_replace('#^/\*\*#', '', $trimmed) ?? $trimmed;
            $trimmed = preg_replace('#\*/$#', '', $trimmed) ?? $trimmed;
            $trimmed = preg_replace('#^\*#', '', $trimmed) ?? $trimmed;
            $text = trim($trimmed);
            $column = '' === $text ? 0 : (int) mb_strpos($line, mb_substr($text, 0, 1), (int) mb_strpos($line, '*'));
            $content[] = ['text' => $text, 'line' => $startLine + $offset, 'column' => $column];
        }

        $summary = [];
        $sawTag = false;

        foreach ($content as $entry) {
            $text = $entry['text'];

            if ('' === $text) {
                if ([] !== $summary) {
                    break;
                }

                continue;
            }

            if (str_starts_with($text, '@')) {
                $sawTag = true;
                break;
            }

            $summary[] = $entry;
        }

        if ([] !== $summary && !$sawTag) {
            // Le resume est le seul contenu : rien de plus a verifier ici.
        }

        if ([] !== $summary) {
            $last = end($summary);
            $text = $last['text'];
            $isInherit = false !== stripos(implode(' ', array_column($summary, 'text')), '@inheritdoc');

            if (!$isInherit && !\in_array(mb_substr($text, -1), SUMMARY_END, true)) {
                $violations[] = [
                    'rule' => 'phpdoc_summary',
                    'line' => $last['line'],
                    'message' => 'le resume du phpdoc doit se terminer par un point.',
                ];
            }
        }

        // phpdoc_align, l'autre moitie : dans un bloc de `@param` consecutifs,
        // les colonnes sont alignees sur l'entree la plus longue, avec UN seul
        // espace de separation. Deux espaces « pour faire joli » suffisent a
        // faire rougir la CI — c'est ce qui est arrive a ce garde meme.
        $params = [];
        foreach ($content as $entry) {
            // Le type est capture en non-greedy : `array<string, int|string>`
            // contient un espace, et un `\S+` ne prendrait que sa moitie —
            // c'est ce qui faisait crier le garde sur du code deja CI-vert.
            if (1 === preg_match('/^@param\s+(.+?)\s+(\$\S+)\s+(\S.*)$/', $entry['text'], $m)) {
                $params[] = ['line' => $entry['line'], 'type' => $m[1], 'name' => $m[2], 'desc' => $m[3], 'raw' => $entry['text']];
                continue;
            }

            $violations = array_merge($violations, alignedParamViolations($params));
            $params = [];
        }

        $violations = array_merge($violations, alignedParamViolations($params));

        // phpdoc_align : une description de tag continuee sur la ligne suivante
        // est realignee par la CI sur la colonne de la description. Le garde ne
        // signale que les continuations ecrites « au fil du texte », c'est-a-dire
        // celles qui ne sont pas deja alignees loin a droite du tag.
        $tagColumn = null;
        $openBraces = 0;

        foreach ($content as $entry) {
            $text = $entry['text'];
            $balance = substr_count($text, '{') - substr_count($text, '}');

            if ('' === $text) {
                $tagColumn = null;
                $openBraces = 0;
                continue;
            }

            if (str_starts_with($text, '@')) {
                $tagColumn = $entry['column'];
                $openBraces = $balance;
                continue;
            }

            // Un type multi-ligne (array shape) n'est pas une description.
            if ($openBraces > 0) {
                $openBraces += $balance;
                continue;
            }

            if (null !== $tagColumn && $entry['column'] <= $tagColumn + 2) {
                $violations[] = [
                    'rule' => 'phpdoc_align',
                    'line' => $entry['line'],
                    'message' => 'une description de tag phpdoc doit tenir sur une seule ligne.',
                ];
            }

            $tagColumn = null;
        }
    }

    return $violations;
}

/**
 * no_blank_lines_after_phpdoc : un phpdoc est colle a ce qu'il documente.
 *
 * @param list<array{0: int, 1: string, 2: int}|string> $tokens
 *
 * @return list<array{rule: string, line: int, message: string}>
 */
function checkBlankLineAfterPhpdoc(array $tokens): array
{
    $violations = [];
    $count = \count($tokens);

    for ($i = 0; $i < $count; ++$i) {
        $token = $tokens[$i];

        if (!\is_array($token) || T_DOC_COMMENT !== $token[0]) {
            continue;
        }

        $next = $tokens[$i + 1] ?? null;

        if (\is_array($next) && T_WHITESPACE === $next[0] && substr_count($next[1], "\n") > 1) {
            $violations[] = [
                'rule' => 'no_blank_lines_after_phpdoc',
                'line' => $token[2],
                'message' => 'un phpdoc ne peut pas etre suivi d\'une ligne vide (un bloc d\'en-tete de fichier s\'ecrit `/*`, pas `/**`).',
            ];
        }
    }

    return $violations;
}

/**
 * phpdoc_to_comment : un `@var` qui ne documente aucune declaration s'ecrit `/*`.
 *
 * Un phpdoc documente **quelque chose** — une propriete, une assignation. Pose
 * devant un `return`, il ne documente rien du tout : php-cs-fixer le degrade
 * alors en commentaire ordinaire.
 *
 * La dixieme regle de ce garde, et la troisieme apprise en cassant la CI. Le
 * controle est volontairement etroit — il ne regarde que le cas `return`, celui
 * qui s'est presente — parce que ce fichier vaut par son absence de faux
 * positifs, pas par son exhaustivite.
 *
 * @param list<array{0: int, 1: string, 2: int}|string> $tokens
 *
 * @return list<array{rule: string, line: int, message: string}>
 */
function checkVarDocBeforeReturn(array $tokens): array
{
    $violations = [];
    $count = \count($tokens);

    for ($i = 0; $i < $count; ++$i) {
        $token = $tokens[$i];

        if (!\is_array($token) || T_DOC_COMMENT !== $token[0] || !str_contains($token[1], '@var')) {
            continue;
        }

        for ($j = $i + 1; $j < $count; ++$j) {
            $next = $tokens[$j];
            if (\is_array($next) && \in_array($next[0], [T_WHITESPACE, T_COMMENT], true)) {
                continue;
            }

            if (\is_array($next) && T_RETURN === $next[0]) {
                $violations[] = [
                    'rule' => 'phpdoc_to_comment',
                    'line' => $token[2],
                    'message' => 'un `@var` pose devant un `return` ne documente rien : il s\'ecrit `/*`, pas `/**`.',
                ];
            }

            break;
        }
    }

    return $violations;
}

/**
 * no_extra_blank_lines et blank_line_between_class_members, dans les deux sens.
 *
 * @param list<string> $lines
 *
 * @return list<array{rule: string, line: int, message: string}>
 */
function checkBlankLines(array $lines): array
{
    $violations = [];
    $count = \count($lines);

    for ($i = 1; $i < $count; ++$i) {
        $previous = $lines[$i - 1];
        $current = $lines[$i];

        if ('' === trim($previous) && '' === trim($current) && $i + 1 < $count) {
            $violations[] = [
                'rule' => 'no_extra_blank_lines',
                'line' => $i + 1,
                'message' => 'deux lignes vides consecutives.',
            ];
        }

        // Fin de membre de classe collee au membre suivant.
        if (preg_match('/^    \}$/', $previous) && preg_match('/^    (public|protected|private|function|const|abstract|final|static|readonly|#\[|\/\*\*|use )/', $current)) {
            $violations[] = [
                'rule' => 'blank_line_between_class_members',
                'line' => $i + 1,
                'message' => 'il manque une ligne vide entre deux membres de classe.',
            ];
        }
    }

    return $violations;
}

/**
 * Colonnes mal alignees dans un bloc de `@param` consecutifs.
 *
 * php-cs-fixer aligne le nom sur le type le plus long, et la description sur le
 * nom le plus long — un seul espace de separation dans les deux cas.
 *
 * @param list<array{line: int, type: string, name: string, desc: string, raw: string}> $params bloc contigu
 *
 * @return list<array{rule: string, line: int, message: string}>
 */
function alignedParamViolations(array $params): array
{
    if (\count($params) < 2) {
        return []; // Un `@param` seul n'a rien a aligner.
    }

    $typeWidth = max(array_map(static fn (array $p): int => \strlen($p['type']), $params));
    $nameWidth = max(array_map(static fn (array $p): int => \strlen($p['name']), $params));

    $violations = [];

    foreach ($params as $param) {
        // Egalite stricte, et non `str_starts_with` : un espace surnumeraire
        // apres le nom passerait le prefixe sans etre vu, alors que c'est
        // exactement le defaut qu'on cherche.
        $expected = sprintf('@param %s %s %s', str_pad($param['type'], $typeWidth), str_pad($param['name'], $nameWidth), $param['desc']);

        if ($param['raw'] !== $expected) {
            $violations[] = [
                'rule' => 'phpdoc_align',
                'line' => $param['line'],
                'message' => 'les colonnes d\'un bloc de @param s\'alignent sur l\'entree la plus longue, avec un seul espace de separation.',
            ];
        }
    }

    return $violations;
}

/**
 * Index du prochain token significatif apres $index, ou null.
 *
 * @param list<array{0: int, 1: string, 2: int}|string> $tokens
 */
function nextMeaningful(array $tokens, int $index): ?int
{
    $count = \count($tokens);

    for ($i = $index + 1; $i < $count; ++$i) {
        $token = $tokens[$i];

        if (\is_array($token) && \in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }

        return $i;
    }

    return null;
}
