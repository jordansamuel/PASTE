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
 *	Bootstrap for scrivo/highlight.php
 *	Works with either layout inside /includes/Highlight:
 *	Repo root https://github.com/scrivo/highlight.php/tree/master/src/Highlight/ copied here:
 *		/includes/Highlight/*.php
 *		/includes/Highlight/languages/*.json
 *
 * 	render.php - bootstrap.php - list_languages.php in this directory are ours
 */

declare(strict_types=1);

if (!defined('HL_BASE_DIR')) {
    define('HL_BASE_DIR', __DIR__);
}

// Find & register the library classes

// Try both autoloader locations
$autoloaders = [
    HL_BASE_DIR . '/Autoloader.php',              // some mirrors place it at root
    HL_BASE_DIR . '/Highlight/Autoloader.php',    // upstream repo layout
];
$autoloader_found = false;
foreach ($autoloaders as $al) {
    if (is_file($al)) {
        require_once $al;
        if (class_exists('\Highlight\Autoloader')) {
            \Highlight\Autoloader::register();
            $autoloader_found = true;
            break;
        }
    }
}

if (!$autoloader_found) {
    // Minimal PSR-4 fallback. Map "Highlight\" to the directory that actually contains the class files.
    $classRoots = [
        HL_BASE_DIR,                    // includes/Highlight/Highlighter.php
    ];
    spl_autoload_register(static function ($class) use ($classRoots) {
        if (strpos($class, 'Highlight\\') !== 0) return;
        $rel = str_replace('\\', '/', $class) . '.php';   // Highlight/Highlighter.php
        foreach ($classRoots as $root) {
            $p = $root . '/' . basename($rel);           // try flat file name
            if (is_file($p)) { require $p; return; }
            $p = $root . '/' . $rel;                     // try nested path
            if (is_file($p)) { require $p; return; }
        }
    });
}

// Resolve languages directory
if (!defined('HL_LANG_DIR')) {
    $candidates = [
        HL_BASE_DIR . '/languages',	// repo-root languages
    ];
    foreach ($candidates as $d) {
        if (is_dir($d)) { define('HL_LANG_DIR', $d); break; }
    }
    if (!defined('HL_LANG_DIR')) {
        // last resort (will fail gracefully later)
        define('HL_LANG_DIR', HL_BASE_DIR . '/languages');
    }
}

// Factory helper bound to HL_LANG_DIR
function make_highlighter(): ?\Highlight\Highlighter {
    if (!class_exists('\Highlight\Highlighter')) return null;

    // expose LanguageFactory at \Highlight\LanguageFactory
    if (class_exists('\Highlight\LanguageFactory')) {
        $factory = new \Highlight\LanguageFactory(HL_LANG_DIR);

        // Prefer setter if available; otherwise pass in constructor
        try {
            $hl = new \Highlight\Highlighter();
            if (method_exists($hl, 'setLanguageFactory')) {
                $hl->setLanguageFactory($factory);
                return $hl;
            }
        } catch (\Throwable $e) {
            // fall through to constructor form
        }

        try {
            return new \Highlight\Highlighter($factory);
        } catch (\Throwable $e) {
            // fall through to plain instance
        }
    }

    try {
        return new \Highlight\Highlighter();
    } catch (\Throwable $e) {
        return null;
    }
}
