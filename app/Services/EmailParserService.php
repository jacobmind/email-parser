<?php

namespace App\Services;

class EmailParserService
{
    public function parse(?string $raw): ?string
    {
        if (empty($raw)) {
            return null;
        }

        try {
            $parser = new \PhpMimeMailParser\Parser();
            $parser->setText($raw);

            $html = $parser->getMessageBody('html');

            if (!empty($html)) {
                return $this->extractVisibleTextFromHtml($html);
            }

            $plain = $parser->getMessageBody('text');
            if (!empty($plain)) {
                return $this->cleanContent($plain);
            }

            return null;
        } catch (\Throwable $e) {
            logger()->error('Email parsing failed: ' . $e->getMessage());
            return null;
        }
    }

    protected function extractVisibleTextFromHtml(string $html): string
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);

        // Remove script and style elements
        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//script | //style') as $node) {
            $node->parentNode->removeChild($node);
        }

        // Extract body content only
        $body = $dom->getElementsByTagName('body')->item(0);
        $text = $body ? $body->textContent : $dom->textContent;
        $text = $this->cleanWeirdWhitespace($text);

        return $this->cleanContent($text);
    }

    protected function cleanContent(string $content): string
    {
        $content = html_entity_decode($content);
        return trim(preg_replace('/\s+/', ' ', $content));
    }

    protected function cleanWeirdWhitespace(string $text): string
    {
        // Remove non-breaking spaces and zero-width characters
        $text = preg_replace('/[\x{00A0}\x{200B}-\x{200D}\x{FEFF}]+/u', ' ', $text);
        // Collapse multiple spaces into one
        return preg_replace('/\s+/', ' ', $text);
    }
}
