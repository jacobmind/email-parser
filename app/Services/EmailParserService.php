<?php

namespace App\Services;

use eXorus\PhpMimeMailParser\Parser;

class EmailParserService
{
    protected Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    public function parse(string $raw): array
    {
        $this->parser->setText($raw);

        $text = $this->parser->getMessageBody('text') ?? '';
        $text = preg_replace('/[^\P{C}\n]/u', '', $text);

        return [
            'raw_text' => trim($text),
            'from' => $this->parser->getHeader('from') ?? 'unknown@example.com',
            'to' => $this->parser->getHeader('to') ?? '',
            'subject' => $this->parser->getHeader('subject') ?? '',
        ];
    }
}
