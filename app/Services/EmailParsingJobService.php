<?php

namespace App\Services;

use App\Models\SuccessfulEmail;
use Illuminate\Console\Command;

class EmailParsingJobService
{
    protected EmailParserService $parser;

    public function __construct(EmailParserService $parser)
    {
        $this->parser = $parser;
    }

    public function processMissingEmails(?Command $console = null): void
    {
        SuccessfulEmail::whereNull('raw_text')
            ->orWhere('raw_text', '')
            ->orderBy('id')
            ->chunkById(15, function ($emails) use ($console) {
                foreach ($emails as $email) {
                    $this->processEmail($email, $console);
                }
            });
    }

    protected function processEmail($email, ?Command $console = null): void
    {
        try {
            $parsedText = $this->parser->parse($email->email);

            $email->raw_text = empty($parsedText) ? '-' : $parsedText;
            $email->save();

            $console?->info("✅ Processed email ID: {$email->id}");
        } catch (\Throwable $e) {
            $console?->error("❌ Failed to process email ID {$email->id}: " . $e->getMessage());
        }
    }
}
