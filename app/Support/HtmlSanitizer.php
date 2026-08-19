<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allowlist HTML sanitizer for rich-text content authored in the admin dashboard.
 *
 * The stored HTML is rendered raw on the public site, so this is the boundary that
 * keeps a malicious or compromised author from injecting scripts. It keeps a fixed set
 * of formatting tags/attributes and strips everything else — unknown tags, event
 * handlers, `javascript:`/`data:` URLs, and disallowed protocols.
 *
 * DOMDocument-based (no third-party dependency). Sanitizing is idempotent.
 */
class HtmlSanitizer
{
    /** Tags kept in the output; their children are preserved even when a wrapper is stripped. */
    private const ALLOWED_TAGS = [
        'p', 'br', 'hr', 'span', 'div',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'sub', 'sup', 'mark', 'small',
        'ul', 'ol', 'li',
        'a', 'blockquote', 'pre', 'code',
        'img', 'figure', 'figcaption',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
    ];

    /** Attributes kept per tag. `*` applies to every allowed tag. */
    private const ALLOWED_ATTRIBUTES = [
        '*' => ['class'],
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan'],
    ];

    /** Protocols permitted in href/src. */
    private const ALLOWED_PROTOCOLS = ['http', 'https', 'mailto', 'tel'];

    public function clean(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $document = new DOMDocument;

        // Wrap so DOMDocument keeps a single root and does not inject <html>/<body> semantics
        // we then have to unpick; the UTF-8 meta prevents mojibake on multibyte content.
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="__root__">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('__root__');

        if (! $root) {
            return '';
        }

        $this->sanitizeChildren($root);

        $output = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }

    private function sanitizeChildren(DOMNode $node): void
    {
        // Snapshot children first — the list mutates as we strip nodes.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $this->sanitizeElement($child);
            }
        }
    }

    private function sanitizeElement(DOMElement $element): void
    {
        $tag = strtolower($element->tagName);

        if (! in_array($tag, self::ALLOWED_TAGS, true)) {
            // Disallowed tag: drop it but keep its (sanitized) children, unless it is a
            // script/style-like container whose contents must never survive.
            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'template'], true)) {
                $element->parentNode?->removeChild($element);

                return;
            }

            $this->sanitizeChildren($element);
            $this->unwrap($element);

            return;
        }

        $this->stripAttributes($element, $tag);
        $this->sanitizeChildren($element);
    }

    private function stripAttributes(DOMElement $element, string $tag): void
    {
        $allowed = array_merge(self::ALLOWED_ATTRIBUTES['*'], self::ALLOWED_ATTRIBUTES[$tag] ?? []);

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->name);

            if (! in_array($name, $allowed, true)) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            if (in_array($name, ['href', 'src'], true) && ! $this->isSafeUrl($attribute->value)) {
                $element->removeAttribute($attribute->name);
            }
        }

        // Links that open a new tab must not leak the opener.
        if ($tag === 'a' && $element->getAttribute('target') === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private function isSafeUrl(string $url): bool
    {
        $url = trim($url);

        // Relative URLs and anchors are fine.
        if ($url === '' || str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }

        if (! str_contains($url, ':')) {
            return true;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, self::ALLOWED_PROTOCOLS, true);
    }

    /** Replace an element with its children, preserving order. */
    private function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if (! $parent) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }
}
