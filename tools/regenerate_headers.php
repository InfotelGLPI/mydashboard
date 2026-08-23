<?php

/**
 * -------------------------------------------------------------------------
 * mydashboard plugin for GLPI
 * Copyright (C) 2016-2026 by the mydashboard Development Team.
 *
 * https://github.com/InfotelGLPI/mydashboard
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of mydashboard.
 *
 * mydashboard is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * mydashboard is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

/**
 * Called by regenerate_headers.sh — do not run directly.
 *
 * Usage: php tools/regenerate_headers.php <plugin_dir> <header_file> [--dry-run]
 *
 * <header_file> holds the RAW licence text only (no comment markers, no
 * per-line prefix). This script wraps it exactly the way the official
 * glpi/tools "tools:licence_headers_check --fix" command does, so headers
 * regenerated here stay byte-compatible with the CI check:
 *   - PHP  : "/**" ... " * <line>" ... " *\/"
 *   - Twig : "{#"  ... " # <line>" ... " #}"
 * Both file types are derived from the SAME raw header, mirroring glpi/tools
 * (which uses a single header file for every language).
 */

$plugin_dir  = $argv[1] ?? null;
$header_file = $argv[2] ?? null;
$dry_run     = in_array('--dry-run', $argv, true);

if (!$plugin_dir || !$header_file) {
    fprintf(STDERR, "Usage: php regenerate_headers.php <plugin_dir> <header_file> [--dry-run]\n");
    exit(1);
}

if (!is_file($header_file)) {
    fprintf(STDERR, "Error: header file not found: %s\n", $header_file);
    exit(1);
}

// Raw licence text, one entry per line, stripped of trailing CR/LF noise.
$raw_lines = explode("\n", rtrim(file_get_contents($header_file), "\r\n"));
$raw_lines = array_map(static fn(string $l): string => rtrim($l, "\r"), $raw_lines);

/**
 * Wrap the raw licence text into a comment block for the given language,
 * replicating glpi/tools LicenceHeadersCheckCommand formatting.
 */
function wrap_header(array $raw_lines, string $ext): string
{
    // open, per-line prefix, blank-line marker, close
    $styles = [
        'php'  => ['/**', ' * ', ' *', ' */'],
        'twig' => ['{#', ' # ', ' #', ' #}'],
    ];
    [$open, $prefix, $blank, $close] = $styles[$ext];

    $out = $open . "\n";
    foreach ($raw_lines as $line) {
        $out .= ($line === '' ? $blank : $prefix . $line) . "\n";
    }
    $out .= $close;

    return $out;
}

// ---------------------------------------------------------------------------
// File type definitions
// ---------------------------------------------------------------------------
$types = [
    'php'  => ['ext' => 'php',  'open' => '<?php', 'comment_start' => '/*', 'comment_end' => '*/'],
    'twig' => ['ext' => 'twig', 'open' => null,    'comment_start' => '{#', 'comment_end' => '#}'],
];

$headers = [
    'php'  => wrap_header($raw_lines, 'php'),
    'twig' => wrap_header($raw_lines, 'twig'),
];

// ---------------------------------------------------------------------------
// Collect files (recursive, skip vendor/)
// ---------------------------------------------------------------------------
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($plugin_dir, RecursiveDirectoryIterator::SKIP_DOTS),
);

$files = [];
foreach ($iterator as $file) {
    $ext = $file->getExtension();
    if (!isset($types[$ext])) {
        continue;
    }
    $rel = str_replace($plugin_dir . DIRECTORY_SEPARATOR, '', $file->getPathname());
    $rel = str_replace('\\', '/', $rel);

    // Mirror glpi/tools exclusions so this script and the CI check agree on scope.
    $excluded = false;
    foreach (['vendor', 'node_modules', 'public/lib', 'lib', 'dist', 'var'] as $dir) {
        if ($rel === $dir || str_starts_with($rel, $dir . '/')) {
            $excluded = true;
            break;
        }
    }
    // Any hidden file or directory (e.g. .php-cs-fixer.php) is ignored by the CI.
    foreach (explode('/', $rel) as $segment) {
        if ($segment !== '' && $segment[0] === '.') {
            $excluded = true;
            break;
        }
    }
    if ($excluded) {
        continue;
    }

    $files[] = ['abs' => $file->getPathname(), 'rel' => $rel, 'ext' => $ext];
}

usort($files, fn($a, $b) => strcmp($a['rel'], $b['rel']));

// ---------------------------------------------------------------------------
// Process each file
// ---------------------------------------------------------------------------
$counts = ['updated' => 0, 'added' => 0, 'ok' => 0, 'skipped' => 0];

foreach ($files as ['abs' => $path, 'rel' => $rel, 'ext' => $ext]) {
    $def     = $types[$ext];
    $header  = $headers[$ext];
    $content = file_get_contents($path);

    if ($content === false) {
        echo "[ERROR ] Cannot read: $rel\n";
        $counts['skipped']++;
        continue;
    }

    // --- For PHP: must start with <?php ---
    if ($def['open'] !== null && !str_starts_with($content, $def['open'])) {
        echo "[SKIP  ] No open tag: $rel\n";
        $counts['skipped']++;
        continue;
    }

    // Strip the open tag for PHP, keep everything for Twig
    $after   = $def['open'] !== null ? substr($content, strlen($def['open'])) : $content;
    $trimmed = ltrim($after, "\r\n");

    $had_header = false;

    if (str_starts_with($trimmed, $def['comment_start'])) {
        $end_pos = strpos($trimmed, $def['comment_end']);
        if ($end_pos === false) {
            echo "[SKIP  ] Malformed comment block: $rel\n";
            $counts['skipped']++;
            continue;
        }
        $rest       = substr($trimmed, $end_pos + strlen($def['comment_end']));
        $had_header = true;
    } else {
        $rest = $trimmed;
    }

    $rest = ltrim($rest, "\r\n");

    // Build new content
    $prefix = $def['open'] !== null ? $def['open'] . "\n\n" : '';

    $new_content = $rest === ''
        ? $prefix . $header . "\n"
        : $prefix . $header . "\n\n" . $rest;

    if ($new_content === $content) {
        $counts['ok']++;
        continue;
    }

    $label = $had_header ? 'UPDATED' : 'ADDED  ';

    if ($dry_run) {
        echo "[DRY $label] $rel\n";
    } else {
        file_put_contents($path, $new_content);
        echo "[$label] $rel\n";
    }

    $had_header ? $counts['updated']++ : $counts['added']++;
}

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------
echo "\n";
echo str_repeat('-', 50) . "\n";
printf("Updated   : %d\n", $counts['updated']);
printf("Added     : %d\n", $counts['added']);
printf("Already OK: %d\n", $counts['ok']);
printf("Skipped   : %d\n", $counts['skipped']);

if ($dry_run) {
    echo "\n[DRY-RUN] No files were written.\n";
}
