<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\ParseEmailsCommand;
use App\Services\EmailParsingJobService;
use Mockery;
use Tests\TestCase;

class ParseEmailsCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_runs_successfully_and_outputs_correct_messages()
    {
        // Mock the EmailParsingJobService
        $jobService = Mockery::mock(EmailParsingJobService::class);
        $jobService->shouldReceive('processMissingEmails')
            ->once()
            ->with(Mockery::type(ParseEmailsCommand::class));

        // Bind the mock to the container
        $this->app->instance(EmailParsingJobService::class, $jobService);

        // Test the command
        $this->artisan('emails:parse')
            ->expectsOutput('Starting email parsing...')
            ->expectsOutput('Email parsing completed.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_has_correct_signature_and_description()
    {
        $command = $this->app->make(ParseEmailsCommand::class);

        $this->assertEquals('emails:parse', $command->getName());
        $this->assertEquals('Parse raw emails and extract plain text content.', $command->getDescription());
    }
}
