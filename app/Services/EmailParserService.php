<?php

namespace App\Services;

use App\Models\SuccessfulEmail;
use PhpMimeMailParser\Parser;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EmailParserService
{
    protected Parser $parser;

    public function __construct(?Parser $parser = null)
    {
        $this->parser = $parser ?? new Parser();
    }

    /**
     * Parses raw email content to extract visible text only, prioritizing HTML content.
     *
     * @param string|null $raw Raw email content
     * @return string|null Extracted text or null if parsing fails
     */
    public function parse_raw_text(?string $raw): ?string
    {
        if (empty($raw)) return null;

        try {
            $this->parser->setText($raw);
            $html = $this->parser->getMessageBody('html');
            return !empty($html) ? $this->extractVisibleText($html) : $this->cleanContent($this->parser->getMessageBody('text') ?: '');
        } catch (\Throwable $e) {
            Log::error('Email text parsing error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Parses raw email content and populates a SuccessfulEmail model with all fields, including envelope, sender_ip, spam_score, spf, and dkim.
     *
     * @param string|null $raw Raw email content
     * @return SuccessfulEmail|null Populated model or null if parsing fails
     */
    public function parse(?string $raw): ?SuccessfulEmail
    {
        if (empty($raw)) return null;

        $email = new SuccessfulEmail();
        $email->email = $raw;

        try {
            $this->parser->setText($raw);

            $email->raw_text = $this->parse_raw_text($raw) ?? '-';
            $email->subject = $this->parser->getHeader('subject') ?: "-";
            $email->from = $this->parser->getHeader('from') ?? "-";
            $email->to = $this->parser->getAddresses('to')[0]['address'] ?? "-";

            $date = $this->parser->getHeader('date');
            $email->date = $date ? Carbon::parse($date) : null;

            $email->envelope = json_encode([
                'to' => $this->parser->getAddresses('to')[0]['address'] ?? [],
                'from' => $email->from,
            ]);

            $email->sender_ip = $this->extractSenderIp($this->parser->getHeader('received') ?: '');

            $email->spam_score = $this->extractSpamScore(
                $this->parser->getHeader('x-spam-score') ?: '',
                $this->parser->getHeader('x-forefront-antispam-report') ?: ''
            );

            $email->spf = $this->extractSpfResult(
                $this->parser->getHeader('received-spf') ?: '',
                $this->parser->getHeader('authentication-results') ?: ''
            );

            $email->dkim = $this->extractDkimResult(
                $this->parser->getHeader('authentication-results') ?: '',
                $this->parser->getHeader('dkim-signature') ?: ''
            );

            return $email;
        } catch (\Throwable $e) {
            Log::error('Email parsing error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extracts the sender's IP address from Received headers.
     *
     * @param string $received Raw Received header value
     * @return string|null IP address or null if not found
     */
    protected function extractSenderIp(string $received): ?string
    {
        return preg_match('/\[([0-9a-fA-F:.]+)\]/', $received, $matches) && filter_var($matches[1], FILTER_VALIDATE_IP)
            ? $matches[1]
            : null;
    }

    /**
     * Extracts the spam score from X-Spam-Score or X-Forefront-Antispam-Report headers.
     *
     * @param string $spamScore Raw X-Spam-Score header value
     * @param string $forefront Raw X-Forefront-Antispam-Report header value
     * @return float|null Spam score or null if not found
     */
    protected function extractSpamScore(string $spamScore, string $forefront): ?float
    {
        if (preg_match('/SCL:([-]?[0-9]+(?:\.[0-9]+)?)/', $forefront, $matches)) {
            return (float) $matches[1];
        }
        return preg_match('/[-]?[0-9]+(?:\.[0-9]+)?(?!\.[0-9])/', $spamScore, $matches) ? (float) $matches[0] : null;
    }

    /**
     * Extracts the SPF result from Received-SPF or Authentication-Results headers.
     *
     * @param string $spf
     * @param string $auth
     * @return string|null SPF result (e.g., "pass", "fail") or null if not found
     */
    protected function extractSpfResult(string $spf, string $auth): ?string
    {
        if (preg_match('/^(pass|fail|softfail|neutral|none|temperror|permerror)/i', $spf, $matches)) {
            return strtolower($matches[1]);
        }
        return preg_match('/spf=(pass|fail|softfail|neutral|none|temperror|permerror)/i', $auth, $matches)
            ? strtolower($matches[1])
            : null;
    }

    /**
     * Extracts the DKIM result and domain from Authentication-Results or DKIM-Signature headers.
     *
     * @param string $auth
     * @param string $dkim
     * @return string|null Formatted DKIM result or null if not found
     */
    protected function extractDkimResult(string $auth, string $dkim): ?string
    {
        $result = preg_match('/dkim=(pass|fail|none|policy|neutral|temperror|permerror)/i', $auth, $matches)
            ? strtolower($matches[1])
            : ($dkim ? 'pass' : null);
        $domain = preg_match('/\bd=([a-zA-Z0-9.-]+)/', $dkim, $matches) ? $matches[1] : null;
        return $result && $domain ? "{@$domain : $result}" : null;
    }

    /**
     * Extracts visible text from HTML content, adding single newlines for block elements.
     *
     * @param string $html HTML content
     * @return string Cleaned text content
     */
    protected function extractVisibleText(string $html): string
    {
        if (strlen($html) > 1_000_000) return $this->cleanContent($html);

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $loaded = $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR | LIBXML_NOWARNING);
        if (!$loaded) return $this->cleanContent($html);

        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//script | //style') as $node) {
            $node->parentNode->removeChild($node);
        }

        return $this->cleanContent($this->extractText($dom->getElementsByTagName('body')->item(0) ?? $dom));
    }

    /**
     * Recursively extracts text from DOM nodes, adding single newlines for block elements.
     *
     * @param \DOMNode $node DOM node to process
     * @return string Extracted text
     */
    protected function extractText(\DOMNode $node): string
    {
        $text = '';
        $blockElements = ['p', 'div', 'br', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li'];

        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMText && ($childText = trim($child->textContent)) !== '') {
                $text .= $childText;
            } elseif ($child instanceof \DOMElement) {
                $childText = $this->extractText($child);
                $text .= $childText;
                if (in_array(strtolower($child->tagName), $blockElements) && $childText) $text .= "\n";
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
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $content = preg_replace('/[\x{00A0}\x{200B}-\x{200D}\x{FEFF}]+/u', ' ', $content);
        $content = preg_replace('/[^\S\n]+/', ' ', $content);
        $content = preg_replace('/\n{2,}/', "\n", $content);
        return trim($content);
    }
}
