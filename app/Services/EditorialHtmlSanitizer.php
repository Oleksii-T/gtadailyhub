<?php

namespace App\Services;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;

class EditorialHtmlSanitizer
{
    private const ALLOWED_TAGS = ['p', 'h2', 'h3', 'h4', 'strong', 'b', 'em', 'i', 'ul', 'ol', 'li', 'a', 'blockquote', 'code', 'pre', 'br', 'hr', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'figure', 'figcaption', 'img'];

    private const REMOVE_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'svg', 'math', 'link', 'meta'];

    public function sanitize(string $html): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        try {
            $document->loadHTML('<?xml encoding="UTF-8"><div id="editorial-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $root = $document->getElementById('editorial-root');
        if (! $root instanceof DOMElement) {
            return '';
        }

        foreach (iterator_to_array($root->childNodes) as $child) {
            $this->clean($child);
        }

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }

    private function clean(DOMNode $node): void
    {
        if ($node instanceof DOMComment) {
            $node->parentNode?->removeChild($node);

            return;
        }
        if (! $node instanceof DOMElement) {
            return;
        }

        $tag = strtolower($node->tagName);
        if (in_array($tag, self::REMOVE_WITH_CONTENT, true)) {
            $node->parentNode?->removeChild($node);

            return;
        }

        foreach (iterator_to_array($node->childNodes) as $child) {
            $this->clean($child);
        }

        if (! in_array($tag, self::ALLOWED_TAGS, true)) {
            while ($node->firstChild !== null) {
                $node->parentNode?->insertBefore($node->firstChild, $node);
            }
            $node->parentNode?->removeChild($node);

            return;
        }

        $href = $tag === 'a' ? trim($node->getAttribute('href')) : '';
        $src = $tag === 'img' ? trim($node->getAttribute('src')) : '';
        $alt = $tag === 'img' ? trim($node->getAttribute('alt')) : '';
        foreach (iterator_to_array($node->attributes) as $attribute) {
            $node->removeAttributeNode($attribute);
        }
        if ($tag === 'a') {
            if ($this->safeUrl($href)) {
                $node->setAttribute('href', $href);
            }
        }
        if ($tag === 'img') {
            if (! $this->safeUrl($src)) {
                $node->parentNode?->removeChild($node);
            } else {
                $node->setAttribute('src', $src);
                $node->setAttribute('alt', mb_substr($alt, 0, 255));
            }
        }
    }

    private function safeUrl(string $url): bool
    {
        if ($url === '' || str_contains($url, chr(92)) || preg_match('/[\x00-\x20\x7f]/', $url)) {
            return false;
        }

        return str_starts_with($url, '/') && ! str_starts_with($url, '//')
            || filter_var($url, FILTER_VALIDATE_URL) && in_array(parse_url($url, PHP_URL_SCHEME), ['https', 'http'], true);
    }
}
