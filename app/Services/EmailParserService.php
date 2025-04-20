<?php

namespace App\Services;

use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Header\HeaderConsts;

class EmailParserService
{
    protected MailMimeParser $parser;

    public function __construct()
    {
        $this->parser = new MailMimeParser();
    }

    public function parse(string $raw): array
    {
        $message = $this->parser->parse($raw, false);

        $text = $message->getTextContent() ?? '';
        $text = preg_replace('/[^\P{C}\n]/u', '', $text);

        return [
            'raw_text' => trim($text),
            'from' => $message->getHeaderValue(HeaderConsts::FROM) ?? 'unknown@example.com',
            'to' => $message->getHeaderValue(HeaderConsts::TO) ?? '',
            'subject' => $message->getSubject() ?? '',
        ];
    }
}
