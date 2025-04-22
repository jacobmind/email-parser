<?php

namespace App\Services;

use App\Models\SuccessfulEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmailParsingJobService
{
    private const CHUNK_SIZE = 15;
    private const DEFAULT_RAW_TEXT = '-';

    protected EmailParserService $parser;

    public function __construct(EmailParserService $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Processes emails that lack raw text by parsing their content.
     *
     * @param Command|null $console Optional console command for logging
     * @param int $limit Maximum number of emails to process
     * @return void
     */
    public function processMissingEmails(?Command $console = null, int $limit = 1000): void
    {
        SuccessfulEmail::unprocessed()
            ->orderBy('id')
            ->take($limit)
            ->chunkById(self::CHUNK_SIZE, function ($emails) use ($console) {
                foreach ($emails as $email) {
                    $this->processEmail($email, $console);
                }
            });
    }

    /**
     * Processes a single email by parsing its content and updating raw text.
     *
     * @param SuccessfulEmail $email The email to process
     * @param Command|null $console Optional console command for logging
     * @return void
     */
    protected function processEmail(SuccessfulEmail $email, ?Command $console = null): void
    {
        try {
            DB::transaction(function () use ($email, $console) {
                $parsedText = $this->parser->parse_raw_text($email->email);
                $email->raw_text = empty($parsedText) ? self::DEFAULT_RAW_TEXT : $parsedText;
                $email->save();

                $console?->info("✅ Processed email ID: {$email->id}");
            });
        } catch (\Throwable $e) {
            $message = "Failed to process email ID {$email->id}: " . $e->getMessage();
            $console?->error("❌ $message");
            Log::error($message);
        }
    }
}
