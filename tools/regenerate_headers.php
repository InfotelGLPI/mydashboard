<?php

/*
 -------------------------------------------------------------------------
 mydashboard plugin for GLPI
 Copyright (C) 2016-2026 by the mydashboard Development Team.

 https://github.com/InfotelGLPI/mydashboard
 -------------------------------------------------------------------------

 LICENSE

 This file is part of mydashboard.

 mydashboard is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 mydashboard is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/**
 * Called by regenerate_headers.sh — do not run directly.
 *
 * Usage: php tools/regenerate_headers.php <plugin_dir> <header_php> <header_twig> [--dry-run]
 */

$plugin_dir   = $argv[1] ?? null;
$header_php   = $argv[2] ?? null;
$header_twig  = $argv[3] ?? null;
$dry_run      = in_array('--dry-run', $argv, true);

if (!$plugin_dir || !$header_php || !$header_twig) {
    fprintf(STDERR, "Usage: php regenerate_headers.php <plugin_dir> <header_php> <header_twig> [--dry-run]\n");
    exit(1);
}

foreach (['php' => $header_php, 'twig' => $header_twig] as $type => $path) {
    if (!is_file($path)) {
        fprintf(STDERR, "Error: %s header file not found: %s\n", $type, $path);
        exit(1);
    }
}

$headers = [
    'php'  => rtrim(file_get_contents($header_php),  "\r\n"),
    'twig' => rtrim(file_get_contents($header_twig), "\r\n"),
];

// ---------------------------------------------------------------------------
// File type definitions
// ---------------------------------------------------------------------------
// Each entry: open_tag (string to detect/prefix), comment_end (closing marker)
$types = [
    'php'  => ['ext' => 'php',  'open' => '<?php', 'comment_start' => '/*', 'comment_end' => '*/'],
    'twig' => ['ext' => 'twig', 'open' => null,     'comment_start' => '{#', 'comment_end' => '#}'],
];

// ---------------------------------------------------------------------------
// Collect files (recursive, skip vendor/)
// ---------------------------------------------------------------------------
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($plugin_dir, RecursiveDirectoryIterator::SKIP_DOTS)
);

$files = [];
foreach ($iterator as $file) {
    $ext = $file->getExtension();
    if (!isset($types[$ext])) {
        continue;
    }
    $rel = str_replace($plugin_dir . DIRECTORY_SEPARATOR, '', $file->getPathname());
    $rel = str_replace('\\', '/', $rel);
    if (str_starts_with($rel, 'vendor/')) {
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
