<?php

declare(strict_types=1);

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Strict allowlist HTML sanitiser for article bodies written in the rich-text
 * editor. Anything not explicitly allowed is removed.
 *
 * Security-critical: this is the only place user-authored HTML is trusted for
 * output. Keep the allowlists tight and covered by tests/run.php.
 */
final class HtmlSanitizer
{
    /** Tags kept as-is (with attributes filtered). */
    private const ALLOWED = [
        'p', 'br', 'h2', 'h3', 'h4', 'blockquote', 'ul', 'ol', 'li',
        'a', 'strong', 'em', 'b', 'i', 'u', 's', 'figure', 'figcaption',
        'img', 'pre', 'code', 'hr',
    ];

    /** Tags removed together with everything inside them. */
    private const STRIP_WITH_CONTENT = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input',
        'textarea', 'select', 'button', 'link', 'meta', 'noscript', 'svg',
        'math', 'template', 'head', 'title', 'base', 'frame', 'frameset',
        'applet', 'canvas', 'audio', 'video', 'source',
    ];

    /** Per-tag attribute allowlist. */
    private const ATTRS = [
        'a'   => ['href'],
        'img' => ['src', 'alt', 'width', 'height'],
    ];

    public static function clean(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);

        // Force UTF-8 interpretation without adding <html>/<body> wrappers.
        $wrapped = '<?xml encoding="UTF-8"><div id="nsa-root">' . $html . '</div>';
        $doc->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $doc->getElementById('nsa-root');
        if (!$root instanceof DOMElement) {
            return '';
        }

        self::cleanChildren($root, $doc);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }

        // Collapse runs of empty paragraphs the editor tends to leave behind.
        $out = preg_replace('#(<p>(\s|&nbsp;|<br\s*/?>)*</p>\s*)+#i', '', $out) ?? $out;

        return trim((string) $out);
    }

    private static function cleanChildren(DOMNode $node, DOMDocument $doc): void
    {
        // Snapshot: we mutate the list as we go.
        foreach (iterator_to_array($node->childNodes) as $child) {
            self::cleanNode($child, $doc);
        }
    }

    private static function cleanNode(DOMNode $node, DOMDocument $doc): void
    {
        if ($node->nodeType === XML_TEXT_NODE || $node->nodeType === XML_CDATA_SECTION_NODE) {
            return; // text is safe; DOM escapes it on save
        }

        if (!$node instanceof DOMElement) {
            // Comments, processing instructions, etc. — drop.
            $node->parentNode?->removeChild($node);
            return;
        }

        $tag = strtolower($node->nodeName);

        if (in_array($tag, self::STRIP_WITH_CONTENT, true)) {
            $node->parentNode?->removeChild($node);
            return;
        }

        // Clean descendants first.
        self::cleanChildren($node, $doc);

        if (!in_array($tag, self::ALLOWED, true)) {
            self::unwrap($node);
            return;
        }

        self::filterAttributes($node, $tag);
    }

    private static function filterAttributes(DOMElement $el, string $tag): void
    {
        $allowed = self::ATTRS[$tag] ?? [];

        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->nodeName);
            if (!in_array($name, $allowed, true)) {
                $el->removeAttribute($attr->nodeName);
            }
        }

        if ($tag === 'a') {
            $href = trim($el->getAttribute('href'));
            $safe = self::safeLink($href);
            if ($safe === null) {
                $el->removeAttribute('href');
            } else {
                $el->setAttribute('href', $safe);
                if (str_starts_with($safe, 'http')) {
                    $el->setAttribute('rel', 'nofollow noopener');
                    $el->setAttribute('target', '_blank');
                }
            }
        }

        if ($tag === 'img') {
            $src = self::safeImage(trim($el->getAttribute('src')));
            if ($src === null) {
                // No usable source — drop the image entirely.
                self::unwrap($el);
                return;
            }
            $el->setAttribute('src', $src);
            $el->setAttribute('loading', 'lazy');
            foreach (['width', 'height'] as $dim) {
                $v = $el->getAttribute($dim);
                if ($v !== '' && preg_match('/^\d{1,5}$/', $v) !== 1) {
                    $el->removeAttribute($dim);
                }
            }
        }
    }

    /** Replace an element with its children. */
    private static function unwrap(DOMElement $el): void
    {
        $parent = $el->parentNode;
        if ($parent === null) {
            return;
        }
        while ($el->firstChild !== null) {
            $parent->insertBefore($el->firstChild, $el);
        }
        $parent->removeChild($el);
    }

    private static function safeLink(string $href): ?string
    {
        if ($href === '') {
            return null;
        }
        // Reject anything with control chars or that isn't a clean scheme.
        if (preg_match('/[\x00-\x1f]/', $href) === 1) {
            return null;
        }
        if (str_starts_with($href, '/') && !str_starts_with($href, '//')) {
            return $href; // site-relative
        }
        $scheme = strtolower((string) parse_url($href, PHP_URL_SCHEME));
        if (in_array($scheme, ['http', 'https', 'mailto'], true)) {
            return $href;
        }
        return null;
    }

    private static function safeImage(string $src): ?string
    {
        if ($src === '' || preg_match('/[\x00-\x1f]/', $src) === 1) {
            return null;
        }
        if (preg_match('#^/uploads/(media|heroes|covers|logos)/[A-Za-z0-9._/-]+$#', $src) === 1) {
            return $src;
        }
        if (preg_match('#^https://[^\s"\'<>]+$#i', $src) === 1) {
            return $src;
        }
        return null;
    }
}
