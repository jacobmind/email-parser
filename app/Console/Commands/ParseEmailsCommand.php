<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\EmailParserService;

class ParseEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:parse';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse raw emails and extract plain text content.';

    protected EmailParserService $parser;

    public function __construct(EmailParserService $parser)
    {
        parent::__construct();
        $this->parser = $parser;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting email parsing...');

        $emails = DB::table('successful_emails')
            ->whereNull('raw_text')
            ->orWhere('raw_text', '')
            ->limit(1) // todo: adjust it accordingly
            ->get();

        if ($emails->isEmpty()) {
            $this->info('No emails to process.');
            return 0;
        }

        foreach ($emails as $email) {
            try {
                $parsed = $this->parser->parse($email->email);

                DB::table('successful_emails')
                    ->where('id', $email->id)
                    ->update(['raw_text' => $parsed['raw_text']]);

                $this->line("Processed email ID: {$email->id}");
            } catch (\Throwable $e) {
                $this->error("Failed to process email ID {$email->id}: " . $e->getMessage());
            }
        }

        $this->info('Email parsing completed.');

        return 0;
    }
}
