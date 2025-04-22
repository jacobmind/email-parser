<?php

namespace App\Services;

use PhpMimeMailParser\Parser;
use Illuminate\Support\Facades\Log;

class EmailParserService
{
    protected Parser $parser;

    public function __construct(Parser $parser = null)
    {
        $this->parser = $parser ?? new Parser();
    }

    /**
     * Parses raw email content to extract visible text, prioritizing HTML content.
     *
     * @param string|null $raw Raw email content
     * @return string|null Extracted text or null if parsing fails
     */
    public function parse(?string $raw): ?string
    {
        if (is_null($raw) || $raw === '') {
            return null;
        }

        try {
            $this->parser->setText($raw);

            $html = $this->parser->getMessageBody('html');
            if (!empty($html)) {
                return $this->extractVisibleTextFromHtml($html);
            }

            $plain = $this->parser->getMessageBody('text');
            if (!empty($plain)) {
                return $this->cleanContent($plain);
            }

            return null;
        } catch (\PhpMimeMailParser\Exception $e) {
            Log::error('Email parsing failed: ' . $e->getMessage());
            return null;
        } catch (\Throwable $e) {
            Log::error('Unexpected error during email parsing: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extracts visible text from HTML content, adding single newlines for block elements.
     *
     * @param string $html HTML content
     * @return string Cleaned text content
     */
    protected function extractVisibleTextFromHtml(string $html): string
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();

        // Limit input size to prevent memory issues
        if (strlen($html) > 1_000_000) {
            Log::warning('HTML content too large, falling back to raw text cleaning');
            return $this->cleanContent($html);
        }

        $loaded = $dom->loadHTML(
            mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'),
            LIBXML_NOERROR | LIBXML_NOWARNING
        );

        if (!$loaded) {
            return $this->cleanContent($html);
        }

        // Remove script and style elements
        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//script | //style') as $node) {
            $node->parentNode->removeChild($node);
        }

        // Extract text with single newlines for block elements
        $body = $dom->getElementsByTagName('body')->item(0) ?? $dom;
        $text = $this->extractTextWithNewlines($body);

        return $this->cleanContent($text);
    }

    /**
     * Recursively extracts text from DOM nodes, adding single newlines for block elements.
     *
     * @param \DOMNode $node DOM node to process
     * @return string Extracted text
     */
    protected function extractTextWithNewlines(\DOMNode $node): string
    {
        $text = '';
        $blockElements = ['p', 'div', 'br', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li'];

        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMText) {
                $childText = trim($child->textContent);
                if ($childText !== '') {
                    $text .= $childText;
                }
            } elseif ($child instanceof \DOMElement) {
                $tagName = strtolower($child->tagName);
                $childText = $this->extractTextWithNewlines($child);
                if ($childText !== '') {
                    $text .= $childText;
                }
                if (in_array($tagName, $blockElements) && $childText !== '') {
                    $text .= "\n";
                }
            }
        }

        return $text;
    }

    /**
     * Cleans content by normalizing whitespace and decoding entities, ensuring single newlines.
     *
     * @param string $content Content to clean
     * @return string Cleaned content
     */
    protected function cleanContent(string $content): string
    {
        // Decode HTML entities
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Remove non-breaking spaces and zero-width characters
        $content = preg_replace('/[\x{00A0}\x{200B}-\x{200D}\x{FEFF}]+/u', ' ', $content);

        // Normalize multiple newlines to a single newline
        $content = preg_replace('/\n{2,}/', "\n", $content);

        // Replace non-newline whitespace with single spaces
        $content = preg_replace('/[^\S\n]+/', ' ', $content);

        // Final pass to ensure no consecutive newlines
        $content = preg_replace('/\n{2,}/', "\n", $content);

        return trim($content);
    }
}
