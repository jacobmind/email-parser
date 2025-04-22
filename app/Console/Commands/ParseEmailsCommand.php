<?php

namespace App\Console\Commands;

use App\Services\EmailParsingJobService;
use Illuminate\Console\Command;

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

    protected EmailParsingJobService $jobService;

    public function __construct(EmailParsingJobService $jobService)
    {
        parent::__construct();
        $this->jobService = $jobService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting email parsing...');
        $this->jobService->processMissingEmails($this);
        $this->info('Email parsing completed.');
        return 0;
    }
}
