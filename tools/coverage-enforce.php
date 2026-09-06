<?php

declare(strict_types=1);

/**
 * Enforce minimum statement coverage from a Clover XML report.
 *
 * Usage: php tools/coverage-enforce.php <clover.xml> [minPercent]
 */
$path = $argv[1] ?? '';
$minPercent = isset($argv[2]) ? (float) $argv[2] : 95.0;

if ($path === '' || ! is_file($path)) {
    fwrite(STDERR, "Clover report not found: {$path}\n");
    exit(1);
}

$xml = simplexml_load_file($path);
if ($xml === false) {
    fwrite(STDERR, "Unable to parse Clover report: {$path}\n");
    exit(1);
}

$metrics = $xml->project->metrics ?? null;
if ($metrics === null) {
    fwrite(STDERR, "Clover report missing project metrics: {$path}\n");
    exit(1);
}

$statements = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];
$percent = $statements > 0 ? round(($covered / $statements) * 100, 2) : 0.0;

echo "Code coverage: {$percent}%\n";

if ($percent < $minPercent) {
    fwrite(STDERR, "Code coverage {$percent}% is below minimum threshold of {$minPercent}%\n");
    exit(1);
}
