<?php

declare(strict_types=1);

/**
 * Verifies that critical AI-facing policy invariants remain visible across adapters.
 */
$root = dirname(__DIR__);

$files = [
    'AGENTS.md',
    'CLAUDE.md',
    'ai/skills/README.md',
    '.github/skills/repo-quality-foundation/SKILL.md',
    '.github/skills/php-package-development/SKILL.md',
];

$missingFiles = [];
$corpus = '';
$failures = [];

foreach ($files as $file) {
    $path = $root.'/'.$file;

    if (! is_file($path)) {
        $missingFiles[] = $file;

        continue;
    }

    $content = file_get_contents($path);

    if ($content === false) {
        $failures[] = 'Unreadable instruction file: '.$file;

        continue;
    }

    $corpus .= "\n\n--- {$file} ---\n".$content;
}

$requiredPatterns = [
    'namespace policy' => '/JOOservices\\\\LaravelConfig/i',
    'develop and master flow' => '/develop.*master/is',
    'stop and ask behavior' => '/stop and ask/i',
    'Pint formatting authority' => '/Pint/i',
    'MongoDB integration tests' => '/MongoDB/i',
];

foreach ($requiredPatterns as $label => $pattern) {
    if (! preg_match($pattern, $corpus)) {
        $failures[] = 'Missing required AI instruction pattern ['.$label.']';
    }
}

if ($missingFiles !== []) {
    $failures[] = 'Missing instruction files: '.implode(', ', $missingFiles);
}

if ($failures !== []) {
    fwrite(STDERR, "AI instruction sync verification failed:\n");

    foreach ($failures as $failure) {
        fwrite(STDERR, ' - '.$failure."\n");
    }

    exit(1);
}

fwrite(STDOUT, "AI instruction sync verification passed.\n");
