<?php

namespace App\Command;

use App\Service\CandidateAiScoringService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:integrations:check', description: 'Check external integrations configuration (AI, mailer, external API).')]
class IntegrationsCheckCommand extends Command
{
    public function __construct(
        private readonly CandidateAiScoringService $aiScoringService,
        private readonly string $mailerDsn,
        private readonly string $mailerFromEmail,
        private readonly string $mailerFromName,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $aiConfigured = $this->aiScoringService->isConfigured();
        $mailerConfigured = trim($this->mailerDsn) !== '' && stripos($this->mailerDsn, 'null://') !== 0;

        $io->title('Integrations Health Check');

        $io->section('AI (Groq)');
        if ($aiConfigured) {
            $io->success('AI key is configured (GROQ_API_KEY).');
        } else {
            $io->warning('AI key is missing. Set GROQ_API_KEY in .env.local.');
        }

        $io->section('Mailer');
        if ($mailerConfigured) {
            $io->success('Mailer DSN looks configured for real sending.');
        } else {
            $io->warning('Mailer DSN is null transport. Emails will not be delivered outside app runtime.');
        }

        if (trim($this->mailerFromEmail) === '' || trim($this->mailerFromName) === '') {
            $io->warning('Sender identity is incomplete. Set MAILER_FROM_EMAIL and MAILER_FROM_NAME.');
        } else {
            $io->text(sprintf('From: %s <%s>', $this->mailerFromName, $this->mailerFromEmail));
        }

        $io->section('External Job Board API');
        $reachable = $this->checkUrl('https://www.arbeitnow.com/api/job-board-api?limit=1', 8);
        if ($reachable) {
            $io->success('Arbeitnow API reachable.');
        } else {
            $io->warning('Arbeitnow API is not reachable right now. Offers externes may be empty.');
        }

        $io->newLine();
        $io->text('Tip: copy .env.local.example to .env.local and fill secrets.');

        return Command::SUCCESS;
    }

    private function checkUrl(string $url, int $timeout): bool
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $timeout,
                'ignore_errors' => true,
                'header' => [
                    'Accept: application/json',
                    'User-Agent: HROne/1.0',
                ],
            ],
        ]);

        $result = @file_get_contents($url, false, $context);

        return is_string($result) && trim($result) !== '';
    }
}
