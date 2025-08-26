<?php
/*
 * Paste $v3.2 2025/08/21 https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 *
 * https://phpaste.sourceforge.io/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License in LICENCE for more details.
 *
 *	This file is part of Paste.
 *	Helpers to enumerate highlight.php languages.
 *	We only look at filenames; we don't execute language files.
 *
 * 	render.php - bootstrap.php - list_languages.php in this directory are ours
 */

require_once __DIR__ . '/bootstrap.php';

// Returns the active languages directory that will be used by the highlighter.
function highlight_lang_dir(): string {
    return defined('HL_LANG_DIR') ? HL_LANG_DIR : (is_dir(__DIR__ . '/languages'));
}

/**
 * Return an array of languages available, sorted by display name.
 * Each item: ['id' => 'php', 'name' => 'Php', 'filename' => 'php.php']
 */
function highlight_supported_languages(?string $dir = null): array {
    $dir = $dir ?: highlight_lang_dir();
    if (!is_dir($dir)) return [];

    // .php (current highlight.php) and .json
    $files = glob($dir . '/*.{php,json}', GLOB_BRACE) ?: [];
    $out = [];
    foreach ($files as $f) {
        $id = pathinfo($f, PATHINFO_FILENAME);
        // Friendly name from id (title case, hyphens/underscores to spaces)
        $name = ucwords(str_replace(['-', '_'], ' ', $id));
        $out[] = [
            'id'       => $id,
            'name'     => $name,
            'filename' => basename($f),
        ];
    }
    usort($out, static function($a,$b){
        $c = strcasecmp($a['name'],$b['name']);
        return $c !== 0 ? $c : strcasecmp($a['id'],$b['id']);
    });
    return $out;
}
