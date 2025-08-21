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
 *	Server side rendering for highlight.php
 *	Usage:
 *	require_once __DIR__ . '/includes/Highlight/render.php';
 *	echo highlight_render($code, $langIdOrEmpty, $withLineNumbers = false, $highlightLines = [])
 *
 * 	render.php - bootstrap.php - list_languages.php in this directory are ours
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php'; // also part of Paste

// Simple wrapper that can add line numbers
function highlight_render(string $code, string $languageId = '', bool $withLineNumbers = false, array $highlightLines = []): string {
    $esc = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $hl  = function_exists('make_highlighter') ? make_highlighter() : null;

    $value = $esc($code);
    $langClass = '';
    if ($hl) {
        try {
            if ($languageId !== '') {
                $res = $hl->highlight($languageId, $code);
            } else {
                $res = $hl->highlightAuto($code);
            }
            $value     = $res->value; // already safe HTML
            $langClass = $res->language ? ('language-' . $res->language) : '';
        } catch (\Throwable $e) { /* fall back to escaped */ }
    }

    if (!$withLineNumbers) {
        return '<pre class="hljs"><code class="hljs ' . $langClass . '">' . $value . '</code></pre>';
    }

    // line-numbered render
    $lines = explode("\n", $value);
    $hlset = $highlightLines ? array_flip($highlightLines) : [];
    $out   = [];
    $out[] = '<pre class="hljs"><code class="hljs ' . $langClass . '"><ol class="hljs-ln">';
    foreach ($lines as $i => $lineHtml) {
        $ln  = $i + 1;
        $cls = isset($hlset[$ln]) ? ' class="hljs-ln-line hljs-hl"' : ' class="hljs-ln-line"';
        $out[] = '<li' . $cls . '><span class="hljs-ln-n">' . $ln . '</span><span class="hljs-ln-c">' . $lineHtml . '</span></li>';
    }
    $out[] = '</ol></code></pre>';
    return implode('', $out);
}

// CSS
function highlight_line_css(): string {
    return <<<CSS
.hljs { overflow:auto; }
.hljs-ln { list-style:none; margin:0; padding:0; counter-reset: hljs-ln; }
.hljs-ln-line { display:flex; align-items:flex-start; white-space:pre; }
.hljs-ln-n { user-select:none; min-width:3em; text-align:right; padding:0 .75em 0 .5em; opacity:.6; border-right:1px solid rgba(0,0,0,.1); }
.hljs-ln-c { display:inline-block; padding-left:.75em; }
.hljs-hl { background: rgba(38,92,255,0.14); }
CSS;
}